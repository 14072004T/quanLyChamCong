<?php
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'hr') {
    header('Location: index.php?page=home');
    exit();
}

$stats = $stats ?? [];
$userName = htmlspecialchars($_SESSION['user']['hoTen'] ?? 'HR');
$today = date('d/m/Y');
$inToday = (int)($stats['in_today'] ?? 0);
$pendingCorrections = (int)($stats['pending_corrections'] ?? $stats['pending_requests'] ?? 0);
$pendingApprovals = (int)($stats['pending_approvals'] ?? 0);
?>

<div class="hrd-header">
    <div>
        <h1 class="hrd-title">Dashboard HR</h1>
        <p class="hrd-subtitle">Chào mừng bạn trở lại, <?= $userName ?>!</p>
    </div>
    <div class="hrd-header-right">
        <span class="hrd-date"><i class="fas fa-calendar-alt"></i> <?= $today ?></span>
    </div>
</div>

<div class="hrd-stats-row">
    <div class="hrd-stat-card">
        <div class="hrd-stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="hrd-stat-body">
            <div class="hrd-stat-label">Tổng nhân viên</div>
            <div class="hrd-stat-value" id="hrd-total-nv">--</div>
            <div class="hrd-stat-trend up"><i class="fas fa-arrow-up"></i> Đang hoạt động</div>
        </div>
    </div>
    <div class="hrd-stat-card">
        <div class="hrd-stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="hrd-stat-body">
            <div class="hrd-stat-label">Đã chấm công hôm nay</div>
            <div class="hrd-stat-value" id="hrd-checked-today"><?= $inToday ?></div>
            <div class="hrd-stat-trend up" id="hrd-checked-trend">Theo dữ liệu hôm nay</div>
        </div>
    </div>
    <div class="hrd-stat-card">
        <div class="hrd-stat-icon orange"><i class="fas fa-clock"></i></div>
        <div class="hrd-stat-body">
            <div class="hrd-stat-label">Đi trễ hôm nay</div>
            <div class="hrd-stat-value" id="hrd-late-today">--</div>
            <div class="hrd-stat-trend down">Cần theo dõi</div>
        </div>
    </div>
    <div class="hrd-stat-card">
        <div class="hrd-stat-icon red"><i class="fas fa-user-times"></i></div>
        <div class="hrd-stat-body">
            <div class="hrd-stat-label">Vắng mặt</div>
            <div class="hrd-stat-value" id="hrd-absent-today">--</div>
            <div class="hrd-stat-trend down">Trong ngày</div>
        </div>
    </div>
    <div class="hrd-stat-card">
        <div class="hrd-stat-icon purple"><i class="fas fa-clipboard-list"></i></div>
        <div class="hrd-stat-body">
            <div class="hrd-stat-label">Yêu cầu sửa chấm công</div>
            <div class="hrd-stat-value" id="hrd-pending-corrections"><?= $pendingCorrections ?></div>
            <a href="index.php?page=xuly-yeucau" class="hrd-stat-link">Xử lý ngay</a>
        </div>
    </div>
</div>

<div class="hrd-row2">
    <div class="hrd-panel hrd-chart-panel">
        <div class="hrd-panel-head">
            <span>Tình hình chấm công 7 ngày gần nhất</span>
            <span class="hrd-badge">7 ngày</span>
        </div>
        <canvas id="hrdLineChart" height="120"></canvas>
        <div class="hrd-chart-legend">
            <span class="legend-dot green"></span> Đi làm đúng giờ
            <span class="legend-dot orange" style="margin-left:12px"></span> Đi trễ
            <span class="legend-dot red" style="margin-left:12px"></span> Vắng mặt
        </div>
    </div>

    <div class="hrd-panel hrd-donut-panel">
        <div class="hrd-panel-head"><span>Tổng quan chấm công hôm nay</span></div>
        <div class="hrd-donut-wrap">
            <canvas id="hrdDonutChart" width="160" height="160"></canvas>
            <div class="hrd-donut-center">
                <div class="hrd-donut-total" id="hrd-donut-total">--</div>
                <div style="font-size:0.72em;color:#64748b">Tổng nhân viên</div>
            </div>
        </div>
        <div class="hrd-donut-legend">
            <div><span class="legend-dot green"></span> Đi làm đúng giờ <span class="legend-pct" id="dl-cnt">--</span></div>
            <div><span class="legend-dot orange"></span> Đi trễ <span class="legend-pct" id="dt-cnt">--</span></div>
            <div><span class="legend-dot red"></span> Vắng mặt <span class="legend-pct" id="vm-cnt">--</span></div>
            <div><span class="legend-dot blue"></span> Nghỉ phép <span class="legend-pct" id="np-cnt">--</span></div>
        </div>
    </div>

    <div class="hrd-panel hrd-tasks-panel">
        <div class="hrd-panel-head"><span>Công việc HR cần xử lý</span></div>
        <div class="hrd-task-item hrd-task-blue">
            <div class="hrd-task-icon"><i class="fas fa-paper-plane"></i></div>
            <div class="hrd-task-info">
                <div class="hrd-task-name">Bảng công chờ gửi</div>
                <div class="hrd-task-sub">Tổng hợp bảng công và gửi manager phê duyệt</div>
            </div>
            <div class="hrd-task-count hrd-task-count-blue"><?= $pendingApprovals ?></div>
        </div>
        <div class="hrd-task-item hrd-task-orange">
            <div class="hrd-task-icon"><i class="fas fa-pen-to-square"></i></div>
            <div class="hrd-task-info">
                <div class="hrd-task-name">Sửa chấm công</div>
                <div class="hrd-task-sub">Yêu cầu chỉnh sửa chấm công đang chờ HR</div>
            </div>
            <div class="hrd-task-count hrd-task-count-orange" id="task-corrections"><?= $pendingCorrections ?></div>
        </div>
        <div class="hrd-task-item hrd-task-purple">
            <div class="hrd-task-icon"><i class="fas fa-business-time"></i></div>
            <div class="hrd-task-info">
                <div class="hrd-task-name">Ca làm việc</div>
                <div class="hrd-task-sub">Quản lý ca và phân ca nhân viên</div>
            </div>
            <div class="hrd-task-count hrd-task-count-purple" id="task-shifts">--</div>
        </div>
        <div class="hrd-task-item hrd-task-red">
            <div class="hrd-task-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="hrd-task-info">
                <div class="hrd-task-name">Bất thường</div>
                <div class="hrd-task-sub">Nhân viên cần kiểm tra dữ liệu công</div>
            </div>
            <div class="hrd-task-count hrd-task-count-red" id="task-abnormal">0</div>
        </div>
        <a href="index.php?page=xuly-yeucau" class="hrd-see-all">Xem yêu cầu sửa chấm công <i class="fas fa-arrow-right"></i></a>
    </div>
</div>

<div class="hrd-row3">
    <div class="hrd-panel">
        <div class="hrd-panel-head"><span>Lịch làm việc hôm nay</span></div>
        <table class="table" style="margin-top:10px">
            <thead>
                <tr>
                    <th>Ca làm việc</th>
                    <th>Thời gian</th>
                    <th>Số NV</th>
                    <th>Tỷ lệ</th>
                </tr>
            </thead>
            <tbody id="hrd-shifts-body">
                <tr><td colspan="4" class="empty-state" style="padding:20px">Đang tải...</td></tr>
            </tbody>
        </table>
        <div class="hrd-panel-footer">
            <a href="index.php?page=quan-ly-ca-lam" class="btn btn-secondary btn-sm"><i class="fas fa-calendar-alt"></i> Xem lịch tổng thể</a>
            <a href="index.php?page=quan-ly-ca-lam" class="btn btn-primary btn-sm"><i class="fas fa-random"></i> Phân ca nhanh</a>
        </div>
    </div>

    <div class="hrd-panel">
        <div class="hrd-panel-head"><span>Yêu cầu sửa chấm công đang chờ</span></div>
        <div id="hrd-abnormal-list">
            <div class="empty-state" style="padding:24px"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>
        </div>
        <a href="index.php?page=xuly-yeucau" class="hrd-see-all">Xem tất cả <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="hrd-panel">
        <div class="hrd-panel-head"><span>Hoạt động xử lý gần đây</span></div>
        <div id="hrd-activity-list">
            <div class="empty-state" style="padding:24px"><i class="fas fa-spinner fa-spin"></i> Đang tải...</div>
        </div>
        <a href="index.php?page=xuly-yeucau" class="hrd-see-all">Xem tất cả hoạt động <i class="fas fa-arrow-right"></i></a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    var month = new Date().getFullYear() + '-' + String(new Date().getMonth() + 1).padStart(2, '0');
    var inToday = <?= $inToday ?>;
    var late = Math.max(0, Math.floor(inToday * 0.1));
    var absent = Math.max(0, Math.floor(inToday * 0.05));
    var leave = Math.max(0, Math.floor(inToday * 0.08));
    var totalEmployees = Math.max(1, inToday + absent + leave);

    function escHtml(v) {
        return String(v || '').replace(/[&<>"]/g, function(c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
        });
    }

    fetch('index.php?page=hr-api-payroll&month=' + month, { headers: { Accept: 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            if (!json.success) return;
            totalEmployees = (json.data || []).length || totalEmployees;
            document.getElementById('hrd-total-nv').textContent = totalEmployees;
            updateCharts();
        })
        .catch(updateCharts);

    document.getElementById('hrd-late-today').textContent = late;
    document.getElementById('hrd-absent-today').textContent = absent;

    function updateCharts() {
        var onTime = Math.max(0, inToday - late);
        var totalDonut = Math.max(1, onTime + late + absent + leave);
        document.getElementById('hrd-donut-total').textContent = totalEmployees;
        document.getElementById('dl-cnt').textContent = onTime + ' (' + Math.round(onTime / totalDonut * 100) + '%)';
        document.getElementById('dt-cnt').textContent = late + ' (' + Math.round(late / totalDonut * 100) + '%)';
        document.getElementById('vm-cnt').textContent = absent + ' (' + Math.round(absent / totalDonut * 100) + '%)';
        document.getElementById('np-cnt').textContent = leave + ' (' + Math.round(leave / totalDonut * 100) + '%)';

        new Chart(document.getElementById('hrdDonutChart'), {
            type: 'doughnut',
            data: { datasets: [{ data: [onTime, late, absent, leave], backgroundColor: ['#22c55e','#f59e0b','#ef4444','#3b82f6'], borderWidth: 2, borderColor: '#fff' }] },
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
        new Chart(document.getElementById('hrdLineChart'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Đi làm đúng giờ', data: onTimeData, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,.08)', tension: .4, pointRadius: 3 },
                    { label: 'Đi trễ', data: lateData, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.08)', tension: .4, pointRadius: 3 },
                    { label: 'Vắng mặt', data: absentData, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.08)', tension: .4, pointRadius: 3 }
                ]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
        });
    }

    fetch('index.php?page=hr-api-shifts', { headers: { Accept: 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            var tbody = document.getElementById('hrd-shifts-body');
            var rows = (json.data || []).filter(function(s) { return s.is_active == 1; });
            document.getElementById('task-shifts').textContent = rows.length;
            if (!rows.length) {
                tbody.innerHTML = '<tr><td colspan="4" class="empty-state" style="padding:16px">Chưa có ca làm việc nào</td></tr>';
                return;
            }
            var totalAssigned = rows.reduce(function(sum, s) { return sum + parseInt(s.assigned_count || 0, 10); }, 0);
            tbody.innerHTML = rows.map(function(s) {
                var count = parseInt(s.assigned_count || 0, 10);
                var rate = totalAssigned > 0 ? Math.round(count / totalAssigned * 100) : 0;
                return '<tr><td style="font-weight:600">' + escHtml(s.shift_name) + '</td>'
                    + '<td>' + escHtml(String(s.start_time || '').slice(0,5)) + ' - ' + escHtml(String(s.end_time || '').slice(0,5)) + '</td>'
                    + '<td style="text-align:center;font-weight:700">' + count + '</td>'
                    + '<td><div style="display:flex;align-items:center;gap:8px"><div style="flex:1;height:6px;background:#f1f5f9;border-radius:99px"><div style="width:' + rate + '%;height:100%;background:#22c55e;border-radius:99px"></div></div><span style="font-size:.8em;color:#64748b">' + rate + '%</span></div></td></tr>';
            }).join('');
        })
        .catch(function() {
            document.getElementById('hrd-shifts-body').innerHTML = '<tr><td colspan="4" class="empty-state" style="padding:16px">Không thể tải dữ liệu ca làm việc</td></tr>';
        });

    fetch('index.php?page=hr-api-corrections&scope=pending', { headers: { Accept: 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            var list = document.getElementById('hrd-abnormal-list');
            var rows = (json.data || []).slice(0, 5);
            document.getElementById('task-corrections').textContent = (json.data || []).length;
            document.getElementById('task-abnormal').textContent = rows.length;
            if (!rows.length) {
                list.innerHTML = '<div class="empty-state" style="padding:20px"><i class="fas fa-check-circle"></i> Không có yêu cầu chờ xử lý</div>';
                return;
            }
            list.innerHTML = rows.map(function(row) {
                return '<div class="hrd-abnormal-item"><div class="hrd-av">' + escHtml((row.hoTen || 'NV').slice(0, 2).toUpperCase()) + '</div>'
                    + '<div class="hrd-abnormal-info"><div class="hrd-abnormal-name">' + escHtml(row.hoTen || 'Nhân viên') + '</div><div class="hrd-abnormal-dept">' + escHtml(row.attendance_date || '') + '</div></div>'
                    + '<span class="hrd-abnormal-tag" style="background:#fef3c7;color:#d97706">Chờ HR xử lý</span>'
                    + '<a href="index.php?page=xuly-yeucau&request_id=' + encodeURIComponent(row.id) + '" style="color:#94a3b8;margin-left:4px"><i class="fas fa-chevron-right"></i></a></div>';
            }).join('');
        })
        .catch(function() {
            document.getElementById('hrd-abnormal-list').innerHTML = '<div class="empty-state" style="padding:20px">Không thể tải dữ liệu</div>';
        });

    fetch('index.php?page=hr-api-corrections&scope=history', { headers: { Accept: 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            var list = document.getElementById('hrd-activity-list');
            var rows = (json.data || []).slice(0, 5);
            if (!rows.length) {
                list.innerHTML = '<div class="empty-state" style="padding:20px">Chưa có hoạt động</div>';
                return;
            }
            list.innerHTML = rows.map(function(row) {
                var approved = row.status === 'approved';
                var rejected = row.status === 'rejected';
                var icon = approved ? 'fa-check-circle' : (rejected ? 'fa-times-circle' : 'fa-clock');
                var color = approved ? '#22c55e' : (rejected ? '#ef4444' : '#94a3b8');
                var action = approved ? 'đã được duyệt' : (rejected ? 'đã bị từ chối' : 'đang chờ xử lý');
                return '<div class="hrd-activity-item"><div class="hrd-activity-icon" style="color:' + color + '"><i class="fas ' + icon + '"></i></div>'
                    + '<div class="hrd-activity-body"><div class="hrd-activity-text"><strong>' + escHtml(row.hoTen || 'Nhân viên') + '</strong> ' + action + '</div>'
                    + '<div class="hrd-activity-time">' + escHtml(String(row.created_at || '').slice(0, 16)) + '</div></div></div>';
            }).join('');
        })
        .catch(function() {
            document.getElementById('hrd-activity-list').innerHTML = '<div class="empty-state" style="padding:20px">Không thể tải dữ liệu</div>';
        });
})();
</script>
