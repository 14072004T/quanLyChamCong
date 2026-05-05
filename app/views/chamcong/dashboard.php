<?php 
// Kiểm tra xác thực
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}



// Khởi tạo biến mặc định (tránh undefined variable)
$role = $_SESSION['role'] ?? 'nhanvien';
$trangThaiHomNay = $trangThaiHomNay ?? null;
$hasWifi = $hasWifi ?? false;
$stats = $stats ?? [];
$history = $history ?? [];
$success = $success ?? null;
$error = $error ?? null;
$view = $view ?? null;

// Kiểm tra quyền hạn
$allowedRoles = ['nhanvien', 'hr', 'manager', 'tech'];
if (!in_array($role, $allowedRoles)) {
    header('Location: index.php?page=home');
    exit();
}

// Determine which view to include (default: nhanvien dashboard)
if (!isset($view) || is_null($view)) {
    if ($role === 'hr') {
        $view = 'app/views/chamcong/hr_panel.php';
    } elseif ($role === 'manager') {
        $view = 'app/views/chamcong/manager_panel.php';
    } elseif ($role === 'tech') {
        // Tech views should be set by TechController
        $view = 'app/views/chamcong/tech_panel.php';
    } else {
        // Default nhanvien view (inline below)
        $view = null;
    }
}
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    
    <div class="dashboard-container">
        <?php if ($view && file_exists($view)): ?>
            <!-- Include role-specific view -->
            <?php include $view; ?>
        <?php else: ?>
            <!-- ========== NHÂN VIÊN DASHBOARD (NEW MO            <!-- ========== NHÂN VIÊN DASHBOARD (REFINED COMPACT UI) ========== -->
            <style>
                .emp-dashboard { max-width: 1000px; margin: 0 auto; padding: 10px; font-family: 'Inter', sans-serif; }
                .emp-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
                .emp-stat-card { background: white; border-radius: 10px; padding: 16px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; align-items: center; text-align: center; transition: all 0.2s; }
                .emp-stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
                .emp-stat-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 8px; }
                .emp-stat-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
                .emp-stat-val { font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 2px; }
                
                .bg-blue { background: #eff6ff; color: #3b82f6; }
                .bg-red { background: #fef2f2; color: #ef4444; }
                .bg-green { background: #f0fdf4; color: #10b981; }
                .bg-purple { background: #faf5ff; color: #a855f7; }

                .emp-main-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; }
                .emp-card { background: white; border-radius: 10px; padding: 20px; border: 1px solid #e2e8f0; }
                
                .status-container { text-align: center; padding: 10px 0; }
                .status-badge-big { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 13px; margin-bottom: 16px; }
                .status-in { background: #dcfce7; color: #15803d; }
                .status-out { background: #fee2e2; color: #b91c1c; }
                .status-none { background: #f1f5f9; color: #475569; }

                .clock-big { font-size: 42px; font-weight: 700; color: #1e293b; letter-spacing: -1px; margin: 0; }
                .date-sub { font-size: 14px; color: #64748b; margin-bottom: 24px; }
                
                .btn-action-mid { display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 12px 32px; border-radius: 8px; font-size: 15px; font-weight: 600; text-decoration: none; transition: all 0.2s; border: none; cursor: pointer; }
                .btn-in { background: #10b981; color: white; box-shadow: 0 4px 6px -1px rgba(16,185,129,0.2); }
                .btn-in:hover { background: #059669; transform: scale(1.02); }
                .btn-out { background: #ef4444; color: white; box-shadow: 0 4px 6px -1px rgba(239,68,68,0.2); }
                .btn-out:hover { background: #dc2626; transform: scale(1.02); }

                .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
                .detail-row:last-child { border-bottom: none; }
                .detail-label { color: #64748b; font-weight: 500; }
                .detail-val { color: #1e293b; font-weight: 600; }

                @media (max-width: 768px) {
                    .emp-stats-grid { grid-template-columns: repeat(2, 1fr); }
                    .emp-main-grid { grid-template-columns: 1fr; }
                }
            </style>

            <div class="emp-dashboard">
                <!-- Thông báo -->
                <?php if ($success): ?><div class="alert alert-success" style="margin-bottom: 16px; border-radius: 8px;"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-error" style="margin-bottom: 16px; border-radius: 8px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <!-- 4 Thống kê nhỏ -->
                <div class="emp-stats-grid">
                    <?php $eStats = $stats['employee'] ?? []; ?>
                    <div class="emp-stat-card">
                        <div class="emp-stat-icon bg-blue"><i class="fas fa-calendar-check"></i></div>
                        <span class="emp-stat-label">Ngày công</span>
                        <span class="emp-stat-val"><?= number_format($eStats['work_days'] ?? 0, 1) ?></span>
                    </div>
                    <div class="emp-stat-card">
                        <div class="emp-stat-icon bg-red"><i class="fas fa-clock"></i></div>
                        <span class="emp-stat-label">Đi trễ</span>
                        <span class="emp-stat-val"><?= $eStats['late_times'] ?? 0 ?></span>
                    </div>
                    <div class="emp-stat-card">
                        <div class="emp-stat-icon bg-green"><i class="fas fa-bolt"></i></div>
                        <span class="emp-stat-label">Tăng ca</span>
                        <span class="emp-stat-val"><?= number_format($eStats['ot_hours'] ?? 0, 1) ?>h</span>
                    </div>
                    <div class="emp-stat-card">
                        <div class="emp-stat-icon bg-purple"><i class="fas fa-umbrella-beach"></i></div>
                        <span class="emp-stat-label">Nghỉ phép</span>
                        <span class="emp-stat-val"><?= number_format($eStats['leave_days'] ?? 0, 1) ?></span>
                    </div>
                </div>

                <div class="emp-main-grid">
                    <!-- Khu vực chấm công -->
                    <div class="emp-card">
                        <div class="status-container">
                            <?php if ($trangThaiHomNay === 'IN'): ?>
                                <span class="status-badge-big status-in"><i class="fas fa-check-circle"></i> Đang trong ca làm việc</span>
                            <?php elseif ($trangThaiHomNay === 'OUT'): ?>
                                <span class="status-badge-big status-none"><i class="fas fa-flag-checkered"></i> Đã hoàn thành công việc</span>
                            <?php else: ?>
                                <span class="status-badge-big status-out"><i class="fas fa-exclamation-circle"></i> Chưa chấm công</span>
                            <?php endif; ?>

                            <h1 class="clock-big" id="realtime-clock">--:--:--</h1>
                            <p class="date-sub"><?= date('l, d F Y') ?></p>

                            <div style="margin-top: 10px;">
                                <?php if ($trangThaiHomNay === null || $trangThaiHomNay === 'OUT'): ?>
                                    <a href="index.php?page=cham-cong-vao" class="btn-action-mid btn-in" <?= ($trangThaiHomNay === 'OUT') ? 'style="opacity:0.5; pointer-events:none;"' : '' ?>>
                                        <i class="fas fa-fingerprint"></i> CHẤM CÔNG VÀO
                                    </a>
                                <?php else: ?>
                                    <a href="index.php?page=cham-cong-ra" class="btn-action-mid btn-out">
                                        <i class="fas fa-sign-out-alt"></i> CHẤM CÔNG RA
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Chi tiết trong ngày -->
                    <div class="emp-card">
                        <h3 style="margin-top: 0; font-size: 15px; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">Chi tiết hôm nay</h3>
                        <?php 
                            $inTime = '--:--'; $outTime = '--:--';
                            foreach ($history ?? [] as $h) {
                                if (substr($h['created_at'], 0, 10) === date('Y-m-d')) {
                                    if ($h['action'] === 'IN') $inTime = substr($h['created_at'], 11, 5);
                                    if ($h['action'] === 'OUT') $outTime = substr($h['created_at'], 11, 5);
                                }
                            }
                        ?>
                        <div class="detail-row">
                            <span class="detail-label">Giờ bắt đầu</span>
                            <span class="detail-val"><?= $inTime ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Giờ kết thúc</span>
                            <span class="detail-val"><?= $outTime ?></span>
                        </div>
                        <div class="detail-row" style="margin-top: 8px;">
                            <span class="detail-label">Ca làm việc</span>
                            <span class="detail-val" style="color: #3b82f6;"><?= htmlspecialchars($todayShiftStatus['shift']['shift_name'] ?? 'Chưa gán') ?></span>
                        </div>
                        
                        <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 8px;">
                            <a href="index.php?page=yeu-cau-chinh-sua-cham-cong" style="font-size: 13px; color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-edit"></i> Quên chấm công? Gửi yêu cầu
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function updateClock() {
                    const now = new Date();
                    document.getElementById('realtime-clock').textContent = now.toLocaleTimeString('en-GB');
                }
                setInterval(updateClock, 1000);
                updateClock();
            </script>
        <?php endif; // End of view check ?>
    </div>
</div>

<?php include 'app/views/layouts/footer.php'; ?>
