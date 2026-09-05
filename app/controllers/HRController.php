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
        // Require HR role for all pages served by this controller
        AuthMiddleware::requireRole(['hr']);
    }

    public function employees()
    {
        AuthMiddleware::requirePermission('quan-ly-nhanvien');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
                $maND = (int)($_POST['maND'] ?? 0);
                $ok = $this->model->resetEmployeePassword($maND);
                $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Đặt lại mật khẩu thành công (Mật khẩu mặc định: 123456)' : 'Không thể đặt lại mật khẩu';
            } else {
                $ok = $this->model->saveEmployee($_POST);
                $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Lưu thông tin nhân viên thành công' : 'Không thể lưu thông tin nhân viên';
            }
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
            $hanhDong = $_POST['form_action'] ?? 'save_shift';
            if ($hanhDong === 'assign_shift') {
                $ok = $this->model->assignShift($_POST['maND'] ?? 0, $_POST['maCa'] ?? 0, $_POST['hieuLucTu'] ?? date('Y-m-d'));
                $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Gán ca làm thành công' : 'Không thể gán ca làm';
            } elseif ($hanhDong === 'delete_shift') {
                $ok = $this->model->deleteShift($_POST['id'] ?? 0);
                $_SESSION[$ok ? 'success' : 'error'] = $ok ? 'Xóa ca làm thành công' : 'Không thể xóa ca làm';
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
            'tenCa' => $_POST['tenCa'] ?? '',
            'kyHieu' => $_POST['kyHieu'] ?? '',
            'mauSac' => $_POST['mauSac'] ?? '#3b82f6',
            'gioBatDau' => $_POST['gioBatDau'] ?? '',
            'gioKetThuc' => $_POST['gioKetThuc'] ?? '',
            'hoatDong' => $_POST['hoatDong'] ?? 1,
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
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->respond(['success' => true, 'data' => []]);
            return;
        }
        $this->jsonOnly(['POST']);

        // Lưới lịch phân ca đổi ca theo từng ô ngày, không được kéo dài sang các ngày khác.
        $ok = $this->model->assignShiftForDate(
            $_POST['maND'] ?? 0,
            $_POST['maCa'] ?? 0,
            $_POST['hieuLucTu'] ?? date('Y-m-d')
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
        ]);
    }

    public function tabletScanHistoryApi()
    {
        AuthMiddleware::requirePermission('hr-api-tablet-scans');
        $this->jsonOnly(['GET']);

        $monthKey = trim($_GET['month'] ?? date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            $this->respond([
                'success' => false,
                'message' => 'Kỳ không hợp lệ',
            ], 422);
        }

        $page = max(1, (int)($_GET['pg'] ?? 1));
        $perPage = 24;
        $keyword = trim($_GET['q'] ?? '');

        $result = $this->model->getTabletScanHistory($monthKey, $page, $perPage, $keyword);

        $this->respond([
            'success' => true,
            'data' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $perPage > 0 ? (int)ceil($result['total'] / $perPage) : 0,
        ]);
    }

    public function deleteTabletScansApi()
    {
        AuthMiddleware::requirePermission('hr-api-tablet-scans-delete');
        $this->jsonOnly(['POST']);

        $ids = $_POST['ids'] ?? [];
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?: [];
        }
        if (!is_array($ids) || empty($ids)) {
            $this->respond([
                'success' => false,
                'message' => 'Chưa chọn bản ghi nào để xoá.',
            ], 422);
        }

        $photos = $this->model->getTabletScanPhotos($ids);
        $deleted = $this->model->deleteTabletScans($ids);

        $uploadDir = 'uploads/attendance_faces/';
        foreach ($photos as $photo) {
            $path = $uploadDir . $photo;
            if ($photo !== '' && is_file($path)) {
                @unlink($path);
            }
        }

        $this->respond([
            'success' => $deleted > 0,
            'message' => $deleted > 0 ? "Đã xoá {$deleted} bản ghi." : 'Không có bản ghi nào bị xoá.',
            'deleted' => $deleted,
        ]);
    }

    public function reports()
    {
        AuthMiddleware::requirePermission('xuat-bao-cao');

        $fromDate = $_POST['tuNgay'] ?? $_GET['tuNgay'] ?? date('Y-m-01');
        $toDate = $_POST['denNgay'] ?? $_GET['denNgay'] ?? date('Y-m-d');
        $phongBan = trim($_POST['phongBan'] ?? $_GET['phongBan'] ?? '');
        $format = strtolower($_POST['format'] ?? 'html');
        $export = (int)($_POST['export'] ?? 0);
        $monthKey = substr($fromDate, 0, 7);

        $reportRows = $this->model->getAttendanceReport($fromDate, $toDate, $phongBan);
        $departments = $this->model->getDistinctDepartments();

        $summaryRows = $this->model->getTimesheetApprovalSummary($monthKey);
        $summaryRow = $summaryRows[0] ?? null;
        $canExport = $summaryRow && (int)($summaryRow['total'] ?? 0) > 0 && (int)($summaryRow['pending'] ?? 0) === 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $export && in_array($format, ['excel', 'csv'], true)) {
            if (!$canExport) {
                $_SESSION['error'] = 'Chỉ xuất khi tất cả nhân viên đã duyệt bảng công.';
                header('Location: index.php?page=tinh-cong&month=' . urlencode($monthKey));
                exit;
            }
            if ($format === 'excel') {
                require_once __DIR__ . '/../helpers/ExcelExporter.php';
                $exporter = new ExcelExporter();
                
                // Sử dụng dữ liệu chi tiết mới cho báo cáo Excel
                $detailedReportRows = $this->model->getMonthlyAttendanceDetailNew($monthKey);
                if ($phongBan !== '') {
                    $detailedReportRows = array_values(array_filter($detailedReportRows, function($r) use ($phongBan) {
                        return (string)($r['phongBan'] ?? '') === $phongBan;
                    }));
                }
                
                $userName = $_SESSION['user']['hoTen'] ?? 'Không xác định';
                $exporter->exportAttendanceReport($detailedReportRows, $monthKey, $phongBan, $userName);
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
            $monthKey = trim($_POST['thangNam'] ?? date('Y-m'));
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

        $monthKey = trim($_POST['thangNam'] ?? date('Y-m'));
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
        if ((int)($approval['maNguoiGuiNS'] ?? 0) !== $currentHrId) {
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
            'thangNam' => $monthKey,
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

    public function timesheetApprovalDetailsApi()
    {
        AuthMiddleware::requirePermission('hr-api-timesheet-approval-details');
        $this->jsonOnly(['GET']);

        $monthKey = trim($_GET['month'] ?? '');
        if (!$monthKey) {
            $this->respond(['success' => false, 'message' => 'Thiếu kỳ công'], 422);
        }

        $details = $this->model->getTimesheetApprovalDetails($monthKey);
        $this->respond([
            'success' => true,
            'data' => $details
        ]);
    }

    private function respond(array $payload, int $trangThai = 200)
    {
        if (ob_get_length()) ob_clean();
        http_response_code($trangThai);
        header('Content-Type: application/json; charset=utf-8');
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            echo json_encode([
                'success' => false, 
                'message' => 'Lỗi mã hóa JSON: ' . json_last_error_msg()
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo $json;
        }
        exit;
    }
}
