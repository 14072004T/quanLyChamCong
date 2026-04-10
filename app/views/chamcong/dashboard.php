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
            <!-- ========== NHÂN VIÊN DASHBOARD ========== -->
            
            <!-- Tiêu đề và mô tả -->
            <div class="panel">
                <h2><i class="fas fa-fingerprint" style="color:#3b82f6"></i> Chấm công hôm nay</h2>
                <p>Chấm công vào/ra qua mạng nội bộ hoặc mã QR dự phòng.</p>
            </div>

            <!-- Thông báo thành công -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <strong><?= htmlspecialchars($success) ?></strong>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Thông báo lỗi -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong><?= htmlspecialchars($error) ?></strong>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Cảnh báo WiFi -->
            <?php if (!$hasWifi): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-wifi"></i>
                    <div>
                        <strong>Không phát hiện WiFi nội bộ.</strong>
                        <p>Vui lòng kết nối WiFi hoặc sử dụng mã QR để chấm công.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Thống kê và Card Stats -->
            <div class="dashboard-stats">
                <div class="card">
                    <h3>Lượt chấm công hôm nay</h3>
                    <p><?= (int)($stats['total_logs_today'] ?? 0) ?></p>
                </div>
                <div class="card">
                    <h3>Vào ca hôm nay</h3>
                    <p><?= (int)($stats['in_today'] ?? 0) ?></p>
                </div>
                <div class="card">
                    <h3>Ra ca hôm nay</h3>
                    <p><?= (int)($stats['out_today'] ?? 0) ?></p>
                </div>
                <div class="card">
                    <h3>Yêu cầu chờ HR</h3>
                    <p><?= (int)($stats['pending_requests'] ?? 0) ?></p>
                </div>
            </div>

            <!-- Khu vực chấm công - Nút chính -->
            <div class="panel">
                <h3><i class="fas fa-clock" style="color:#3b82f6"></i> Thao tác chấm công</h3>
                
                <div class="btn-group">
                    <!-- Nút Chấm công vào -->
                    <?php if ($trangThaiHomNay === null || $trangThaiHomNay === 'OUT'): ?>
                        <!-- Chưa vào hoặc đã ra → cho phép chấm vào -->
                        <a href="index.php?page=cham-cong-vao" class="btn btn-success">
                            <i class="fas fa-sign-in-alt"></i> Chấm công vào
                        </a>
                    <?php else: ?>
                        <!-- Đã vào → disable nút chấm vào -->
                        <button class="btn btn-success btn-disabled" disabled>
                            <i class="fas fa-sign-in-alt"></i> Chấm công vào
                        </button>
                    <?php endif; ?>

                    <!-- Nút Chấm công ra -->
                    <?php if ($trangThaiHomNay === 'IN'): ?>
                        <!-- Đã vào → cho phép chấm ra -->
                        <a href="index.php?page=cham-cong-ra" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Chấm công ra
                        </a>
                    <?php else: ?>
                        <!-- Chưa vào hoặc đã ra → disable nút chấm ra -->
                        <button class="btn btn-danger btn-disabled" disabled>
                            <i class="fas fa-sign-out-alt"></i> Chấm công ra
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Trạng thái -->
                <div style="margin-top: 18px; padding: 14px 18px; border-radius: 10px; display:flex; align-items:center; gap:10px;
                    <?php if ($trangThaiHomNay === 'IN'): ?>
                        background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d;
                    <?php elseif ($trangThaiHomNay === 'OUT'): ?>
                        background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d;
                    <?php else: ?>
                        background:#fffbeb; border:1px solid #fde68a; color:#b45309;
                    <?php endif; ?>
                ">
                    <?php if ($trangThaiHomNay === 'IN'): ?>
                        <i class="fas fa-circle-check" style="font-size:1.2em;flex-shrink:0"></i>
                        <span style="font-weight:600;font-size:.9em">Bạn đã chấm vào. Vui lòng chấm ra khi kết thúc ca làm.</span>
                    <?php elseif ($trangThaiHomNay === 'OUT'): ?>
                        <i class="fas fa-circle-check" style="font-size:1.2em;flex-shrink:0"></i>
                        <span style="font-weight:600;font-size:.9em">Bạn đã hoàn thành chấm công hôm nay.</span>
                    <?php else: ?>
                        <i class="fas fa-triangle-exclamation" style="font-size:1.2em;flex-shrink:0"></i>
                        <span style="font-weight:600;font-size:.9em">Chưa chấm công vào. Vui lòng chấm vào ngay.</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lịch sử gần đây -->
            <div class="panel">
                <h3><i class="fas fa-list-check" style="color:#3b82f6"></i> Lịch sử chấm công gần đây</h3>
                
                <?php if (!empty($history) && is_array($history)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Giờ vào</th>
                                <th>Giờ ra</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($history, 0, 5) as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($row['created_at'] ?? '', 0, 10)) ?></td>
                                    <td><?= ($row['action'] === 'IN') ? htmlspecialchars(substr($row['created_at'] ?? '', 11, 8)) : '-' ?></td>
                                    <td><?= ($row['action'] === 'OUT') ? htmlspecialchars(substr($row['created_at'] ?? '', 11, 8)) : '-' ?></td>
                                    <td>
                                        <span class="status-badge status-active">
                                            <?= ($row['action'] === 'IN') ? '✓ Vào' : '✓ Ra' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty-state">
                        <i class="fas fa-inbox"></i> Chưa có dữ liệu chấm công hôm nay.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Chức năng nhanh -->
            <div class="panel">
                <h3><i class="fas fa-grid" style="color:#3b82f6"></i> Các chức năng khác</h3>
                <div class="btn-group">
                    <a href="index.php?page=lich-su-cham-cong" class="btn btn-primary">
                        <i class="fas fa-history"></i> Xem lịch sử
                    </a>
                    <a href="index.php?page=yeu-cau-chinh-sua-cham-cong" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Yêu cầu chỉnh sửa
                    </a>
                </div>
            </div>

        <?php endif; // End of view check ?>

    </div>
</div>

<?php include 'app/views/layouts/footer.php'; ?>
