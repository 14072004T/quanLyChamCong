<?php
$role = $_SESSION['role'] ?? 'nhanvien';
$currentPage = $_GET['page'] ?? 'home';

$menus = [
    'nhanvien' => [
        ['page' => 'lich-su-cham-cong', 'label' => 'Xem lịch sử', 'icon' => 'fa-clock-rotate-left'],
        ['page' => 'bang-cong-thang', 'label' => 'Bảng công tháng', 'icon' => 'fa-file-invoice'],
        ['page' => 'yeu-cau-chinh-sua-cham-cong', 'label' => 'Gửi yêu cầu chỉnh sửa', 'icon' => 'fa-pen-to-square'],
        ['page' => 'create-leave-request', 'label' => 'Đơn nghỉ phép', 'icon' => 'fa-calendar-check'],
    ],
    'hr' => [
        ['page' => 'quan-ly-nhanvien', 'label' => 'Quản lý Nhân viên', 'icon' => 'fa-users'],
        ['page' => 'quan-ly-ca-lam', 'label' => 'Quản lý Ca làm việc', 'icon' => 'fa-business-time'],
        ['page' => 'tinh-cong', 'label' => 'Tính công & Báo cáo', 'icon' => 'fa-calculator'],
        ['page' => 'face-register', 'label' => 'Đăng ký khuôn mặt', 'icon' => 'fa-portrait'],
        ['divider' => true, 'label' => 'Cá nhân'],
    ],
    'manager' => [
        ['page' => 'bao-cao-tong-hop', 'label' => 'Báo cáo tổng hợp', 'icon' => 'fa-file-lines'],
        ['page' => 'xuly-yeucau', 'label' => 'Quản lý điều chỉnh công', 'icon' => 'fa-clipboard-check'],
        ['page' => 'list-leave-requests', 'label' => 'Quản lý Đơn phép', 'icon' => 'fa-calendar-check'],
    ],
    'tech' => [
        ['page' => 'tech-accounts', 'label' => 'Quản lý Tài khoản', 'icon' => 'fa-users-cog'],
        ['page' => 'tech-settings', 'label' => 'Cấu hình Hệ thống', 'icon' => 'fa-server'],
        ['divider' => true, 'label' => 'Cá nhân'],
    ],

];

$roleLabels = [
    'nhanvien' => 'Nhân viên',
    'hr' => 'HR',
    'manager' => 'Quản lý',
    'tech' => 'Kỹ thuật',
];

if (AuthMiddleware::isPhone()) {
    // Điện thoại: Tất cả chỉ hiển thị chức năng nhân viên cá nhân
    $roleMenus = $menus['nhanvien'];
    $displayRoleTitle = 'Nhân viên';
} elseif (AuthMiddleware::isTablet()) {
    // Tablet: Chỉ hiển thị các chức năng của role HR/Tech (bỏ divider Cá nhân & không gộp menu nhân viên)
    $rawMenus = $menus[$role] ?? $menus['nhanvien'];
    $roleMenus = array_values(array_filter($rawMenus, fn($m) => empty($m['divider'])));
    $displayRoleTitle = $roleLabels[$role] ?? 'Menu';
} else {
    // Desktop: Giữ nguyên (gộp menu role + menu nhân viên cho HR & Tech)
    if ($role === 'hr' || $role === 'tech') {
        $roleMenus = array_merge($menus[$role] ?? [], $menus['nhanvien']);
    } else {
        $roleMenus = $menus[$role] ?? $menus['nhanvien'];
    }
    $displayRoleTitle = $roleLabels[$role] ?? 'Menu';
}


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
    if (!$isHR || AuthMiddleware::isMobile()) {
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
    if (empty($menu['divider']) && ($menu['page'] === 'face-register') && !$showFaceRegisterMenu) {
        continue;
    }
    $filteredMenus[] = $menu;
}

?>

<nav class="sidebar-nav <?= AuthMiddleware::isMobile() ? 'mobile-sidebar-nav' : '' ?>">
    <div class="sidebar-top-section">
        <i class="fa-solid fa-fingerprint"></i>
        <span>CHẤM CÔNG</span>
        <button type="button" class="sidebar-close" onclick="window.toggleMobileMenu(false); return false;" aria-label="Đóng menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <h3><?= htmlspecialchars($displayRoleTitle) ?></h3>
    <ul id="sidebarList">
        <?php foreach ($filteredMenus as $menu): ?>
            <?php if (!empty($menu['divider'])): ?>
                <li class="sidebar-divider">
                    <span><?= htmlspecialchars($menu['label']) ?></span>
                </li>
            <?php else: ?>
            <li>
                <a href="index.php?page=<?= htmlspecialchars($menu['page']) ?>"
                         class="menu-item <?= ($menu['page'] === $currentPage) ? 'active' : '' ?>"
                         title="<?= htmlspecialchars($menu['label']) ?>">
                    <i class="fa-solid <?= htmlspecialchars($menu['icon']) ?>"></i>
                    <span><?= htmlspecialchars($menu['label']) ?></span>
                </a>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</nav>
