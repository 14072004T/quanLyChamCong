<?php
/**
 * API Admin Controller — RESTful endpoints cho Quản trị hệ thống (Bộ phận Kỹ thuật)
 * 
 * Nghiệp vụ:
 *  4.1 Cấu hình hệ thống — quản lý tham số hoạt động
 *  4.2 Thiết lập kết nối — WiFi công ty, xác thực chấm công
 */

class ApiAdminController
{
    private $model;

    public function __construct(ChamCongModel $model)
    {
        $this->model = $model;
    }

    public function handle($method, $action, $id, $subAction, $body)
    {
        requireRole('tech');

        switch ($action) {
            case 'wifi':
                $this->handleWifi($method, $id, $subAction, $body);
                break;
            case 'settings':
                $this->handleSettings($method, $id, $body);
                break;
            default:
                respondError('Admin endpoint not found: ' . $action, 404);
        }
    }

    // ========================================================
    // 4.2 THIẾT LẬP KẾT NỐI — WiFi công ty
    // GET     /admin/wifi              — Danh sách mạng
    // GET     /admin/wifi/{id}         — Chi tiết mạng
    // POST    /admin/wifi              — Thêm mạng mới
    // PUT     /admin/wifi/{id}         — Cập nhật mạng
    // DELETE  /admin/wifi/{id}         — Xóa mạng
    // PUT     /admin/wifi/{id}/toggle  — Bật/tắt mạng
    // ========================================================
    private function handleWifi($method, $id, $subAction, $body)
    {
        // PUT /admin/wifi/{id}/toggle — Bật/tắt mạng
        if ($method === 'PUT' && $subAction === 'toggle' && $id) {
            $ok = $this->model->toggleNetwork((int)$id);
            respond(
                ['success' => $ok, 'message' => $ok ? 'Đã cập nhật trạng thái mạng' : 'Lỗi cập nhật'],
                $ok ? 200 : 500
            );
            return;
        }

        switch ($method) {
            case 'GET':
                if ($id) {
                    $network = $this->model->getNetworkById((int)$id);
                    if (!$network) respondError('Không tìm thấy mạng', 404);
                    respond(['success' => true, 'data' => $network]);
                } else {
                    $networks = $this->model->getAllNetworks();
                    respond(['success' => true, 'data' => $networks, 'meta' => ['count' => count($networks)]]);
                }
                break;

            case 'POST':
                $payload = $_POST;
                if (empty($payload)) $payload = $body;

                $wifiName = trim($payload['wifi_name'] ?? '');
                $ipRange = trim($payload['ip_range'] ?? '');
                $gateway = trim($payload['gateway'] ?? '');
                $description = trim($payload['description'] ?? '');
                $isActive = (int)($payload['is_active'] ?? 1);
                $ssid = trim($payload['ssid'] ?? '');
                $location = trim($payload['location'] ?? '');

                // Validate
                $errors = $this->validateNetwork($wifiName, $ipRange, $gateway);
                if ($this->model->checkNetworkExists($wifiName)) {
                    $errors[] = 'Tên mạng "' . $wifiName . '" đã tồn tại';
                }
                if ($this->model->checkGatewayExists($gateway)) {
                    $errors[] = 'Gateway "' . $gateway . '" đã được sử dụng';
                }

                if (!empty($errors)) {
                    respond(['success' => false, 'errors' => $errors], 422);
                    return;
                }

                $ok = $this->model->addNetwork($wifiName, $ipRange, $gateway, $description, $isActive, $ssid, '', $location);
                respond(
                    ['success' => $ok, 'message' => $ok ? 'Thêm mạng thành công' : 'Lỗi thêm mạng'],
                    $ok ? 201 : 500
                );
                break;

            case 'PUT':
                if (!$id) respondError('Thiếu ID mạng', 422);

                $wifiName = trim($body['wifi_name'] ?? '');
                $ipRange = trim($body['ip_range'] ?? '');
                $gateway = trim($body['gateway'] ?? '');
                $description = trim($body['description'] ?? '');
                $isActive = (int)($body['is_active'] ?? 1);
                $ssid = trim($body['ssid'] ?? '');
                $location = trim($body['location'] ?? '');

                $errors = $this->validateNetwork($wifiName, $ipRange, $gateway);
                if ($this->model->checkNetworkExists($wifiName, (int)$id)) {
                    $errors[] = 'Tên mạng "' . $wifiName . '" đã tồn tại';
                }
                if ($this->model->checkGatewayExists($gateway, (int)$id)) {
                    $errors[] = 'Gateway "' . $gateway . '" đã được sử dụng';
                }

                if (!empty($errors)) {
                    respond(['success' => false, 'errors' => $errors], 422);
                    return;
                }

                // Preserve existing password
                $existing = $this->model->getNetworkById((int)$id);
                $password = $existing['password'] ?? '';

                $ok = $this->model->updateNetwork((int)$id, $wifiName, $ipRange, $gateway, $description, $isActive, $ssid, $password, $location);
                respond(
                    ['success' => $ok, 'message' => $ok ? 'Cập nhật mạng thành công' : 'Lỗi cập nhật'],
                    $ok ? 200 : 500
                );
                break;

            case 'DELETE':
                if (!$id) respondError('Thiếu ID mạng', 422);
                $ok = $this->model->deleteNetwork((int)$id);
                respond(
                    ['success' => $ok, 'message' => $ok ? 'Xóa mạng thành công' : 'Lỗi xóa mạng'],
                    $ok ? 200 : 500
                );
                break;

            default:
                respondError('Method not allowed', 405);
        }
    }

    // ========================================================
    // 4.1 CẤU HÌNH HỆ THỐNG
    // GET    /admin/settings           — Danh sách cài đặt
    // PUT    /admin/settings/{key}     — Cập nhật cài đặt
    // ========================================================
    private function handleSettings($method, $key, $body)
    {
        switch ($method) {
            case 'GET':
                $settings = $this->model->getAllSettings();
                respond(['success' => true, 'data' => $settings, 'meta' => ['count' => count($settings)]]);
                break;

            case 'PUT':
                if (!$key) respondError('Thiếu tên cài đặt (setting key)', 422);
                $value = $body['value'] ?? $body['setting_value'] ?? '';

                if ($value === '') respondError('Giá trị cài đặt không được để trống', 422);

                // Validate format
                $errors = $this->validateSetting($key, $value);
                if (!empty($errors)) {
                    respond(['success' => false, 'errors' => $errors], 422);
                    return;
                }

                $ok = $this->model->updateSetting($key, $value);
                respond(
                    ['success' => $ok, 'message' => $ok ? 'Cập nhật cài đặt thành công' : 'Lỗi cập nhật'],
                    $ok ? 200 : 500
                );
                break;

            default:
                respondError('Method not allowed', 405);
        }
    }

    // ========================================================
    // VALIDATION HELPERS
    // ========================================================

    private function validateNetwork($wifiName, $ipRange, $gateway)
    {
        $errors = [];
        if (empty($wifiName)) $errors[] = 'Tên mạng không được để trống';
        if (strlen($wifiName) > 120) $errors[] = 'Tên mạng không quá 120 ký tự';
        if (empty($ipRange)) $errors[] = 'Dải IP không được để trống';
        if (!$this->isValidIpRange($ipRange)) $errors[] = 'Dải IP không hợp lệ (VD: 192.168.1)';
        if (empty($gateway)) $errors[] = 'Gateway không được để trống';
        if (!filter_var($gateway, FILTER_VALIDATE_IP)) $errors[] = 'Gateway IP không hợp lệ';
        return $errors;
    }

    private function isValidIpRange($ipRange)
    {
        $ipRange = rtrim(trim($ipRange), '.');
        $parts = explode('.', $ipRange);
        if (count($parts) < 1 || count($parts) > 3) return false;
        foreach ($parts as $part) {
            if (!is_numeric($part) || (int)$part < 0 || (int)$part > 255) return false;
        }
        return true;
    }

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
                if (!is_numeric($value)) $errors[] = 'Giá trị phải là số';
                elseif ((int)$value < 0) $errors[] = 'Giá trị phải >= 0';
                break;
            case 'TIMEZONE':
                if (strlen($value) > 50) $errors[] = 'Timezone không quá 50 ký tự';
                break;
            default:
                if (strlen($value) > 255) $errors[] = 'Giá trị không quá 255 ký tự';
        }
        return $errors;
    }
}
