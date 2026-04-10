<?php 
// Security check - only Manager can access this panel
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'manager') {
    header('Location: index.php?page=home');
    exit();
}
?>

<!-- GIAO DIỆN MANAGER -->
<div class="panel">
    <h2>Bảng điều khiển Quản lý</h2>
    <p>Phê duyệt bảng công, báo cáo tổng hợp và thống kê toàn công ty.</p>
</div>

<!-- Thống kê tổng (chỉ Manager xem) -->
<div class="dashboard-stats">
    <div class="card">
        <h3>Tổng lượt chấm công</h3>
        <p><?= (int)($stats['total_logs'] ?? 0) ?></p>
    </div>
    <div class="card">
        <h3>Vào ca</h3>
        <p><?= (int)($stats['total_in'] ?? 0) ?></p>
    </div>
    <div class="card">
        <h3>Ra ca</h3>
        <p><?= (int)($stats['total_out'] ?? 0) ?></p>
    </div>
    <div class="card">
        <h3>Bảng công chờ phê duyệt</h3>
        <p><?= (int)($stats['pending_approvals'] ?? 0) ?></p>
    </div>
</div>

<!-- Phê duyệt bảng công -->
<div class="panel">
    <h3>Phê duyệt Bảng Công</h3>
    <p>Phê duyệt các bảng công từ HR trước khi gửi sang kế toán.</p>
    <a href="index.php?page=pheduyet-bang-cong" class="btn btn-success">
        <i class="fas fa-check-circle"></i> Xem bảng cần phê duyệt
    </a>
</div>

<!-- Báo cáo tổng hợp -->
<div class="panel">
    <h3>Báo Cáo Tổng Hợp</h3>
    <p>Xem báo cáo chấm công, thống kê chi tiết toàn công ty.</p>
    <div class="btn-group">
        <a href="index.php?page=bao-cao-tong-hop" class="btn btn-primary">
            <i class="fas fa-chart-bar"></i> Báo cáo tổng hợp
        </a>
        <a href="index.php?page=thong-ke-bieu-do" class="btn btn-secondary">
            <i class="fas fa-chart-line"></i> Thống kê & Biểu đồ
        </a>
    </div>
</div>

<!-- Danh sách bảng công gần đây -->
<div class="panel">
    <h3>Lịch sử Bảng Công</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Tháng</th>
                <th>Từ HR vào</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($payrolls) && is_array($payrolls)): ?>
                <?php foreach (array_slice($payrolls, 0, 10) as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['month_key'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['submitted_at'] ?? 'N/A') ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower($row['status'] ?? 'pending') ?>">
                                <?= htmlspecialchars($row['status'] ?? 'pending') ?>
                            </span>
                        </td>
                        <td>
                            <a href="index.php?page=chi-tiet-bang-cong&id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Chi tiết</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="empty-state">Không có bảng công nào</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

