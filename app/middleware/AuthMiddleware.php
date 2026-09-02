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
            'face-api-verify',
            'face-liveness-session',
            'logout'
        ],
        'hr' => [
            'home',
            'cham-cong',
            'cham-cong-dashboard',
            'quan-ly-nhanvien',
            'quan-ly-ca-lam',
            'tablet-cham-cong',
            'tablet-face-api-verify',
            'tinh-cong',
            'xuat-bao-cao',
            'gui-bang-cong-phe-duyet',
            'xuly-yeucau',
            'hr-api-employees',
            'hr-api-shifts',
            'hr-api-shift-assignments',
            'hr-api-payroll',
            'hr-api-payroll-submit',
            'hr-api-tablet-scans',
            'hr-api-approval-detail',
            'hr-api-timesheet-approval-details',
            'hr-api-corrections',
            'hr-api-correction-hanhDong',
            'lich-su-cham-cong',
            'yeu-cau-chinh-sua-cham-cong',
            'store-edit-request',
            'create-leave-request',
            'store-leave-request',
            'get-leave-detail',
            'get-correction-detail',
            'face-register',
            'face-api-register',
            'face-api-delete',
            'face-api-verify',
            'face-liveness-session',
            'logout'
        ],
        'manager' => [
            'home',
            'cham-cong-dashboard',
            'quan-ly-cham-cong',
            'bao-cao-tong-hop',
            'thong-ke-bieu-do',
            'lich-su-cham-cong',
            'yeu-cau-chinh-sua-cham-cong',
            'store-edit-request',
            'create-leave-request',
            'store-leave-request',
            'list-leave-requests',
            'approve-leave-request',
            'get-leave-detail',
            'get-correction-detail',
            'face-api-verify',
            'face-liveness-session',
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
            'lich-su-cham-cong',
            'yeu-cau-chinh-sua-cham-cong',
            'store-edit-request',
            'create-leave-request',
            'store-leave-request',
            'get-leave-detail',
            'get-correction-detail',
            'face-api-verify',
            'face-liveness-session',
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

    /**
     * Detect if request is from mobile or if device type is overridden via query string.
     */
    public static function isMobile()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_GET['device'])) {
            if ($_GET['device'] === 'mobile') {
                $_SESSION['device_override'] = 'mobile';
            } elseif ($_GET['device'] === 'desktop') {
                $_SESSION['device_override'] = 'desktop';
            }
        }

        if (isset($_SESSION['device_override'])) {
            return $_SESSION['device_override'] === 'mobile';
        }

        // iPadOS Safari thường gửi User-Agent giống desktop; dựa vào cookie do JS phía client
        // ghi nhận (kích thước màn hình + cảm ứng) để dựng đúng layout mobile cho các trang sau.
        if (($_COOKIE['device_hint'] ?? '') === 'mobile') {
            return true;
        }

        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isMobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(ad|hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent) 
            || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|co|am|ut)|semc|snd\/|shg\-|shk\-|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($userAgent, 0, 4));

        return $isMobile;
    }
}

