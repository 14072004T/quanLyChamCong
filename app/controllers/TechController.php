<?php

require_once __DIR__ . '/../models/ChamCongModel.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class TechController
{
    private $model;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->model = new ChamCongModel();
        AuthMiddleware::requireRole(['tech']);
    }

    // ========== WIFI MANAGEMENT ==========

    /**
     * Display WiFi management page
     */
    public function wifiManagement()
    {
        $wifiList = $this->model->getAllNetworks() ?? [];
        $errorsJson = isset($_SESSION['errors']) ? json_encode($_SESSION['errors']) : '[]';
        $success = $_SESSION['success'] ?? null;
        unset($_SESSION['success'], $_SESSION['errors']);
        
        $view = 'app/views/chamcong/wifi_management.php';
        include __DIR__ . '/../views/chamcong/dashboard.php';
    }

    /**
     * Add new network with IP range and congMacDinh
     * POST: tenWifi, daiIP, congMacDinh, moTa, hoatDong
     */
    public function addWifi()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $wifiName = trim($_POST['tenWifi'] ?? '');
        $ipRange = trim($_POST['daiIP'] ?? '');
        $congMacDinh = trim($_POST['congMacDinh'] ?? '');
        $moTa = trim($_POST['moTa'] ?? '');
        $isActive = (int)($_POST['hoatDong'] ?? 1);
        $ssid = trim($_POST['ssid'] ?? '');
        $matKhau = ''; // Ignore matKhau as per requirement
        $viTri = trim($_POST['viTri'] ?? '');
        $errors = [];

        // Validate tenWifi
        if (empty($wifiName)) {
            $errors[] = 'Tên mạng (WiFi Name) không được để trống';
        } elseif (strlen($wifiName) > 120) {
            $errors[] = 'Tên mạng không được vượt quá 120 ký tự';
        }

        // Validate daiIP
        if (empty($ipRange)) {
            $errors[] = 'Dải IP (IP Range) không được để trống';
        } elseif (!$this->isValidIpRange($ipRange)) {
            $errors[] = 'Dải IP không hợp lệ (ví dụ: 192.168.1)';
        }

        // Validate congMacDinh
        if (empty($congMacDinh)) {
            $errors[] = 'Gateway không được để trống';
        } elseif (!filter_var($congMacDinh, FILTER_VALIDATE_IP)) {
            $errors[] = 'Gateway IP không hợp lệ';
        }

        // Check duplicate tenWifi
        if (empty($errors) && $this->model->checkNetworkExists($wifiName)) {
            $errors[] = 'Tên mạng "' . htmlspecialchars($wifiName) . '" đã tồn tại';
        }

        // Check duplicate congMacDinh
        if (empty($errors) && $this->model->checkGatewayExists($congMacDinh)) {
            $errors[] = 'Gateway "' . htmlspecialchars($congMacDinh) . '" đã được sử dụng';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        // Insert
        $result = $this->model->addNetwork($wifiName, $ipRange, $congMacDinh, $moTa, $isActive, $ssid, $matKhau, $viTri);
        if ($result) {
            $_SESSION['success'] = 'Thêm mạng thành công';
            echo json_encode(['success' => true, 'message' => 'Thêm mạng thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm mạng']);
        }
        exit;
    }

    /**
     * Get WiFi details by ID for editing (includes matKhau securely)
     */
    public function getWifiDetails()
    {
        header('Content-Type: application/json');
        
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }

        $network = $this->model->getNetworkById($id);
        if ($network) {
            echo json_encode(['success' => true, 'data' => $network]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Network not found']);
        }
        exit;
    }

    /**
     * Update network with IP range and congMacDinh
     * POST: id, tenWifi, daiIP, congMacDinh, moTa, hoatDong
     */
    public function updateWifi()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $wifiId = (int)($_POST['id'] ?? 0);
        $wifiName = trim($_POST['tenWifi'] ?? '');
        $ipRange = trim($_POST['daiIP'] ?? '');
        $congMacDinh = trim($_POST['congMacDinh'] ?? '');
        $moTa = trim($_POST['moTa'] ?? '');
        $isActive = (int)($_POST['hoatDong'] ?? 1);
        $ssid = trim($_POST['ssid'] ?? '');
        $viTri = trim($_POST['viTri'] ?? '');
        
        // Preserve existing matKhau as per requirement
        $existingNetwork = $this->model->getNetworkById($wifiId);
        $matKhau = $existingNetwork['matKhau'] ?? '';
        $errors = [];

        // Validate ID
        if ($wifiId <= 0) {
            $errors[] = 'ID mạng không hợp lệ';
        }

        // Validate tenWifi
        if (empty($wifiName)) {
            $errors[] = 'Tên mạng (WiFi Name) không được để trống';
        } elseif (strlen($wifiName) > 120) {
            $errors[] = 'Tên mạng không được vượt quá 120 ký tự';
        }

        // Validate daiIP
        if (empty($ipRange)) {
            $errors[] = 'Dải IP (IP Range) không được để trống';
        } elseif (!$this->isValidIpRange($ipRange)) {
            $errors[] = 'Dải IP không hợp lệ (ví dụ: 192.168.1)';
        }

        // Validate congMacDinh
        if (empty($congMacDinh)) {
            $errors[] = 'Gateway không được để trống';
        } elseif (!filter_var($congMacDinh, FILTER_VALIDATE_IP)) {
            $errors[] = 'Gateway IP không hợp lệ';
        }

        // Check duplicate tenWifi (khác ID hiện tại)
        if (empty($errors) && $this->model->checkNetworkExists($wifiName, $wifiId)) {
            $errors[] = 'Tên mạng "' . htmlspecialchars($wifiName) . '" đã tồn tại';
        }

        // Check duplicate congMacDinh (khác ID hiện tại)
        if (empty($errors) && $this->model->checkGatewayExists($congMacDinh, $wifiId)) {
            $errors[] = 'Gateway "' . htmlspecialchars($congMacDinh) . '" đã được sử dụng';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        // Update
        $result = $this->model->updateNetwork($wifiId, $wifiName, $ipRange, $congMacDinh, $moTa, $isActive, $ssid, $matKhau, $viTri);
        if ($result) {
            $_SESSION['success'] = 'Cập nhật mạng thành công';
            echo json_encode(['success' => true, 'message' => 'Cập nhật mạng thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật mạng']);
        }
        exit;
    }

    /**
     * Toggle network active trangThai
     * POST: id
     */
    public function toggleWifi()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $wifiId = (int)($_POST['id'] ?? 0);
        
        if ($wifiId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID mạng không hợp lệ']);
            exit;
        }

        $result = $this->model->toggleNetwork($wifiId);
        if ($result) {
            $_SESSION['success'] = 'Đã cập nhật trạng thái mạng';
            echo json_encode(['success' => true, 'message' => 'Đã cập nhật trạng thái mạng']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái mạng']);
        }
        exit;
    }

    /**
     * Delete network
     * POST: id
     */
    public function deleteWifi()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $wifiId = (int)($_POST['id'] ?? 0);
        
        if ($wifiId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID mạng không hợp lệ']);
            exit;
        }

        $result = $this->model->deleteNetwork($wifiId);
        if ($result) {
            $_SESSION['success'] = 'Xóa mạng thành công';
            echo json_encode(['success' => true, 'message' => 'Xóa mạng thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa mạng']);
        }
        exit;
    }

    // ========== SETTINGS MANAGEMENT ==========

    /**
     * Display settings management page
     */
    public function settingsManagement()
    {
        $settings = $this->model->getAllSettings() ?? [];
        $success = $_SESSION['success'] ?? null;
        $errorsJson = isset($_SESSION['errors']) ? json_encode($_SESSION['errors']) : '[]';
        unset($_SESSION['success'], $_SESSION['errors']);
        
        $view = 'app/views/chamcong/settings_management.php';
        include __DIR__ . '/../views/chamcong/dashboard.php';
    }

    /**
     * Update system settings
     * POST: tenCaiDat, giaTri
     */
    public function updateSettings()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $settingKey = trim($_POST['tenCaiDat'] ?? '');
        $settingValue = trim($_POST['giaTri'] ?? '');
        $errors = [];

        // Validate
        if (empty($settingKey)) {
            $errors[] = 'Tên cài đặt không được để trống';
        } elseif (strlen($settingKey) > 100) {
            $errors[] = 'Tên cài đặt không được vượt quá 100 ký tự';
        }

        if (!isset($_POST['giaTri']) || $settingValue === '') {
            $errors[] = 'Giá trị cài đặt không được để trống';
        }

        // Validate format
        if (empty($errors)) {
            $validationErrors = $this->validateSetting($settingKey, $settingValue);
            if (!empty($validationErrors)) {
                $errors = array_merge($errors, $validationErrors);
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        // Update
        $result = $this->model->updateSetting($settingKey, $settingValue);
        if ($result) {
            $_SESSION['success'] = 'Cập nhật cài đặt thành công';
            echo json_encode(['success' => true, 'message' => 'Cập nhật cài đặt thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật cài đặt']);
        }
        exit;
    }

    /**
     * Validate setting value format
     */
    private function validateSetting($key, $value)
    {
        $errors = [];

        switch ($key) {
            case 'ALLOW_QR_CHECKIN':
            case 'ALLOW_OFFLINE_CHECKIN':
                if (!in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no'])) {
                    $errors[] = 'Giá trị phải là true/false, 1/0 hoặc yes/no';
                }
                break;

            case 'MAX_CORRECTION_DAYS':
            case 'DEFAULT_WORK_MINUTES':
            case 'EARLY_CHECKIN_MINUTES':
            case 'LATE_CHECKOUT_MINUTES':
            case 'LATE_THRESHOLD_MINUTES':
            case 'OVERTIME_THRESHOLD_MINUTES':
                if (!is_numeric($value)) {
                    $errors[] = 'Giá trị phải là số';
                } elseif ((int)$value < 0) {
                    $errors[] = 'Giá trị phải lớn hơn hoặc bằng 0';
                } elseif ($key !== 'EARLY_CHECKIN_MINUTES' && $key !== 'LATE_CHECKOUT_MINUTES' && (int)$value === 0) {
                    $errors[] = 'Giá trị phải lớn hơn 0';
                }
                break;

            case 'TIMEZONE':
                if (strlen($value) > 50) {
                    $errors[] = 'Timezone không được vượt quá 50 ký tự';
                }
                break;

            default:
                if (strlen($value) > 255) {
                    $errors[] = 'Giá trị không được vượt quá 255 ký tự';
                }
        }

        return $errors;
    }

    // ========== VALIDATION HELPERS ==========

    /**
     * Validate IP range format (e.g., 192.168.1, 10.0.0)
     */
    private function isValidIpRange($ipRange)
    {
        // Remove leading/trailing spaces
        $ipRange = trim($ipRange);
        
        // Remove trailing dot if exists
        $ipRange = rtrim($ipRange, '.');
        
        // Split by dot
        $parts = explode('.', $ipRange);
        
        // Must have 1-3 parts
        if (count($parts) < 1 || count($parts) > 3) {
            return false;
        }
        
        // Each part must be a valid IP octet (0-255)
        foreach ($parts as $part) {
            if (!is_numeric($part) || (int)$part < 0 || (int)$part > 255) {
                return false;
            }
        }
        
        return true;
    }

    // ========== BACKWARD COMPATIBILITY ALIASES ==========

    /**
     * Alias for wifi() - backward compatibility with old routing
     */
    public function wifi()
    {
        $this->wifiManagement();
    }

    /**
     * Alias for settings() - backward compatibility with old routing
     */
    public function settings()
    {
        $this->settingsManagement();
    }

    /**
     * Alias for updateSetting() - handles singular form from routing
     */
    public function updateSetting()
    {
        $this->updateSettings();
    }

    // ========== ACCOUNT & ROLE MANAGEMENT ==========

    /**
     * Display Account & Role Management page
     */
    public function accountManagement()
    {
        $departments = $this->model->getAllDepartments() ?? [];
        $view = 'app/views/chamcong/account_management.php';
        include __DIR__ . '/../views/chamcong/dashboard.php';
    }

    /**
     * API: Get list of all accounts with filters & search
     */
    public function accountsApi()
    {
        header('Content-Type: application/json');

        $phongBan = trim($_GET['phongBan'] ?? '');
        $search = trim($_GET['search'] ?? '');
        $fromDate = trim($_GET['fromDate'] ?? '');
        $toDate = trim($_GET['toDate'] ?? '');

        $filters = [
            'phongBan' => $phongBan,
            'search' => $search,
            'fromDate' => $fromDate,
            'toDate' => $toDate
        ];

        $accounts = $this->model->getAllAccountsWithUsers($filters) ?? [];

        // Format system role for each account
        foreach ($accounts as &$acc) {
            $acc['role'] = $this->mapChucVuToRole($acc['chucVu'] ?? '');
        }

        echo json_encode([
            'success' => true,
            'data' => $accounts,
            'total' => count($accounts)
        ]);
        exit;
    }

    /**
     * API: Update role (chucVu) for a user
     * POST: maND, role ('hr' | 'tech' | 'manager' | 'nhanvien')
     */
    public function updateRole()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
            exit;
        }

        $maND = (int)($_POST['maND'] ?? 0);
        $role = trim($_POST['role'] ?? '');

        if ($maND <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã người dùng không hợp lệ']);
            exit;
        }

        $allowedRoles = ['hr', 'tech', 'manager', 'nhanvien'];
        if (!in_array($role, $allowedRoles, true)) {
            echo json_encode(['success' => false, 'message' => 'Quyền (role) không hợp lệ']);
            exit;
        }

        $chucVuMap = [
            'hr' => 'Bộ phận nhân sự',
            'tech' => 'Bộ phận kỹ thuật',
            'manager' => 'Quản lý / Ban lãnh đạo',
            'nhanvien' => 'Nhân viên'
        ];

        $chucVu = $chucVuMap[$role];

        $result = $this->model->updateUserRole($maND, $chucVu);
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật phân quyền thành công. User sẽ cần đăng nhập lại để áp dụng quyền mới.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật phân quyền']);
        }
        exit;
    }

    /**
     * API: Toggle account active status (taikhoan.trangThai)
     * POST: maTK
     */
    public function toggleAccount()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
            exit;
        }

        $maTK = (int)($_POST['maTK'] ?? 0);

        if ($maTK <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã tài khoản không hợp lệ']);
            exit;
        }

        $result = $this->model->toggleAccountStatus($maTK);
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái tài khoản thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái tài khoản']);
        }
        exit;
    }

    /**
     * Map chucVu to system role code
     */
    private function mapChucVuToRole($chucVu)
    {
        $text = mb_strtolower(trim((string)$chucVu), 'UTF-8');
        
        if (strpos($text, 'nhân sự') !== false || strpos($text, 'nhan su') !== false || strpos($text, 'hr') !== false) {
            return 'hr';
        }
        if (strpos($text, 'kỹ thuật') !== false || strpos($text, 'ky thuat') !== false || strpos($text, 'tech') !== false) {
            return 'tech';
        }
        if (strpos($text, 'quản lý') !== false || strpos($text, 'quan ly') !== false || strpos($text, 'lãnh đạo') !== false || strpos($text, 'lanh dao') !== false || strpos($text, 'manager') !== false) {
            return 'manager';
        }
        return 'nhanvien';
    }
}


