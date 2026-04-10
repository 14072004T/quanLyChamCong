<?php

require_once __DIR__ . '/../models/ChamCongModel.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class ManagerController
{
    private $model;

    public function __construct()
    {
        $this->model = new ChamCongModel();
        // Require manager role
        AuthMiddleware::requireRole(['manager']);
    }

    /**
     * Display dashboard for managers
     */
    public function dashboard()
    {
        $stats = $this->model->getThongKeTongQuan() ?? [];
        $payrolls = $this->model->getMonthlyApprovals();

        require __DIR__ . '/../views/chamcong/manager_panel.php';
    }

    /**
     * Display approval page
     */
    public function approvals()
    {
        AuthMiddleware::requirePermission('pheduyet-bang-cong');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['approve_type'] ?? 'monthly';

            if ($type === 'correction') {
                $correctionId = (int)($_POST['correction_id'] ?? 0);
                $action = $_POST['action'] ?? '';
                $note = trim($_POST['note'] ?? '');
                if ($correctionId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
                    $_SESSION['error'] = 'Dữ liệu duyệt yêu cầu chỉnh sửa không hợp lệ';
                } else {
                    $ok = $this->model->processCorrection($correctionId, $action, $note);
                    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Đã xử lý yêu cầu chỉnh sửa' : 'Không thể xử lý yêu cầu chỉnh sửa';
                }
            } else {
                $approvalId = (int)($_POST['approval_id'] ?? 0);
                $action = $_POST['action'] ?? '';
                $note = trim($_POST['note'] ?? '');

                $status = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : '');
                $managerId = (int)($_SESSION['user']['maND'] ?? 0);

                if ($approvalId <= 0 || $status === '') {
                    $_SESSION['error'] = 'Dữ liệu phê duyệt bảng công không hợp lệ';
                } else {
                    $ok = $this->model->updateMonthlyApproval($approvalId, $status, $managerId, $note);
                    $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Đã cập nhật trạng thái bảng công' : 'Không thể cập nhật trạng thái';
                }
            }

            header('Location: index.php?page=pheduyet-bang-cong');
            exit;
        }

        $approvalRows = $this->model->getMonthlyApprovals('submitted');
        $correctionRows = $this->model->getCorrectionRequests('pending');
        require __DIR__ . '/../views/chamcong/pheduyet.php';
    }

    /**
     * Display reports
     */
    public function reports()
    {
        AuthMiddleware::requirePermission('bao-cao-tong-hop');

        $fromDate = $_POST['from_date'] ?? $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_POST['to_date'] ?? $_GET['to_date'] ?? date('Y-m-d');
        $department = trim($_POST['department'] ?? $_GET['department'] ?? '');
        $reportRows = $this->model->getAttendanceReport($fromDate, $toDate, $department);
        $departments = $this->model->getDistinctDepartments();

        require __DIR__ . '/../views/chamcong/baocao.php';
    }

    /**
     * Display statistics
     */
    public function statistics()
    {
        AuthMiddleware::requirePermission('thong-ke-bieu-do');

        $selectedMonth = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = date('Y-m');
        }

        $salaryRows = $this->model->getMonthlyWorkSummary($selectedMonth);
        $totalEmployees = count($salaryRows);
        $checkedEmployees = 0;
        $totalHours = 0;
        foreach ($salaryRows as $row) {
            if ((float)($row['work_hours'] ?? 0) > 0) {
                $checkedEmployees++;
            }
            $totalHours += (float)($row['work_hours'] ?? 0);
        }

        $statsSummary = [
            'total_employees' => $totalEmployees,
            'checked_employees' => $checkedEmployees,
            'unchecked_employees' => max($totalEmployees - $checkedEmployees, 0),
            'attendance_rate' => $totalEmployees > 0 ? round(($checkedEmployees / $totalEmployees) * 100, 2) : 0,
            'total_hours' => round($totalHours, 2),
        ];

        $departmentSummary = [];
        foreach ($salaryRows as $row) {
            $department = $row['phongBan'] ?: 'Chưa phân phòng';
            if (!isset($departmentSummary[$department])) {
                $departmentSummary[$department] = ['employees' => 0, 'hours' => 0.0];
            }
            $departmentSummary[$department]['employees']++;
            $departmentSummary[$department]['hours'] += (float)($row['work_hours'] ?? 0);
        }

        require __DIR__ . '/../views/chamcong/thongke.php';
    }

    /**
     * Display attendance details
     */
    public function attendanceDetails()
    {
        AuthMiddleware::requirePermission('chi-tiet-bang-cong');

        $approvalRows = $this->model->getMonthlyApprovals();
        require __DIR__ . '/../views/chamcong/pheduyet.php';
    }

    /**
     * API: Get approval list as JSON
     */
    public function approvalsApi()
    {
        AuthMiddleware::requirePermission('manager-api-approvals');
        $this->jsonOnly(['GET']);

        $status = trim($_GET['status'] ?? '');
        $year = trim($_GET['year'] ?? '');

        $filterStatus = null;
        if ($status === 'submitted') $filterStatus = 'submitted';
        elseif ($status === 'approved') $filterStatus = 'approved';
        elseif ($status === 'rejected') $filterStatus = 'rejected';

        $rows = $this->model->getMonthlyApprovals($filterStatus);

        if ($status === 'history' || $status === 'processed') {
            $rows = array_values(array_filter(
                $this->model->getMonthlyApprovals(),
                function ($row) {
                    return in_array($row['status'] ?? '', ['approved', 'rejected'], true);
                }
            ));
        }

        // filter by year if provided
        if ($year !== '' && preg_match('/^\d{4}$/', $year)) {
            $rows = array_values(array_filter($rows, function ($r) use ($year) {
                return strpos($r['month_key'] ?? '', $year) === 0;
            }));
        }

        // enrich each row with summary
        foreach ($rows as &$row) {
            $monthKey = $row['month_key'] ?? '';
            $summary = $this->model->getMonthlyWorkSummary($monthKey);
            $totalEmployees = count($summary);
            $totalWorkDays = 0;
            $totalOTHours = 0;
            $violations = 0;
            foreach ($summary as $s) {
                $totalWorkDays += (float)($s['work_days'] ?? 0);
                $totalOTHours += (float)($s['overtime_hours'] ?? 0);
            }
            $violationRate = $totalEmployees > 0 ? round(($violations / $totalEmployees) * 100, 1) : 0;
            $row['total_employees'] = $totalEmployees;
            $row['total_work_days'] = round($totalWorkDays, 1);
            $row['total_ot_hours'] = round($totalOTHours, 1);
            $row['violation_rate'] = $violationRate;
        }
        unset($row);

        $this->respond([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function approvalDetailApi()
    {
        AuthMiddleware::requirePermission('manager-api-approval-detail');
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

        $this->respond([
            'success' => true,
            'data' => $detail,
        ]);
    }

    /**
     * API: Process approval action (approve/reject) as JSON
     */
    public function processApprovalApi()
    {
        AuthMiddleware::requirePermission('manager-api-approve');
        $this->jsonOnly(['POST']);

        $approvalId = (int)($_POST['approval_id'] ?? 0);
        $action = trim($_POST['action'] ?? '');
        $note = trim($_POST['note'] ?? '');

        $status = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : '');
        $managerId = (int)($_SESSION['user']['maND'] ?? 0);

        if ($approvalId <= 0 || $status === '') {
            $this->respond([
                'success' => false,
                'message' => 'Dữ liệu phê duyệt không hợp lệ',
            ], 422);
        }

        $ok = $this->model->updateMonthlyApproval($approvalId, $status, $managerId, $note);
        $this->respond([
            'success' => $ok,
            'message' => $ok ? 'Đã cập nhật trạng thái bảng công' : 'Không thể cập nhật trạng thái',
        ], $ok ? 200 : 500);
    }

    /* ---- helpers ---- */

    private function expectsJson()
    {
        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return strpos($accept, 'application/json') !== false || $requestedWith === 'xmlhttprequest';
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
