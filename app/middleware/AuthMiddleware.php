<?php

class AuthMiddleware
{
    // Map role code to permissions
    private static $permissions = [
        'nhanvien' => [
            'home',
            'cham-cong',
            'cham-cong-dashboard',
            'cham-cong-vao',
            'cham-cong-ra',
            'lich-su-cham-cong',
            'yeu-cau-chinh-sua-cham-cong',
            'store-edit-request',
            'bang-cong-thang',
            'nv-api-monthly-timesheet',
            'nv-api-approve-timesheet',
            'create-leave-request',
            'store-leave-request',
            'get-leave-detail',
            'get-correction-detail',
            'logout'
        ],
        'hr' => [
            'home',
            'cham-cong',
            'cham-cong-dashboard',
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
            'lich-su-cham-cong',
            'create-leave-request',
            'store-leave-request',
            'list-leave-requests',
            'approve-leave-request',
            'get-leave-detail',
            'get-correction-detail',
            'logout'
        ],
        'manager' => [
            'home',
            'cham-cong-dashboard',
            'pheduyet-yeucau',
            'quan-ly-cham-cong',
            'bao-cao-tong-hop',
            'thong-ke-bieu-do',
            'manager-api-requests',
            'manager-api-request-action',
            'create-leave-request',
            'store-leave-request',
            'list-leave-requests',
            'approve-leave-request',
            'get-leave-detail',
            'get-correction-detail',
            'logout'
        ],
        'tech' => [
            'home',
            'cham-cong-dashboard',
            'tech-wifi',
            'tech-get-wifi',
            'tech-add-wifi',
            'tech-update-wifi',
            'tech-toggle-wifi',
            'tech-delete-wifi',
            'tech-settings',
            'tech-update-setting',
            'tech-update-settings',
            'create-leave-request',
            'store-leave-request',
            'get-leave-detail',
            'get-correction-detail',
            'logout'
        ]
    ];

    /**
     * Kiểm tra user đã đăng nhập
     */
    public static function checkLogin()
    {
        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }
    }

    /**
     * Lấy danh sách quyền của role hiện tại
     * @return array
     */
    public static function getCurrentPermissions()
    {
        self::checkLogin();
        $role = $_SESSION['role'] ?? 'nhanvien';
        return self::$permissions[$role] ?? [];
    }

    /**
     * Kiểm tra xem user có quyền truy cập page không
     * @param string $page
     * @return bool
     */
    public static function hasPermissionForPage($page)
    {
        self::checkLogin();
        $role = $_SESSION['role'] ?? 'nhanvien';
        $permissions = self::$permissions[$role] ?? [];
        return in_array($page, $permissions);
    }

    /**
     * Kiểm tra user có role cụ thể không
     * @param string|array $requiredRoles
     * @return bool
     */
    public static function hasRole($requiredRoles)
    {
        self::checkLogin();
        $userRole = $_SESSION['role'] ?? 'nhanvien';
        if (is_array($requiredRoles)) {
            return in_array($userRole, $requiredRoles);
        }
        return $userRole === $requiredRoles;
    }

    /**
     * Redirect nếu không có quyền
     * @param string|array $requiredRoles
     */
    public static function requireRole($requiredRoles)
    {
        if (!self::hasRole($requiredRoles)) {
            header('Location: index.php?page=home');
            exit;
        }
    }

    /**
     * Get current user info
     * @return array|null
     */
    public static function getCurrentUser()
    {
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }
        return null;
    }

    /**
     * Get current role
     * @return string
     */
    public static function getCurrentRole()
    {
        return $_SESSION['role'] ?? 'nhanvien';
    }

    public static function checkPermission($page)
    {
        $permissions = self::getCurrentPermissions();
        return in_array($page, $permissions, true);
    }

    public static function requirePermission($page)
    {
        if (!self::checkPermission($page)) {
            header('Location: index.php?page=home');
            exit;
        }
    }
}

