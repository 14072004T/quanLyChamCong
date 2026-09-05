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
        $payrolls = $this->model->getMonthlyApprovals(null, '');
        $salaryRows = $this->model->getMonthlyWorkSummary(date('Y-m'), '');

        require __DIR__ . '/../views/chamcong/manager_panel.php';
    }

    /**
     * Display approval page
     */
    public function approvals()
    {
        AuthMiddleware::requirePermission('pheduyet-bang-cong');
        // Mặc định quản lý có thể xem tất cả phòng ban
        $phongBan = trim($_GET['phongBan'] ?? '');
        $departments = $this->model->getDistinctDepartments();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $approvalId = (int)($_POST['approval_id'] ?? 0);
            $hanhDong = $_POST['hanhDong'] ?? '';
            $ghiChu = trim($_POST['ghiChu'] ?? '');

            $trangThai = $hanhDong === 'approve' ? 'approved' : ($hanhDong === 'reject' ? 'rejected' : '');
            $managerId = (int)($_SESSION['user']['maND'] ?? 0);

            if ($approvalId <= 0 || $trangThai === '') {
                $_SESSION['error'] = 'Dữ liệu phê duyệt bảng công không hợp lệ';
            } else {
                $ok = $this->model->updateMonthlyApproval($approvalId, $trangThai, $managerId, $ghiChu, $phongBan);
                $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Đã cập nhật trạng thái bảng công' : 'Không thể cập nhật trạng thái';
            }

            header('Location: index.php?page=pheduyet-bang-cong');
            exit;
        }

        $approvalRows = $this->model->getMonthlyApprovals('submitted', $phongBan);
        require __DIR__ . '/../views/chamcong/pheduyet.php';
    }

    /**
     * Display reports
     */
    public function reports()
    {
        AuthMiddleware::requirePermission('bao-cao-tong-hop');

        $fromDate = $_POST['tuNgay'] ?? $_GET['tuNgay'] ?? date('Y-m-01');
        $toDate = $_POST['denNgay'] ?? $_GET['denNgay'] ?? date('Y-m-d');
        // Manager có thể lọc theo phòng ban hoặc xem tất cả
        $phongBan = trim($_POST['phongBan'] ?? $_GET['phongBan'] ?? '');
        $format = strtolower($_POST['format'] ?? 'html');
        $export = (int)($_POST['export'] ?? 0);

        $reportRows = $this->model->getAttendanceReport($fromDate, $toDate, $phongBan);
        $departments = $this->model->getValidDepartments();
        $monthKey = substr($fromDate, 0, 7);
        
        // Sử dụng dữ liệu tính toán chính xác mới (có khấu trừ nghỉ trưa, bù công)
        $payrollRows = $this->model->getMonthlyAttendanceDetailNew($monthKey, $phongBan);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $export && in_array($format, ['excel', 'csv'], true)) {
            if ($format === 'excel') {
                // Tắt output buffering để tránh lỗi header
                if (ob_get_level()) {
                    ob_end_clean();
                }
                
                try {
                    require_once __DIR__ . '/../helpers/ExcelExporter.php';
                    $exporter = new ExcelExporter();
                    $monthKey = substr($fromDate, 0, 7);

                    // Sử dụng dữ liệu chi tiết mới cho báo cáo Excel
                    $detailedReportRows = $this->model->getMonthlyAttendanceDetailNew($monthKey, $phongBan);

                    $userName = $_SESSION['user']['hoTen'] ?? 'Không xác định';
                    $exporter->exportAttendanceReport($detailedReportRows, $monthKey, $phongBan, $userName);
                    exit;
                } catch (Exception $e) {
                    error_log('Excel Export Error: ' . $e->getMessage());
                    $_SESSION['error'] = 'Lỗi xuất Excel: ' . $e->getMessage();
                    header('Location: index.php?page=' . $reportActionPage);
                    exit;
                }
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
            $phongBan = $row['phongBan'] ?: 'Chưa phân phòng';
            if (!isset($departmentSummary[$phongBan])) {
                $departmentSummary[$phongBan] = ['employees' => 0, 'soGio' => 0.0];
            }
            $departmentSummary[$phongBan]['employees']++;
            $departmentSummary[$phongBan]['soGio'] += (float)($row['work_hours'] ?? 0);
        }

        require __DIR__ . '/../views/chamcong/thongke.php';
    }

    /**
     * Display attendance details
     */
    public function attendanceDetails()
    {
        AuthMiddleware::requirePermission('chi-tiet-bang-cong');

        $phongBan = trim($_SESSION['user']['phongBan'] ?? '');
        $approvalRows = $this->model->getMonthlyApprovals(null, $phongBan);
        require __DIR__ . '/../views/chamcong/pheduyet.php';
    }

    /**
     * API: Get approval list as JSON
     */
    public function approvalsApi()
    {
        AuthMiddleware::requirePermission('manager-api-approvals');
        $this->jsonOnly(['GET']);

        $trangThai = trim($_GET['trangThai'] ?? '');
        $year = trim($_GET['year'] ?? '');
        $phongBan = trim($_GET['phongBan'] ?? '');
        $filterStatus = null;
        if ($trangThai === 'submitted') $filterStatus = 'submitted';
        elseif ($trangThai === 'approved') $filterStatus = 'approved';
        elseif ($trangThai === 'rejected') $filterStatus = 'rejected';

        $rows = $this->model->getMonthlyApprovals($filterStatus, $phongBan);

        if ($trangThai === 'history' || $trangThai === 'processed') {
            $rows = $this->model->getMonthlyApprovalHistory($year, 100, $phongBan);
        }

        // filter by year if provided
        if ($year !== '' && preg_match('/^\d{4}$/', $year) && $trangThai !== 'history' && $trangThai !== 'processed') {
            $rows = array_values(array_filter($rows, function ($r) use ($year) {
                return strpos($r['thangNam'] ?? '', $year) === 0;
            }));
        }

        // enrich each row with summary
        foreach ($rows as &$row) {
            $monthKey = $row['thangNam'] ?? '';
            $summary = $this->model->getMonthlyAttendanceDetailNew($monthKey, $phongBan);

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
        $phongBan = trim($_SESSION['user']['phongBan'] ?? '');
        if ($approvalId <= 0) {
            $this->respond([
                'success' => false,
                'message' => 'Mã phê duyệt không hợp lệ',
            ], 422);
        }

        $detail = $this->model->getMonthlyApprovalDetail($approvalId, $phongBan);
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
     * API: Process approval hanhDong (approve/reject) as JSON
     */
    public function processApprovalApi()
    {
        AuthMiddleware::requirePermission('manager-api-approve');
        $this->jsonOnly(['POST']);

        $approvalId = (int)($_POST['approval_id'] ?? 0);
        $hanhDong = trim($_POST['hanhDong'] ?? '');
        $ghiChu = trim($_POST['ghiChu'] ?? '');
        $phongBan = trim($_SESSION['user']['phongBan'] ?? '');

        $trangThai = $hanhDong === 'approve' ? 'approved' : ($hanhDong === 'reject' ? 'rejected' : '');
        $managerId = (int)($_SESSION['user']['maND'] ?? 0);

        if ($approvalId <= 0 || $trangThai === '') {
            $this->respond([
                'success' => false,
                'message' => 'Dữ liệu phê duyệt không hợp lệ',
            ], 422);
        }

        $ok = $this->model->updateMonthlyApproval($approvalId, $trangThai, $managerId, $ghiChu, $phongBan);
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
        $phongBan = trim($_SESSION['user']['phongBan'] ?? '');

        if ($limit <= 0 || $limit > 500) {
            $limit = 50;
        }

        $rows = $this->model->getMonthlyApprovalHistory($year, $limit, $phongBan);

        // enrich each row with summary
        foreach ($rows as &$row) {
            $monthKey = $row['thangNam'] ?? '';
            $summary = $this->model->getMonthlyAttendanceDetailNew($monthKey, $phongBan);

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

    // ============================
    // QUẢN LÝ ĐIỀU CHỈNH CÔNG - Chuyển từ HR sang Manager
    // ============================

    /**
     * Trang trung tâm xử lý yêu cầu điều chỉnh công
     */
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

    /**
     * API: Lấy danh sách yêu cầu điều chỉnh công
     */
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
        $trangThai = $scope === 'history' ? null : 'pending';
        $historyOnly = $scope === 'history';

        $this->respond([
            'success' => true,
            'data' => $this->model->getCorrectionRequests($trangThai, $filters, 50, $historyOnly),
        ]);
    }

    /**
     * Xử lý duyệt/từ chối yêu cầu điều chỉnh công
     */
    public function processCorrection()
    {
        AuthMiddleware::requirePermission('hr-api-correction-hanhDong');

        if ($this->expectsJson()) {
            $this->jsonOnly(['POST']);
            $correctionId = (int)($_POST['correction_id'] ?? 0);
            $hanhDong = trim($_POST['hanhDong'] ?? '');
            $ghiChu = trim($_POST['ghiChu'] ?? '');

            if ($correctionId <= 0 || !in_array($hanhDong, ['approve', 'reject'], true)) {
                $this->respond([
                    'success' => false,
                    'message' => 'Dữ liệu xử lý yêu cầu không hợp lệ',
                ], 422);
            }

            $ok = $this->model->processCorrection($correctionId, $hanhDong, $ghiChu);
            $this->respond([
                'success' => $ok,
                'message' => $ok ? 'Đã xử lý yêu cầu chỉnh sửa' : 'Không thể xử lý yêu cầu chỉnh sửa',
            ], $ok ? 200 : 500);
        }

        $redirectPage = 'xuly-yeucau';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correctionId = (int)($_POST['correction_id'] ?? $_GET['id'] ?? 0);
            $hanhDong = $_POST['hanhDong'] ?? (isset($_POST['approve']) ? 'approve' : 'reject');
            $ghiChu = trim($_POST['ghiChu'] ?? '');

            if ($correctionId <= 0 || !in_array($hanhDong, ['approve', 'reject'], true)) {
                $_SESSION['error'] = 'Dữ liệu xử lý yêu cầu không hợp lệ';
                header('Location: index.php?page=' . $redirectPage);
                exit;
            }

            $ok = $this->model->processCorrection($correctionId, $hanhDong, $ghiChu);
            $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Đã xử lý yêu cầu chỉnh sửa' : 'Không thể xử lý yêu cầu chỉnh sửa';
        }

        header('Location: index.php?page=' . $redirectPage);
        exit;
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
        $trangThai   = trim($_POST['trangThai'] ?? '');
        if ($trangThai === 'approve') $trangThai = 'approved';
        if ($trangThai === 'reject')  $trangThai = 'rejected';
        $approvedBy  = (int)($_SESSION['user']['maND'] ?? 0);

        if ($id <= 0 || !in_array($trangThai, ['approved', 'rejected'], true)) {
            $_SESSION['leave_error'] = 'Dữ liệu không hợp lệ';
            header('Location: index.php?page=list-leave-requests');
            exit;
        }

        $ok    = $this->model->updateLeaveRequestStatus($id, $trangThai, $approvedBy);
        $label = $trangThai === 'approved' ? 'phê duyệt' : 'từ chối';
        $_SESSION[$ok ? 'leave_success' : 'leave_error'] = $ok
            ? "Đã $label đơn nghỉ phép thành công"
            : "Không thể $label đơn nghỉ phép (có thể đã được xử lý)";

        header('Location: index.php?page=list-leave-requests');
        exit;
    }

    /**
     * API: Lấy danh sách tất cả yêu cầu (đơn nghỉ phép) cho Manager
     */
    public function requestsApi()
    {
        AuthMiddleware::requirePermission('manager-api-requests');
        $this->jsonOnly(['GET']);

        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'date' => trim($_GET['date'] ?? ''),
            'type' => trim($_GET['type'] ?? ''),
            'trangThai' => trim($_GET['trangThai'] ?? ''),
            'phongBan' => trim($_GET['phongBan'] ?? ''),
        ];
        $limit = (int)($_GET['limit'] ?? 300);

        $this->respond([
            'success' => true,
            'data' => $this->model->getManagerEmployeeRequests($filters, $limit),
        ]);
    }

    /**
     * API: Phê duyệt / Từ chối đơn nghỉ phép cho Manager
     */
    public function processRequestApi()
    {
        AuthMiddleware::requirePermission('manager-api-request-hanhDong');
        $this->jsonOnly(['POST']);

        $requestId = (int)($_POST['request_id'] ?? $_POST['id'] ?? 0);
        $type = trim($_POST['type'] ?? 'leave');
        $hanhDong = trim($_POST['hanhDong'] ?? $_POST['trangThai'] ?? '');
        $ghiChu = trim($_POST['ghiChu'] ?? '');
        $managerId = (int)($_SESSION['user']['maND'] ?? 0);

        if ($requestId <= 0 || !in_array($hanhDong, ['approve', 'reject', 'approved', 'rejected'], true)) {
            $this->respond([
                'success' => false,
                'message' => 'Dữ liệu xử lý yêu cầu không hợp lệ',
            ], 422);
        }

        $ok = $this->model->processManagerEmployeeRequest($type, $requestId, $hanhDong, $managerId, $ghiChu);
        $label = ($hanhDong === 'approve' || $hanhDong === 'approved') ? 'phê duyệt' : 'từ chối';
        $this->respond([
            'success' => $ok,
            'message' => $ok ? "Đã $label đơn nghỉ phép thành công" : "Không thể $label đơn nghỉ phép (có thể đã được xử lý)",
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

    private function respond(array $payload, int $trangThai = 200)
    {
        http_response_code($trangThai);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
