<?php 
// Security check - must be logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}

// Safety check - initialize variables if not provided by controller
$history = $history ?? [];
$from = $from ?? date('Y-m-01');
$to = $to ?? date('Y-m-d');
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">

        <!-- Tiêu đề chính -->
        <div class="panel">
            <h2>Lịch sử Chấm công</h2>
            <p>Xem chi tiết tất cả lần chấm công của bạn trong hệ thống.</p>
        </div>

        <!-- BỘ LỌC -->
        <div class="panel">
            <h3>Lọc dữ liệu</h3>
            <form method="GET" action="index.php?page=lich-su-cham-cong" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="from-date">Từ ngày</label>
                    <input type="date" id="from-date" name="from_date" value="<?= htmlspecialchars($from ?? date('Y-m-01')) ?>">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="to-date">Đến ngày</label>
                    <input type="date" id="to-date" name="to_date" value="<?= htmlspecialchars($to ?? date('Y-m-d')) ?>">
                </div>
                
                <div style="display: flex; align-items: flex-end; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                    <a href="index.php?page=lich-su-cham-cong" class="btn btn-secondary" style="flex: 1; text-align: center;">
                        <i class="fas fa-redo"></i> Đặt lại
                    </a>
                </div>
            </form>
        </div>

        <!-- BẢNG LỊCH SỬ -->
        <div class="panel">
            <h3>Chi tiết Chấm công</h3>
            
            <?php if (!empty($history) && is_array($history)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Hành động</th>
                            <th>Phương thức</th>
                            <th>WiFi / Thiết bị</th>
                            <th>Ghi chú</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $count = 0;
                            foreach ($history as $row): 
                                if ($count >= 50) break;
                                $count++;
                        ?>
                            <tr>
                                <td style="font-family: 'Inter', sans-serif;">
                                    <?= htmlspecialchars($row['created_at'] ?? '') ?>
                                </td>
                                <td>
                                    <?php 
                                        $action = $row['action'] === 'IN' ? 'Chấm vào' : 'Chấm ra';
                                        $icon = $row['action'] === 'IN' ? '📥' : '📤';
                                        echo $icon . ' ' . $action;
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-active">
                                        <?= htmlspecialchars($row['method'] ?? 'LAN') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['wifi_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['note'] ?? '-') ?></td>
                                <td>
                                    <span class="status-badge status-approved">
                                        <i class="fas fa-check-circle"></i> Đã ghi nhận
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($count >= 50): ?>
                    <p style="text-align: center; color: #999; margin-top: 15px;">
                        Hiển thị 50 dòng mới nhất. <a href="#" style="color: #0099ff;">Xem thêm</a>
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>
                        <i class="fas fa-inbox" style="font-size: 2em; color: #ccc;"></i>
                    </p>
                    <p>Chưa có dữ liệu chấm công.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- THỐNG KÊ THÁNG -->
        <div class="panel">
            <h3>Thống kê Tháng hiện tại</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                <div style="background-color: #f0f7ff; border-left: 4px solid #0099ff; padding: 15px; border-radius: 6px;">
                    <h4 style="margin: 0 0 8px 0; color: #666; font-size: 0.85em; text-transform: uppercase;">Tổng lần chấm</h4>
                    <p style="margin: 0; font-size: 1.6em; font-weight: bold; color: #0099ff;">
                        <?php 
                            $total_count = is_array($history) ? count($history) : 0;
                            echo $total_count;
                        ?>
                    </p>
                </div>
                
                <div style="background-color: #f0fdf4; border-left: 4px solid #22c55e; padding: 15px; border-radius: 6px;">
                    <h4 style="margin: 0 0 8px 0; color: #666; font-size: 0.85em; text-transform: uppercase;">Lần chấm vào</h4>
                    <p style="margin: 0; font-size: 1.6em; font-weight: bold; color: #22c55e;">
                        <?php 
                            $in_count = 0;
                            if (is_array($history)) {
                                foreach ($history as $row) {
                                    if ($row['action'] === 'IN') $in_count++;
                                }
                            }
                            echo $in_count;
                        ?>
                    </p>
                </div>
                
                <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; border-radius: 6px;">
                    <h4 style="margin: 0 0 8px 0; color: #666; font-size: 0.85em; text-transform: uppercase;">Lần chấm ra</h4>
                    <p style="margin: 0; font-size: 1.6em; font-weight: bold; color: #ef4444;">
                        <?php 
                            $out_count = 0;
                            if (is_array($history)) {
                                foreach ($history as $row) {
                                    if ($row['action'] === 'OUT') $out_count++;
                                }
                            }
                            echo $out_count;
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- NÚT QUAY LẠI -->
        <div class="panel text-center">
            <a href="index.php?page=cham-cong-dashboard" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại Dashboard
            </a>
        </div>

    </div>
</div>
<?php include 'app/views/layouts/footer.php'; ?>
