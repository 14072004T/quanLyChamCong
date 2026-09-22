<?php
$roles = $_SESSION['roles'] ?? [$_SESSION['role'] ?? 'nhanvien'];
$currentPage = $_GET['page'] ?? 'home';

$menus = [
    'tech' => [
        ['page' => 'tech-accounts', 'label' => 'Quản lý Tài khoản', 'icon' => 'fa-users-cog'],
    ],
    'manager' => [
        ['page' => 'bao-cao-tong-hop', 'label' => 'Báo cáo tổng hợp', 'icon' => 'fa-file-lines'],
        ['page' => 'xuly-yeucau', 'label' => 'Quản lý điều chỉnh công', 'icon' => 'fa-clipboard-check'],
        ['page' => 'list-leave-requests', 'label' => 'Quản lý Đơn phép', 'icon' => 'fa-calendar-check'],
        ['page' => 'manager-ot-requests', 'label' => 'Duyệt đơn OT', 'icon' => 'fa-business-time'],
    ],
    'hr' => [
        ['page' => 'quan-ly-nhanvien', 'label' => 'Quản lý Nhân viên', 'icon' => 'fa-users'],
        ['page' => 'quan-ly-ca-lam', 'label' => 'Quản lý Ca làm việc', 'icon' => 'fa-business-time'],
        ['page' => 'tinh-cong', 'label' => 'Tính công & Báo cáo', 'icon' => 'fa-calculator'],
        ['page' => 'face-register', 'label' => 'Đăng ký khuôn mặt', 'icon' => 'fa-portrait'],
        ['page' => 'cham-cong-ho', 'label' => 'Chấm công hộ nhân viên', 'icon' => 'fa-fingerprint', 'badge' => 'Mới'],
        ['page' => 'cham-cong', 'label' => 'Máy quét chấm công', 'icon' => 'fa-tablet-screen-button'],
    ],
    'nhanvien' => [
        ['page' => 'lich-su-cham-cong', 'label' => 'Xem lịch sử', 'icon' => 'fa-clock-rotate-left'],
        ['page' => 'bang-cong-thang', 'label' => 'Bảng công tháng', 'icon' => 'fa-file-invoice'],
        ['page' => 'yeu-cau-chinh-sua-cham-cong', 'label' => 'Gửi yêu cầu chỉnh sửa', 'icon' => 'fa-pen-to-square'],
        ['page' => 'create-leave-request', 'label' => 'Đơn nghỉ phép', 'icon' => 'fa-calendar-check'],
        ['page' => 'create-ot-request', 'label' => 'Đăng ký OT', 'icon' => 'fa-business-time'],
    ],
];

$roleLabels = [
    'tech' => 'Bộ phận Kỹ thuật',
    'manager' => 'Quản lý / Ban lãnh đạo',
    'hr' => 'Bộ phận Nhân sự',
    'nhanvien' => 'Nhân viên',
];

$roleShortLabels = [
    'tech' => 'Kỹ thuật',
    'manager' => 'Quản lý',
    'hr' => 'HR',
    'nhanvien' => 'Nhân viên',
];

$roleMenus = [];
$displayRoleTitle = 'Menu';

if (AuthMiddleware::isPhone()) {
    // Mobile: nếu có chọn Nhân viên thì hiển thị role Nhân viên, ngược lại hiển thị role chính
    if (in_array('nhanvien', $roles, true)) {
        $roleMenus = $menus['nhanvien'];
        $displayRoleTitle = 'Nhân viên';
    } else {
        $primary = $roles[0] ?? 'nhanvien';
        $roleMenus = $menus[$primary] ?? $menus['nhanvien'];
        $displayRoleTitle = $roleShortLabels[$primary] ?? 'Menu';
    }
} elseif (AuthMiddleware::isTablet()) {
    // Tablet: Chỉ hiển thị role HR, Tech
    $tabletRoles = array_intersect(['tech', 'hr'], $roles);
    if (empty($tabletRoles)) $tabletRoles = array_intersect(['manager', 'nhanvien'], $roles);
    if (empty($tabletRoles)) $tabletRoles = ['nhanvien'];

    $addedPages = [];
    $firstGroup = true;
    foreach ($tabletRoles as $r) {
        $groupItems = [];
        foreach ($menus[$r] ?? [] as $m) {
            if (!in_array($m['page'], $addedPages, true)) {
                $groupItems[] = $m;
                $addedPages[] = $m['page'];
            }
        }
        if (!empty($groupItems)) {
            if (!$firstGroup) {
                $roleMenus[] = ['divider' => true, 'label' => $roleShortLabels[$r] ?? $r];
            } else {
                $firstGroup = false;
            }
            foreach ($groupItems as $gi) {
                $roleMenus[] = $gi;
            }
        }
    }
    $displayRoleTitle = implode(' & ', array_map(fn($r) => $roleShortLabels[$r] ?? $r, $tabletRoles));
} else {
    // Laptop / Desktop: Hiển thị đầy đủ tất cả role đã chọn, có ngăn cách rõ ràng
    $displayOrder = ['tech', 'manager', 'hr', 'nhanvien'];
    $activeRoles = array_intersect($displayOrder, $roles);
    if (empty($activeRoles)) $activeRoles = ['nhanvien'];

    $addedPages = [];
    $firstGroup = true;
    foreach ($activeRoles as $r) {
        $groupItems = [];
        foreach ($menus[$r] ?? [] as $m) {
            if (!in_array($m['page'], $addedPages, true)) {
                $groupItems[] = $m;
                $addedPages[] = $m['page'];
            }
        }
        if (!empty($groupItems)) {
            if (!$firstGroup) {
                $roleMenus[] = ['divider' => true, 'label' => $roleShortLabels[$r] ?? $r];
            } else {
                $firstGroup = false;
            }
            foreach ($groupItems as $gi) {
                $roleMenus[] = $gi;
            }
        }
    }
    $displayRoleTitle = implode(' | ', array_map(fn($r) => $roleShortLabels[$r] ?? $r, $activeRoles));
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
                    <?php if (!empty($menu['badge'])): ?>
                        <span class="badge-menu"><?= htmlspecialchars($menu['badge']) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</nav>
