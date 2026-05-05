<?php
$role = $_SESSION['role'] ?? 'nhanvien';
$canViewStats = ($role === 'hr' || $role === 'manager');
$isLoggedIn = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? [];
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>

<style>
    /* User Profile Header (Matching Screenshot) */
    .user-profile-header {
        background: white; border-radius: 16px; padding: 25px 30px; margin-bottom: 25px;
        border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .user-info-main { display: flex; align-items: center; gap: 20px; }
    .user-avatar-circle {
        width: 54px; height: 54px; border-radius: 50%; background: #eff6ff; color: #3b82f6;
        display: flex; align-items: center; justify-content: center; font-size: 20px; border: 1px solid #dbeafe;
    }
    .user-meta h2 { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; }
    .user-meta p { font-size: 13px; color: #64748b; margin: 0; display: flex; align-items: center; gap: 8px; }
    
    .role-badge { 
        padding: 5px 12px; border-radius: 6px; font-size: 10px; font-weight: 800; 
        text-transform: uppercase; letter-spacing: 0.5px;
        background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7;
        display: flex; align-items: center; gap: 5px;
    }

    /* Full Width Layout Overrides (Pinned Sidebar) */
    .main-layout-wrapper {
        max-width: 100% !important;
        width: 100% !important;
        margin: var(--content-top) 0 0 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        display: flex !important;
    }

    .main-content {
        flex: 1;
        padding: 25px !important;
        background: #f8fafc !important;
    }

    /* Sidebar Overrides (to match screenshot) */
    .sidebar-nav {
        background: #0f172a !important; padding: 0 !important;
    }
    .sidebar-top-section {
        background: #1e293b; padding: 18px 20px; display: flex; align-items: center; gap: 12px;
        color: #ffffff !important; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;
    }
    .sidebar-top-section span { color: #ffffff !important; }
    .sidebar-top-section i { font-size: 18px; color: #60a5fa; }
    .sidebar-top-section a { color: white; text-decoration: none; }
    
    .sidebar-nav h3 {
        padding: 20px 20px 10px 20px !important; color: #475569 !important; font-size: 11px !important;
        text-transform: uppercase !important; letter-spacing: 1.5px !important; margin: 0 !important;
    }
    .sidebar-nav ul { padding: 0 10px !important; }
    .sidebar-nav ul li a {
        padding: 12px 15px !important; margin-bottom: 4px !important; border-radius: 10px !important;
        font-size: 14px !important; transition: 0.2s !important;
    }
    .sidebar-nav ul li a.active {
        background: rgba(59, 130, 246, 0.15) !important; color: #60a5fa !important; font-weight: 600 !important;
    }
    .sidebar-nav ul li a:hover:not(.active) {
        background: rgba(255, 255, 255, 0.05) !important;
    }

    /* Modern Clickable Card */
    .m-card {
        display: block !important; text-decoration: none !important; color: inherit !important;
        background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0;
        padding: 24px; transition: all 0.3s; cursor: pointer !important;
        position: relative !important; z-index: 10;
    }
    .m-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); border-color: #3b82f6; }
    .m-card:active { transform: scale(0.98); }

    .stat-label { font-size: 12px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; }
    .stat-value { font-size: 28px; font-weight: 800; color: #1e293b; }
    
    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px; }

    @media (max-width: 1024px) { .grid-4 { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 640px) { .grid-4, .grid-2 { grid-template-columns: 1fr; .user-profile-header { flex-direction: column; align-items: flex-start; gap: 15px; } } }
</style>

<div class="main-layout-wrapper">
    <?php include 'app/views/layouts/sidebar.php'; ?>

    <div class="main-content">
        
        <!-- TOP USER INFO BAR -->
        <div class="user-profile-header">
            <div class="user-info-main">
                <div class="user-avatar-circle">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-meta">
                    <h2><?= htmlspecialchars($user['hoTen'] ?? 'Hội viên') ?></h2>
                    <p><i class="fas fa-building"></i> <?= htmlspecialchars($user['phongBan'] ?? 'Sản xuất') ?> • ID: #<?= htmlspecialchars($user['maND'] ?? '1') ?></p>
                </div>
            </div>
            <div>
                <span class="role-badge">
                    <i class="fas fa-shield-halved"></i> VAI TRÒ: <?= strtoupper($role) ?>
                </span>
            </div>
        </div>
        

        <?php if ($role === 'nhanvien'): ?>
            <!-- ========================================== -->
            <!-- NHÂN VIÊN                                  -->
            <!-- ========================================== -->
            <?php
            $maND = $_SESSION['user']['maND'] ?? 0;
            $history = $chamCongModel->getLichSu($maND, date('Y-m-01'), date('Y-m-d')) ?? [];
            $workedDays = count(array_unique(array_map(fn($h) => date('Y-m-d', strtotime($h['created_at'])), $history)));
            $lateCount = 0;
            foreach($history as $h) {
                if($h['action'] === 'IN' && date('H:i:s', strtotime($h['created_at'])) > '08:15:00') $lateCount++;
            }
            $inTime = $todayStatus['in'] ? date('H:i', strtotime($todayStatus['in'])) : null;
            ?>

            <div class="grid-4">
                <a href="index.php?page=cham-cong-dashboard" class="m-card" style="background: #3b82f6; color: white !important; border: none;">
                    <div style="font-size: 24px; margin-bottom: 10px;"><i class="fas fa-fingerprint"></i></div>
                    <div style="font-size: 18px; font-weight: 800;">Chấm công ngay</div>
                    <div style="font-size: 13px; opacity: 0.8;">Vào ca hoặc Ra ca</div>
                </a>
                <a href="index.php?page=lich-su-cham-cong" class="m-card">
                    <div class="stat-label">Ngày công (Tháng <?= date('m') ?>)</div>
                    <div class="stat-value"><?= $workedDays ?></div>
                </a>
                <a href="index.php?page=lich-su-cham-cong" class="m-card">
                    <div class="stat-label">Số lần đi muộn</div>
                    <div class="stat-value" style="color: #ef4444;"><?= $lateCount ?></div>
                </a>
                <a href="index.php?page=yeu-cau-chinh-sua-cham-cong" class="m-card">
                    <div class="stat-label">Yêu cầu chờ duyệt</div>
                    <div class="stat-value">0</div>
                </a>
            </div>

        <?php elseif ($role === 'tech'): ?>
            <!-- ========================================== -->
            <!-- KỸ THUẬT                                   -->
            <!-- ========================================== -->
            <div class="grid-4">
                <div class="m-card" style="cursor: default;">
                    <div class="stat-label">System Uptime</div>
                    <div class="stat-value" style="color: #16a34a;">99.9%</div>
                </div>
                <div class="m-card" style="cursor: default;">
                    <div class="stat-label">Active Users</div>
                    <div class="stat-value"><?= (int)($thongKe['total_users'] ?? 0) ?></div>
                </div>
                <div class="m-card" style="cursor: default;">
                    <div class="stat-label">Response Time</div>
                    <div class="stat-value" style="color: #2563eb;">12ms</div>
                </div>
                <div class="m-card" style="cursor: default;">
                    <div class="stat-label">Logs Today</div>
                    <div class="stat-value"><?= (int)($thongKe['total_logs_today'] ?? 0) ?></div>
                </div>
            </div>

            <div class="grid-2">
                <a href="index.php?page=tech-wifi" class="m-card">
                    <div style="width: 50px; height: 50px; border-radius: 12px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 20px;">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 8px;">Quản lý WiFi & MAC</h3>
                    <p style="font-size: 14px; color: #64748b; margin: 0;">Thiết lập SSID và danh sách địa chỉ MAC được phép chấm công.</p>
                </a>
                <a href="index.php?page=tech-settings" class="m-card">
                    <div style="width: 50px; height: 50px; border-radius: 12px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 20px;">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 8px;">Cấu hình Hệ thống</h3>
                    <p style="font-size: 14px; color: #64748b; margin: 0;">Điều chỉnh tham số vận hành, quy tắc chấm công và bảo mật lõi.</p>
                </a>
            </div>

        <?php else: ?>
            <!-- ========================================== -->
            <!-- QUẢN LÝ / HR                               -->
            <!-- ========================================== -->
            <div class="grid-4">
                <a href="index.php?page=hr-cham-cong" class="m-card">
                    <div class="stat-label">Tổng lượt Logs</div>
                    <div class="stat-value"><?= (int)($thongKe['total_logs_today'] ?? 0) ?></div>
                </a>
                <a href="index.php?page=hr-cham-cong" class="m-card">
                    <div class="stat-label">Lượt Check-in</div>
                    <div class="stat-value" style="color: #16a34a;"><?= (int)($thongKe['in_today'] ?? 0) ?></div>
                </a>
                <a href="index.php?page=hr-cham-cong" class="m-card">
                    <div class="stat-label">Lượt Check-out</div>
                    <div class="stat-value" style="color: #2563eb;"><?= (int)($thongKe['out_today'] ?? 0) ?></div>
                </a>
                <a href="index.php?page=hr-cham-cong" class="m-card" style="border-color: #fca5a5;">
                    <div class="stat-label" style="color: #ef4444;">Yêu cầu chờ duyệt</div>
                    <div class="stat-value" style="color: #ef4444;"><?= (int)($thongKe['pending_requests'] ?? 0) ?></div>
                </a>
            </div>

            <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 20px; color: #1e293b;">Công cụ điều hành</h3>
            <div class="grid-4">
                <?php if (!empty($menuItems)): ?>
                    <?php foreach ($menuItems as $cat => $items): ?>
                        <?php foreach ($items as $item): ?>
                            <a href="index.php?page=<?= htmlspecialchars($item['link']) ?>" class="m-card" style="text-align: center;">
                                <i class="<?= htmlspecialchars($item['icon']) ?>" style="font-size: 22px; color: #3b82f6; margin-bottom: 12px; display: block;"></i>
                                <span style="font-size: 14px; font-weight: 700;"><?= htmlspecialchars($item['text']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'app/views/layouts/footer.php'; ?>