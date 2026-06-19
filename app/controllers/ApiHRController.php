<?php
/**
 * API HR Controller — RESTful endpoints cho Bộ phận Nhân sự
 * 
 * Nghiệp vụ:
 *  1.1 Quản lý nhân viên (CRUD) — chỉ xem role "Nhân viên"
 *  1.2 Quản lý ca làm việc (HC, OT)
 *  1.3 Xử lý yêu cầu chỉnh sửa chấm công (duyệt/từ chối → cập nhật bảng công)
 *  1.4 Tổng hợp công → gửi Manager phê duyệt theo phòng ban
 *  1.5 Quản lý nghỉ phép (theo dõi đơn đã duyệt)
 *  1.6 Báo cáo
 */

class ApiHRController
{
    private $model;

    public function __construct(ChamCongModel $model)
    {
        $this->model = $model;
    }

    public function handle($phuongThuc, $hanhDong, $id, $subAction, $body)
    {
        // Tất cả HR endpoints yêu cầu role HR
        requireRole('hr');

        switch ($hanhDong) {
            case 'employees':
                $this->handleEmployees($phuongThuc, $id, $body);
                break;
            case 'shifts':
                $this->handleShifts($phuongThuc, $id, $subAction, $body);
                break;
            case 'corrections':
                $this->handleCorrections($phuongThuc, $id, $body);
                break;
            case 'payroll':
                $this->handlePayroll($phuongThuc, $id, $subAction, $body);
                break;
            case 'leaves':
                $this->handleLeaves($phuongThuc, $id, $body);
                break;
            case 'reports':
                $this->handleReports($phuongThuc, $id);
                break;
            case 'departments':
                $this->handleDepartments($phuongThuc);
                break;
            default:
                respondError('HR endpoint not found: ' . $hanhDong, 404);
        }
    }

    // ========================================================
    // 1.1 QUẢN LÝ NHÂN VIÊN
    // GET    /hr/employees          — Danh sách (chỉ role "Nhân viên")
    // GET    /hr/employees/{id}     — Chi tiết
    // POST   /hr/employees          — Thêm mới
    // PUT    /hr/employees/{id}     — Cập nhật
    // ========================================================
    private function handleEmployees($phuongThuc, $id, $body)
    {
        switch ($phuongThuc) {
            case 'GET':
                if ($id) {
                    // Chi tiết 1 nhân viên
                    $employees = $this->model->getEmployees('', false, 0);
                    $found = null;
                    foreach ($employees as $emp) {
                        if ((int)$emp['maND'] === (int)$id
                            && mb_strtolower(trim($emp['chucVu'] ?? ''), 'UTF-8') === 'nhân viên') {
                            $found = $emp;
                            break;
                        }
                    }
                    if (!$found) {
                        respondError('Không tìm thấy nhân viên', 404);
                    }
                    respond(['success' => true, 'data' => $found]);
                } else {
                    // Danh sách nhân viên — chỉ role "Nhân viên", tất cả phòng ban
                    $keyword = trim($_GET['q'] ?? '');
                    $activeOnly = ($_GET['active'] ?? '1') !== '0';
                    $limit = max(0, (int)($_GET['limit'] ?? 0));

                    $allEmployees = $this->model->getEmployees($keyword, $activeOnly, 0);

                    // HR chỉ xem được nhân viên có chức vụ "Nhân viên" (tất cả phòng ban)
                    $employees = array_values(array_filter($allEmployees, function($e) {
                        return mb_strtolower(trim($e['chucVu'] ?? ''), 'UTF-8') === 'nhân viên';
                    }));

                    if ($limit > 0) {
                        $employees = array_slice($employees, 0, $limit);
                    }

                    respond([
                        'success' => true,
                        'data' => $employees,
                        'meta' => [
                            'q' => $keyword,
                            'active' => $activeOnly,
                            'limit' => $limit,
                            'count' => count($employees),
                        ]
                    ]);
                }
                break;

            case 'POST':
                // Thêm nhân viên mới
                $payload = $_POST;
                if (empty($payload)) $payload = $body;
                $payload['chucVu'] = 'Nhân viên'; // HR chỉ thêm được nhân viên

                $ok = $this->model->saveEmployee($payload);
                respond(
                    ['success' => $ok, 'message' => $ok ? 'Thêm nhân viên thành công' : 'Không thể thêm nhân viên'],
                    $ok ? 201 : 422
                );
                break;

            case 'PUT':
                if (!$id) respondError('Thiếu mã nhân viên', 422);
                $payload = $body;
                $payload['maND'] = (int)$id;
                $payload['chucVu'] = 'Nhân viên'; // HR chỉ sửa được nhân viên

                $ok = $this->model->saveEmployee($payload);
                respond(
                    ['success' => $ok, 'message' => $ok ? 'Cập nhật nhân viên thành công' : 'Không thể cập nhật'],
                    $ok ? 200 : 422
                );
                break;

            default:
                respondError('Method not allowed', 405);
        }
    }

    // ========================================================
    // 1.2 QUẢN LÝ CA LÀM VIỆC
    // GET    /hr/shifts              — Danh sách ca
    // POST   /hr/shifts              — Tạo ca mới
    // PUT    /hr/shifts/{id}         — Cập nhật ca
    // POST   /hr/shifts/assign       — Gán ca cho NV
    // ========================================================
    private function handleShifts($phuongThuc, $id, $subAction, $body)
    {
        // POST /hr/shifts/assign — Gán ca cho nhân viên
        if ($phuongThuc === 'POST' && $id === 'assign') {
            $payload = $_POST;
            if (empty($payload)) $payload = $body;
            $ok = $this->model->assignShift(
                $payload['maND'] ?? 0,
                $payload['maCa'] ?? 0,
                $payload['hieuLucTu'] ?? date('Y-m-d')
            );
            respond(
                ['success' => $ok, 'message' => $ok ? 'Gán ca làm thành công' : 'Không thể gán ca làm'],
                $ok ? 200 : 422
            );
            return;
        }

        switch ($phuongThuc) {
            case 'GET':
                $shifts = $this->model->getShifts();
                respond(['success' => true, 'data' => $shifts, 'meta' => ['count' => count($shifts)]]);
                break;

            case 'POST':
                $payload = $_POST;
                if (empty($payload)) $payload = $body;
                $ok = $this->model->saveShift($payload);
                respond(
                    ['success' => $ok, 'message' => $ok ? 'Tạo ca làm thành công' : 'Dữ liệu ca làm không hợp lệ'],
                    $ok ? 201 : 422
                );
                break;

            case 'PUT':
                if (!$id) respondError('Thiếu ID ca làm', 422);
                $payload = $body;
                $payload['id'] = (int)$id;
                $ok = $this->model->saveShift($payload);
                respond(
                    ['success' => $ok, 'message' => $ok ? 'Cập nhật ca làm thành công' : 'Dữ liệu không hợp lệ'],
                    $ok ? 200 : 422
                );
                break;

            default:
                respondError('Method not allowed', 405);
        }
    }

    // ========================================================
    // 1.3 XỬ LÝ YÊU CẦU CHỈNH SỬA CHẤM CÔNG
    // GET    /hr/corrections          — Danh sách yêu cầu
    // GET    /hr/corrections/{id}     — Chi tiết
    // PUT    /hr/corrections/{id}     — Duyệt/từ chối
    // ========================================================
    private function handleCorrections($phuongThuc, $id, $body)
    {
        switch ($phuongThuc) {
            case 'GET':
                if ($id) {
                    // Chi tiết yêu cầu
                    $detail = $this->model->getCorrectionById((int)$id);
                    if (!$detail) respondError('Không tìm thấy yêu cầu', 404);
                    respond(['success' => true, 'data' => $detail]);
                } else {
                    // Danh sách yêu cầu
                    $filters = [
                        'q' => trim($_GET['q'] ?? ''),
                        'date' => trim($_GET['date'] ?? ''),
                        'type' => trim($_GET['type'] ?? ''),
                    ];
                    $scope = trim($_GET['scope'] ?? 'pending');
                    $trangThai = $scope === 'history' ? null : 'pending';
                    $historyOnly = $scope === 'history';
                    $limit = max(0, (int)($_GET['limit'] ?? 50));

                    $data = $this->model->getCorrectionRequests($trangThai, $filters, $limit, $historyOnly);
                    respond(['success' => true, 'data' => $data, 'meta' => ['scope' => $scope, 'count' => count($data)]]);
                }
                break;

            case 'PUT':
                // Duyệt hoặc từ chối yêu cầu → cập nhật bảng công
                if (!$id) respondError('Thiếu ID yêu cầu', 422);
                $hanhDong = $body['hanhDong'] ?? '';
                $ghiChu = trim($body['ghiChu'] ?? '');

                if (!in_array($hanhDong, ['approve', 'reject'])) {
                    respondError('Action phải là "approve" hoặc "reject"', 422);
                }

                $ok = $this->model->processCorrection((int)$id, $hanhDong, $ghiChu);
                respond(
                    ['success' => $ok, 'message' => $ok ? 'Đã xử lý yêu cầu chỉnh sửa' : 'Không thể xử lý'],
                    $ok ? 200 : 500
                );
                break;

            default:
                respondError('Method not allowed', 405);
        }
    }

    // ========================================================
    // 1.4 TỔNG HỢP CÔNG & GỬI BẢNG CÔNG ĐẾN NHÂN VIÊN
    // GET    /hr/payroll               — Bảng tổng hợp công
    // GET    /hr/payroll/detail        — Chi tiết tính công
    // POST   /hr/payroll/submit        — Gửi bảng công đến từng nhân viên
    // GET    /hr/payroll/approval/{id} — Chi tiết kỳ phê duyệt
    // GET    /hr/payroll/ot-schedule   — Lịch OT đã duyệt
    // ========================================================
    private function handlePayroll($phuongThuc, $id, $subAction, $body)
    {
        // Handle sub-routes: /hr/payroll/detail, /hr/payroll/submit, /hr/payroll/approval/{id}
        if ($id === 'detail' && $phuongThuc === 'GET') {
            $monthKey = trim($_GET['month'] ?? date('Y-m'));
            if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) respondError('Tháng không hợp lệ', 422);
            $data = $this->model->getMonthlyAttendanceDetailNew($monthKey);
            respond(['success' => true, 'thangNam' => $monthKey, 'data' => $data, 'meta' => ['count' => count($data)]]);
            return;
        }

        if ($id === 'submit' && $phuongThuc === 'POST') {
            $payload = $_POST;
            if (empty($payload)) $payload = $body;
            $monthKey = trim($payload['thangNam'] ?? date('Y-m'));
            $phongBan = trim($payload['phongBan'] ?? '');
            $hrSenderId = (int)($_SESSION['user']['maND'] ?? 0);

            if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) respondError('Kỳ chấm công không hợp lệ', 422);

            $ok = $this->model->submitTimesheetToEmployees($monthKey, $hrSenderId);
            $msg = $ok ? 'Đã gửi bảng công đến từng nhân viên thành công' : 'Không thể gửi bảng công. Kiểm tra dữ liệu chấm công.';

            respond(
                ['success' => $ok, 'message' => $msg, 'approvalSummary' => $this->model->getTimesheetApprovalSummary($monthKey)],
                $ok ? 200 : 500
            );
            return;
        }

        if ($id === 'approval' && $subAction && $phuongThuc === 'GET') {
            $detail = $this->model->getMonthlyApprovalDetail((int)$subAction);
            if (!$detail) respondError('Không tìm thấy chi tiết kỳ công', 404);

            $currentHrId = (int)($_SESSION['user']['maND'] ?? 0);
            if ((int)($detail['approval']['maNguoiGuiNS'] ?? 0) !== $currentHrId) {
                respondError('Bạn không có quyền xem chi tiết kỳ công này', 403);
            }
            respond(['success' => true, 'data' => $detail]);
            return;
        }



        // Default: GET /hr/payroll — Bảng tổng hợp công
        if ($phuongThuc === 'GET') {
            $monthKey = trim($_GET['month'] ?? date('Y-m'));
            $employeeKeyword = trim($_GET['employee_q'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) respondError('Kỳ chấm công không hợp lệ', 422);

            $salaryRows = $this->model->getMonthlyAttendanceDetailNew($monthKey);

            // Filter by keyword if provided
            if ($employeeKeyword !== '' && mb_strtolower($employeeKeyword, 'UTF-8') !== 'tất cả') {
                $needle = mb_strtolower($employeeKeyword);
                $salaryRows = array_values(array_filter($salaryRows, function ($row) use ($needle) {
                    foreach (['hoTen', 'phongBan', 'maND'] as $key) {
                        if (mb_strpos(mb_strtolower((string)($row[$key] ?? '')), $needle) !== false) return true;
                    }
                    return false;
                }));
            }

            $summary = ['employees' => count($salaryRows), 'total_work_days' => 0, 'total_work_hours' => 0, 'total_overtime_hours' => 0];
            foreach ($salaryRows as $row) {
                $summary['total_work_days'] += (float)($row['work_days'] ?? 0);
                $summary['total_work_hours'] += (float)($row['work_hours'] ?? 0);
                $summary['total_overtime_hours'] += (float)($row['overtime_hours'] ?? 0);
            }

            respond([
                'success' => true,
                'data' => $salaryRows,
                'summary' => $summary,
                'approval' => $this->model->getMonthlyApprovalByMonth($monthKey),
            ]);
            return;
        }

        respondError('Payroll endpoint not found', 404);
    }

    // ========================================================
    // 1.5 QUẢN LÝ NGHỈ PHÉP
    // GET    /hr/leaves           — Danh sách đơn nghỉ
    // GET    /hr/leaves/{id}      — Chi tiết đơn
    // PUT    /hr/leaves/{id}      — Duyệt/từ chối
    // ========================================================
    private function handleLeaves($phuongThuc, $id, $body)
    {
        switch ($phuongThuc) {
            case 'GET':
                if ($id) {
                    $detail = $this->model->getLeaveById((int)$id);
                    if (!$detail) respondError('Không tìm thấy đơn nghỉ phép', 404);

                    $types = ['annual' => 'Nghỉ phép năm', 'sick' => 'Nghỉ ốm', 'personal' => 'Việc riêng', 'unpaid' => 'Nghỉ không lương'];
                    $detail['leave_type_text'] = $types[$detail['loaiNghiPhep']] ?? $detail['loaiNghiPhep'];
                    $detail['from_date_fmt'] = date('d/m/Y', strtotime($detail['tuNgay']));
                    $detail['to_date_fmt'] = date('d/m/Y', strtotime($detail['denNgay']));

                    respond(['success' => true, 'data' => $detail]);
                } else {
                    $data = $this->model->getAllLeaveRequests();
                    respond(['success' => true, 'data' => $data, 'meta' => ['count' => count($data)]]);
                }
                break;

            case 'PUT':
                if (!$id) respondError('Thiếu ID đơn nghỉ', 422);
                $trangThai = $body['trangThai'] ?? $body['hanhDong'] ?? '';
                // Normalize hanhDong to trangThai
                if ($trangThai === 'approve') $trangThai = 'approved';
                if ($trangThai === 'reject') $trangThai = 'rejected';

                if (!in_array($trangThai, ['approved', 'rejected'])) {
                    respondError('Status phải là "approved" hoặc "rejected"', 422);
                }
                $approvedBy = (int)($_SESSION['user']['maND'] ?? 0);
                $ok = $this->model->updateLeaveRequestStatus((int)$id, $trangThai, $approvedBy);
                $label = $trangThai === 'approved' ? 'phê duyệt' : 'từ chối';
                respond(
                    ['success' => $ok, 'message' => $ok ? "Đã $label đơn nghỉ phép" : 'Không thể xử lý'],
                    $ok ? 200 : 500
                );
                break;

            default:
                respondError('Method not allowed', 405);
        }
    }

    // ========================================================
    // 1.6 BÁO CÁO
    // GET    /hr/reports             — Báo cáo chấm công
    // GET    /hr/reports/holidays    — Ngày lễ
    // ========================================================
    private function handleReports($phuongThuc, $id)
    {
        if ($phuongThuc !== 'GET') respondError('Method not allowed', 405);

        if ($id === 'holidays') {
            $monthKey = trim($_GET['month'] ?? date('Y-m'));
            if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) respondError('Tháng không hợp lệ', 422);
            $data = $this->model->getHolidaysForMonth($monthKey);
            respond(['success' => true, 'data' => $data]);
            return;
        }

        // Báo cáo chấm công
        $fromDate = $_GET['tuNgay'] ?? date('Y-m-01');
        $toDate = $_GET['denNgay'] ?? date('Y-m-d');
        $phongBan = trim($_GET['phongBan'] ?? '');

        $data = $this->model->getAttendanceReport($fromDate, $toDate, $phongBan);
        respond([
            'success' => true,
            'data' => $data,
            'meta' => [
                'tuNgay' => $fromDate,
                'denNgay' => $toDate,
                'phongBan' => $phongBan,
                'count' => count($data),
            ]
        ]);
    }

    // ========================================================
    // DANH SÁCH PHÒNG BAN
    // GET    /hr/departments
    // ========================================================
    private function handleDepartments($phuongThuc)
    {
        if ($phuongThuc !== 'GET') respondError('Method not allowed', 405);
        $data = $this->model->getDistinctDepartments();
        respond(['success' => true, 'data' => $data]);
    }
}
