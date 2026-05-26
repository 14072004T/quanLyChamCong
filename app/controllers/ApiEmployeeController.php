<?php
/**
 * API Employee Controller — RESTful endpoints cho Nhân viên
 * 
 * Nghiệp vụ:
 *  3.1 Chấm công — qua WiFi nội bộ (IP validation server-side)
 *  3.2 Xem lịch sử chấm công
 *  3.3 Gửi yêu cầu điều chỉnh công → HR duyệt
 *  3.4 Đăng ký nghỉ phép → Manager duyệt
 */

class ApiEmployeeController
{
    private $model;

    public function __construct(ChamCongModel $model)
    {
        $this->model = $model;
    }

    public function handle($phuongThuc, $resource, $hanhDong, $id, $body)
    {
        // Tất cả endpoints yêu cầu đăng nhập (bất kỳ role nào)
        requireAuth();

        if ($resource === 'attendance') {
            $this->handleAttendance($phuongThuc, $hanhDong, $id, $body);
        } elseif ($resource === 'leaves') {
            $this->handleLeaves($phuongThuc, $hanhDong, $id, $body);
        } else {
            respondError('Resource not found', 404);
        }
    }

    // ========================================================
    // ATTENDANCE ENDPOINTS
    // ========================================================
    private function handleAttendance($phuongThuc, $hanhDong, $id, $body)
    {
        switch ($hanhDong) {
            case 'check-in':
                $this->handleCheckIn($phuongThuc);
                break;
            case 'check-out':
                $this->handleCheckOut($phuongThuc);
                break;
            case 'today':
                $this->handleToday($phuongThuc);
                break;
            case 'validate-network':
                $this->handleValidateNetwork($phuongThuc);
                break;
            case 'history':
                $this->handleHistory($phuongThuc);
                break;
            case 'corrections':
                $this->handleCorrections($phuongThuc, $id, $body);
                break;
            default:
                respondError('Attendance endpoint not found: ' . $hanhDong, 404);
        }
    }

    // ========================================================
    // 3.1 CHẤM CÔNG — qua WiFi nội bộ
    // POST   /attendance/check-in
    // POST   /attendance/check-out
    // GET    /attendance/today
    // GET    /attendance/validate-network
    // ========================================================

    private function handleCheckIn($phuongThuc)
    {
        if ($phuongThuc !== 'POST') respondError('Method not allowed', 405);

        $maND = $_SESSION['user']['maND'] ?? null;
        $serverIP = $this->model->getServerIP();

        // Validate mạng nội bộ
        if (!$this->model->isInternalNetwork($serverIP)) {
            respondError("Không thuộc mạng nội bộ ($serverIP). Chấm công yêu cầu kết nối WiFi công ty.", 403);
        }

        // Auto-detect WiFi
        $wifiName = $this->detectWifiName($serverIP);

        // Kiểm tra đã chấm công chưa
        $logs = $this->model->getTodayLogs($maND);
        foreach ($logs as $log) {
            if ($log['hanhDong'] === 'IN') {
                respondError('Bạn đã chấm công vào hôm nay rồi. Chỉ được 1 lần/ngày.');
                return;
            }
        }

        // Kiểm tra ca làm việc
        $shift = $this->model->getShiftForUser($maND);
        if (!$shift) {
            respondError('Bạn chưa được phân ca làm việc. Vui lòng liên hệ HR.');
            return;
        }

        // Kiểm tra khung giờ ca làm
        $shiftError = $this->checkShiftTime($shift);
        if ($shiftError) respondError($shiftError);

        // Lưu chấm công
        $ok = $this->model->chamCong($maND, 'IN', 'LAN', $wifiName, 'Check-in via REST API', $serverIP);
        respond([
            'success' => $ok,
            'message' => $ok ? 'Chấm công vào thành công' : 'Lỗi không thể lưu dữ liệu',
            'data' => [
                'time' => date('Y-m-d H:i:s'),
                'wifi' => $wifiName,
                'ip' => $serverIP,
            ]
        ], $ok ? 200 : 500);
    }

    private function handleCheckOut($phuongThuc)
    {
        if ($phuongThuc !== 'POST') respondError('Method not allowed', 405);

        $maND = $_SESSION['user']['maND'] ?? null;
        $serverIP = $this->model->getServerIP();

        if (!$this->model->isInternalNetwork($serverIP)) {
            respondError("Không thuộc mạng nội bộ ($serverIP)", 403);
        }

        $wifiName = $this->detectWifiName($serverIP);

        $logs = $this->model->getTodayLogs($maND);
        $hasIn = false;
        $hasOut = false;
        foreach ($logs as $log) {
            if ($log['hanhDong'] === 'IN') $hasIn = true;
            if ($log['hanhDong'] === 'OUT') $hasOut = true;
        }

        if (!$hasIn) respondError('Bạn chưa chấm công vào hôm nay');
        if ($hasOut) respondError('Bạn đã chấm công ra rồi. Chỉ được 1 lần/ngày.');

        $ok = $this->model->chamCong($maND, 'OUT', 'LAN', $wifiName, 'Check-out via REST API', $serverIP);
        respond([
            'success' => $ok,
            'message' => $ok ? 'Chấm công ra thành công' : 'Lỗi lưu dữ liệu',
            'data' => [
                'time' => date('Y-m-d H:i:s'),
                'wifi' => $wifiName,
            ]
        ], $ok ? 200 : 500);
    }

    private function handleToday($phuongThuc)
    {
        if ($phuongThuc !== 'GET') respondError('Method not allowed', 405);

        $maND = $_SESSION['user']['maND'] ?? null;
        $logs = $this->model->getTodayLogs($maND);

        $checkIn = null;
        $checkOut = null;
        foreach ($logs as $log) {
            if ($log['hanhDong'] === 'IN') $checkIn = $log['ngayTao'];
            if ($log['hanhDong'] === 'OUT') $checkOut = $log['ngayTao'];
        }

        $totalHours = 0;
        if ($checkIn && $checkOut) {
            $totalHours = round((strtotime($checkOut) - strtotime($checkIn)) / 3600, 2);
        }

        // Lấy trạng thái ca làm
        $shiftStatus = $this->model->getTodayShiftStatus($maND);

        respond([
            'success' => true,
            'data' => [
                'checkIn' => $checkIn,
                'checkOut' => $checkOut,
                'total_hours' => $totalHours,
                'shift_status' => $shiftStatus,
                'date' => date('Y-m-d'),
            ]
        ]);
    }

    private function handleValidateNetwork($phuongThuc)
    {
        if ($phuongThuc !== 'GET') respondError('Method not allowed', 405);

        $serverIP = $this->model->getServerIP();
        $allowedNetworks = $this->model->getActiveWifiConfigurations();
        $isValid = $this->model->isAllowedIp($serverIP);

        respond([
            'success' => true,
            'data' => [
                'ip' => $serverIP,
                'is_allowed' => $isValid,
                'allowed_networks' => $allowedNetworks,
                'message' => $isValid ? 'Bạn đang ở trong mạng nội bộ' : 'Bạn không ở mạng nội bộ công ty'
            ]
        ]);
    }

    // ========================================================
    // 3.2 LỊCH SỬ CHẤM CÔNG
    // GET    /attendance/history
    // ========================================================
    private function handleHistory($phuongThuc)
    {
        if ($phuongThuc !== 'GET') respondError('Method not allowed', 405);

        $maND = $_SESSION['user']['maND'] ?? null;
        $from = trim($_GET['tuNgay'] ?? date('Y-m-01'));
        $to = trim($_GET['denNgay'] ?? date('Y-m-d'));
        $limit = max(1, (int)($_GET['limit'] ?? 30));

        // Validation date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');

        $history = $this->model->getLichSu($maND, $from, $to);

        respond([
            'success' => true,
            'data' => $history,
            'meta' => [
                'tuNgay' => $from,
                'denNgay' => $to,
                'count' => count($history),
            ]
        ]);
    }

    // ========================================================
    // 3.3 YÊU CẦU ĐIỀU CHỈNH CÔNG → Gửi HR
    // GET    /attendance/corrections         — DS yêu cầu của tôi
    // POST   /attendance/corrections         — Gửi yêu cầu mới
    // GET    /attendance/corrections/{id}    — Chi tiết
    // ========================================================
    private function handleCorrections($phuongThuc, $id, $body)
    {
        $maND = $_SESSION['user']['maND'] ?? null;

        switch ($phuongThuc) {
            case 'GET':
                if ($id) {
                    $detail = $this->model->getCorrectionById((int)$id);
                    if (!$detail) respondError('Không tìm thấy yêu cầu', 404);

                    // Format dates
                    $detail['attendance_date_fmt'] = date('d/m/Y', strtotime($detail['ngayChamCong']));
                    $detail['created_at_fmt'] = date('d/m/Y H:i', strtotime($detail['ngayTao']));

                    respond(['success' => true, 'data' => $detail]);
                } else {
                    $requests = $this->model->getYeuCauTheoNhanVien($maND);
                    respond(['success' => true, 'data' => $requests, 'meta' => ['count' => count($requests)]]);
                }
                break;

            case 'POST':
                // Gửi yêu cầu chỉnh sửa chấm công
                $payload = $_POST;
                if (empty($payload)) $payload = $body;

                $attendanceDate = trim($payload['ngayChamCong'] ?? '');
                $lyDo = trim($payload['lyDo'] ?? '');
                $proposedCheckin = trim($payload['gioVaoDeXuat'] ?? '');
                $proposedCheckout = trim($payload['gioRaDeXuat'] ?? '');

                if ($attendanceDate === '' || $lyDo === '') {
                    respondError('Vui lòng nhập đầy đủ ngày và lý do', 422);
                }
                if ($proposedCheckin === '' && $proposedCheckout === '') {
                    respondError('Vui lòng nhập ít nhất giờ vào hoặc giờ ra đề xuất', 422);
                }

                // Get original attendance
                $attendanceRecords = $this->model->getAttendanceByUser($maND, 60);
                $originalIn = null;
                foreach ($attendanceRecords as $rec) {
                    if (($rec['ngayLamViec'] ?? '') === $attendanceDate) {
                        $originalIn = $rec['gioVaoDau'] ?? null;
                        break;
                    }
                }

                $data = [
                    'maND' => $maND,
                    'ngayChamCong' => $attendanceDate,
                    'gioCu' => $originalIn,
                    'gioMoi' => $proposedCheckin ?: $proposedCheckout ?: date('Y-m-d H:i:s'),
                    'lyDo' => $lyDo,
                    'gioVaoDeXuat' => $proposedCheckin ?: null,
                    'gioRaDeXuat' => $proposedCheckout ?: null,
                    'tepMinhChung' => null,
                ];

                // Handle file upload if present
                if (isset($_FILES['tepMinhChung']) && $_FILES['tepMinhChung']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['tepMinhChung'];
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($file['tmp_name']);
                    $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];

                    if (!isset($allowedMimes[$mimeType])) {
                        respondError('Loại file không hợp lệ. Chỉ chấp nhận JPG, PNG, PDF.', 422);
                    }
                    if ($file['size'] > 5 * 1024 * 1024) {
                        respondError('File quá lớn. Tối đa 5MB.', 422);
                    }

                    $uploadDir = 'uploads/attendance_evidence/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $ext = $allowedMimes[$mimeType];
                    $uniqueName = $maND . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $uniqueName)) {
                        $data['tepMinhChung'] = $uniqueName;
                    }
                }

                $ok = $this->model->insertEditRequest($data);
                respond(
                    ['success' => $ok, 'message' => $ok ? 'Gửi yêu cầu chỉnh sửa thành công' : 'Không thể gửi yêu cầu'],
                    $ok ? 201 : 500
                );
                break;

            default:
                respondError('Method not allowed', 405);
        }
    }

    // ========================================================
    // 3.4 ĐĂNG KÝ NGHỈ PHÉP → Manager duyệt
    // GET    /leaves          — DS đơn nghỉ của tôi
    // POST   /leaves          — Tạo đơn mới
    // GET    /leaves/{id}     — Chi tiết
    // ========================================================
    private function handleLeaves($phuongThuc, $hanhDong, $id, $body)
    {
        $maND = (int)($_SESSION['user']['maND'] ?? 0);

        // If hanhDong is a number, treat as ID
        if (is_numeric($hanhDong)) {
            $id = $hanhDong;
            $hanhDong = '';
        }

        switch ($phuongThuc) {
            case 'GET':
                if ($id) {
                    $detail = $this->model->getLeaveById((int)$id);
                    if (!$detail) respondError('Không tìm thấy đơn nghỉ phép', 404);

                    $types = ['annual' => 'Nghỉ phép năm', 'sick' => 'Nghỉ ốm', 'personal' => 'Việc riêng', 'unpaid' => 'Nghỉ không lương'];
                    $detail['leave_type_text'] = $types[$detail['loaiNghiPhep']] ?? $detail['loaiNghiPhep'];
                    $detail['from_date_fmt'] = date('d/m/Y', strtotime($detail['tuNgay']));
                    $detail['to_date_fmt'] = date('d/m/Y', strtotime($detail['denNgay']));
                    $detail['created_at_fmt'] = date('d/m/Y H:i', strtotime($detail['ngayTao']));

                    respond(['success' => true, 'data' => $detail]);
                } else {
                    $requests = $this->model->getLeaveRequestsByUser($maND);
                    respond(['success' => true, 'data' => $requests, 'meta' => ['count' => count($requests)]]);
                }
                break;

            case 'POST':
                $payload = $_POST;
                if (empty($payload)) $payload = $body;

                $loaiNghiPhep = trim($payload['loaiNghiPhep'] ?? 'personal');
                $tuNgay = trim($payload['tuNgay'] ?? '');
                $denNgay = trim($payload['denNgay'] ?? '');
                $lyDo = trim($payload['lyDo'] ?? '');

                if ($tuNgay === '' || $denNgay === '' || $lyDo === '') {
                    respondError('Vui lòng điền đầy đủ: ngày bắt đầu, ngày kết thúc, lý do', 422);
                }
                if ($tuNgay > $denNgay) {
                    respondError('Ngày bắt đầu phải trước hoặc bằng ngày kết thúc', 422);
                }

                // Handle evidence file
                $tepMinhChung = null;
                if (isset($_FILES['tepMinhChung']) && $_FILES['tepMinhChung']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['tepMinhChung'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
                        respondError('Chỉ chấp nhận file JPG, PNG hoặc PDF', 422);
                    }
                    if ($file['size'] > 5 * 1024 * 1024) {
                        respondError('Kích thước file tối đa là 5MB', 422);
                    }

                    $uploadDir = 'uploads/leave_evidence/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $fileName = 'leave_' . $maND . '_' . date('YmdHis') . '_' . mt_rand(100, 999) . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                        $tepMinhChung = $uploadDir . $fileName;
                    }
                }

                $ok = $this->model->insertLeaveRequest($maND, $loaiNghiPhep, $tuNgay, $denNgay, $lyDo, $tepMinhChung);
                respond(
                    ['success' => $ok, 'message' => $ok ? 'Gửi đơn nghỉ phép thành công' : 'Không thể gửi đơn'],
                    $ok ? 201 : 500
                );
                break;

            default:
                respondError('Method not allowed', 405);
        }
    }

    // ========================================================
    // HELPER METHODS
    // ========================================================

    private function detectWifiName($serverIP)
    {
        $wifiName = '';
        $configs = $this->model->getActiveWifiConfigurations();
        foreach ($configs as $cfg) {
            if (strpos($serverIP, $cfg['daiIP']) === 0) {
                $wifiName = $cfg['tenWifi'];
                break;
            }
        }
        if (empty($wifiName)) {
            $wifiName = $this->model->getFirstActiveWifiName() ?? 'Unknown WiFi';
        }
        return $wifiName;
    }

    private function checkShiftTime($shift)
    {
        $now = date('H:i:s');
        $start = $shift['gioBatDau'];
        $end = $shift['gioKetThuc'];

        if ($start < $end) {
            if ($now < $start) return 'Ca làm việc chưa bắt đầu. Ca bắt đầu lúc ' . substr($start, 0, 5);
            if ($now > $end) return 'Ca làm việc đã kết thúc (' . substr($end, 0, 5) . ')';
        } else {
            if ($now > $end && $now < $start) return 'Ngoài giờ ca làm việc';
        }
        return null;
    }
}
