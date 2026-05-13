<?php

require_once __DIR__ . '/../models/ChamCongModel.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class HRController
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->model = new ChamCongModel();

        // Đơn nghỉ phép: cho phép tất cả nhân viên đã đăng nhập
        $leavePages = ['create-leave-request', 'store-leave-request'];
        $currentPage = $_GET['page'] ?? '';
        if (!in_array($currentPage, $leavePages, true)) {
            AuthMiddleware::requireRole(['hr']);
        }
    }

    public function employees()
    {
        AuthMiddleware::requirePermission('quan-ly-nhanvien');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ok = $this->model->saveEmployee($_POST);
            $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Lưu thông tin nhân viên thành công' : 'Không thể lưu thông tin nhân viên';
            header('Location: index.php?page=quan-ly-nhanvien');
            exit;
        }

        $keyword = trim($_GET['q'] ?? '');
        $employees = $this->model->getEmployees($keyword);
        require __DIR__ . '/../views/chamcong/quanly_nhanvien.php';
    }

    public function employeesApi()
    {
        AuthMiddleware::requirePermission('hr-api-employees');
        $this->jsonOnly(['GET']);

        $keyword = trim($_GET['q'] ?? '');
        $activeOnly = ($_GET['active'] ?? '1') !== '0';
        $limit = max(0, (int)($_GET['limit'] ?? 20));
        $allEmployees = $this->model->getEmployees($keyword, $activeOnly, 0);
        
        // HR chỉ xem được nhân viên có chức vụ "Nhân viên" (tất cả phòng ban)
        $employees = array_values(array_filter($allEmployees, function($e) {
            return mb_strtolower(trim($e['chucVu'] ?? ''), 'UTF-8') === 'nhân viên';
        }));
        
        if ($limit > 0) {
            $employees = array_slice($employees, 0, $limit);
        }

        $this->respond([
            'success' => true,
            'data' => $employees,
            'meta' => [
                'q' => $keyword,
                'active' => $activeOnly,
                'limit' => $limit,
                'count' => count($employees),
            ],
        ]);
    }

    public function shifts()
    {
        AuthMiddleware::requirePermission('quan-ly-ca-lam');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['form_action'] ?? 'save_shift';
            if ($action === 'assign_shift') {
                $ok = $this->model->assignShift($_POST['maND'] ?? 0, $_POST['shift_id'] ?? 0, $_POST['effective_from'] ?? date('Y-m-d'));
                $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Gán ca làm thành công' : 'Không thể gán ca làm';
            } else {
                $ok = $this->model->saveShift($_POST);
                $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Lưu ca làm thành công' : 'Dữ liệu ca làm không hợp lệ';
            }
            header('Location: index.php?page=quan-ly-ca-lam');
            exit;
        }

        $shifts = $this->model->getShifts();
        $employees = $this->model->getEmployees();
        require __DIR__ . '/../views/chamcong/quanly_calam.php';
    }

    public function shiftsApi()
    {
        AuthMiddleware::requirePermission('hr-api-shifts');

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->respond([
                'success' => true,
                'data' => $this->model->getShifts(),
            ]);
        }

        $this->jsonOnly(['POST']);
        $payload = [
            'id' => $_POST['id'] ?? 0,
            'shift_name' => $_POST['shift_name'] ?? '',
            'start_time' => $_POST['start_time'] ?? '',
            'end_time' => $_POST['end_time'] ?? '',
            'is_active' => $_POST['is_active'] ?? 1,
        ];
        $ok = $this->model->saveShift($payload);

        $this->respond([
            'success' => $ok,
            'message' => $ok ? 'Lưu ca làm thành công' : 'Dữ liệu ca làm không hợp lệ',
        ], $ok ? 200 : 422);
    }

    public function shiftAssignmentsApi()
    {
        AuthMiddleware::requirePermission('hr-api-shift-assignments');
        $this->jsonOnly(['POST']);

        $ok = $this->model->assignShift(
            $_POST['maND'] ?? 0,
            $_POST['shift_id'] ?? 0,
            $_POST['effective_from'] ?? date('Y-m-d')
        );

        $this->respond([
            'success' => $ok,
            'message' => $ok ? 'Gán ca làm thành công' : 'Không thể gán ca làm',
        ], $ok ? 200 : 422);
    }

    public function salary()
    {
        AuthMiddleware::requirePermission('tinh-cong');

        $selectedMonth = $_POST['month'] ?? $_GET['month'] ?? date('Y-m');
        $employeeKeyword = trim($_POST['employee_q'] ?? $_GET['employee_q'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = date('Y-m');
        }

        $salaryRows = $this->filterPayrollRows(
            $this->model->getMonthlyAttendanceDetailNew($selectedMonth),
            $employeeKeyword
        );
        $allActive = $this->model->getEmployees('', true, 0);
        // HR chỉ xem được nhân viên có chức vụ "Nhân viên" (tất cả phòng ban)
        $filterEmployees = array_values(array_filter($allActive, function($e) {
            return mb_strtolower(trim($e['chucVu'] ?? ''), 'UTF-8') === 'nhân viên';
        }));
        
        $monthlyApproval = $this->model->getMonthlyApprovalByMonth($selectedMonth);
        $approvalHistory = $this->model->getTimesheetApprovalSummary();
        require __DIR__ . '/../views/chamcong/tinhcong.php';
    }

    public function payrollApi()
    {
        AuthMiddleware::requirePermission('hr-api-payroll');
        $this->jsonOnly(['GET']);

        $monthKey = trim($_GET['month'] ?? date('Y-m'));
        $employeeKeyword = trim($_GET['employee_q'] ?? '');
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            $this->respond([
                'success' => false,
                'message' => 'Kỳ chấm công không hợp lệ',
            ], 422);
        }

        $salaryRows = $this->filterPayrollRows(
            $this->model->getMonthlyAttendanceDetailNew($monthKey),
            $employeeKeyword
        );
        $summary = [
            'employees' => count($salaryRows),
            'total_work_days' => 0,
            'total_work_hours' => 0,
            'total_overtime_hours' => 0,
        ];

        foreach ($salaryRows as $row) {
            $summary['total_work_days'] += (float)($row['work_days'] ?? 0);
            $summary['total_work_hours'] += (float)($row['work_hours'] ?? 0);
            $summary['total_overtime_hours'] += (float)($row['overtime_hours'] ?? 0);
        }

        $this->respond([
            'success' => true,
            'data' => $salaryRows,
            'summary' => $summary,
            'approval' => $this->model->getMonthlyApprovalByMonth($monthKey),
            'approvalSummary' => $this->model->getTimesheetApprovalSummary($monthKey),
            'approvalHistory' => $this->model->getTimesheetApprovalSummary(),
            'otSchedule' => $this->model->getApprovedOtSchedule($monthKey),
        ]);
    }

    public function reports()
    {
        AuthMiddleware::requirePermission('xuat-bao-cao');

        $fromDate = $_POST['from_date'] ?? $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_POST['to_date'] ?? $_GET['to_date'] ?? date('Y-m-d');
        $department = trim($_POST['department'] ?? $_GET['department'] ?? '');
        $format = strtolower($_POST['format'] ?? 'html');
        $export = (int)($_POST['export'] ?? 0);

        $reportRows = $this->model->getAttendanceReport($fromDate, $toDate, $department);
        $departments = $this->model->getDistinctDepartments();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $export && in_array($format, ['excel', 'csv'], true)) {
            if ($format === 'excel') {
                require_once __DIR__ . '/../helpers/ExcelExporter.php';
                $exporter = new ExcelExporter();
                $monthKey = substr($fromDate, 0, 7);
                $userName = $_SESSION['user']['hoTen'] ?? 'Không xác định';
                $exporter->exportAttendanceReport($reportRows, $monthKey, $department, $userName);
                exit;
            } else {
                // CSV export
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=bao_cao_cham_cong_' . $fromDate . '_' . $toDate . '.csv');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Ma NV', 'Ho ten', 'Phong ban', 'So ngay co cham cong', 'So lan vao', 'So lan ra']);
                foreach ($reportRows as $row) {
                    fputcsv($out, [
                        $row['maND'] ?? '',
                        $row['hoTen'] ?? '',
                        $row['phongBan'] ?? '',
                        $row['work_days'] ?? 0,
                        $row['checkin_count'] ?? 0,
                        $row['checkout_count'] ?? 0,
                    ]);
                }
                fclose($out);
                exit;
            }
        }

        require __DIR__ . '/../views/chamcong/baocao.php';
    }

    public function attendance()
    {
        AuthMiddleware::requirePermission('gui-bang-cong-phe-duyet');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $monthKey = trim($_POST['month_key'] ?? date('Y-m'));
            $hrSenderId = (int)($_SESSION['user']['maND'] ?? 0);

            if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
                $_SESSION['error'] = 'Kỳ chấm công không hợp lệ';
            } else {
                $ok = $this->model->submitTimesheetToEmployees($monthKey, $hrSenderId);
                $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Đã gửi bảng công đến từng nhân viên thành công' : 'Không thể gửi bảng công. Có thể chưa có dữ liệu chấm công trong tháng này.';
            }
        }

        header('Location: index.php?page=tinh-cong');
        exit;
    }

    public function submitPayrollApi()
    {
        AuthMiddleware::requirePermission('hr-api-payroll-submit');
        $this->jsonOnly(['POST']);

        $monthKey = trim($_POST['month_key'] ?? date('Y-m'));
        $hrSenderId = (int)($_SESSION['user']['maND'] ?? 0);
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            $this->respond([
                'success' => false,
                'message' => 'Kỳ chấm công không hợp lệ',
            ], 422);
        }

        $ok = $this->model->submitTimesheetToEmployees($monthKey, $hrSenderId);
        $this->respond([
            'success' => $ok,
            'message' => $ok ? 'Đã gửi bảng công đến từng nhân viên' : 'Không thể gửi bảng công. Kiểm tra dữ liệu chấm công.',
            'approvalSummary' => $this->model->getTimesheetApprovalSummary($monthKey),
        ], $ok ? 200 : 500);
    }

    public function approvalDetailApi()
    {
        AuthMiddleware::requirePermission('hr-api-approval-detail');
        $this->jsonOnly(['GET']);

        $approvalId = (int)($_GET['approval_id'] ?? 0);
        if ($approvalId <= 0) {
            $this->respond([
                'success' => false,
                'message' => 'Mã phê duyệt không hợp lệ',
            ], 422);
        }

        $detail = $this->model->getMonthlyApprovalDetail($approvalId);
        if (!$detail) {
            $this->respond([
                'success' => false,
                'message' => 'Không tìm thấy chi tiết kỳ công',
            ], 404);
        }

        $approval = $detail['approval'] ?? [];
        $currentHrId = (int)($_SESSION['user']['maND'] ?? 0);
        if ((int)($approval['hr_sender_id'] ?? 0) !== $currentHrId) {
            $this->respond([
                'success' => false,
                'message' => 'Bạn không có quyền xem chi tiết kỳ công này',
            ], 403);
        }

        $this->respond([
            'success' => true,
            'data' => $detail,
        ]);
    }

    public function requestCenter()
    {
        AuthMiddleware::requirePermission('xuly-yeucau');

        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'date' => trim($_GET['date'] ?? ''),
            'type' => trim($_GET['type'] ?? ''),
        ];
        $pendingCorrections = $this->model->getCorrectionRequests('pending', $filters);
        $processedCorrections = $this->model->getCorrectionRequests(null, $filters, 20, true);
        require __DIR__ . '/../views/chamcong/xuly_yeucau.php';
    }

    public function correctionsApi()
    {
        AuthMiddleware::requirePermission('hr-api-corrections');
        $this->jsonOnly(['GET']);

        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'date' => trim($_GET['date'] ?? ''),
            'type' => trim($_GET['type'] ?? ''),
        ];
        $scope = trim($_GET['scope'] ?? 'pending');
        $status = $scope === 'history' ? null : 'pending';
        $historyOnly = $scope === 'history';

        $this->respond([
            'success' => true,
            'data' => $this->model->getCorrectionRequests($status, $filters, 50, $historyOnly),
        ]);
    }

    public function processCorrection()
    {
        AuthMiddleware::requirePermission('hr-api-correction-action');

        if ($this->expectsJson()) {
            $this->jsonOnly(['POST']);
            $correctionId = (int)($_POST['correction_id'] ?? 0);
            $action = trim($_POST['action'] ?? '');
            $note = trim($_POST['note'] ?? '');

            if ($correctionId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
                $this->respond([
                    'success' => false,
                    'message' => 'Dữ liệu xử lý yêu cầu không hợp lệ',
                ], 422);
            }

            $ok = $this->model->processCorrection($correctionId, $action, $note);
            $this->respond([
                'success' => $ok,
                'message' => $ok ? 'Đã xử lý yêu cầu chỉnh sửa' : 'Không thể xử lý yêu cầu chỉnh sửa',
            ], $ok ? 200 : 500);
        }

        $redirectPage = 'xuly-yeucau';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correctionId = (int)($_POST['correction_id'] ?? $_GET['id'] ?? 0);
            $action = $_POST['action'] ?? (isset($_POST['approve']) ? 'approve' : 'reject');
            $note = trim($_POST['note'] ?? '');

            if ($correctionId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
                $_SESSION['error'] = 'Dữ liệu xử lý yêu cầu không hợp lệ';
                header('Location: index.php?page=' . $redirectPage);
                exit;
            }

            $ok = $this->model->processCorrection($correctionId, $action, $note);
            $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Đã xử lý yêu cầu chỉnh sửa' : 'Không thể xử lý yêu cầu chỉnh sửa';
        }

        header('Location: index.php?page=' . $redirectPage);
        exit;
    }

    private function expectsJson()
    {
        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return strpos($accept, 'application/json') !== false || $requestedWith === 'xmlhttprequest';
    }

    private function filterPayrollRows(array $rows, $keyword)
    {
        $keyword = trim((string)$keyword);
        if (mb_strtolower($keyword, 'UTF-8') === 'tất cả') {
            $keyword = '';
        }
        
        if ($keyword === '') {
            return $rows;
        }

        $needle = mb_strtolower($keyword);
        return array_values(array_filter($rows, function ($row) use ($needle) {
            $haystacks = [
                (string)($row['hoTen'] ?? ''),
                (string)($row['phongBan'] ?? ''),
                (string)($row['maND'] ?? ''),
            ];

            foreach ($haystacks as $value) {
                if (mb_strpos(mb_strtolower($value), $needle) !== false) {
                    return true;
                }
            }

            return false;
        }));
    }

    private function jsonOnly(array $methods)
    {
        if (!in_array($_SERVER['REQUEST_METHOD'], $methods, true)) {
            $this->respond([
                'success' => false,
                'message' => 'Method not allowed',
            ], 405);
        }
    }

    /**
     * API: Lấy dữ liệu tính công chi tiết theo quy định 2026 (có phép, lễ, OT)
     */
    public function payrollDetailApi()
    {
        AuthMiddleware::requirePermission('hr-api-payroll');
        $this->jsonOnly(['GET']);

        $monthKey = trim($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            $this->respond([
                'success' => false,
                'message' => 'Kỳ chấm công không hợp lệ',
            ], 422);
        }

        // Lấy dữ liệu chi tiết với phép, lễ, OT
        $detailedData = $this->model->getMonthlyAttendanceDetailNew($monthKey);

        $this->respond([
            'success' => true,
            'month_key' => $monthKey,
            'data' => $detailedData,
            'count' => count($detailedData),
        ]);
    }

    /**
     * API: Lấy thông tin ngày lễ tháng
     */
    public function holidaysApi()
    {
        AuthMiddleware::requirePermission('hr-api-payroll');
        $this->jsonOnly(['GET']);

        $monthKey = trim($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            $this->respond([
                'success' => false,
                'message' => 'Tháng không hợp lệ',
            ], 422);
        }

        $holidays = $this->model->getHolidaysForMonth($monthKey);

        $this->respond([
            'success' => true,
            'data' => $holidays,
        ]);
    }

    // ============================
    // ĐƠN NGHỈ PHÉP (Leave Request)
    // ============================

    public function createLeaveRequest()
    {
        $message = $_SESSION['leave_success'] ?? '';
        $error = $_SESSION['leave_error'] ?? '';
        unset($_SESSION['leave_success'], $_SESSION['leave_error']);

        $maND = (int)($_SESSION['user']['maND'] ?? 0);
        $myRequests = $this->model->getLeaveRequestsByUser($maND);
        require __DIR__ . '/../views/chamcong/leave_request_form.php';
    }

    public function storeLeaveRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=create-leave-request');
            exit;
        }

        $maND = (int)($_SESSION['user']['maND'] ?? 0);
        $leave_type = trim($_POST['leave_type'] ?? 'personal');
        $from_date = trim($_POST['from_date'] ?? '');
        $to_date = trim($_POST['to_date'] ?? '');
        $reason = trim($_POST['reason'] ?? '');

        if ($from_date === '' || $to_date === '' || $reason === '') {
            $_SESSION['leave_error'] = 'Vui lòng điền đầy đủ các trường bắt buộc (*)';
            header('Location: index.php?page=create-leave-request');
            exit;
        }

        if ($from_date > $to_date) {
            $_SESSION['leave_error'] = 'Ngày bắt đầu phải trước hoặc bằng ngày kết thúc';
            header('Location: index.php?page=create-leave-request');
            exit;
        }

        // Handle file upload
        $evidence_file = null;
        if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['evidence_file'];
            $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts, true)) {
                $_SESSION['leave_error'] = 'Chỉ chấp nhận file JPG, PNG hoặc PDF';
                header('Location: index.php?page=create-leave-request');
                exit;
            }
            if ($file['size'] > $maxSize) {
                $_SESSION['leave_error'] = 'Kích thước file tối đa là 5MB';
                header('Location: index.php?page=create-leave-request');
                exit;
            }

            $uploadDir = 'uploads/leave_evidence/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = 'leave_' . $maND . '_' . date('YmdHis') . '_' . mt_rand(100, 999) . '.' . $ext;
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $evidence_file = $filePath;
            }
        }

        $ok = $this->model->insertLeaveRequest($maND, $leave_type, $from_date, $to_date, $reason, $evidence_file);
        $_SESSION[$ok ? 'leave_success' : 'leave_error'] = $ok
            ? 'Gửi đơn nghỉ phép thành công!'
            : 'Không thể gửi đơn nghỉ phép. Vui lòng thử lại.';

        header('Location: index.php?page=create-leave-request');
        exit;
    }

    public function listLeaveRequests()
    {
        AuthMiddleware::requireRole(['hr', 'manager']);

        $leaveRequests = $this->model->getAllLeaveRequests();
        $successMsg = $_SESSION['leave_success'] ?? '';
        $errorMsg = $_SESSION['leave_error'] ?? '';
        unset($_SESSION['leave_success'], $_SESSION['leave_error']);

        require __DIR__ . '/../views/chamcong/leave_request_list.php';
    }

    public function approveLeaveRequest()
    {
        AuthMiddleware::requireRole(['hr', 'manager']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=list-leave-requests');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $approvedBy = (int)($_SESSION['user']['maND'] ?? 0);

        if ($id <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
            $_SESSION['leave_error'] = 'Dữ liệu không hợp lệ';
            header('Location: index.php?page=list-leave-requests');
            exit;
        }

        $ok = $this->model->updateLeaveRequestStatus($id, $status, $approvedBy);
        $label = $status === 'approved' ? 'phê duyệt' : 'từ chối';
        $_SESSION[$ok ? 'leave_success' : 'leave_error'] = $ok
            ? "Đã $label đơn nghỉ phép thành công"
            : "Không thể $label đơn nghỉ phép (có thể đã được xử lý)";

        header('Location: index.php?page=list-leave-requests');
        exit;
    }

    private function respond(array $payload, int $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
