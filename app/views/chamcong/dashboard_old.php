<?php 
// Kiểm tra xác thực
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}

$role = $_SESSION['role'] ?? 'nhanvien';

// Kiểm tra quyền hạn
$allowedRoles = ['nhanvien', 'hr', 'manager', 'tech'];
if (!in_array($role, $allowedRoles)) {
    header('Location: index.php?page=home');
    exit();
}

// Bảo vệ: TECH không có dashboard chấm công
if ($role === 'tech') {
    header('Location: index.php?page=home');
    exit();
}
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">
        
        <?php
            // Kiểm tra vai trò người dùng và hiển thị giao diện tương ứng
            if ($role === 'hr') {
                // HR Panel
                include 'app/views/chamcong/hr_panel.php';
            } elseif ($role === 'manager') {
                // Manager Panel
                include 'app/views/chamcong/manager_panel.php';
            } else {
                // Giao diện mặc định cho nhân viên (nhanvien)
        ?>
        <!-- Giao diện chấm công nhân viên -->
        <div class="panel">
            <h2>Chấm công hôm nay</h2>
            <p>Chấm công vào/ra qua mạng nội bộ hoặc mã QR dự phòng.</p>
        </div>

        <!-- Thông báo -->
        <?php if (!empty($success)): ?>
            <div class="panel" style="background-color: #f0fdf4; border-left: 4px solid #22c55e;">
                <p style="margin: 0; color: #166534;">
                    <i class="fas fa-check-circle"></i> <strong><?= htmlspecialchars($success) ?></strong>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="panel" style="background-color: #fef2f2; border-left: 4px solid #ef4444;">
                <p style="margin: 0; color: #991b1b;">
                    <i class="fas fa-exclamation-circle"></i> <strong><?= htmlspecialchars($error) ?></strong>
                </p>
            </div>
        <?php endif; ?>

        <!-- Thống kê ngày -->
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
                <h3>Yêu cầu chờ HR xử lý</h3>
                <p><?= (int)($stats['pending_requests'] ?? 0) ?></p>
            </div>
        </div>

        <!-- Nút chấm công -->
        <div class="panel">
            <h3>Thao tác chấm công</h3>
            
            <?php if (!$hasWifi): ?>
                <div style="background-color: #fefce8; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #92400e;">
                        <i class="fas fa-wifi"></i> <strong>Không phát hiện WiFi nội bộ.</strong> Vui lòng kết nối WiFi hoặc sử dụng mã QR.
                    </p>
                </div>
            <?php endif; ?>

            <div class="btn-group">
                <a href="index.php?page=cham-cong-vao" 
                   class="btn btn-success" 
                   style="<?= ($trangThaiHomNay === 'IN' || !$hasWifi) ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : '' ?>"
                   onclick="<?= ($trangThaiHomNay === 'IN' || !$hasWifi) ? 'return false;' : '' ?>">
                    <i class="fas fa-sign-in-alt"></i> Chấm công vào
                </a>
                <a href="index.php?page=cham-cong-ra" 
                   class="btn btn-danger" 
                   style="<?= ($trangThaiHomNay !== 'IN' || !$hasWifi) ? 'opacity: 0.5; cursor: not-allowed; pointer-events: none;' : '' ?>"
                   onclick="<?= ($trangThaiHomNay !== 'IN' || !$hasWifi) ? 'return false;' : '' ?>">
                    <i class="fas fa-sign-out-alt"></i> Chấm công ra
                </a>
            </div>

            <?php if ($trangThaiHomNay === 'IN'): ?>
                <p style="text-align: center; color: #22c55e; margin-top: 15px; font-weight: bold;">
                    ✓ Bạn đã chấm vào. Vui lòng chấm ra khi kết thúc ca làm.
                </p>
            <?php elseif ($trangThaiHomNay === 'OUT'): ?>
                <p style="text-align: center; color: #22c55e; margin-top: 15px; font-weight: bold;">
                    ✓ Bạn đã chấm ra hôm nay.
                </p>
            <?php else: ?>
                <p style="text-align: center; color: #f59e0b; margin-top: 15px;">
                    Chưa chấm công vào. Vui lòng chấm vào ngay.
                </p>
            <?php endif; ?>

        <!-- Lịch sử gần đây -->
        <div class="panel">
            <h3>Lịch sử chấm công gần đây</h3>
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
                                <td><?= ($row['action'] === 'IN') ? htmlspecialchars($row['created_at'] ?? '') : '-' ?></td>
                                <td><?= ($row['action'] === 'OUT') ? htmlspecialchars($row['created_at'] ?? '') : '-' ?></td>
                                <td>
                                    <?php 
                                        $status = ($row['action'] === 'IN') ? 'Đã chấm vào' : 'Đã chấm ra';
                                        echo '<span class="status-badge status-active">' . $status . '</span>';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="empty-state">Chưa có dữ liệu chấm công.</p>
            <?php endif; ?>
        </div>

        <!-- Điều hướng -->
        <div class="panel">
            <h3>Chức năng</h3>
            <div class="btn-group">
                <a href="index.php?page=lich-su-cham-cong" class="btn btn-primary">
                    <i class="fas fa-history"></i> Xem lịch sử
                </a>
                <a href="index.php?page=yeu-cau-chinh-sua-cham-cong" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Gửi yêu cầu chỉnh sửa
                </a>
            </div>
        </div>

        <?php } ?>

    </div>
</div>
<?php include 'app/views/layouts/footer.php'; ?>
