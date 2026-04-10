<?php
$role = $_SESSION['role'] ?? 'nhanvien';
$canViewStats = ($role === 'hr' || $role === 'manager');
$isLoggedIn = isset($_SESSION['user']);
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>

<div class="main-container">

    <?php include 'app/views/layouts/sidebar.php'; ?>

    <div class="dashboard-container home-modern">
        <section class="home-hero panel">
            <div class="home-hero-content">
                <p class="home-kicker">TRUNG TÂM ĐIỀU HÀNH CHẤM CÔNG</p>
                <h1>Xin chào, <?= htmlspecialchars($_SESSION['user']['hoTen'] ?? '') ?></h1>
                <p class="home-subtitle">
                    Theo dõi trạng thái chấm công, xử lý tác vụ theo vai trò và truy cập nhanh các chức năng bạn dùng hàng ngày.
                </p>
                <div class="home-chip-row">
                    <span class="home-chip"><i class="fas fa-user-tag"></i> Vai trò: <?= htmlspecialchars(strtoupper($role)) ?></span>
                    <span class="home-chip"><i class="fas fa-building"></i> Phòng ban: <?= htmlspecialchars($_SESSION['user']['phongBan'] ?? 'Chưa cập nhật') ?></span>
                </div>
            </div>
            <div class="home-hero-art" aria-hidden="true">
                <div class="orb orb-a"></div>
                <div class="orb orb-b"></div>
                <div class="orb orb-c"></div>
            </div>
        </section>

        <section class="dashboard-stats home-stats">
            <div class="card stat-card">
                <h3>Lượt chấm công hôm nay</h3>
                <p><?= (int)($thongKe['total_logs_today'] ?? 0) ?></p>
            </div>
            <div class="card stat-card">
                <h3>Check-in hôm nay</h3>
                <p><?= (int)($thongKe['in_today'] ?? 0) ?></p>
            </div>
            <div class="card stat-card">
                <h3>Check-out hôm nay</h3>
                <p><?= (int)($thongKe['out_today'] ?? 0) ?></p>
            </div>
            <div class="card stat-card">
                <h3>Yêu cầu chờ duyệt</h3>
                <p><?= (int)($thongKe['pending_requests'] ?? 0) ?></p>
            </div>
        </section>

        <section class="panel home-actions">
            <div class="home-actions-head">
                <h2>Chức năng theo vai trò</h2>
                <p>Nhấn vào một chức năng để thao tác nhanh.</p>
            </div>

            <div class="function-categories">
                <?php if (!empty($menuItems)): ?>
                    <?php foreach ($menuItems as $categoryName => $items): ?>
                        <div class="function-category">
                            <h3 class="category-title"><?= htmlspecialchars($categoryName) ?></h3>
                            <div class="function-grid">
                                <?php foreach ($items as $item): ?>
                                    <a class="function-item" href="index.php?page=<?= htmlspecialchars($item['link']) ?>">
                                        <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>
                                        <span><?= htmlspecialchars($item['text']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-circle-info"></i>
                        Chưa có chức năng phù hợp với vai trò hiện tại.
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!$canViewStats): ?>
            <div class="alert alert-warning">
                <i class="fas fa-lock"></i>
                Bạn có thể sử dụng chức năng chấm công và theo dõi dữ liệu cá nhân. Thống kê tổng hợp được mở cho HR và Quản lý.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'app/views/layouts/footer.php'; ?>