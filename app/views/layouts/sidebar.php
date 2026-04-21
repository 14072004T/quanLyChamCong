<?php
$role = $_SESSION['role'] ?? 'nhanvien';
$currentPage = $_GET['page'] ?? 'home';

$menus = [
    'nhanvien' => [
        ['page' => 'cham-cong-dashboard', 'label' => 'Chấm công vào', 'icon' => 'fa-fingerprint'],
        ['page' => 'cham-cong-ra', 'label' => 'Chấm công ra', 'icon' => 'fa-right-from-bracket'],
        ['page' => 'lich-su-cham-cong', 'label' => 'Xem lịch sử', 'icon' => 'fa-clock-rotate-left'],
        ['page' => 'yeu-cau-chinh-sua-cham-cong', 'label' => 'Gửi yêu cầu chỉnh sửa', 'icon' => 'fa-pen-to-square'],
    ],
    'hr' => [
        ['page' => 'quan-ly-nhanvien', 'label' => 'Quản lý Nhân viên', 'icon' => 'fa-users'],
        ['page' => 'quan-ly-ca-lam', 'label' => 'Quản lý Ca làm việc', 'icon' => 'fa-business-time'],
        ['page' => 'xuly-yeucau', 'label' => 'Xử lý Yêu cầu', 'icon' => 'fa-clipboard-check'],
        ['page' => 'tinh-cong', 'label' => 'Tính công & Báo cáo', 'icon' => 'fa-calculator'],
    ],
    'manager' => [
        ['page' => 'pheduyet-bang-cong', 'label' => 'Phê duyệt Bảng công', 'icon' => 'fa-circle-check'],
        ['page' => 'thong-ke-bieu-do', 'label' => 'Thống kê & Biểu đồ', 'icon' => 'fa-chart-pie'],
        ['page' => 'bao-cao-tong-hop', 'label' => 'Báo cáo tổng hợp', 'icon' => 'fa-file-lines'],
    ],
    'tech' => [
        ['page' => 'cham-cong-dashboard', 'label' => 'Trang chính', 'icon' => 'fa-house'],
        ['page' => 'tech-wifi', 'label' => 'Quản lý WiFi', 'icon' => 'fa-wifi'],
        ['page' => 'tech-settings', 'label' => 'Cài đặt Hệ thống', 'icon' => 'fa-sliders'],
    ],
];

$roleMenus = $menus[$role] ?? $menus['nhanvien'];

$roleLabels = [
    'nhanvien' => 'Nhân viên',
    'hr' => 'HR',
    'manager' => 'Quản lý',
    'tech' => 'Kỹ thuật',
];
?>

<nav class="sidebar-nav">
    <?php
    $attendanceTabLabel = ($role === 'nhanvien') ? 'Chấm công' : 'Quản lý chấm công';
    $attendanceTabIcon = ($role === 'nhanvien') ? 'fa-fingerprint' : 'fa-users-gear';
    ?>
    <div class="sidebar-top-section">
        <i class="fa-solid <?= htmlspecialchars($attendanceTabIcon) ?>"></i>
        <a href="index.php?page=cham-cong-dashboard">
            <?= htmlspecialchars($attendanceTabLabel) ?>
        </a>
    </div>

    <h3><?= htmlspecialchars($roleLabels[$role] ?? 'Menu') ?></h3>
    <ul id="sidebarList">
        <?php foreach ($roleMenus as $menu): ?>
            <li>
                <a href="index.php?page=<?= htmlspecialchars($menu['page']) ?>"
                   class="menu-item <?= ($menu['page'] === $currentPage) ? 'active' : '' ?>">
                    <i class="fa-solid <?= htmlspecialchars($menu['icon']) ?>"></i>
                    <span><?= htmlspecialchars($menu['label']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
