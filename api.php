<?php
/**
 * RESTful API Router — Hệ Thống Quản Lý Chấm Công
 * 
 * Base URL: /quanlychamcong/api.php
 * Format:   /api.php/{resource}/{action}/{id}
 * 
 * Nghiệp vụ:
 *  1. HR setup lịch → NV chấm công WiFi → NV gửi yêu cầu chỉnh sửa → HR duyệt → HR tổng hợp → Manager duyệt bảng công
 *  2. NV tạo đơn nghỉ phép → Manager duyệt → cập nhật HR + lịch làm việc
 *  3. Tech thiết lập WiFi + hệ thống → NV chấm công
 *  4. HR ghi nhận giờ công → tổng hợp ngày công, OT → bảng chấm công theo phòng ban → gửi Manager
 *  5. Manager xem báo cáo tổng hợp phòng ban
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // JSON API should not output HTML errors
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CORS headers for API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// === Parse request ===
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Remove query string
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove base path (e.g., /quanlychamcong/api.php)
$basePath = dirname($scriptName);
$path = substr($path, strlen($basePath));
$path = str_replace('/api.php', '', $path);
$path = trim($path, '/');

// Split path into segments
$segments = $path !== '' ? explode('/', $path) : [];
$method = $_SERVER['REQUEST_METHOD'];

// Parse PUT/DELETE body
$inputBody = [];
if (in_array($method, ['PUT', 'DELETE'])) {
    $raw = file_get_contents('php://input');
    $inputBody = json_decode($raw, true) ?? [];
    // Also try form-urlencoded
    if (empty($inputBody)) {
        parse_str($raw, $inputBody);
    }
}

// === Helper functions ===
function respond($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function respondError($message, $statusCode = 400) {
    respond(['success' => false, 'message' => $message], $statusCode);
}

function requireAuth() {
    if (!isset($_SESSION['user'])) {
        respondError('Chưa đăng nhập. Vui lòng đăng nhập trước.', 401);
    }
    return $_SESSION['user'];
}

function requireRole($allowedRoles) {
    $user = requireAuth();
    $role = $_SESSION['role'] ?? 'nhanvien';
    if (!in_array($role, (array)$allowedRoles)) {
        respondError('Bạn không có quyền truy cập chức năng này.', 403);
    }
    return $user;
}

function getSegment($segments, $index, $default = null) {
    return $segments[$index] ?? $default;
}

// === Routing ===
$resource = getSegment($segments, 0, '');

require_once __DIR__ . '/app/models/ChamCongModel.php';

switch ($resource) {
    // ========== HR ENDPOINTS ==========
    case 'hr':
        require_once __DIR__ . '/app/controllers/ApiHRController.php';
        $controller = new ApiHRController(new ChamCongModel());
        $action = getSegment($segments, 1, '');
        $id = getSegment($segments, 2);
        $subAction = getSegment($segments, 3);
        $controller->handle($method, $action, $id, $subAction, $inputBody);
        break;

    // ========== MANAGER ENDPOINTS ==========
    case 'manager':
        require_once __DIR__ . '/app/controllers/ApiManagerController.php';
        $controller = new ApiManagerController(new ChamCongModel());
        $action = getSegment($segments, 1, '');
        $id = getSegment($segments, 2);
        $subAction = getSegment($segments, 3);
        $controller->handle($method, $action, $id, $subAction, $inputBody);
        break;

    // ========== EMPLOYEE/ATTENDANCE ENDPOINTS ==========
    case 'attendance':
    case 'leaves':
        require_once __DIR__ . '/app/controllers/ApiEmployeeController.php';
        $controller = new ApiEmployeeController(new ChamCongModel());
        $action = getSegment($segments, 1, '');
        $id = getSegment($segments, 2);
        $controller->handle($method, $resource, $action, $id, $inputBody);
        break;

    // ========== ADMIN/TECH ENDPOINTS ==========
    case 'admin':
        require_once __DIR__ . '/app/controllers/ApiAdminController.php';
        $controller = new ApiAdminController(new ChamCongModel());
        $action = getSegment($segments, 1, '');
        $id = getSegment($segments, 2);
        $subAction = getSegment($segments, 3);
        $controller->handle($method, $action, $id, $subAction, $inputBody);
        break;

    // ========== AUTH ENDPOINTS ==========
    case 'auth':
        $action = getSegment($segments, 1, '');
        handleAuth($method, $action);
        break;

    default:
        respond([
            'success' => true,
            'message' => 'RESTful API — Hệ Thống Quản Lý Chấm Công v1.0',
            'endpoints' => [
                'POST /auth/login' => 'Đăng nhập',
                'POST /auth/logout' => 'Đăng xuất',
                'GET  /auth/me' => 'Thông tin user hiện tại',
                '/hr/*' => 'HR: Quản lý NV, ca làm, yêu cầu, tính công, nghỉ phép, báo cáo',
                '/manager/*' => 'Manager: Phê duyệt bảng công, yêu cầu, báo cáo phòng ban',
                '/attendance/*' => 'Nhân viên: Chấm công, lịch sử, yêu cầu chỉnh sửa',
                '/leaves/*' => 'Nhân viên: Đơn nghỉ phép',
                '/admin/*' => 'Tech: Cấu hình WiFi, hệ thống',
            ]
        ]);
        break;
}

// === Auth handler ===
function handleAuth($method, $action) {
    switch ($action) {
        case 'login':
            if ($method !== 'POST') respondError('Method not allowed', 405);

            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($username) || empty($password)) {
                respondError('Vui lòng nhập tên đăng nhập và mật khẩu', 422);
            }

            require_once __DIR__ . '/app/models/ketNoi.php';
            $db = new KetNoi();
            $conn = $db->connect();

            $sql = "SELECT tk.*, nd.hoTen, nd.chucVu, nd.phongBan, nd.maND, nd.trangThai as trangThaiND, tk.trangThai as trangThaiTK
                    FROM taikhoan tk 
                    LEFT JOIN nguoidung nd ON tk.maTK = nd.maTK 
                    WHERE tk.tenDangNhap = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                respondError('Sai tên đăng nhập hoặc mật khẩu', 401);
            }

            $user = $result->fetch_assoc();
            if (md5($password) !== $user['matKhau']) {
                respondError('Sai tên đăng nhập hoặc mật khẩu', 401);
            }

            if (($user['trangThaiTK'] ?? '') !== 'Hoạt động' || ($user['trangThaiND'] ?? 1) != 1) {
                respondError('Tài khoản đã bị ngưng hoạt động', 403);
            }

            $roleMapping = [
                'Nhân viên' => 'nhanvien',
                'Bộ phận Nhân sự' => 'hr',
                'Quản lý / Ban lãnh đạo' => 'manager',
                'Bộ phận Kỹ thuật' => 'tech'
            ];
            $chucVu = $user['chucVu'] ?? 'Nhân viên';
            $role = $roleMapping[$chucVu] ?? 'nhanvien';

            $_SESSION['user'] = [
                'maTK' => $user['maTK'],
                'maND' => $user['maND'] ?? null,
                'tenDangNhap' => $user['tenDangNhap'],
                'hoTen' => $user['hoTen'] ?? '',
                'chucVu' => $chucVu,
                'phongBan' => $user['phongBan'] ?? ''
            ];
            $_SESSION['role'] = $role;

            respond([
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'data' => [
                    'maND' => $user['maND'],
                    'hoTen' => $user['hoTen'],
                    'chucVu' => $chucVu,
                    'phongBan' => $user['phongBan'] ?? '',
                    'role' => $role
                ]
            ]);
            break;

        case 'logout':
            if ($method !== 'POST') respondError('Method not allowed', 405);
            session_unset();
            session_destroy();
            respond(['success' => true, 'message' => 'Đăng xuất thành công']);
            break;

        case 'me':
            if ($method !== 'GET') respondError('Method not allowed', 405);
            $user = requireAuth();
            respond([
                'success' => true,
                'data' => [
                    'maND' => $user['maND'],
                    'hoTen' => $user['hoTen'],
                    'chucVu' => $user['chucVu'],
                    'phongBan' => $user['phongBan'] ?? '',
                    'role' => $_SESSION['role'] ?? 'nhanvien'
                ]
            ]);
            break;

        default:
            respondError('Auth endpoint not found', 404);
    }
}
