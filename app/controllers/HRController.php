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
        AuthMiddleware::requireRole(['hr']);
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
        $employees = $this->model->getEmployees($keyword, $activeOnly, $limit);

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
            $this->model->getMonthlyWorkSummary($selectedMonth),
            $employeeKeyword
        );
        $monthlyApproval = $this->model->getMonthlyApprovalByMonth($selectedMonth);
        $approvalHistory = $this->model->getMonthlyApprovalsBySender((int)($_SESSION['user']['maND'] ?? 0), ['submitted', 'approved', 'rejected'], 12);
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
            $this->model->getMonthlyWorkSummary($monthKey),
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
            'approvalHistory' => $this->model->getMonthlyApprovalsBySender((int)($_SESSION['user']['maND'] ?? 0), ['submitted', 'approved', 'rejected'], 12),
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

        $reportRows = $this->model->getAttendanceReport($fromDate, $toDate, $department);
        $departments = $this->model->getDistinctDepartments();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($format, ['excel', 'csv'], true)) {
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
                $ok = $this->model->submitMonthlyApproval($monthKey, $hrSenderId);
                $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Đã gửi bảng công để phê duyệt' : 'Không thể gửi bảng công';
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

        $ok = $this->model->submitMonthlyApproval($monthKey, $hrSenderId);
        $this->respond([
            'success' => $ok,
            'message' => $ok ? 'Đã gửi bảng công để phê duyệt' : 'Không thể gửi bảng công',
            'approval' => $this->model->getMonthlyApprovalByMonth($monthKey),
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

    private function respond(array $payload, int $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
