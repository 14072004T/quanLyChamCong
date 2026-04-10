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
     * Add new network with IP range and gateway
     * POST: wifi_name, ip_range, gateway, description, is_active
     */
    public function addWifi()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $wifiName = trim($_POST['wifi_name'] ?? '');
        $ipRange = trim($_POST['ip_range'] ?? '');
        $gateway = trim($_POST['gateway'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isActive = (int)($_POST['is_active'] ?? 1);
        $errors = [];

        // Validate wifi_name
        if (empty($wifiName)) {
            $errors[] = 'Tên mạng (WiFi Name) không được để trống';
        } elseif (strlen($wifiName) > 120) {
            $errors[] = 'Tên mạng không được vượt quá 120 ký tự';
        }

        // Validate ip_range
        if (empty($ipRange)) {
            $errors[] = 'Dải IP (IP Range) không được để trống';
        } elseif (!$this->isValidIpRange($ipRange)) {
            $errors[] = 'Dải IP không hợp lệ (ví dụ: 192.168.1)';
        }

        // Validate gateway
        if (empty($gateway)) {
            $errors[] = 'Gateway không được để trống';
        } elseif (!filter_var($gateway, FILTER_VALIDATE_IP)) {
            $errors[] = 'Gateway IP không hợp lệ';
        }

        // Check duplicate wifi_name
        if (empty($errors) && $this->model->checkNetworkExists($wifiName)) {
            $errors[] = 'Tên mạng "' . htmlspecialchars($wifiName) . '" đã tồn tại';
        }

        // Check duplicate gateway
        if (empty($errors) && $this->model->checkGatewayExists($gateway)) {
            $errors[] = 'Gateway "' . htmlspecialchars($gateway) . '" đã được sử dụng';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        // Insert
        $result = $this->model->addNetwork($wifiName, $ipRange, $gateway, $description, $isActive);
        if ($result) {
            $_SESSION['success'] = 'Thêm mạng thành công';
            echo json_encode(['success' => true, 'message' => 'Thêm mạng thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm mạng']);
        }
        exit;
    }

    /**
     * Update network with IP range and gateway
     * POST: id, wifi_name, ip_range, gateway, description, is_active
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
        $wifiName = trim($_POST['wifi_name'] ?? '');
        $ipRange = trim($_POST['ip_range'] ?? '');
        $gateway = trim($_POST['gateway'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $isActive = (int)($_POST['is_active'] ?? 1);
        $errors = [];

        // Validate ID
        if ($wifiId <= 0) {
            $errors[] = 'ID mạng không hợp lệ';
        }

        // Validate wifi_name
        if (empty($wifiName)) {
            $errors[] = 'Tên mạng (WiFi Name) không được để trống';
        } elseif (strlen($wifiName) > 120) {
            $errors[] = 'Tên mạng không được vượt quá 120 ký tự';
        }

        // Validate ip_range
        if (empty($ipRange)) {
            $errors[] = 'Dải IP (IP Range) không được để trống';
        } elseif (!$this->isValidIpRange($ipRange)) {
            $errors[] = 'Dải IP không hợp lệ (ví dụ: 192.168.1)';
        }

        // Validate gateway
        if (empty($gateway)) {
            $errors[] = 'Gateway không được để trống';
        } elseif (!filter_var($gateway, FILTER_VALIDATE_IP)) {
            $errors[] = 'Gateway IP không hợp lệ';
        }

        // Check duplicate wifi_name (khác ID hiện tại)
        if (empty($errors) && $this->model->checkNetworkExists($wifiName, $wifiId)) {
            $errors[] = 'Tên mạng "' . htmlspecialchars($wifiName) . '" đã tồn tại';
        }

        // Check duplicate gateway (khác ID hiện tại)
        if (empty($errors) && $this->model->checkGatewayExists($gateway, $wifiId)) {
            $errors[] = 'Gateway "' . htmlspecialchars($gateway) . '" đã được sử dụng';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        // Update
        $result = $this->model->updateNetwork($wifiId, $wifiName, $ipRange, $gateway, $description, $isActive);
        if ($result) {
            $_SESSION['success'] = 'Cập nhật mạng thành công';
            echo json_encode(['success' => true, 'message' => 'Cập nhật mạng thành công']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật mạng']);
        }
        exit;
    }

    /**
     * Toggle network active status
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
     * POST: setting_key, setting_value
     */
    public function updateSettings()
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $settingKey = trim($_POST['setting_key'] ?? '');
        $settingValue = trim($_POST['setting_value'] ?? '');
        $errors = [];

        // Validate
        if (empty($settingKey)) {
            $errors[] = 'Tên cài đặt không được để trống';
        } elseif (strlen($settingKey) > 100) {
            $errors[] = 'Tên cài đặt không được vượt quá 100 ký tự';
        }

        if (empty($settingValue)) {
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
}

