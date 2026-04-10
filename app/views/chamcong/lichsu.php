<?php
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}

$history = $history ?? [];
$from = $from ?? date('Y-m-01');
$to = $to ?? date('Y-m-d');

$totalCount = is_array($history) ? count($history) : 0;
$inCount = 0;
$outCount = 0;
if (is_array($history)) {
    foreach ($history as $row) {
        if (($row['action'] ?? '') === 'IN') {
            $inCount++;
        } elseif (($row['action'] ?? '') === 'OUT') {
            $outCount++;
        }
    }
}

$fromTs = strtotime($from);
$toTs = strtotime($to);
$days = ($fromTs && $toTs) ? (int)max(1, floor(abs($toTs - $fromTs) / 86400) + 1) : 1;
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">
        <div class="panel">
            <h2><i class="fas fa-calendar-check"></i> Lịch sử Chấm công</h2>
            <p>Xem chi tiết tất cả lần chấm công của bạn trong hệ thống.</p>
        </div>

        <div class="panel">
            <h3><i class="fas fa-filter"></i> Lọc dữ liệu</h3>
            <form method="GET" action="index.php" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
                <input type="hidden" name="page" value="lich-su-cham-cong">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="from-date">Từ ngày</label>
                    <input type="date" id="from-date" name="from_date" value="<?= htmlspecialchars($from) ?>">
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label for="to-date">Đến ngày</label>
                    <input type="date" id="to-date" name="to_date" value="<?= htmlspecialchars($to) ?>">
                </div>

                <div style="display:flex;align-items:flex-end;gap:10px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;margin-bottom:0;">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                    <a href="index.php?page=lich-su-cham-cong" class="btn btn-secondary" style="flex:1;text-align:center;margin-bottom:0;text-decoration:none;">
                        <i class="fas fa-rotate-left"></i> Đặt lại
                    </a>
                </div>
            </form>
        </div>

        <div class="panel">
            <h3><i class="fas fa-table"></i> Chi tiết chấm công</h3>
            <?php if (!empty($history) && is_array($history)): ?>
                <div class="history-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Hành động</th>
                                <th>Phương thức</th>
                                <th>WiFi</th>
                                <th>Ghi chú</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $count = 0; ?>
                        <?php foreach ($history as $row): ?>
                            <?php if ($count >= 100) break; ?>
                            <?php $count++; ?>
                            <tr>
                                <td style="font-family: Consolas, monospace; font-size: .9em;">
                                    <?= htmlspecialchars($row['created_at'] ?? '') ?>
                                </td>
                                <td>
                                    <?php if (($row['action'] ?? '') === 'IN'): ?>
                                        <span class="status-badge status-approved"><i class="fas fa-right-to-bracket"></i> Chấm vào</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending"><i class="fas fa-right-from-bracket"></i> Chấm ra</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="status-badge status-active"><?= htmlspecialchars($row['method'] ?? 'LAN') ?></span></td>
                                <td><?= htmlspecialchars($row['wifi_name'] ?? '—') ?></td>
                                <td style="font-size:.9em;color:#64748b;"><?= htmlspecialchars(substr($row['note'] ?? '—', 0, 60)) ?></td>
                                <td><span class="status-badge status-approved"><i class="fas fa-check-circle"></i> Ghi nhận</span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (($count ?? 0) >= 100): ?>
                    <p style="text-align:center;color:#64748b;margin-top:12px;">Hiển thị 100 dòng gần nhất. Dùng bộ lọc để xem chi tiết hơn.</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox" style="font-size:2.2em;opacity:.35;display:block;margin-bottom:10px;"></i>
                    <p>Chưa có dữ liệu chấm công trong khoảng ngày đã chọn.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h3><i class="fas fa-chart-column"></i> Thống kê khoảng ngày</h3>
            <div class="dashboard-stats">
                <div class="card">
                    <h3>Tổng lần chấm</h3>
                    <p><?= $totalCount ?></p>
                </div>
                <div class="card">
                    <h3>Lần chấm vào</h3>
                    <p><?= $inCount ?></p>
                </div>
                <div class="card">
                    <h3>Lần chấm ra</h3>
                    <p><?= $outCount ?></p>
                </div>
                <div class="card">
                    <h3>Số ngày trong kỳ</h3>
                    <p><?= $days ?></p>
                </div>
            </div>
        </div>

        <div class="panel">
            <h3><i class="fas fa-bolt"></i> Chức năng nhanh</h3>
            <div class="btn-group">
                <a href="index.php?page=cham-cong-dashboard" class="btn btn-primary">
                    <i class="fas fa-house"></i> Về Dashboard
                </a>
                <a href="index.php?page=yeu-cau-chinh-sua-cham-cong" class="btn btn-warning">
                    <i class="fas fa-pen-to-square"></i> Gửi yêu cầu chỉnh sửa
                </a>
            </div>
        </div>
    </div>
</div>
<?php include 'app/views/layouts/footer.php'; ?>
