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
$lateCount = 0;
$totalLateMinutes = 0;

if (is_array($history)) {
    foreach ($history as $row) {
        if (($row['action'] ?? '') === 'IN') {
            $inCount++;
            // Calculate late info
            $time = date('H:i:s', strtotime($row['created_at']));
            if ($time > '08:15:00') {
                $lateCount++;
                $diff = strtotime($time) - strtotime('08:00:00');
                $totalLateMinutes += floor($diff / 60);
            }
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

<style>
/* ===== Scoped Employee History Styles ===== */
.ls-wrap { max-width:1100px; margin:0 auto; padding:6px 0 24px; font-family:'Inter',sans-serif; }
.ls-panel { background:#fff; border-radius:8px; padding:14px 16px; box-shadow:0 1px 3px rgba(0,0,0,0.05); margin-bottom:12px; border:1px solid #e2e8f0; }
.ls-panel h2 { font-size:16px; font-weight:600; color:#1e293b; margin:0 0 4px; display:flex; align-items:center; gap:8px; }
.ls-panel h2 i { color:#4f6ef7; font-size:16px; }
.ls-panel h3 { font-size:14px; font-weight:600; color:#1e293b; margin:0 0 12px; display:flex; align-items:center; gap:6px; }
.ls-panel h3 i { color:#4f6ef7; font-size:14px; }
.ls-panel > p { font-size:13px; color:#64748b; margin:0; }

/* Filter form */
.ls-filter-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; align-items:end; }
.ls-form-group { margin-bottom:0; }
.ls-form-group label { display:block; font-size:12px; font-weight:500; color:#475569; margin-bottom:4px; }
.ls-form-group input[type="date"] { width:100%; height:36px; padding:6px 10px; border:1px solid #e2e8f0; border-radius:6px; font-size:13px; font-family:'Inter',sans-serif; background:#f8fafc; box-sizing:border-box; transition:border-color .15s; }
.ls-form-group input[type="date"]:focus { border-color:#4f6ef7; outline:none; box-shadow:0 0 0 2px rgba(79,110,247,0.08); background:#fff; }

.ls-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; height:36px; padding:0 14px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; border:none; text-decoration:none; font-family:'Inter',sans-serif; transition:all .15s; }
.ls-btn-primary { background:#4f6ef7; color:#fff; box-shadow:0 1px 4px rgba(79,110,247,0.18); }
.ls-btn-primary:hover { background:#3b5de7; }
.ls-btn-outline { background:transparent; color:#475569; border:1px solid #e2e8f0; }
.ls-btn-outline:hover { background:#f1f5f9; border-color:#cbd5e1; }

/* Table */
.ls-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.ls-table { width:100%; border-collapse:collapse; font-size:13px; }
.ls-table thead th { background:#f8fafc; color:#64748b; font-weight:600; font-size:12px; text-transform:uppercase; letter-spacing:0.3px; padding:8px 10px; text-align:left; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.ls-table tbody td { padding:8px 10px; border-bottom:1px solid #f1f5f9; color:#334155; vertical-align:middle; }
.ls-table tbody tr:hover { background:#f8fafc; }
.ls-table tbody tr:last-child td { border-bottom:none; }

/* Badges */
.ls-badge { display:inline-flex; align-items:center; gap:3px; padding:3px 8px; border-radius:4px; font-size:11px; font-weight:600; white-space:nowrap; }
.ls-badge-in { background:#dcfce7; color:#166534; }
.ls-badge-out { background:#fef3c7; color:#b45309; }
.ls-badge-method { background:#dbeafe; color:#1e40af; }
.ls-badge-ok { background:#dcfce7; color:#166534; }

/* Stats */
.ls-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
.ls-stat-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; text-align:center; }
.ls-stat-card h4 { font-size:11px; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:0.3px; margin:0 0 6px; }
.ls-stat-card .ls-stat-val { font-size:22px; font-weight:700; color:#1e293b; line-height:1; }

/* Quick actions */
.ls-actions { display:flex; gap:8px; flex-wrap:wrap; }

/* Empty */
.ls-empty { text-align:center; padding:24px 16px; color:#94a3b8; }
.ls-empty i { font-size:24px; margin-bottom:6px; display:block; color:#cbd5e1; }
.ls-empty p { font-size:13px; margin:0; }

.ls-stat-card { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; text-decoration: none; display: block; }
.ls-stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: #4f6ef7; }
.ls-badge-late { background: #fee2e2; color: #dc2626; }
.ls-badge-ontime { background: #f0fdf4; color: #166534; }

@media(max-width:640px) {
    .ls-stats { grid-template-columns:repeat(2,1fr); }
    .ls-btn { width:100%; }
    .ls-actions { flex-direction:column; }
    .ls-filter-grid { grid-template-columns:1fr; }
}
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">
        <div class="ls-wrap">

            <!-- Header -->
            <div class="ls-panel">
                <h2><i class="fas fa-calendar-check"></i> Lịch sử Chấm công</h2>
                <p>Xem chi tiết tất cả lần chấm công trong hệ thống.</p>
            </div>

            <!-- Filter -->
            <div class="ls-panel">
                <h3><i class="fas fa-filter"></i> Lọc dữ liệu</h3>
                <form method="GET" action="index.php" class="ls-filter-grid">
                    <input type="hidden" name="page" value="lich-su-cham-cong">
                    <div class="ls-form-group">
                        <label for="from-date">Từ ngày</label>
                        <input type="date" id="from-date" name="from_date" value="<?= htmlspecialchars($from) ?>">
                    </div>
                    <div class="ls-form-group">
                        <label for="to-date">Đến ngày</label>
                        <input type="date" id="to-date" name="to_date" value="<?= htmlspecialchars($to) ?>">
                    </div>
                    <div style="display:flex;gap:8px;align-items:flex-end;">
                        <button type="submit" class="ls-btn ls-btn-primary" style="flex:1"><i class="fas fa-filter"></i> Lọc</button>
                        <a href="index.php?page=lich-su-cham-cong" class="ls-btn ls-btn-outline" style="flex:1;text-align:center"><i class="fas fa-rotate-left"></i> Đặt lại</a>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="ls-panel" style="padding: 10px;">
                <h3 style="font-size: 14px; margin-bottom: 10px;"><i class="fas fa-table"></i> Lịch sử chi tiết</h3>
                <?php if (!empty($history) && is_array($history)): ?>
                    <div class="ls-table-wrap">
                        <table class="ls-table">
                            <thead>
                                <tr>
                                    <th style="padding: 6px 10px;">Thời gian</th>
                                    <th style="padding: 6px 10px;">Hành động</th>
                                    <th style="padding: 6px 10px;">Phương thức</th>
                                    <th style="padding: 6px 10px;">WiFi</th>
                                    <th style="padding: 6px 10px;">Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $count = 0; ?>
                            <?php foreach ($history as $row): ?>
                                <?php if ($count >= 100) break; ?>
                                <?php $count++; ?>
                                <tr>
                                    <td style="font-size:11px; padding: 4px 10px;"><?= htmlspecialchars($row['created_at'] ?? '') ?></td>
                                    <td style="padding: 4px 10px;">
                                        <?php if (($row['action'] ?? '') === 'IN'): ?>
                                            <span class="ls-badge ls-badge-in" style="padding: 2px 6px; font-size: 10px;">VÀO</span>
                                        <?php else: ?>
                                            <span class="ls-badge ls-badge-out" style="padding: 2px 6px; font-size: 10px;">RA</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 4px 10px;"><span class="ls-badge ls-badge-method" style="padding: 2px 6px; font-size: 10px;"><?= htmlspecialchars($row['method'] ?? 'LAN') ?></span></td>
                                    <td style="font-size:11px; padding: 4px 10px;"><?= htmlspecialchars(!empty($row['wifi_name']) ? $row['wifi_name'] : 'Wifi Công ty') ?></td>
                                    <td style="padding: 4px 10px;">
                                        <?php 
                                        if (($row['action'] ?? '') === 'IN') {
                                            $time = date('H:i:s', strtotime($row['created_at']));
                                            if ($time > '08:15:00') {
                                                $diff = strtotime($time) - strtotime('08:00:00');
                                                $mins = floor($diff / 60);
                                                echo "<span class='ls-badge ls-badge-late'>Đi muộn {$mins}p</span>";
                                            } else {
                                                echo "<span class='ls-badge ls-badge-ontime'>Đúng giờ</span>";
                                            }
                                        } else {
                                            echo "<span class='ls-badge ls-badge-ok'><i class='fas fa-check'></i> Đã lưu</span>";
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="ls-empty">
                        <i class="fas fa-inbox"></i>
                        <p>Chưa có dữ liệu chấm công.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="ls-panel" style="padding: 10px;">
                <h3 style="font-size: 14px; margin-bottom: 10px;"><i class="fas fa-chart-column"></i> Thống kê nhanh</h3>
                <div class="ls-stats" style="grid-template-columns: repeat(3, 1fr);">
                    <a href="index.php?page=lich-su-cham-cong" class="ls-stat-card" style="padding: 8px;">
                        <h4 style="font-size: 10px;">Tổng lần</h4>
                        <div class="ls-stat-val" style="font-size: 18px;"><?= $totalCount ?></div>
                    </a>
                    <a href="index.php?page=lich-su-cham-cong" class="ls-stat-card" style="padding: 8px;">
                        <h4 style="font-size: 10px;">Vào/Ra</h4>
                        <div class="ls-stat-val" style="font-size: 18px;"><?= $inCount ?>/<?= $outCount ?></div>
                    </a>
                    <a href="index.php?page=yeu-cau-chinh-sua-cham-cong" class="ls-stat-card" style="padding: 8px;">
                        <h4 style="font-size: 10px; color: #dc2626;">Đi muộn</h4>
                        <div class="ls-stat-val" style="font-size: 18px; color: #dc2626;"><?= $lateCount ?></div>
                    </a>
                </div>
            </div>

            <!-- Quick actions -->
            <div class="ls-panel">
                <h3><i class="fas fa-bolt"></i> Chức năng nhanh</h3>
                <div class="ls-actions">
                    <a href="index.php?page=cham-cong-dashboard" class="ls-btn ls-btn-primary"><i class="fas fa-house"></i> Dashboard</a>
                    <a href="index.php?page=yeu-cau-chinh-sua-cham-cong" class="ls-btn ls-btn-outline"><i class="fas fa-pen-to-square"></i> Yêu cầu chỉnh sửa</a>
                </div>
            </div>

        </div>
    </div>
</div>
<?php include 'app/views/layouts/footer.php'; ?>
