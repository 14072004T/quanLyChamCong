<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require middleware
require_once 'app/middleware/AuthMiddleware.php';

$defaultPage = 'login';
if (isset($_SESSION['user'])) {
    $defaultPage = in_array(($_SESSION['role'] ?? 'nhanvien'), ['hr', 'manager'], true)
        ? 'cham-cong-dashboard'
        : 'home';
}

$page = $_GET['page'] ?? $defaultPage;

// Login / Logout
if ($page === 'login' || $page === 'login-process' || $page === 'logout') {
    require_once 'app/controllers/LoginController.php';
    $controller = new LoginController();

    if ($page === 'login') {
        $controller->index();
        exit;
    }

    if ($page === 'login-process') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->handleLogin();
        } else {
            header('Location: index.php?page=login');
        }
        exit;
    }

    $controller->logout();
    exit;
}

// API menu theo vai tro
if ($page === 'get-menu-by-role') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user'])) {
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
        exit;
    }

    require_once 'app/controllers/HomeController.php';
    $homeController = new HomeController();
    $reflection = new ReflectionClass($homeController);
    $method = $reflection->getMethod('getMenuItemsByRole');
    $method->setAccessible(true);
    $menuItems = $method->invoke($homeController);

    echo json_encode([
        'success' => true,
        'menuItems' => $menuItems,
        'role' => $_SESSION['user']['vaiTro'] ?? 'Không xác định'
    ]);
    exit;
}

$allowedPages = [
    'home',
    // Nhân viên - Chấm công
    'cham-cong',
    'cham-cong-vao',
    'cham-cong-ra',
    'lich-su-cham-cong',
    'yeu-cau-chinh-sua-cham-cong',
    'attendance-panel',
    // Attendance API endpoints
    'attendance-check-in',
    'attendance-check-out',
    'attendance-validate-network',
    'attendance-today',
    'attendance-history',
    // HR
    'quan-ly-nhanvien',
    'quan-ly-ca-lam',
    'tinh-cong',
    'xuat-bao-cao',
    'gui-bang-cong-phe-duyet',
    'xuly-yeucau',
    'hr-api-employees',
    'hr-api-shifts',
    'hr-api-shift-assignments',
    'hr-api-payroll',
    'hr-api-payroll-submit',
    'hr-api-approval-detail',
    'hr-api-corrections',
    'hr-api-correction-action',
    // Manager
    'pheduyet-bang-cong',
    'bao-cao-tong-hop',
    'pheduyet-yeucau',
    'thong-ke-bieu-do',
    'chi-tiet-bang-cong',
    'manager-api-approvals',
    'manager-api-approval-detail',
    'manager-api-approval-history',
    'manager-api-approve',
    'manager-api-requests',
    'manager-api-request-action',
    // Tech
    'wifi',
    'cau-hinh-he-thong',
    'luu-cauhinh',
    'tech-wifi',
    'tech-add-wifi',
    'tech-update-wifi',
    'tech-toggle-wifi',
    'tech-delete-wifi',
    'tech-settings',
    'tech-update-setting',
    'tech-update-settings',
    // Đơn nghỉ phép
    'create-leave-request',
    'store-leave-request',
    'list-leave-requests',
    'approve-leave-request',
    // Legacy
    'cham-cong-dashboard',
    'hr-cham-cong',
    'quan-ly-cham-cong',
    'ky-thuat-cham-cong',
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

// Kiểm tra xác thực (không check login cho home và login pages)
if (!isset($_SESSION['user']) && !in_array($page, ['login', 'login-process', 'logout'])) {
    header('Location: index.php?page=login');
    exit;
}

// Kiểm tra quyền hạn nếu user đã login
if (isset($_SESSION['user']) && !in_array($page, ['home', 'logout'])) {
    if (!AuthMiddleware::hasPermissionForPage($page)) {
        header('Location: index.php?page=home');
        exit;
    }
}

switch ($page) {
    case 'home':
        require_once 'app/controllers/HomeController.php';
        (new HomeController())->index();
        break;

    // === CHẤM CÔNG ===
    case 'cham-cong':
    case 'cham-cong-dashboard':
        require_once 'app/controllers/ChamCongController.php';
        (new ChamCongController())->dashboard();
        break;

    case 'cham-cong-vao':
        require_once 'app/controllers/ChamCongController.php';
        (new ChamCongController())->chamCong('IN');
        break;

    case 'cham-cong-ra':
        require_once 'app/controllers/ChamCongController.php';
        (new ChamCongController())->chamCong('OUT');
        break;

    case 'lich-su-cham-cong':
        require_once 'app/controllers/ChamCongController.php';
        (new ChamCongController())->lichSu();
        break;

    case 'yeu-cau-chinh-sua-cham-cong':
        require_once 'app/controllers/ChamCongController.php';
        (new ChamCongController())->yeuCauChinhSua();
        break;

    case 'attendance-panel':
        require_once 'app/views/chamcong/attendance_panel.php';
        break;

    // === ATTENDANCE API ENDPOINTS ===
    case 'attendance-check-in':
        require_once 'app/controllers/ChamCongController.php';
        (new ChamCongController())->checkIn();
        break;

    case 'attendance-check-out':
        require_once 'app/controllers/ChamCongController.php';
        (new ChamCongController())->checkOut();
        break;

    case 'attendance-validate-network':
        require_once 'app/controllers/ChamCongController.php';
        (new ChamCongController())->validateNetwork();
        break;

    case 'attendance-today':
        require_once 'app/controllers/ChamCongController.php';
        (new ChamCongController())->getTodayAttendance();
        break;

    case 'attendance-history':
        require_once 'app/controllers/ChamCongController.php';
        (new ChamCongController())->getAttendanceHistory();
        break;

    // === HR PANEL ===
    case 'hr-cham-cong':
        require_once 'app/controllers/ChamCongController.php';
        (new ChamCongController())->hrPanel();
        break;

    case 'quan-ly-nhanvien':
        require_once 'app/controllers/HRController.php';
        (new HRController())->employees();
        break;

    case 'quan-ly-ca-lam':
        require_once 'app/controllers/HRController.php';
        (new HRController())->shifts();
        break;

    case 'tinh-cong':
        require_once 'app/controllers/HRController.php';
        (new HRController())->salary();
        break;

    case 'xuat-bao-cao':
        require_once 'app/controllers/HRController.php';
        (new HRController())->reports();
        break;

    case 'gui-bang-cong-phe-duyet':
        require_once 'app/controllers/HRController.php';
        (new HRController())->attendance();
        break;

    case 'xuly-yeucau':
        require_once 'app/controllers/HRController.php';
        (new HRController())->requestCenter();
        break;

    case 'hr-api-employees':
        require_once 'app/controllers/HRController.php';
        (new HRController())->employeesApi();
        break;

    case 'hr-api-shifts':
        require_once 'app/controllers/HRController.php';
        (new HRController())->shiftsApi();
        break;

    case 'hr-api-shift-assignments':
        require_once 'app/controllers/HRController.php';
        (new HRController())->shiftAssignmentsApi();
        break;

    case 'hr-api-payroll':
        require_once 'app/controllers/HRController.php';
        (new HRController())->payrollApi();
        break;

    case 'hr-api-payroll-submit':
        require_once 'app/controllers/HRController.php';
        (new HRController())->submitPayrollApi();
        break;

    case 'hr-api-approval-detail':
        require_once 'app/controllers/HRController.php';
        (new HRController())->approvalDetailApi();
        break;

    case 'hr-api-corrections':
        require_once 'app/controllers/HRController.php';
        (new HRController())->correctionsApi();
        break;

    case 'hr-api-correction-action':
        require_once 'app/controllers/HRController.php';
        (new HRController())->processCorrection();
        break;

    // === MANAGER PANEL ===
    case 'pheduyet-bang-cong':
        require_once 'app/controllers/ManagerController.php';
        (new ManagerController())->approvals();
        break;

    case 'quan-ly-cham-cong':
        require_once 'app/controllers/ChamCongController.php';
        (new ChamCongController())->quanLyPanel();
        break;

    case 'bao-cao-tong-hop':
        require_once 'app/controllers/ManagerController.php';
        (new ManagerController())->reports();
        break;

    case 'pheduyet-yeucau':
        require_once 'app/controllers/ManagerController.php';
        (new ManagerController())->requests();
        break;

    case 'thong-ke-bieu-do':
        require_once 'app/controllers/ManagerController.php';
        (new ManagerController())->statistics();
        break;

    case 'chi-tiet-bang-cong':
        require_once 'app/controllers/ManagerController.php';
        (new ManagerController())->attendanceDetails();
        break;

    case 'manager-api-approvals':
        require_once 'app/controllers/ManagerController.php';
        (new ManagerController())->approvalsApi();
        break;

    case 'manager-api-approval-detail':
        require_once 'app/controllers/ManagerController.php';
        (new ManagerController())->approvalDetailApi();
        break;

    case 'manager-api-approval-history':
        require_once 'app/controllers/ManagerController.php';
        (new ManagerController())->approvalHistoryApi();
        break;

    case 'manager-api-approve':
        require_once 'app/controllers/ManagerController.php';
        (new ManagerController())->processApprovalApi();
        break;

    case 'manager-api-requests':
        require_once 'app/controllers/ManagerController.php';
        (new ManagerController())->requestsApi();
        break;

    case 'manager-api-request-action':
        require_once 'app/controllers/ManagerController.php';
        (new ManagerController())->processRequestApi();
        break;

    // === TECH PANEL ===
    case 'tech-wifi':
        require_once 'app/controllers/TechController.php';
        (new TechController())->wifi();
        break;

    case 'tech-add-wifi':
        require_once 'app/controllers/TechController.php';
        (new TechController())->addWifi();
        break;

    case 'tech-update-wifi':
        require_once 'app/controllers/TechController.php';
        (new TechController())->updateWifi();
        break;

    case 'tech-toggle-wifi':
        require_once 'app/controllers/TechController.php';
        (new TechController())->toggleWifi();
        break;

    case 'tech-delete-wifi':
        require_once 'app/controllers/TechController.php';
        (new TechController())->deleteWifi();
        break;

    case 'tech-settings':
        require_once 'app/controllers/TechController.php';
        (new TechController())->settings();
        break;

    case 'tech-update-setting':
        require_once 'app/controllers/TechController.php';
        (new TechController())->updateSetting();
        break;

    case 'tech-update-settings':
        require_once 'app/controllers/TechController.php';
        (new TechController())->updateSettings();
        break;

    // Legacy tech routes
    case 'wifi':
        require_once 'app/views/chamcong/wifi.php';
        break;

    case 'cau-hinh-he-thong':
    case 'ky-thuat-cham-cong':
        require_once 'app/views/chamcong/cauhinh.php';
        break;

    case 'luu-cauhinh':
        // TODO: Implement logic lưu cấu hình
        header('Location: index.php?page=cau-hinh-he-thong');
        break;

    // === ĐƠN NGHỈ PHÉP ===
    case 'create-leave-request':
        require_once 'app/controllers/HRController.php';
        (new HRController())->createLeaveRequest();
        break;

    case 'store-leave-request':
        require_once 'app/controllers/HRController.php';
        (new HRController())->storeLeaveRequest();
        break;

    case 'list-leave-requests':
        require_once 'app/controllers/HRController.php';
        (new HRController())->listLeaveRequests();
        break;

    case 'approve-leave-request':
        require_once 'app/controllers/HRController.php';
        (new HRController())->approveLeaveRequest();
        break;

    default:
        require_once 'app/controllers/HomeController.php';
        (new HomeController())->index();
        break;
}

