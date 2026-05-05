<?php
require_once 'app/models/ChamCongModel.php';
require_once 'app/middleware/AuthMiddleware.php';

class ChamCongController
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new ChamCongModel();
    }

    public function dashboard()
    {
        $this->requireLogin();
        $maND = $_SESSION['user']['maND'] ?? $_SESSION['user']['maTK'] ?? '';
        
        // Lấy thống kê tổng quan
        $stats = $this->model->getThongKeTongQuan() ?? [
            'total_logs_today' => 0,
            'in_today' => 0,
            'out_today' => 0,
            'pending_requests' => 0
        ];
        
        // Lấy trạng thái chấm công hôm nay (IN, OUT, hoặc null)
        $trangThaiHomNay = $this->model->getTrangThaiHomNay($maND);
        
        // Kiểm tra xem có WiFi được phép trong hệ thống không
        $hasWifi = $this->model->checkWifi();
        
        // Lấy lịch sử chấm công gần đây
        $history = $this->model->getLichSuTheoNhanVien($maND, 5) ?? [];
        
        // Lấy các message từ session (thông báo thành công/lỗi)
        $success = $_SESSION['success'] ?? null;
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['success'], $_SESSION['error']);
        
        // Biến này dùng để include view cho từng role
        $view = null;
        $role = $_SESSION['role'] ?? 'nhanvien';
        
        // Fetch tech panel data if role is tech
        if ($role === 'tech') {
            $wifiList = $this->model->getAllNetworks() ?? [];
            $settings = $this->model->getAllSettings() ?? [];
        }
        
        // Lấy trạng thái ca làm hôm nay (shift-based status)
        $todayShiftStatus = $this->model->getTodayShiftStatus($maND);

        // Fetch employee monthly stats for dashboard cards
        $currentMonth = date('Y-m');
        $empStatsRaw = $this->model->getEmployeeDashboardStats($maND, $currentMonth);
        
        $stats['employee'] = [
            'work_days' => $empStatsRaw['work_days'],
            'late_times' => $this->formatMinutes($empStatsRaw['late_minutes']),
            'ot_hours' => $empStatsRaw['ot_hours'],
            'leave_days' => $empStatsRaw['leave_days']
        ];
        
        require 'app/views/chamcong/dashboard.php';
    }

    private function formatMinutes($totalMinutes)
    {
        $totalMinutes = (int)$totalMinutes;
        if ($totalMinutes <= 0) return '0';
        
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'p' : '');
        }
        return $totalMinutes . 'p';
    }

    public function chamCongVao()
    {
        $this->thucHienChamCong('IN');
    }

    public function chamCongRa()
    {
        $this->thucHienChamCong('OUT');
    }

    public function lichSu()
    {
        $this->requireLogin();
        $maND = $_SESSION['user']['maND'] ?? $_SESSION['user']['maTK'] ?? '';
        
        // Lấy khoảng ngày từ GET hoặc mặc định là tháng hiện tại
        $from = trim($_GET['from_date'] ?? date('Y-m-01'));
        $to = trim($_GET['to_date'] ?? date('Y-m-d'));
        
        // Validation
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $from = date('Y-m-01');
            $to = date('Y-m-d');
        }
        
        $history = $this->model->getLichSu($maND, $from, $to);
        
        require 'app/views/chamcong/lichsu.php';
    }

    public function chamCong($action)
    {
        $this->thucHienChamCong($action);
    }

    public function yeuCauChinhSua()
    {
        $this->requireLogin();
        $maND = $_SESSION['user']['maND'] ?? $_SESSION['user']['maTK'] ?? '';
        $message = '';
        $messageType = ''; // 'success' or 'error'

        // Existing POST handler (kept intact for backward compat)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $attendanceDate = trim($_POST['attendance_date'] ?? '');
            $oldTime = trim($_POST['old_time'] ?? '');
            $newTime = trim($_POST['new_time'] ?? '');
            $reason = trim($_POST['reason'] ?? '');

            if ($attendanceDate !== '' && $newTime !== '' && $reason !== '') {
                $ok = $this->model->taoYeuCauChinhSua($maND, $attendanceDate, $oldTime ?: null, $newTime, $reason);
                $message = $ok ? 'Gửi yêu cầu chỉnh sửa thành công.' : 'Không thể gửi yêu cầu, vui lòng thử lại.';
                $messageType = $ok ? 'success' : 'error';
            } else {
                $message = 'Vui lòng nhập đủ các trường bắt buộc.';
                $messageType = 'error';
            }
        }

        // Load additional data for the view (ensures view always receives data)
        $requests = $this->model->getYeuCauTheoNhanVien($maND);
        $attendanceRecords = $this->model->getAttendanceByUser($maND, 30);
        $todayShiftStatus = $this->model->getTodayShiftStatus($maND);
        $userShift = $this->model->getShiftForUser($maND);

        require 'app/views/chamcong/yeucau_chinhsua.php';
    }

    /**
     * Store enhanced edit request with file upload.
     * POST handler with MIME type validation for evidence files.
     */
    public function storeEditRequest()
    {
        $this->requireLogin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=yeu-cau-chinh-sua-cham-cong');
            exit;
        }

        $maND = $_SESSION['user']['maND'] ?? $_SESSION['user']['maTK'] ?? '';
        $attendanceDate = trim($_POST['attendance_date'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $proposedCheckin = trim($_POST['proposed_checkin'] ?? '');
        $proposedCheckout = trim($_POST['proposed_checkout'] ?? '');

        // Validation
        if ($attendanceDate === '' || $reason === '') {
            $_SESSION['edit_request_error'] = 'Vui lòng nhập đầy đủ ngày và lý do.';
            header('Location: index.php?page=yeu-cau-chinh-sua-cham-cong');
            exit;
        }

        if ($proposedCheckin === '' && $proposedCheckout === '') {
            $_SESSION['edit_request_error'] = 'Vui lòng nhập ít nhất giờ vào hoặc giờ ra đề xuất.';
            header('Location: index.php?page=yeu-cau-chinh-sua-cham-cong');
            exit;
        }

        // Handle file upload with MIME type validation
        $evidenceFile = null;
        if (!isset($_FILES['evidence_file']) || $_FILES['evidence_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['edit_request_error'] = 'Vui lòng đính kèm file minh chứng (bắt buộc).';
            header('Location: index.php?page=yeu-cau-chinh-sua-cham-cong');
            exit;
        }

        $tmpName = $_FILES['evidence_file']['tmp_name'];
        $fileSize = $_FILES['evidence_file']['size'];

            // Validate file size (max 5MB)
            if ($fileSize > 5 * 1024 * 1024) {
                $_SESSION['edit_request_error'] = 'File quá lớn. Tối đa 5MB.';
                header('Location: index.php?page=yeu-cau-chinh-sua-cham-cong');
                exit;
            }

            // Validate MIME type (not extension) — do not trust original filename
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpName);
            $allowedMimes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'application/pdf' => 'pdf',
            ];

            if (!isset($allowedMimes[$mimeType])) {
                $_SESSION['edit_request_error'] = 'Loại file không hợp lệ. Chỉ chấp nhận JPG, PNG, PDF.';
                header('Location: index.php?page=yeu-cau-chinh-sua-cham-cong');
                exit;
            }

            // Create upload directory if not exists
            $uploadDir = 'uploads/attendance_evidence/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename using hash — do NOT use original filename
            $ext = $allowedMimes[$mimeType];
            $uniqueName = $maND . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $destPath = $uploadDir . $uniqueName;

            if (!move_uploaded_file($tmpName, $destPath)) {
                $_SESSION['edit_request_error'] = 'Lỗi khi tải file lên. Vui lòng thử lại.';
                header('Location: index.php?page=yeu-cau-chinh-sua-cham-cong');
                exit;
            }

            $evidenceFile = $uniqueName;

        // Get original attendance data for the selected date
        $attendanceRecords = $this->model->getAttendanceByUser($maND, 60);
        $originalIn = null;
        $originalOut = null;
        foreach ($attendanceRecords as $rec) {
            if (($rec['work_date'] ?? '') === $attendanceDate) {
                $originalIn = $rec['first_in'] ?? null;
                $originalOut = $rec['last_out'] ?? null;
                break;
            }
        }

        // Insert via model
        $data = [
            'maND' => $maND,
            'attendance_date' => $attendanceDate,
            'old_time' => $originalIn,
            'new_time' => $proposedCheckin ?: $proposedCheckout ?: date('Y-m-d H:i:s'),
            'reason' => $reason,
            'proposed_checkin' => $proposedCheckin ?: null,
            'proposed_checkout' => $proposedCheckout ?: null,
            'evidence_file' => $evidenceFile,
        ];

        $ok = $this->model->insertEditRequest($data);

        if ($ok) {
            $_SESSION['edit_request_success'] = 'Gửi yêu cầu chỉnh sửa thành công!';
        } else {
            $_SESSION['edit_request_error'] = 'Không thể gửi yêu cầu, vui lòng thử lại.';
        }

        header('Location: index.php?page=yeu-cau-chinh-sua-cham-cong');
        exit;
    }

    public function hrPanel()
    {
        $this->requireLogin();
        $stats = $this->model->getThongKeTongQuan();
        $corrections = $this->model->getCorrectionRequests('pending');
        require 'app/views/chamcong/hr_panel.php';
    }

    public function quanLyPanel()
    {
        $this->requireLogin();
        $stats = $this->model->getThongKeTongQuan();
        $payrolls = $this->model->getMonthlyApprovals();
        require 'app/views/chamcong/manager_panel.php';
    }

    public function kyThuatPanel()
    {
        $this->requireLogin();
        $wifiList = $this->model->getAllNetworks() ?? [];
        $settings = $this->model->getAllSettings() ?? [];
        require 'app/views/chamcong/tech_panel.php';
    }

    private function thucHienChamCong($action)
    {
        $this->requireLogin();
        $maND = $_SESSION['user']['maND'] ?? $_SESSION['user']['maTK'] ?? '';

        // Xác định phương thức chấm công trước khi kiểm tra WiFi.
        $method = ($_POST['method'] ?? 'LAN') === 'QR' ? 'QR' : 'LAN';

        // Lấy WiFi name từ POST hoặc GET
        $wifiName = trim($_POST['wifi_name'] ?? $_GET['wifi_name'] ?? 'INTERNAL_NETWORK');

        if ($method === 'LAN') {
            // ===== NETWORK VALIDATION (SERVER-SIDE ONLY) =====
            // Get server-side client IP (cannot be spoofed from frontend)
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
            
            // Handle localhost testing: allow access for testing purposes
            if ($clientIp === '127.0.0.1' || $clientIp === '::1') {
                $clientIp = '192.168.1.129'; // Test IP within company network
            }
            
            // Check if client IP is in any allowed network range
            if (!$this->model->isAllowedIp($clientIp)) {
                $_SESSION['error'] = 'Bạn không ở trong mạng nội bộ công ty. Bạn không được phép chấm công.';
                header('Location: index.php?page=cham-cong');
                exit;
            }
            // ===== END NETWORK VALIDATION =====

            // Kiểm tra có WiFi được phép không
            if (!$this->model->checkWifi()) {
                $_SESSION['error'] = 'Không có WiFi nào được phép. Vui lòng liên hệ IT.';
                header('Location: index.php?page=cham-cong');
                exit;
            }

            // Bypass giá trị mặc định INTERNAL_NETWORK: dùng WiFi active đầu tiên nếu cần.
            if ($wifiName === '' || strtoupper($wifiName) === 'INTERNAL_NETWORK' || !$this->model->isWifiAllowed($wifiName)) {
                $fallbackWifi = $this->model->getFirstActiveWifiName();
                if (!empty($fallbackWifi)) {
                    $wifiName = $fallbackWifi;
                }
            }

            // Sau fallback mà vẫn không hợp lệ thì mới chặn.
            if (!$this->model->isWifiAllowed($wifiName)) {
                $_SESSION['error'] = 'WiFi "' . htmlspecialchars($wifiName) . '" không được phép chấm công.';
                header('Location: index.php?page=cham-cong');
                exit;
            }
        } else {
            // QR dự phòng không phụ thuộc WiFi nội bộ.
            $wifiName = $wifiName !== '' ? $wifiName : 'QR_FALLBACK';
        }
        
        // Kiểm tra số lần chấm công hôm nay (chỉ 1 IN và 1 OUT)
        $todayAttendance = $this->model->getAttendanceByUser($maND, 1);
        $hasIn = false;
        $hasOut = false;
        if (!empty($todayAttendance) && $todayAttendance[0]['work_date'] === date('Y-m-d')) {
            $hasIn = !empty($todayAttendance[0]['first_in']);
            $hasOut = !empty($todayAttendance[0]['last_out']);
        }
        
        // Logic: Nếu chưa IN thì không thể OUT
        if ($action === 'OUT' && !$hasIn) {
            $_SESSION['error'] = 'Bạn chưa chấm công vào. Vui lòng chấm vào trước.';
            header('Location: index.php?page=cham-cong');
            exit;
        }
        
        // Logic: Nếu đã OUT thì không thể chấm OUT lại
        if ($action === 'OUT' && $hasOut) {
            $_SESSION['error'] = 'Bạn đã chấm công ra hôm nay rồi. Chỉ được phép 1 lần/ngày.';
            header('Location: index.php?page=cham-cong');
            exit;
        }
        
        // Logic: Nếu đã IN thì không thể chấm IN lại
        if ($action === 'IN' && $hasIn) {
            $_SESSION['error'] = 'Bạn đã chấm công vào hôm nay rồi. Chỉ được phép 1 lần/ngày.';
            header('Location: index.php?page=cham-cong');
            exit;
        }
        
        // Lưu chấm công
        $note = trim($_POST['note'] ?? '');
        
        $ok = $this->model->chamCong($maND, $action, $method, $wifiName, $note);
        
        if ($ok) {
            $actionText = ($action === 'IN') ? 'vào' : 'ra';
            $_SESSION['success'] = 'Chấm công ' . $actionText . ' thành công!';
        } else {
            $_SESSION['error'] = 'Chấm công thất bại. Vui lòng thử lại.';
        }
        
        header('Location: index.php?page=cham-cong');
        exit;
    }

    private function requireLogin()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }
    }

    /**
     * ========== ATTENDANCE API ENDPOINTS (JSON) ==========
     */

    /**
     * Validate network access (IP + WiFi)
     * GET /attendance/validate-network
     * Returns: { valid, ip_valid, wifi_valid, ip, message }
     */
    /**
     * Validate Company Network - FOR DISPLAY ONLY
     * Returns current IP and network status (informational, not blocking)
     * GET/POST /attendance/validate-network
     * Returns: { ip, allowed_networks, message }
     * 
     * NOTE: This endpoint is for UI display only - NOT FOR BLOCKING ACCESS
     * Actual IP validation happens only during clock-in/out in thucHienChamCong()
     */
    public function validateNetwork()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $serverIP = $this->model->getServerIP();
        $allowedNetworks = $this->model->getActiveWifiConfigurations(); // Detailed config
        $isValid = $this->model->isAllowedIp($serverIP);

        echo json_encode([
            'ip' => $serverIP,
            'allowed_networks' => $allowedNetworks,
            'is_allowed' => $isValid,
            'message' => $isValid ? 'Bạn đang ở trong mạng nội bộ' : 'Bạn không ở mạng nội bộ công ty'
        ]);
        exit;
    }

    /**
     * Check In (with server-side IP validation only)
     * POST /attendance/check-in
     * Payload: {} (empty - validation is server-only)
     * Returns: { success, message, ip }
     */
    public function checkIn()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $maND = $_SESSION['user']['maND'] ?? $_SESSION['user']['maTK'] ?? null;
        $serverIP = $this->model->getServerIP();
        
        // Validation
        if (!$this->model->isInternalNetwork($serverIP)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Không thuộc mạng nội bộ ($serverIP)"]);
            exit;
        }

        // Handle WiFi Name
        $wifiName = trim($_POST['wifi_name'] ?? '');
        if (empty($wifiName)) {
            // Auto-detect by IP or use first active
            $configs = $this->model->getActiveWifiConfigurations();
            foreach ($configs as $cfg) {
                if (strpos($serverIP, $cfg['ip_range']) === 0) {
                    $wifiName = $cfg['wifi_name'];
                    break;
                }
            }
            if (empty($wifiName)) {
                $wifiName = $this->model->getFirstActiveWifiName() ?? 'Unknown WiFi';
            }
        }

        // Check already checked in
        $logs = $this->model->getTodayLogs($maND);
        foreach ($logs as $log) {
            if ($log['action'] === 'IN') {
                echo json_encode(['success' => false, 'message' => 'Bạn đã chấm công vào hôm nay rồi']);
                exit;
            }
        }

        // Save
        $ok = $this->model->chamCong($maND, 'IN', 'LAN', $wifiName, 'Check-in via API', $serverIP);
        
        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Chấm công vào thành công' : 'Lỗi không thể lưu dữ liệu',
            'wifi' => $wifiName
        ]);
        exit;
    }

    /**
     * Check Out (with server-side IP validation only)
     * POST /attendance/check-out
     * Payload: {} (empty - validation is server-only)
     * Returns: { success, message, ip }
     */
    public function checkOut()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $maND = $_SESSION['user']['maND'] ?? $_SESSION['user']['maTK'] ?? null;
        $serverIP = $this->model->getServerIP();
        
        if (!$this->model->isInternalNetwork($serverIP)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Không thuộc mạng nội bộ ($serverIP)"]);
            exit;
        }

        // Handle WiFi Name
        $wifiName = trim($_POST['wifi_name'] ?? '');
        if (empty($wifiName)) {
            $configs = $this->model->getActiveWifiConfigurations();
            foreach ($configs as $cfg) {
                if (strpos($serverIP, $cfg['ip_range']) === 0) {
                    $wifiName = $cfg['wifi_name'];
                    break;
                }
            }
            if (empty($wifiName)) $wifiName = $this->model->getFirstActiveWifiName() ?? 'Unknown WiFi';
        }

        $logs = $this->model->getTodayLogs($maND);
        $hasIn = false; $hasOut = false;
        foreach ($logs as $log) {
            if ($log['action'] === 'IN') $hasIn = true;
            if ($log['action'] === 'OUT') $hasOut = true;
        }

        if (!$hasIn) {
            echo json_encode(['success' => false, 'message' => 'Bạn chưa chấm công vào hôm nay']);
            exit;
        }
        if ($hasOut) {
            echo json_encode(['success' => false, 'message' => 'Bạn đã chấm công ra rồi']);
            exit;
        }

        $ok = $this->model->chamCong($maND, 'OUT', 'LAN', $wifiName, 'Check-out via API', $serverIP);
        
        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Chấm công ra thành công' : 'Lỗi lưu dữ liệu',
            'wifi' => $wifiName
        ]);
        exit;
    }

    /**
     * Get Today's Attendance Record (JSON API)
     * GET /attendance/today
     * Returns: { success, checkIn: datetime|null, checkOut: datetime|null, total_hours: float }
     */
    public function getTodayAttendance()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $maND = $_SESSION['user']['maND'] ?? $_SESSION['user']['maTK'] ?? null;
        $logs = $this->model->getTodayLogs($maND);
        
        $checkIn = null; $checkOut = null;
        foreach ($logs as $log) {
            if ($log['action'] === 'IN') $checkIn = $log['created_at'];
            if ($log['action'] === 'OUT') $checkOut = $log['created_at'];
        }

        $totalHours = 0;
        if ($checkIn && $checkOut) {
            $totalHours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
        }

        echo json_encode([
            'success' => true,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'total_hours' => $totalHours
        ]);
        exit;
    }

    /**
     * Get Attendance History (JSON API)
     * GET /attendance/history?limit=10
     * Returns: { success, data: [{date, checkIn, checkOut, hours}, ...] }
     */
    public function getAttendanceHistory()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $maND = $_SESSION['user']['maND'] ?? $_SESSION['user']['maTK'] ?? null;
        $limit = intval($_GET['limit'] ?? 10);
        
        $history = $this->model->getLichSuTheoNhanVien($maND, $limit);
        
        // Group by date to match front-end expectation
        $grouped = [];
        foreach ($history as $h) {
            $date = date('Y-m-d', strtotime($h['created_at']));
            if (!isset($grouped[$date])) {
                // Ensure fallback for wifi_name
                $wifiDisplay = !empty($h['wifi_name']) ? $h['wifi_name'] : 'Wifi Công ty';
                $grouped[$date] = [
                    'date' => $date, 
                    'checkIn' => null, 
                    'checkOut' => null, 
                    'wifi_name' => $wifiDisplay
                ];
            }
            if ($h['action'] === 'IN') $grouped[$date]['checkIn'] = date('H:i:s', strtotime($h['created_at']));
            if ($h['action'] === 'OUT') $grouped[$date]['checkOut'] = date('H:i:s', strtotime($h['created_at']));
        }

        echo json_encode([
            'success' => true,
            'data' => array_values($grouped)
        ]);
        exit;
    }

    public function getLeaveDetail()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }

        $detail = $this->model->getLeaveById($id);
        if ($detail) {
            // Translate leave type
            $types = ['annual' => 'Nghỉ phép năm', 'sick' => 'Nghỉ ốm', 'personal' => 'Việc riêng', 'unpaid' => 'Nghỉ không lương'];
            $detail['leave_type_text'] = $types[$detail['leave_type']] ?? $detail['leave_type'];
            
            // Format dates
            $detail['from_date_fmt'] = date('d/m/Y', strtotime($detail['from_date']));
            $detail['to_date_fmt'] = date('d/m/Y', strtotime($detail['to_date']));
            $detail['created_at_fmt'] = date('d/m/Y H:i', strtotime($detail['created_at']));
            $detail['approved_at_fmt'] = $detail['approved_at'] ? date('d/m/Y H:i', strtotime($detail['approved_at'])) : null;

            echo json_encode(['success' => true, 'data' => $detail]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
        }
        exit;
    }

    public function getCorrectionDetail()
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }

        $detail = $this->model->getCorrectionById($id);
        if ($detail) {
            // Format dates
            $detail['attendance_date_fmt'] = date('d/m/Y', strtotime($detail['attendance_date']));
            $detail['old_time_fmt'] = $detail['old_time'] ? date('H:i', strtotime($detail['old_time'])) : '--:--';
            $detail['new_time_fmt'] = date('H:i', strtotime($detail['new_time']));
            $detail['proposed_checkin_fmt'] = $detail['proposed_checkin'] ? date('H:i', strtotime($detail['proposed_checkin'])) : '--:--';
            $detail['proposed_checkout_fmt'] = $detail['proposed_checkout'] ? date('H:i', strtotime($detail['proposed_checkout'])) : '--:--';
            $detail['created_at_fmt'] = date('d/m/Y H:i', strtotime($detail['created_at']));
            $detail['approved_at_fmt'] = ($detail['status'] !== 'pending' && $detail['updated_at']) ? date('d/m/Y H:i', strtotime($detail['updated_at'])) : null;
            $detail['approver_name'] = null; // Table doesn't track this yet

            echo json_encode(['success' => true, 'data' => $detail]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
        }
        exit;
    }
}
?>
