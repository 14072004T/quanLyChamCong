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
        ['page' => 'list-leave-requests', 'label' => 'Quản lý Đơn phép', 'icon' => 'fa-calendar-check'],
    ],
    'manager' => [
        ['page' => 'pheduyet-yeucau', 'label' => 'Phê duyệt yêu cầu', 'icon' => 'fa-clipboard-list'],
        ['page' => 'bao-cao-tong-hop', 'label' => 'Báo cáo tổng hợp', 'icon' => 'fa-file-lines'],
    ],
    'tech' => [
        ['page' => 'tech-wifi', 'label' => 'Mạng & WiFi', 'icon' => 'fa-wifi'],
        ['page' => 'tech-settings', 'label' => 'Cấu hình Hệ thống', 'icon' => 'fa-server'],
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
    <div class="sidebar-top-section">
        <i class="fa-solid fa-fingerprint"></i>
        <span>CHẤM CÔNG</span>
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
