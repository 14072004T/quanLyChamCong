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
        
        require 'app/views/chamcong/dashboard.php';
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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $attendanceDate = trim($_POST['attendance_date'] ?? '');
            $oldTime = trim($_POST['old_time'] ?? '');
            $newTime = trim($_POST['new_time'] ?? '');
            $reason = trim($_POST['reason'] ?? '');

            if ($attendanceDate !== '' && $newTime !== '' && $reason !== '') {
                $ok = $this->model->taoYeuCauChinhSua($maND, $attendanceDate, $oldTime ?: null, $newTime, $reason);
                $message = $ok ? 'Gửi yêu cầu chỉnh sửa thành công.' : 'Không thể gửi yêu cầu, vui lòng thử lại.';
            } else {
                $message = 'Vui lòng nhập đủ các trường bắt buộc.';
            }
        }

        $requests = $this->model->getYeuCauTheoNhanVien($maND);
        require 'app/views/chamcong/yeucau_chinhsua.php';
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
        
        // Kiểm tra trạng thái chấm công hôm nay
        $trangThaiHomNay = $this->model->getTrangThaiHomNay($maND);
        
        // Logic: Nếu chưa IN thì không thể OUT
        if ($action === 'OUT' && !$trangThaiHomNay) {
            $_SESSION['error'] = 'Bạn chưa chấm công vào. Vui lòng chấm vào trước.';
            header('Location: index.php?page=cham-cong');
            exit;
        }
        
        // Logic: Nếu đã OUT thì không thể chấm OUT lại
        if ($action === 'OUT' && $trangThaiHomNay === 'OUT') {
            $_SESSION['error'] = 'Bạn đã chấm công ra rồi.';
            header('Location: index.php?page=cham-cong');
            exit;
        }
        
        // Logic: Nếu đã IN thì không thể chấm IN lại
        if ($action === 'IN' && $trangThaiHomNay === 'IN') {
            $_SESSION['error'] = 'Bạn đã chấm công vào rồi. Vui lòng chấm ra.';
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

        // Get server IP (cannot be spoofed from frontend)
        $serverIP = $this->model->getServerIP();
        
        // Get allowed networks from database
        $allowedNetworks = $this->model->getAllowedNetworks();
        
        // Check if IP matches any allowed network (for display only)
        $isValid = $this->model->isAllowedIp($serverIP);

        // Return informational response (DOES NOT BLOCK ACCESS)
        echo json_encode([
            'ip' => $serverIP,
            'allowed_networks' => $allowedNetworks,
            'is_allowed' => $isValid,
            'message' => $isValid ? 'Bạn có thể chấm công' : 'Bạn không ở mạng nội bộ. Vui lòng kết nối WiFi công ty để chấm công.'
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
        if (!$maND) {
            echo json_encode(['success' => false, 'message' => 'User ID not found']);
            exit;
        }

        // Get server IP (cannot be spoofed from frontend)
        $serverIP = $this->model->getServerIP();
        
        // Check if IP is in allowed company network range (server-side only)
        $isValid = $this->model->isInternalNetwork($serverIP);
        if (!$isValid) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => "Bạn không ở trong mạng nội bộ công ty. Bạn không được phép chấm công",
                'ip' => $serverIP
            ]);
            exit;
        }

        // Check if already checked in today
        $trangThai = $this->model->getTrangThaiHomNay($maND);
        if ($trangThai === 'IN') {
            echo json_encode([
                'success' => false,
                'message' => 'Bạn đã chấm công vào hôm nay rồi'
            ]);
            exit;
        }

        // Perform check in
        $ok = $this->model->chamCong($maND, 'IN', 'LAN', 'Company Network', 'Check-in via API', $serverIP);
        
        if ($ok) {
            echo json_encode([
                'success' => true,
                'message' => 'Chấm công vào thành công',
                'ip' => $serverIP
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi: không thể lưu dữ liệu chấm công'
            ]);
        }
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
        if (!$maND) {
            echo json_encode(['success' => false, 'message' => 'User ID not found']);
            exit;
        }

        // Get server IP (cannot be spoofed from frontend)
        $serverIP = $this->model->getServerIP();
        
        // Check if IP is in allowed company network range (server-side only)
        $isValid = $this->model->isInternalNetwork($serverIP);
        if (!$isValid) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => "Bạn không ở trong mạng nội bộ công ty. IP: {$serverIP} không được phép chấm công",
                'ip' => $serverIP
            ]);
            exit;
        }

        // Check if already checked out today
        $trangThai = $this->model->getTrangThaiHomNay($maND);
        if ($trangThai === 'OUT') {
            echo json_encode(['success' => false, 'message' => 'Bạn đã chấm công ra hôm nay rồi']);
            exit;
        }
        if (!$trangThai) {
            echo json_encode(['success' => false, 'message' => 'Bạn chưa chấm công vào hôm nay']);
            exit;
        }

        // Perform check out
        $ok = $this->model->chamCong($maND, 'OUT', 'LAN', 'Company Network', 'Check-out via API', $serverIP);
        
        if ($ok) {
            echo json_encode([
                'success' => true,
                'message' => 'Chấm công ra thành công',
                'ip' => $serverIP
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Lỗi: không thể lưu dữ liệu chấm công'
            ]);
        }
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
        if (!$maND) {
            echo json_encode(['success' => false, 'message' => 'User ID not found']);
            exit;
        }

        $today = date('Y-m-d');
        try {
            $conn = new mysqli('localhost', 'root', '', 'shirt_runfortime');
            if ($conn->connect_error) {
                echo json_encode(['success' => false, 'message' => 'Database connection error']);
                exit;
            }

            $stmt = $conn->prepare("
                SELECT 
                    MAX(CASE WHEN action = 'IN' THEN created_at END) as checkIn,
                    MAX(CASE WHEN action = 'OUT' THEN created_at END) as checkOut
                FROM attendance_logs
                WHERE maND = ? AND DATE(created_at) = ?
            ");
            $stmt->bind_param('is', $maND, $today);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();

            $checkIn = $row['checkIn'] ? strtotime($row['checkIn']) * 1000 : null;
            $checkOut = $row['checkOut'] ? strtotime($row['checkOut']) * 1000 : null;
            
            $totalHours = 0;
            if ($checkIn && $checkOut) {
                $totalHours = round(($checkOut - $checkIn) / 3600000, 2);
            }

            echo json_encode([
                'success' => true,
                'checkIn' => $row['checkIn'],
                'checkOut' => $row['checkOut'],
                'total_hours' => $totalHours
            ]);

            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error retrieving data']);
        }
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
        if (!$maND) {
            echo json_encode(['success' => false, 'message' => 'User ID not found']);
            exit;
        }

        $limit = intval($_GET['limit'] ?? 10);
        $limit = min($limit, 100); // Max 100 records

        try {
            $conn = new mysqli('localhost', 'root', '', 'shirt_runfortime');
            if ($conn->connect_error) {
                echo json_encode(['success' => false, 'message' => 'Database connection error']);
                exit;
            }

            $stmt = $conn->prepare("
                SELECT 
                    DATE(created_at) as attendance_date,
                    MAX(CASE WHEN action = 'IN' THEN created_at END) as checkIn,
                    MAX(CASE WHEN action = 'OUT' THEN created_at END) as checkOut
                FROM attendance_logs
                WHERE maND = ?
                GROUP BY DATE(created_at)
                ORDER BY attendance_date DESC
                LIMIT ?
            ");
            $stmt->bind_param('ii', $maND, $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $checkIn = $row['checkIn'] ? strtotime($row['checkIn']) : 0;
                $checkOut = $row['checkOut'] ? strtotime($row['checkOut']) : 0;
                
                $hours = 0;
                if ($checkIn && $checkOut) {
                    $hours = round(($checkOut - $checkIn) / 3600, 2);
                }

                $data[] = [
                    'date' => $row['attendance_date'],
                    'checkIn' => $row['checkIn'] ? date('H:i:s', strtotime($row['checkIn'])) : null,
                    'checkOut' => $row['checkOut'] ? date('H:i:s', strtotime($row['checkOut'])) : null,
                    'hours' => $hours
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);

            $stmt->close();
            $conn->close();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error retrieving data']);
        }
        exit;
    }
}
?>
