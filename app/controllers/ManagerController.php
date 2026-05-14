<?php

require_once __DIR__ . '/../models/ChamCongModel.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class ManagerController
{
    private $model;

    public function __construct()
    {
        $this->model = new ChamCongModel();
        // Require manager role for all pages served by this controller
        AuthMiddleware::requireRole(['manager']);
    }

    /**
     * Display dashboard for managers
     */
    public function dashboard()
    {
        $stats = $this->model->getThongKeTongQuan() ?? [];
        $department = trim($_SESSION['user']['phongBan'] ?? '');
        if ($department === '') {
            $department = '__none__';
        }
        $payrolls = $this->model->getMonthlyApprovals(null, $department);
        $salaryRows = $this->model->getMonthlyWorkSummary(date('Y-m'), $department);

        require __DIR__ . '/../views/chamcong/manager_panel.php';
    }

    /**
     * Display approval page
     */
    public function approvals()
    {
        AuthMiddleware::requirePermission('pheduyet-bang-cong');
        // Mặc định quản lý có thể xem tất cả phòng ban
        $department = trim($_GET['department'] ?? '');
        $departments = $this->model->getDistinctDepartments();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $approvalId = (int)($_POST['approval_id'] ?? 0);
            $action = $_POST['action'] ?? '';
            $note = trim($_POST['note'] ?? '');

            $status = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : '');
            $managerId = (int)($_SESSION['user']['maND'] ?? 0);

            if ($approvalId <= 0 || $status === '') {
                $_SESSION['error'] = 'Dữ liệu phê duyệt bảng công không hợp lệ';
            } else {
                $ok = $this->model->updateMonthlyApproval($approvalId, $status, $managerId, $note, $department);
                $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Đã cập nhật trạng thái bảng công' : 'Không thể cập nhật trạng thái';
            }

            header('Location: index.php?page=pheduyet-bang-cong');
            exit;
        }

        $approvalRows = $this->model->getMonthlyApprovals('submitted', $department);
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
        $format = strtolower($_POST['format'] ?? 'html');
        $export = (int)($_POST['export'] ?? 0);

        $reportRows = $this->model->getAttendanceReport($fromDate, $toDate, $department);
        $departments = $this->model->getDistinctDepartments();
        $monthKey = substr($fromDate, 0, 7);
        
        // Sử dụng dữ liệu tính toán chính xác mới (có khấu trừ nghỉ trưa, bù công)
        $payrollRows = $this->model->getMonthlyAttendanceDetailNew($monthKey);
        if ($department !== '') {
            $payrollRows = array_values(array_filter($payrollRows, function ($row) use ($department) {
                return (string)($row['phongBan'] ?? '') === $department;
            }));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $export && in_array($format, ['excel', 'csv'], true)) {
            if ($format === 'excel') {
                require_once __DIR__ . '/../helpers/ExcelExporter.php';
                $exporter = new ExcelExporter();
                $monthKey = substr($fromDate, 0, 7);

                // Sử dụng dữ liệu chi tiết mới cho báo cáo Excel
                $detailedReportRows = $this->model->getMonthlyAttendanceDetailNew($monthKey);
                if ($department !== '') {
                    $detailedReportRows = array_values(array_filter($detailedReportRows, function($r) use ($department) {
                        return (string)($r['phongBan'] ?? '') === $department;
                    }));
                }

                $userName = $_SESSION['user']['hoTen'] ?? 'Không xác định';
                $exporter->exportAttendanceReport($detailedReportRows, $monthKey, $department, $userName);
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

        $salaryRows = $this->model->getMonthlyAttendanceDetailNew($selectedMonth);
        $totalEmployees = count($salaryRows);
        $checkedEmployees = 0;
        $totalHours = 0;
        
        $statusDistribution = [
            'on_time' => 0,
            'late' => 0,
            'absent' => 0,
            'leave' => 0,
            'holiday' => 0
        ];

        foreach ($salaryRows as $row) {
            // Check if employee had any activity this month
            if ((float)($row['work_days'] ?? 0) > 0) {
                $checkedEmployees++;
            }
            $totalHours += (float)($row['work_hours'] ?? 0);

            // Accumulate daily statuses for the chart
            $breakdown = $row['daily_breakdown'] ?? [];
            foreach ($breakdown as $day) {
                $type = $day['day_type'] ?? '';
                if ($type === 'working') {
                    $checkIn = $day['check_in'] ?? '';
                    // Extract time part from datetime
                    $timeIn = $checkIn ? substr($checkIn, 11, 8) : '';
                    if ($timeIn && $timeIn > '08:00:59') {
                        $statusDistribution['late']++;
                    } else {
                        $statusDistribution['on_time']++;
                    }
                } elseif ($type === 'absent') {
                    $statusDistribution['absent']++;
                } elseif ($type === 'leave') {
                    $statusDistribution['leave']++;
                } elseif ($type === 'holiday') {
                    $statusDistribution['holiday']++;
                }
                // We ignore 'weekend' to focus on work capacity/discipline
            }
        }

        $statsSummary = [
            'total_employees' => $totalEmployees,
            'checked_employees' => $checkedEmployees,
            'unchecked_employees' => max($totalEmployees - $checkedEmployees, 0),
            'attendance_rate' => $totalEmployees > 0 ? round(($checkedEmployees / $totalEmployees) * 100, 2) : 0,
            'total_hours' => round($totalHours, 2),
            'status_distribution' => $statusDistribution
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

        $department = trim($_SESSION['user']['phongBan'] ?? '');
        if ($department === '') {
            $department = '__none__';
        }
        $approvalRows = $this->model->getMonthlyApprovals(null, $department);
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
        $department = trim($_GET['department'] ?? '');
        $filterStatus = null;
        if ($status === 'submitted') $filterStatus = 'submitted';
        elseif ($status === 'approved') $filterStatus = 'approved';
        elseif ($status === 'rejected') $filterStatus = 'rejected';

        $rows = $this->model->getMonthlyApprovals($filterStatus, $department);

        if ($status === 'history' || $status === 'processed') {
            $rows = $this->model->getMonthlyApprovalHistory($year, 100, $department);
        }

        // filter by year if provided
        if ($year !== '' && preg_match('/^\d{4}$/', $year) && $status !== 'history' && $status !== 'processed') {
            $rows = array_values(array_filter($rows, function ($r) use ($year) {
                return strpos($r['month_key'] ?? '', $year) === 0;
            }));
        }

        // enrich each row with summary
        foreach ($rows as &$row) {
            $monthKey = $row['month_key'] ?? '';
            $summary = $this->model->getMonthlyAttendanceDetailNew($monthKey);
            if ($department !== '') {
                $summary = array_values(array_filter($summary, function($s) use ($department) {
                    return (string)($s['phongBan'] ?? '') === $department;
                }));
            }

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
        $department = trim($_SESSION['user']['phongBan'] ?? '');
        if ($department === '') {
            $department = '__none__';
        }
        if ($approvalId <= 0) {
            $this->respond([
                'success' => false,
                'message' => 'Mã phê duyệt không hợp lệ',
            ], 422);
        }

        $detail = $this->model->getMonthlyApprovalDetail($approvalId, $department);
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
        $department = trim($_SESSION['user']['phongBan'] ?? '');
        if ($department === '') {
            $department = '__none__';
        }

        $status = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : '');
        $managerId = (int)($_SESSION['user']['maND'] ?? 0);

        if ($approvalId <= 0 || $status === '') {
            $this->respond([
                'success' => false,
                'message' => 'Dữ liệu phê duyệt không hợp lệ',
            ], 422);
        }

        $ok = $this->model->updateMonthlyApproval($approvalId, $status, $managerId, $note, $department);
        $this->respond([
            'success' => $ok,
            'message' => $ok ? 'Đã cập nhật trạng thái bảng công' : 'Không thể cập nhật trạng thái',
        ], $ok ? 200 : 500);
    }

    /**
     * API: Get approval history as JSON
     */
    public function approvalHistoryApi()
    {
        AuthMiddleware::requirePermission('manager-api-approvals');
        $this->jsonOnly(['GET']);

        $year = trim($_GET['year'] ?? '');
        $limit = (int)($_GET['limit'] ?? 50);
        $department = trim($_SESSION['user']['phongBan'] ?? '');
        if ($department === '') {
            $department = '__none__';
        }

        if ($limit <= 0 || $limit > 500) {
            $limit = 50;
        }

        $rows = $this->model->getMonthlyApprovalHistory($year, $limit, $department);

        // enrich each row with summary
        foreach ($rows as &$row) {
            $monthKey = $row['month_key'] ?? '';
                        $summary = $this->model->getMonthlyAttendanceDetailNew($monthKey);
            if ($department !== '') {
                $summary = array_values(array_filter($summary, function($s) use ($department) {
                    return (string)($s['phongBan'] ?? '') === $department;
                }));
            }

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
            'count' => count($rows),
        ]);
    }

    /* ---- helpers ---- */

    // ============================
    // ĐƠN NGHỈ PHÉP (Leave Request) - Quản lý bởi Manager
    // ============================

    /**
     * Danh sách tất cả đơn nghỉ phép
     */
    public function listLeaveRequests()
    {
        $leaveRequests = $this->model->getAllLeaveRequests();
        $successMsg = $_SESSION['leave_success'] ?? '';
        $errorMsg   = $_SESSION['leave_error'] ?? '';
        unset($_SESSION['leave_success'], $_SESSION['leave_error']);

        require __DIR__ . '/../views/chamcong/leave_request_list.php';
    }

    /**
     * Phê duyệt / Từ chối đơn nghỉ phép
     */
    public function approveLeaveRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?page=list-leave-requests');
            exit;
        }

        $id          = (int)($_POST['id'] ?? 0);
        $status      = trim($_POST['status'] ?? '');
        $approvedBy  = (int)($_SESSION['user']['maND'] ?? 0);

        if ($id <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
            $_SESSION['leave_error'] = 'Dữ liệu không hợp lệ';
            header('Location: index.php?page=list-leave-requests');
            exit;
        }

        $ok    = $this->model->updateLeaveRequestStatus($id, $status, $approvedBy);
        $label = $status === 'approved' ? 'phê duyệt' : 'từ chối';
        $_SESSION[$ok ? 'leave_success' : 'leave_error'] = $ok
            ? "Đã $label đơn nghỉ phép thành công"
            : "Không thể $label đơn nghỉ phép (có thể đã được xử lý)";

        header('Location: index.php?page=list-leave-requests');
        exit;
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
