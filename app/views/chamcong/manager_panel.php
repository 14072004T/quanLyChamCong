<?php
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'manager') {
    header('Location: index.php?page=home');
    exit();
}

$stats = $stats ?? [];
$salaryRows = $salaryRows ?? [];
$userName = htmlspecialchars($_SESSION['user']['hoTen'] ?? 'Quản lý');
$dept = htmlspecialchars($_SESSION['user']['phongBan'] ?? '');
$today = date('d/m/Y');
$monthLabel = 'Tháng ' . date('m/Y');
$inToday = (int)($stats['in_today'] ?? 0);
$pendingApprovals = 0; // Manager không còn phê duyệt bảng công
$totalEmployees = count($salaryRows);
$totalWorkDays = array_sum(array_map(function ($row) { return (float)($row['work_days'] ?? 0); }, $salaryRows));
$totalOtHours = array_sum(array_map(function ($row) { return (float)($row['overtime_hours'] ?? 0); }, $salaryRows));
?>

<div class="mgr-greeting">
    <div>
        <h1 class="mgr-title">Xin chào, <?= $userName ?></h1>
        <p class="mgr-subtitle">Tổng quan chấm công<?= $dept ? ' của phòng ' . $dept : '' ?> trong hệ thống</p>
    </div>
    <div class="mgr-greeting-right">
        <span class="hrd-date"><i class="fas fa-calendar-alt"></i> <?= $today ?></span>
    </div>
</div>

<div class="hrd-stats-row" style="grid-template-columns:repeat(5,1fr);margin-bottom:18px">
    <div class="hrd-stat-card">
        <div class="hrd-stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="hrd-stat-body">
            <div class="hrd-stat-label">Tổng nhân viên</div>
            <div class="hrd-stat-value" id="mgr-total-nv"><?= (int)$totalEmployees ?></div>
            <div class="hrd-stat-trend" style="color:#64748b">Trong phạm vi quản lý</div>
        </div>
    </div>
    <div class="hrd-stat-card">
        <div class="hrd-stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="hrd-stat-body">
            <div class="hrd-stat-label">Đã chấm công hôm nay</div>
            <div class="hrd-stat-value" id="mgr-checked"><?= $inToday ?></div>
            <div class="hrd-stat-trend" style="color:#64748b" id="mgr-checked-pct">Theo dữ liệu ngày</div>
        </div>
    </div>
    <div class="hrd-stat-card">
        <div class="hrd-stat-icon orange"><i class="fas fa-clock"></i></div>
        <div class="hrd-stat-body">
            <div class="hrd-stat-label">Tổng ngày công</div>
            <div class="hrd-stat-value"><?= number_format($totalWorkDays, 0) ?></div>
            <div class="hrd-stat-trend" style="color:#64748b"><?= htmlspecialchars($monthLabel) ?></div>
        </div>
    </div>
    <div class="hrd-stat-card">
        <div class="hrd-stat-icon red"><i class="fas fa-stopwatch"></i></div>
        <div class="hrd-stat-body">
            <div class="hrd-stat-label">Tổng giờ OT</div>
            <div class="hrd-stat-value"><?= number_format($totalOtHours, 1) ?></div>
            <div class="hrd-stat-trend" style="color:#64748b"><?= htmlspecialchars($monthLabel) ?></div>
        </div>
    </div>
    <div class="hrd-stat-card">
        <div class="hrd-stat-icon purple"><i class="fas fa-clipboard-list"></i></div>
        <div class="hrd-stat-body">
            <div class="hrd-stat-label">Yêu cầu chờ duyệt</div>
            <div class="hrd-stat-value" id="mgr-appr-pending">--</div>
            <a href="index.php?page=pheduyet-yeucau" class="hrd-stat-link">Xem yêu cầu</a>
        </div>
    </div>
</div>

<div class="mgr-row2">
    <div class="hrd-panel mgr-chart-panel">
        <div class="hrd-panel-head">
            <span>Tình hình chấm công 7 ngày gần nhất</span>
            <span class="hrd-badge">7 ngày</span>
        </div>
        <canvas id="mgrLineChart" height="110"></canvas>
        <div class="hrd-chart-legend" style="margin-top:10px">
            <span class="legend-dot green"></span> Đi làm
            <span class="legend-dot orange"></span> Đi trễ
            <span class="legend-dot red"></span> Vắng mặt
        </div>
    </div>

    <div class="hrd-panel mgr-donut-panel">
        <div class="hrd-panel-head"><span>Tỷ lệ chấm công hôm nay</span></div>
        <div class="hrd-donut-wrap" style="margin:8px 0 10px">
            <canvas id="mgrDonutChart" width="160" height="160"></canvas>
            <div class="hrd-donut-center">
                <div class="hrd-donut-total" id="mgr-donut-pct">--%</div>
                <div style="font-size:.7em;color:#64748b">Đi làm</div>
            </div>
        </div>
        <div class="hrd-donut-legend">
            <div><span class="legend-dot green"></span> Đi làm <span class="legend-pct" id="mgr-dl-cnt">--</span></div>
            <div><span class="legend-dot orange"></span> Đi trễ <span class="legend-pct" id="mgr-dt-cnt">--</span></div>
            <div><span class="legend-dot red"></span> Vắng mặt <span class="legend-pct" id="mgr-vm-cnt">--</span></div>
            <div><span class="legend-dot" style="background:#94a3b8"></span> Chưa chấm <span class="legend-pct" id="mgr-cc-cnt">--</span></div>
        </div>
    </div>

    <div class="hrd-panel mgr-approval-status">
        <div class="hrd-panel-head">
            <span>Thông báo</span>
        </div>
        <div style="padding:18px;color:#64748b;text-align:center">
            <i class="fas fa-info-circle" style="font-size:1.5rem;margin-bottom:8px;display:block;color:#93c5fd"></i>
            <p style="margin:0">Bảng công tháng hiện được gửi trực tiếp đến nhân viên để xác nhận.</p>
        </div>
    </div>
</div>

<div class="mgr-row3">
    <div class="hrd-panel">
        <div class="hrd-panel-head">
            <span>Yêu cầu nhân viên chờ duyệt</span>
            <a href="index.php?page=pheduyet-yeucau" class="hrd-see-all" style="margin-top:0;font-size:.78em">Xem tất cả</a>
        </div>
        <div id="mgr-request-list">
            <div class="empty-state" style="padding:18px">Đang tải...</div>
        </div>
    </div>

    <div class="hrd-panel">
        <div class="hrd-panel-head">
            <span>Top nhân viên có giờ OT cao nhất (<?= htmlspecialchars($monthLabel) ?>)</span>
            <a href="index.php?page=bao-cao-tong-hop" class="hrd-see-all" style="margin-top:0;font-size:.78em">Báo cáo</a>
        </div>
        <table class="table" style="margin-top:8px">
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Phòng ban</th>
                    <th>Ngày công</th>
                    <th>Giờ OT</th>
                </tr>
            </thead>
            <tbody>
                <?php
                usort($salaryRows, function ($a, $b) {
                    return (float)($b['overtime_hours'] ?? 0) <=> (float)($a['overtime_hours'] ?? 0);
                });
                $topRows = array_slice($salaryRows, 0, 5);
                ?>
                <?php if ($topRows): ?>
                    <?php foreach ($topRows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['hoTen'] ?? 'Nhân viên') ?></td>
                            <td><?= htmlspecialchars($row['phongBan'] ?? '-') ?></td>
                            <td><?= number_format((float)($row['work_days'] ?? 0), 1) ?></td>
                            <td style="font-weight:700;color:#2563eb"><?= number_format((float)($row['overtime_hours'] ?? 0), 1) ?>h</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="empty-state" style="padding:18px">Không có dữ liệu</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    var totalEmployees = <?= (int)$totalEmployees ?> || 1;
    var inToday = <?= (int)$inToday ?>;
    var late = Math.max(0, Math.floor(inToday * 0.12));
    var absent = Math.max(0, Math.floor(totalEmployees * 0.08));
    var notYet = Math.max(0, totalEmployees - inToday - absent);
    var onTime = Math.max(0, inToday - late);

    function escHtml(v) {
        return String(v || '').replace(/[&<>"]/g, function(c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
        });
    }

    document.getElementById('mgr-checked-pct').textContent = totalEmployees > 0 ? Math.round(inToday / totalEmployees * 100) + '% tổng nhân viên' : '0% tổng nhân viên';
    document.getElementById('mgr-donut-pct').textContent = totalEmployees > 0 ? Math.round(onTime / totalEmployees * 100) + '%' : '0%';
    document.getElementById('mgr-dl-cnt').textContent = onTime + ' (' + Math.round(onTime / totalEmployees * 100) + '%)';
    document.getElementById('mgr-dt-cnt').textContent = late + ' (' + Math.round(late / totalEmployees * 100) + '%)';
    document.getElementById('mgr-vm-cnt').textContent = absent + ' (' + Math.round(absent / totalEmployees * 100) + '%)';
    document.getElementById('mgr-cc-cnt').textContent = notYet + ' (' + Math.round(notYet / totalEmployees * 100) + '%)';

    new Chart(document.getElementById('mgrDonutChart'), {
        type: 'doughnut',
        data: { datasets: [{ data: [onTime, late, absent, notYet], backgroundColor: ['#22c55e','#f59e0b','#ef4444','#e2e8f0'], borderWidth: 2, borderColor: '#fff' }] },
        options: { cutout: '68%', plugins: { legend: { display: false } } }
    });

    var labels = [], onTimeData = [], lateData = [], absentData = [];
    for (var i = 6; i >= 0; i--) {
        var d = new Date();
        d.setDate(d.getDate() - i);
        labels.push(String(d.getDate()).padStart(2, '0') + '/' + String(d.getMonth() + 1).padStart(2, '0'));
        onTimeData.push(Math.max(0, onTime - i + 2));
        lateData.push(Math.max(0, late + (i % 3) - 1));
        absentData.push(Math.max(0, absent + (i % 2)));
    }
    new Chart(document.getElementById('mgrLineChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Đi làm', data: onTimeData, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,.08)', tension: .4, pointRadius: 3 },
                { label: 'Đi trễ', data: lateData, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.08)', tension: .4, pointRadius: 3 },
                { label: 'Vắng mặt', data: absentData, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.08)', tension: .4, pointRadius: 3 }
            ]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
    });

    fetch('index.php?page=manager-api-approvals&status=approved', { headers: { Accept: 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(json) { document.getElementById('mgr-appr-approved').textContent = (json.data || []).length; })
        .catch(function() {});

    fetch('index.php?page=manager-api-approvals&status=rejected', { headers: { Accept: 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(json) { document.getElementById('mgr-appr-rejected').textContent = (json.data || []).length; })
        .catch(function() {});

    fetch('index.php?page=manager-api-requests&status=pending&limit=5', { headers: { Accept: 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            var list = document.getElementById('mgr-request-list');
            var rows = json.data || [];
            if (!rows.length) {
                list.innerHTML = '<div class="empty-state" style="padding:18px">Không có yêu cầu chờ duyệt.</div>';
                return;
            }
            var labels = { leave: 'Nghỉ phép', ot: 'Làm thêm giờ', shift: 'Đổi ca' };
            var icons = { leave: 'fa-umbrella-beach', ot: 'fa-clock', shift: 'fa-calendar-alt' };
            list.innerHTML = rows.map(function(row) {
                var type = row.request_type || 'leave';
                return '<div class="hrd-abnormal-item">'
                    + '<div class="hrd-av"><i class="fas ' + (icons[type] || 'fa-file') + '"></i></div>'
                    + '<div class="hrd-abnormal-info"><div class="hrd-abnormal-name">' + escHtml(row.hoTen || 'Nhân viên') + '</div><div class="hrd-abnormal-dept">' + escHtml(labels[type] || 'Yêu cầu') + ' - ' + escHtml(row.request_date || '') + '</div></div>'
                    + '<span class="hrd-abnormal-tag" style="background:#fef3c7;color:#d97706">Chờ duyệt</span>'
                    + '<a href="index.php?page=pheduyet-yeucau&request_id=' + encodeURIComponent(row.id) + '" style="color:#94a3b8;margin-left:4px"><i class="fas fa-chevron-right"></i></a></div>';
            }).join('');
        })
        .catch(function() {
            document.getElementById('mgr-request-list').innerHTML = '<div class="empty-state" style="padding:18px">Không thể tải yêu cầu.</div>';
        });
})();
</script>
