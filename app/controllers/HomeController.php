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

        if (($_SESSION['role'] ?? '') === 'manager') {
            header('Location: index.php?page=bao-cao-tong-hop');
            exit;
        }

        $isLoggedIn = true;
        $chamCongModel = new ChamCongModel();
        $thongKe = $chamCongModel->getThongKeTongQuan();
        
        $maND = $_SESSION['user']['maND'] ?? 0;
        
        require_once 'app/models/FaceModel.php';
        $faceModel = new FaceModel();
        $hasFaceRegistered = $faceModel->getFaceProfile($maND) !== null;
        
        $role = $_SESSION['role'] ?? 'nhanvien';
        $isHR = ($role === 'hr');
        $showFaceRegisterOption = false;
        
        if ($isHR) {
            $showFaceRegisterOption = true;
            if ($hasFaceRegistered) {
                $rawList = $chamCongModel->getEmployees('', true) ?? [];
                $hasUnregisteredEmp = false;
                foreach ($rawList as $emp) {
                    if ($emp['maND'] != $maND && !$faceModel->getFaceProfile($emp['maND'])) {
                        $hasUnregisteredEmp = true;
                        break;
                    }
                }
                if (!$hasUnregisteredEmp) {
                    $showFaceRegisterOption = false;
                }
            }
        }
        
        $todayLogs = $chamCongModel->getTodayLogs($maND) ?? [];
        $todayStatus = [
            'in' => null,
            'out' => null
        ];
        foreach ($todayLogs as $log) {
            if ($log['hanhDong'] === 'IN') $todayStatus['in'] = $log['ngayTao'];
            if ($log['hanhDong'] === 'OUT') $todayStatus['out'] = $log['ngayTao'];
        }

        require_once 'app/middleware/AuthMiddleware.php';
        $menuItems = $this->getMenuItemsByRole($showFaceRegisterOption);

        require_once 'app/views/home.php';
    }

    /**
     * ✅ Lấy danh sách menu items theo role của user
     */
    private function getMenuItemsByRole($showFaceRegisterOption = true)
    {
        $allCategories = [
            'Chức năng Quản trị' => [
                ['link' => 'quan-ly-nhanvien', 'icon' => 'fa-users', 'text' => 'Quản lý Nhân viên'],
                ['link' => 'quan-ly-ca-lam', 'icon' => 'fa-business-time', 'text' => 'Quản lý Ca làm việc'],
                ['link' => 'tinh-cong', 'icon' => 'fa-calculator', 'text' => 'Tính công & Báo cáo'],
                ['link' => 'face-register', 'icon' => 'fa-portrait', 'text' => 'Đăng ký khuôn mặt'],
                ['link' => 'bao-cao-tong-hop', 'icon' => 'fa-file-lines', 'text' => 'Báo cáo tổng hợp'],
                ['link' => 'xuly-yeucau', 'icon' => 'fa-clipboard-check', 'text' => 'Điều chỉnh công'],
                ['link' => 'tech-accounts', 'icon' => 'fa-users-cog', 'text' => 'Quản lý Tài khoản'],
                ['link' => 'tech-settings', 'icon' => 'fa-server', 'text' => 'Cấu hình Hệ thống'],
            ],
            'Chấm công cá nhân' => [
                ['link' => 'lich-su-cham-cong', 'icon' => 'fa-clock-rotate-left', 'text' => 'Lịch sử chấm công'],
                ['link' => 'bang-cong-thang', 'icon' => 'fa-file-invoice', 'text' => 'Bảng công tháng'],
                ['link' => 'yeu-cau-chinh-sua-cham-cong', 'icon' => 'fa-pen-to-square', 'text' => 'Yêu cầu chỉnh sửa'],
                ['link' => 'create-leave-request', 'icon' => 'fa-calendar-check', 'text' => 'Đơn nghỉ phép'],
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
                    if ($item['link'] === 'face-register' && !$showFaceRegisterOption) {
                        continue;
                    }
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
