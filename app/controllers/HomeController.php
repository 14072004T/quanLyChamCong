<?php
require_once 'app/models/ChamCongModel.php';

class HomeController
{
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $isLoggedIn = true;
        $chamCongModel = new ChamCongModel();
        $thongKe = $chamCongModel->getThongKeTongQuan();
        
        $maND = $_SESSION['user']['maND'] ?? 0;
        $todayLogs = $chamCongModel->getTodayLogs($maND) ?? [];
        $todayStatus = [
            'in' => null,
            'out' => null
        ];
        foreach ($todayLogs as $log) {
            if ($log['action'] === 'IN') $todayStatus['in'] = $log['created_at'];
            if ($log['action'] === 'OUT') $todayStatus['out'] = $log['created_at'];
        }

        require_once 'app/middleware/AuthMiddleware.php';
        $menuItems = $this->getMenuItemsByRole();

        require_once 'app/views/home.php';
    }

    /**
     * ✅ Lấy danh sách menu items theo role của user
     */
    private function getMenuItemsByRole()
    {
        // Định nghĩa tất cả chức năng theo danh mục
        $allCategories = [
            'Chấm công nhân viên' => [
                ['link' => 'cham-cong-dashboard', 'icon' => 'fa-fingerprint', 'text' => 'Dashboard chấm công'],
                ['link' => 'lich-su-cham-cong', 'icon' => 'fa-clock-rotate-left', 'text' => 'Lịch sử chấm công'],
                ['link' => 'yeu-cau-chinh-sua-cham-cong', 'icon' => 'fa-pen-to-square', 'text' => 'Yêu cầu chỉnh sửa'],
            ],
            'Điều hành chấm công' => [
                ['link' => 'hr-cham-cong', 'icon' => 'fa-users', 'text' => 'Điều hành HR'],
                ['link' => 'quan-ly-cham-cong', 'icon' => 'fa-circle-check', 'text' => 'Báo cáo quản lý'],
                ['link' => 'ky-thuat-cham-cong', 'icon' => 'fa-wifi', 'text' => 'Cấu hình kỹ thuật'],
            ],
            'Tài khoản' => [
                ['link' => 'cham-cong-dashboard', 'icon' => 'fa-house', 'text' => 'Trang chấm công'],
            ],
        ];

        // Lấy danh sách quyền của role hiện tại
        $permissions = AuthMiddleware::getCurrentPermissions();

        // Lọc các chức năng theo quyền
        $menuByCategory = [];
        foreach ($allCategories as $categoryName => $items) {
            $filteredItems = [];
            foreach ($items as $item) {
                if (in_array($item['link'], $permissions)) {
                    $filteredItems[] = $item;
                }
            }
            if (!empty($filteredItems)) {
                $menuByCategory[$categoryName] = $filteredItems;
            }
        }

        return $menuByCategory;
    }
}
