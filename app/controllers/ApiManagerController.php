<?php
/**
 * API Manager Controller — RESTful endpoints cho Quản lý
 * 
 * Nghiệp vụ:
 *  2.1 Phê duyệt bảng công — chỉ phòng ban mình quản lý
 *  2.2 Phê duyệt yêu cầu (nghỉ phép, OT, đổi ca) — NV phòng ban mình
 *  2.3 Báo cáo tổng hợp — hoạt động NV phòng ban mình
 */

class ApiManagerController
{
    private $model;

    public function __construct(ChamCongModel $model)
    {
        $this->model = $model;
    }

    public function handle($method, $action, $id, $subAction, $body)
    {
        requireRole('manager');

        switch ($action) {
            case 'approvals':
                $this->handleApprovals($method, $id, $subAction, $body);
                break;
            case 'requests':
                $this->handleRequests($method, $id, $body);
                break;
            case 'reports':
                $this->handleReports($method, $id);
                break;
            case 'statistics':
                $this->handleStatistics($method);
                break;
            case 'employees':
                $this->handleEmployees($method);
                break;
            default:
                respondError('Resource not found', 404);
        }
    }

    private function handleEmployees($method)
    {
        if ($method !== 'GET') respondError('Method not allowed', 405);

        $department = $this->getManagerDepartment();
        $keyword = trim($_GET['q'] ?? '');
        $activeOnly = (int)($_GET['active_only'] ?? 1) === 1;

        $rows = $this->model->getEmployeesByDepartment($department, $keyword, $activeOnly);
        respond([
            'success' => true,
            'data' => $rows,
            'meta' => ['count' => count($rows), 'department' => $department]
        ]);
    }

    /**
     * Lấy phòng ban của Manager hiện tại
     */
    private function getManagerDepartment()
    {
        $department = trim($_SESSION['user']['phongBan'] ?? '');
        return $department !== '' ? $department : '__none__';
    }

    // ========================================================
    // 2.1 PHÊ DUYỆT BẢNG CÔNG (theo phòng ban Manager quản lý)
    // GET    /manager/approvals              — Bảng công chờ duyệt
    // GET    /manager/approvals/history      — Lịch sử phê duyệt
    // GET    /manager/approvals/{id}         — Chi tiết bảng công
    // PUT    /manager/approvals/{id}         — Duyệt/từ chối
    // ========================================================
    private function handleApprovals($method, $id, $subAction, $body)
    {
        $department = $this->getManagerDepartment();

        // GET /manager/approvals/history
        if ($id === 'history' && $method === 'GET') {
            $year = trim($_GET['year'] ?? '');
            $limit = max(1, min(500, (int)($_GET['limit'] ?? 50)));
            $rows = $this->model->getMonthlyApprovalHistory($year, $limit, $department);
            $this->enrichApprovalRows($rows, $department);

            respond(['success' => true, 'data' => $rows, 'meta' => ['count' => count($rows), 'department' => $department]]);
            return;
        }

        switch ($method) {
            case 'GET':
                if ($id && $id !== 'history') {
                    // Chi tiết bảng công
                    $detail = $this->model->getMonthlyApprovalDetail((int)$id, $department);
                    if (!$detail) respondError('Không tìm thấy chi tiết kỳ công', 404);
                    respond(['success' => true, 'data' => $detail]);
                } else {
                    // Danh sách bảng công chờ duyệt
                    $status = trim($_GET['status'] ?? 'submitted');
                    $year = trim($_GET['year'] ?? '');
                    $filterStatus = in_array($status, ['submitted', 'approved', 'rejected']) ? $status : null;

                    $rows = $this->model->getMonthlyApprovals($filterStatus, $department);

                    // Xử lý lịch sử nếu status=history
                    if ($status === 'history' || $status === 'processed') {
                        $rows = $this->model->getMonthlyApprovalHistory($year, 100, $department);
                    }

                    // Filter by year
                    if ($year !== '' && preg_match('/^\d{4}$/', $year) && !in_array($status, ['history', 'processed'])) {
                        $rows = array_values(array_filter($rows, function ($r) use ($year) {
                            return strpos($r['month_key'] ?? '', $year) === 0;
                        }));
                    }

                    $this->enrichApprovalRows($rows, $department);
                    respond([
                        'success' => true,
                        'data' => $rows,
                        'meta' => ['status' => $status, 'department' => $department, 'count' => count($rows)]
                    ]);
                }
                break;

            case 'PUT':
                // Duyệt/từ chối bảng công
                if (!$id) respondError('Thiếu ID phê duyệt', 422);
                $action = $body['action'] ?? '';
                $note = trim($body['note'] ?? '');
                $status = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : '');
                $managerId = (int)($_SESSION['user']['maND'] ?? 0);

                if ($status === '') respondError('Action phải là "approve" hoặc "reject"', 422);

                $ok = $this->model->updateMonthlyApproval((int)$id, $status, $managerId, $note, $department);
                respond(
                    ['success' => $ok, 'message' => $ok ? 'Đã cập nhật trạng thái bảng công' : 'Không thể cập nhật'],
                    $ok ? 200 : 500
                );
                break;

            default:
                respondError('Method not allowed', 405);
        }
    }

    /**
     * Bổ sung thông tin tổng hợp cho mỗi bảng công
     */
    private function enrichApprovalRows(&$rows, $department)
    {
        foreach ($rows as &$row) {
            $monthKey = $row['month_key'] ?? '';
            $summary = $this->model->getMonthlyWorkSummary($monthKey, $department);
            $totalWorkDays = 0;
            $totalOTHours = 0;
            foreach ($summary as $s) {
                $totalWorkDays += (float)($s['work_days'] ?? 0);
                $totalOTHours += (float)($s['overtime_hours'] ?? 0);
            }
            $row['total_employees'] = count($summary);
            $row['total_work_days'] = round($totalWorkDays, 1);
            $row['total_ot_hours'] = round($totalOTHours, 1);
        }
        unset($row);
    }

    // ========================================================
    // 2.2 PHÊ DUYỆT YÊU CẦU (nghỉ phép, OT, đổi ca)
    // GET    /manager/requests          — DS yêu cầu NV phòng ban
    // PUT    /manager/requests/{id}     — Duyệt/từ chối
    // ========================================================
    private function handleRequests($method, $id, $body)
    {
        switch ($method) {
            case 'GET':
                $filters = [
                    'q' => trim($_GET['q'] ?? ''),
                    'date' => trim($_GET['date'] ?? ''),
                    'type' => trim($_GET['type'] ?? ''),
                    'status' => trim($_GET['status'] ?? ''),
                    'department' => trim($_GET['department'] ?? ''),
                    'date_from' => trim($_GET['date_from'] ?? ''),
                    'date_to' => trim($_GET['date_to'] ?? ''),
                ];
                if (!in_array($filters['type'], ['', 'leave', 'ot', 'shift'])) $filters['type'] = '';
                if (!in_array($filters['status'], ['', 'pending', 'approved', 'rejected'])) $filters['status'] = '';
                $limit = min(500, max(1, (int)($_GET['limit'] ?? 100)));

                $rows = $this->model->getManagerEmployeeRequests($filters, $limit);
                respond(['success' => true, 'data' => $rows, 'meta' => ['count' => count($rows)]]);
                break;

            case 'PUT':
                // Duyệt/từ chối yêu cầu
                if (!$id) respondError('Thiếu ID yêu cầu', 422);
                $type = $body['type'] ?? '';
                $action = $body['action'] ?? '';
                $note = trim($body['note'] ?? '');

                if (!in_array($type, ['leave', 'ot', 'shift'])) respondError('Type phải là "leave", "ot" hoặc "shift"', 422);
                if (!in_array($action, ['approve', 'reject'])) respondError('Action phải là "approve" hoặc "reject"', 422);

                $managerId = (int)($_SESSION['user']['maND'] ?? 0);
                $ok = $this->model->processManagerEmployeeRequest($type, (int)$id, $action, $managerId, $note);
                respond(
                    ['success' => $ok, 'message' => $ok ? 'Đã xử lý yêu cầu' : 'Không thể xử lý'],
                    $ok ? 200 : 500
                );
                break;

            default:
                respondError('Method not allowed', 405);
        }
    }

    // ========================================================
    // 2.3 BÁO CÁO TỔNG HỢP PHÒNG BAN
    // GET    /manager/reports       — Báo cáo chấm công phòng ban
    // ========================================================
    private function handleReports($method, $id)
    {
        if ($method !== 'GET') respondError('Method not allowed', 405);

        $fromDate = $_GET['from_date'] ?? date('Y-m-01');
        $toDate = $_GET['to_date'] ?? date('Y-m-d');
        $department = trim($_GET['department'] ?? '');
        $monthKey = substr($fromDate, 0, 7);

        $reportRows = $this->model->getAttendanceReport($fromDate, $toDate, $department);
        $payrollRows = $this->model->getMonthlyWorkSummary($monthKey);
        if ($department !== '') {
            $payrollRows = array_values(array_filter($payrollRows, function ($row) use ($department) {
                return (string)($row['phongBan'] ?? '') === $department;
            }));
        }

        respond([
            'success' => true,
            'data' => [
                'attendance_report' => $reportRows,
                'payroll_summary' => $payrollRows,
            ],
            'meta' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'department' => $department,
                'report_count' => count($reportRows),
                'payroll_count' => count($payrollRows),
            ]
        ]);
    }

    // ========================================================
    // 2.3 THỐNG KÊ BIỂU ĐỒ
    // GET    /manager/statistics    — Thống kê phòng ban
    // ========================================================
    private function handleStatistics($method)
    {
        if ($method !== 'GET') respondError('Method not allowed', 405);

        $selectedMonth = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) $selectedMonth = date('Y-m');

        $salaryRows = $this->model->getMonthlyWorkSummary($selectedMonth);
        $totalEmployees = count($salaryRows);
        $checkedEmployees = 0;
        $totalHours = 0;
        foreach ($salaryRows as $row) {
            if ((float)($row['work_hours'] ?? 0) > 0) $checkedEmployees++;
            $totalHours += (float)($row['work_hours'] ?? 0);
        }

        // Thống kê theo phòng ban
        $departmentSummary = [];
        foreach ($salaryRows as $row) {
            $dept = $row['phongBan'] ?: 'Chưa phân phòng';
            if (!isset($departmentSummary[$dept])) {
                $departmentSummary[$dept] = ['employees' => 0, 'hours' => 0.0];
            }
            $departmentSummary[$dept]['employees']++;
            $departmentSummary[$dept]['hours'] += (float)($row['work_hours'] ?? 0);
        }

        respond([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_employees' => $totalEmployees,
                    'checked_employees' => $checkedEmployees,
                    'unchecked_employees' => max($totalEmployees - $checkedEmployees, 0),
                    'attendance_rate' => $totalEmployees > 0 ? round(($checkedEmployees / $totalEmployees) * 100, 2) : 0,
                    'total_hours' => round($totalHours, 2),
                ],
                'departments' => $departmentSummary,
            ],
            'meta' => ['month' => $selectedMonth]
        ]);
    }
}
