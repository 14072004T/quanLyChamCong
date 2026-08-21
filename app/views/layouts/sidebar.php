<?php
$role = $_SESSION['role'] ?? 'nhanvien';
$currentPage = $_GET['page'] ?? 'home';

$menus = [
    'nhanvien' => [
        ['page' => 'cham-cong-dashboard', 'label' => 'Chấm công', 'icon' => 'fa-fingerprint'],
        ['page' => 'lich-su-cham-cong', 'label' => 'Xem lịch sử', 'icon' => 'fa-clock-rotate-left'],
        ['page' => 'bang-cong-thang', 'label' => 'Bảng công tháng', 'icon' => 'fa-file-invoice'],
        ['page' => 'yeu-cau-chinh-sua-cham-cong', 'label' => 'Gửi yêu cầu chỉnh sửa', 'icon' => 'fa-pen-to-square'],
        ['page' => 'create-leave-request', 'label' => 'Đơn nghỉ phép', 'icon' => 'fa-calendar-check'],
    ],
    'hr' => [
        ['page' => 'quan-ly-nhanvien', 'label' => 'Quản lý Nhân viên', 'icon' => 'fa-users'],
        ['page' => 'quan-ly-ca-lam', 'label' => 'Quản lý Ca làm việc', 'icon' => 'fa-business-time'],
        ['page' => 'xuly-yeucau', 'label' => 'Xử lý Yêu cầu', 'icon' => 'fa-clipboard-check'],
        ['page' => 'tinh-cong', 'label' => 'Tính công & Báo cáo', 'icon' => 'fa-calculator'],
        ['page' => 'face-register', 'label' => 'Đăng ký khuôn mặt', 'icon' => 'fa-portrait'],
    ],
    'manager' => [
        ['page' => 'bao-cao-tong-hop', 'label' => 'Báo cáo tổng hợp', 'icon' => 'fa-file-lines'],
        ['page' => 'list-leave-requests', 'label' => 'Quản lý Đơn phép', 'icon' => 'fa-calendar-check'],
    ],
    'tech' => [
        ['page' => 'tech-wifi', 'label' => 'Mạng & WiFi', 'icon' => 'fa-wifi'],
        ['page' => 'tech-settings', 'label' => 'Cấu hình Hệ thống', 'icon' => 'fa-server'],
    ],
];

$roleMenus = $menus[$role] ?? $menus['nhanvien'];

// Check if face is registered to hide the menu
require_once 'app/models/FaceModel.php';
require_once 'app/models/ChamCongModel.php';
$faceModel = new FaceModel();
$chamCongModel = new ChamCongModel();
$maND = $_SESSION['user']['maND'] ?? null;
$hasFace = $faceModel->getFaceProfile($maND) !== null;
$isHR = ($role === 'hr');
$showFaceRegisterMenu = true;

if ($hasFace) {
    if (!$isHR) {
        $showFaceRegisterMenu = false;
    } else {
        $rawList = $chamCongModel->getEmployees('', true) ?? [];
        $hasUnregisteredEmp = false;
        foreach ($rawList as $emp) {
            if ($emp['maND'] != $maND && !$faceModel->getFaceProfile($emp['maND'])) {
                $hasUnregisteredEmp = true;
                break;
            }
        }
        if (!$hasUnregisteredEmp) {
            $showFaceRegisterMenu = false;
        }
    }
}

$filteredMenus = [];
foreach ($roleMenus as $menu) {
    if ($menu['page'] === 'face-register' && !$showFaceRegisterMenu) {
        continue;
    }
    $filteredMenus[] = $menu;
}

$roleLabels = [
    'nhanvien' => 'Nhân viên',
    'hr' => 'HR',
    'manager' => 'Quản lý',
    'tech' => 'Kỹ thuật',
];

$appVersion = 'v2.4.10';
?>

<nav class="sidebar-nav <?= AuthMiddleware::isMobile() ? 'mobile-sidebar-nav' : '' ?>">
    <div class="sidebar-top-section">
        <i class="fa-solid fa-fingerprint"></i>
        <span>CHẤM CÔNG</span>
        <button type="button" class="sidebar-close" onclick="window.toggleMobileMenu(false); return false;" aria-label="Đóng menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <h3><?= htmlspecialchars($roleLabels[$role] ?? 'Menu') ?></h3>
    <ul id="sidebarList">
        <?php foreach ($filteredMenus as $menu): ?>
            <li>
                <a href="index.php?page=<?= htmlspecialchars($menu['page']) ?>"
                         class="menu-item <?= ($menu['page'] === $currentPage) ? 'active' : '' ?>"
                         title="<?= htmlspecialchars($menu['label']) ?>">
                    <i class="fa-solid <?= htmlspecialchars($menu['icon']) ?>"></i>
                    <span><?= htmlspecialchars($menu['label']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="sidebar-version">
        <span>RFT</span>
        <strong><?= htmlspecialchars($appVersion) ?></strong>
    </div>
</nav>
