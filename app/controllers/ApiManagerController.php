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

    public function handle($phuongThuc, $hanhDong, $id, $subAction, $body)
    {
        requireRole('manager');

        switch ($hanhDong) {
            case 'approvals':
                $this->handleApprovals($phuongThuc, $id, $subAction, $body);
                break;
            case 'requests':
                $this->handleRequests($phuongThuc, $id, $body);
                break;
            case 'reports':
                $this->handleReports($phuongThuc, $id);
                break;
            case 'statistics':
                $this->handleStatistics($phuongThuc);
                break;
            case 'employees':
                $this->handleEmployees($phuongThuc);
                break;
            default:
                respondError('Resource not found', 404);
        }
    }

    private function handleEmployees($phuongThuc)
    {
        if ($phuongThuc !== 'GET') respondError('Method not allowed', 405);

        $keyword = trim($_GET['q'] ?? '');
        $activeOnly = (int)($_GET['active_only'] ?? 1) === 1;

        $rows = $this->model->getEmployeesByDepartment('', $keyword, $activeOnly);
        respond([
            'success' => true,
            'data' => $rows,
            'meta' => ['count' => count($rows), 'phongBan' => $phongBan]
        ]);
    }

    /**
     * Lấy phòng ban của Manager hiện tại
     */
    private function getManagerDepartment()
    {
        return trim($_SESSION['user']['phongBan'] ?? '');
    }

    // ========================================================
    // 2.1 PHÊ DUYỆT BẢNG CÔNG (theo phòng ban Manager quản lý)
    // GET    /manager/approvals              — Bảng công chờ duyệt
    // GET    /manager/approvals/history      — Lịch sử phê duyệt
    // GET    /manager/approvals/{id}         — Chi tiết bảng công
    // PUT    /manager/approvals/{id}         — Duyệt/từ chối
    // ========================================================
    private function handleApprovals($phuongThuc, $id, $subAction, $body)
    {
        $phongBan = $this->getManagerDepartment();

        // GET /manager/approvals/history
        if ($id === 'history' && $phuongThuc === 'GET') {
            $year = trim($_GET['year'] ?? '');
            $limit = max(1, min(500, (int)($_GET['limit'] ?? 50)));
            $rows = $this->model->getMonthlyApprovalHistory($year, $limit, $phongBan);
            $this->enrichApprovalRows($rows, $phongBan);

            respond(['success' => true, 'data' => $rows, 'meta' => ['count' => count($rows), 'phongBan' => $phongBan]]);
            return;
        }

        switch ($phuongThuc) {
            case 'GET':
                if ($id && $id !== 'history') {
                    // Chi tiết bảng công
                    $detail = $this->model->getMonthlyApprovalDetail((int)$id, $phongBan);
                    if (!$detail) respondError('Không tìm thấy chi tiết kỳ công', 404);
                    respond(['success' => true, 'data' => $detail]);
                } else {
                    // Danh sách bảng công chờ duyệt
                    $trangThai = trim($_GET['trangThai'] ?? 'submitted');
                    $year = trim($_GET['year'] ?? '');
                    $filterStatus = in_array($trangThai, ['submitted', 'approved', 'rejected']) ? $trangThai : null;

                    $rows = $this->model->getMonthlyApprovals($filterStatus, $phongBan);

                    // Xử lý lịch sử nếu trangThai=history
                    if ($trangThai === 'history' || $trangThai === 'processed') {
                        $rows = $this->model->getMonthlyApprovalHistory($year, 100, $phongBan);
                    }

                    // Filter by year
                    if ($year !== '' && preg_match('/^\d{4}$/', $year) && !in_array($trangThai, ['history', 'processed'])) {
                        $rows = array_values(array_filter($rows, function ($r) use ($year) {
                            return strpos($r['thangNam'] ?? '', $year) === 0;
                        }));
                    }

                    $this->enrichApprovalRows($rows, $phongBan);
                    respond([
                        'success' => true,
                        'data' => $rows,
                        'meta' => ['trangThai' => $trangThai, 'phongBan' => $phongBan, 'count' => count($rows)]
                    ]);
                }
                break;

            case 'PUT':
                // Duyệt/từ chối bảng công
                if (!$id) respondError('Thiếu ID phê duyệt', 422);
                $hanhDong = $body['hanhDong'] ?? '';
                $ghiChu = trim($body['ghiChu'] ?? '');
                $trangThai = $hanhDong === 'approve' ? 'approved' : ($hanhDong === 'reject' ? 'rejected' : '');
                $managerId = (int)($_SESSION['user']['maND'] ?? 0);

                if ($trangThai === '') respondError('Action phải là "approve" hoặc "reject"', 422);

                $ok = $this->model->updateMonthlyApproval((int)$id, $trangThai, $managerId, $ghiChu, $phongBan);
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
    private function enrichApprovalRows(&$rows, $phongBan)
    {
        foreach ($rows as &$row) {
            $monthKey = $row['thangNam'] ?? '';
            $summary = $this->model->getMonthlyWorkSummary($monthKey, $phongBan);
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
    private function handleRequests($phuongThuc, $id, $body)
    {
        switch ($phuongThuc) {
            case 'GET':
                $filters = [
                    'q' => trim($_GET['q'] ?? ''),
                    'date' => trim($_GET['date'] ?? ''),
                    'type' => trim($_GET['type'] ?? ''),
                    'trangThai' => trim($_GET['trangThai'] ?? ''),
                    'phongBan' => trim($_GET['phongBan'] ?? ''),
                    'date_from' => trim($_GET['date_from'] ?? ''),
                    'date_to' => trim($_GET['date_to'] ?? ''),
                ];
                if (!in_array($filters['type'], ['', 'leave', 'ot', 'shift'])) $filters['type'] = '';
                if (!in_array($filters['trangThai'], ['', 'pending', 'approved', 'rejected'])) $filters['trangThai'] = '';
                $limit = min(500, max(1, (int)($_GET['limit'] ?? 100)));

                $rows = $this->model->getManagerEmployeeRequests($filters, $limit);
                respond(['success' => true, 'data' => $rows, 'meta' => ['count' => count($rows)]]);
                break;

            case 'PUT':
                // Duyệt/từ chối yêu cầu
                if (!$id) respondError('Thiếu ID yêu cầu', 422);
                $type = $body['type'] ?? '';
                $hanhDong = $body['hanhDong'] ?? '';
                $ghiChu = trim($body['ghiChu'] ?? '');

                if (!in_array($type, ['leave', 'ot', 'shift'])) respondError('Type phải là "leave", "ot" hoặc "shift"', 422);
                if (!in_array($hanhDong, ['approve', 'reject'])) respondError('Action phải là "approve" hoặc "reject"', 422);

                $managerId = (int)($_SESSION['user']['maND'] ?? 0);
                $ok = $this->model->processManagerEmployeeRequest($type, (int)$id, $hanhDong, $managerId, $ghiChu);
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
    private function handleReports($phuongThuc, $id)
    {
        if ($phuongThuc !== 'GET') respondError('Method not allowed', 405);

        $fromDate = $_GET['tuNgay'] ?? date('Y-m-01');
        $toDate = $_GET['denNgay'] ?? date('Y-m-d');
        $phongBan = trim($_GET['phongBan'] ?? '');
        $monthKey = substr($fromDate, 0, 7);

        $reportRows = $this->model->getAttendanceReport($fromDate, $toDate, $phongBan);
        $payrollRows = $this->model->getMonthlyWorkSummary($monthKey);
        if ($phongBan !== '') {
            $payrollRows = array_values(array_filter($payrollRows, function ($row) use ($phongBan) {
                return (string)($row['phongBan'] ?? '') === $phongBan;
            }));
        }

        respond([
            'success' => true,
            'data' => [
                'attendance_report' => $reportRows,
                'payroll_summary' => $payrollRows,
            ],
            'meta' => [
                'tuNgay' => $fromDate,
                'denNgay' => $toDate,
                'phongBan' => $phongBan,
                'report_count' => count($reportRows),
                'payroll_count' => count($payrollRows),
            ]
        ]);
    }

    // ========================================================
    // 2.3 THỐNG KÊ BIỂU ĐỒ
    // GET    /manager/statistics    — Thống kê phòng ban
    // ========================================================
    private function handleStatistics($phuongThuc)
    {
        if ($phuongThuc !== 'GET') respondError('Method not allowed', 405);

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
                $departmentSummary[$dept] = ['employees' => 0, 'soGio' => 0.0];
            }
            $departmentSummary[$dept]['employees']++;
            $departmentSummary[$dept]['soGio'] += (float)($row['work_hours'] ?? 0);
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
