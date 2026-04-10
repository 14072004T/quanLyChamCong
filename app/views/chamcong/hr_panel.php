<?php
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'hr') {
    header('Location: index.php?page=home');
    exit();
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
$corrections = $corrections ?? [];
$stats = $stats ?? [];
$userName = htmlspecialchars($_SESSION['user']['hoTen'] ?? 'HR');
?>

<div class="hr-hero">
    <p class="hr-hero-kicker">TRUNG TÂM ĐIỀU HÀNH HR</p>
    <h1>Xin chào, <?= $userName ?></h1>
    <p class="hr-hero-subtitle">Theo dõi trung tâm chấm công, xử lý tác vụ theo vai trò và truy cập nhanh các chức năng.</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="stat-groups">
    <div class="stat-group">
        <div class="stat-group-title">Cần xử lý ngay</div>
        <div class="stat-group-cards">
            <div class="stat-mini warning">
                <i class="fas fa-triangle-exclamation stat-icon"></i>
                <div class="stat-number" id="hr-pending-requests"><?= (int)($stats['pending_corrections'] ?? 0) ?></div>
                <div class="stat-label">Yêu cầu chờ duyệt</div>
            </div>
            <div class="stat-mini danger">
                <i class="fas fa-triangle-exclamation stat-icon"></i>
                <div class="stat-number" id="hr-pending-payroll"><?= (int)($stats['pending_approvals'] ?? 0) ?></div>
                <div class="stat-label">Bảng công chưa gửi</div>
            </div>
        </div>
    </div>
    <div class="stat-group">
        <div class="stat-group-title">Tình hình hôm nay</div>
        <div class="stat-group-cards">
            <div class="stat-mini info">
                <i class="fas fa-chart-line stat-icon"></i>
                <div class="stat-number" id="hr-active-employees"><?= (int)($stats['in_today'] ?? 0) ?></div>
                <div class="stat-label">Nhân viên đang làm việc</div>
            </div>
            <div class="stat-mini success">
                <i class="fas fa-chart-line stat-icon"></i>
                <div class="stat-number" id="hr-leave-employees"><?= (int)($stats['out_today'] ?? 0) ?></div>
                <div class="stat-label">Nghỉ phép</div>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h3 style="margin-bottom:4px;">BẢNG CÔNG ĐÃ TÍNH TOÁN - THÁNG <span id="hr-dash-month"><?= date('m/Y') ?></span></h3>
        </div>
    </div>
    <table class="table" style="margin-top:14px;">
        <thead>
            <tr>
                <th>Nhân viên<br><small>Name/Dept</small></th>
                <th>Ngày công</th>
                <th>Giờ OT</th>
                <th>Vi phạm<br><small>Late/Early Leaver</small></th>
            </tr>
        </thead>
        <tbody id="hr-dash-payroll-body">
            <tr><td colspan="4" class="empty-state">Đang tải dữ liệu...</td></tr>
        </tbody>
        <tfoot id="hr-dash-payroll-footer"></tfoot>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var monthSpan = document.getElementById('hr-dash-month');
    var payrollBody = document.getElementById('hr-dash-payroll-body');
    var payrollFooter = document.getElementById('hr-dash-payroll-footer');

    function escapeHtml(val) {
        return String(val ?? '').replace(/[&<>"]/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
        });
    }

    function getMonthKey() {
        var now = new Date();
        return now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    }

    function loadPayrollDashboard() {
        var month = getMonthKey();
        fetch('index.php?page=hr-api-payroll&month=' + encodeURIComponent(month), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (!json.success) return;
            var rows = json.data || [];
            var summary = json.summary || {};

            if (!rows.length) {
                payrollBody.innerHTML = '<tr><td colspan="4" class="empty-state">Không có dữ liệu chấm công cho tháng này.</td></tr>';
                payrollFooter.innerHTML = '';
                return;
            }

            payrollBody.innerHTML = rows.map(function (row) {
                var workDays = Number(row.work_days || 0);
                var otHours = Number(row.overtime_hours || 0);
                var dayColor = workDays > 0 ? '#22c55e' : '#ef4444';
                var otDisplay = otHours > 0 ? otHours.toFixed(1) + ' OT' : '0';
                return '<tr>' +
                    '<td><strong>' + escapeHtml(row.hoTen) + '</strong><br><small style="color:#64748b">Name / Dept</small></td>' +
                    '<td><span style="color:' + dayColor + ';font-weight:700;">■</span> ' + workDays + ' (' + otDisplay + ')</td>' +
                    '<td><span style="color:' + (otHours > 0 ? '#ef4444' : '#22c55e') + ';font-weight:700;">●</span> ' + (otHours > 0 ? otHours.toFixed(1) + ' (1.5 OT)' : '0 (OT)') + '</td>' +
                    '<td><span style="color:#ef4444;font-weight:700;">●</span> 1P</td>' +
                    '</tr>';
            }).join('');

            payrollFooter.innerHTML = '<tr class="row-total">' +
                '<td><strong>Tổng toán tháng ' + month.split('-')[1] + '/' + month.split('-')[0] + '</strong></td>' +
                '<td>Tổng công: ' + Number(summary.total_work_days || 0) + '</td>' +
                '<td>Tổng OT: ' + Number(summary.total_overtime_hours || 0) + '</td>' +
                '<td>Tổng đi trễ: 0</td>' +
                '</tr>';
        })
        .catch(function () {});
    }

    loadPayrollDashboard();
});
</script>
