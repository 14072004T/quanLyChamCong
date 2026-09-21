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
        // roles field already included as array from model

        echo json_encode([
            'success' => true,
            'data' => $accounts,
            'total' => count($accounts)
        ]);
        exit;
    }

    /**
     * API: Cập nhật nhiều roles cho user
     * POST: maND, roles[] (array: 'hr' | 'tech' | 'manager' | 'nhanvien')
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
        $roles = $_POST['roles'] ?? [];

        if ($maND <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã người dùng không hợp lệ']);
            exit;
        }

        // Accept both array and single string
        if (!is_array($roles)) {
            $roles = !empty($roles) ? [$roles] : [];
        }

        $allowedRoles = ['hr', 'tech', 'manager', 'nhanvien'];
        $roles = array_values(array_unique(array_filter($roles, fn($r) => in_array($r, $allowedRoles, true))));

        if (empty($roles)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn ít nhất một quyền']);
            exit;
        }

        $result = $this->model->updateUserRoles($maND, $roles);
        if ($result) {
            $this->model->activateAccountByUserId($maND);
            echo json_encode([
                'success' => true,
                'message' => 'Kích hoạt & cập nhật phân quyền thành công. User sẽ cần đăng nhập lại để áp dụng quyền mới.'
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
        $action = trim($_POST['action'] ?? '');

        if ($maTK <= 0) {
            echo json_encode(['success' => false, 'message' => 'Mã tài khoản không hợp lệ']);
            exit;
        }

        // Nếu có action cụ thể thì xử lý theo action, không thì toggle
        if ($action === 'activate' || $action === 'unlock') {
            $result = $this->model->setAccountStatus($maTK, '1');
            $msg = $action === 'activate' ? 'Kích hoạt tài khoản thành công' : 'Mở khóa tài khoản thành công';
        } elseif ($action === 'lock') {
            $result = $this->model->setAccountStatus($maTK, '0');
            $msg = 'Khóa tài khoản thành công';
        } else {
            $result = $this->model->toggleAccountStatus($maTK);
            $msg = 'Cập nhật trạng thái tài khoản thành công';
        }

        if ($result) {
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật trạng thái tài khoản']);
        }
        exit;
    }

    /**
     * Map chucVu to system role code (legacy compatibility)
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
