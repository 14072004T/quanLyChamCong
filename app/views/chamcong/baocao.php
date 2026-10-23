<?php
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}

if (!in_array($_SESSION['role'], ['hr', 'manager'], true)) {
    header('Location: index.php?page=home');
    exit();
}

$isHr = $_SESSION['role'] === 'hr';
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$reportActionPage = $isHr ? 'xuat-bao-cao' : 'bao-cao-tong-hop';
$fromDate = $fromDate ?? date('Y-m-01');
$toDate = $toDate ?? date('Y-m-d');
$phongBan = $phongBan ?? '';
$reportRows = $reportRows ?? [];
$payrollRows = $payrollRows ?? [];
$departments = $departments ?? [];
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>

<?php if ($isHr): ?>
<?php
// Group report rows by phongBan
$reportByDept = [];
if (!empty($reportRows)) {
    foreach ($reportRows as $row) {
        $dept = $row['phongBan'] ?: 'Khác';
        if (!isset($reportByDept[$dept])) $reportByDept[$dept] = [];
        $reportByDept[$dept][] = $row;
    }
}
?>
<style>
.hr-report-page {
    --text: #1e293b;
    --muted: #64748b;
    --border: #e2e8f0;
    --primary: #3b82f6;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    background: #f8fafc;
}
.hrr-header {
    margin-bottom: 24px;
}
.hrr-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 8px;
    padding-top: 22px;
}
.hrr-subtitle {
    color: var(--muted);
    margin: 0;
}
.hrr-filters {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
}
.hrr-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: end;
}
.hrr-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.hrr-form-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text);
}
.hrr-input, .hrr-select {
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 0.95rem;
    outline: none;
    transition: border-color 0.2s;
}
.hrr-input:focus, .hrr-select:focus {
    border-color: var(--primary);
}
.hrr-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    color: #fff;
    transition: background 0.2s, transform 0.1s;
    text-decoration: none;
}
.hrr-btn:hover { transform: translateY(-1px); }
.hrr-btn-primary { background: var(--primary); }
.hrr-btn-primary:hover { background: #2563eb; }
.hrr-btn-success { background: var(--success); }
.hrr-btn-success:hover { background: #059669; }
.hrr-btn-warning { background: var(--warning); color: #fff; }
.hrr-btn-warning:hover { background: #d97706; }

.hrr-dept-section {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 12px;
    margin-bottom: 24px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
}
.hrr-dept-header {
    background: #f1f5f9;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border);
}
.hrr-dept-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
}
.hrr-dept-badge {
    background: #e2e8f0;
    color: #475569;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 0.75rem;
}
.hrr-table {
    width: 100%;
    border-collapse: collapse;
}
.hrr-table th, .hrr-table td {
    padding: 12px 20px;
    text-align: left;
    border-bottom: 1px solid var(--border);
}
.hrr-table th {
    background: #f8fafc;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
}
.hrr-table td {
    font-size: 0.95rem;
    color: var(--text);
}
.hrr-table tr:last-child td {
    border-bottom: none;
}
.hrr-table tbody tr:hover {
    background: #f8fafc;
}
.hrr-avatar-text {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #dbeafe;
    color: #1d4ed8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.85rem;
    margin-right: 12px;
}
.hrr-user-cell {
    display: flex;
    align-items: center;
}
.hrr-empty {
    padding: 40px;
    text-align: center;
    color: var(--muted);
}
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container hr-report-page">
        <div class="hrr-header">
            <h1 class="hrr-title">Báo Cáo & Chấm Công HR</h1>
            <p class="hrr-subtitle">Quản lý giờ công và gửi bảng công cho các trưởng phòng phê duyệt</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 20px; border-radius: 8px; padding: 12px 16px; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 20px; border-radius: 8px; padding: 12px 16px; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="hrr-filters">
            <form method="POST" action="index.php?page=<?= htmlspecialchars($reportActionPage) ?>" class="hrr-filter-grid">
                <div class="hrr-form-group">
                    <label>Từ ngày</label>
                    <input type="date" class="hrr-input" name="tuNgay" value="<?= htmlspecialchars($fromDate) ?>" required>
                </div>
                <div class="hrr-form-group">
                    <label>Đến ngày</label>
                    <input type="date" class="hrr-input" name="denNgay" value="<?= htmlspecialchars($toDate) ?>" required>
                </div>
                <div class="hrr-form-group">
                    <label>Phòng ban</label>
                    <select class="hrr-select" name="phongBan">
                        <option value="">Tất cả phòng ban</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>" <?= $phongBan === $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="format" value="excel">
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="hrr-btn hrr-btn-primary" style="flex: 1;"><i class="fas fa-filter"></i> Lọc dữ liệu</button>
                    <button type="submit" class="hrr-btn hrr-btn-success" name="export" value="1" title="Xuất Excel"><i class="fas fa-file-excel"></i> Xuất</button>
                </div>
            </form>
        </div>

        <?php if (empty($reportByDept)): ?>
            <div class="hrr-dept-section hrr-empty">
                <i class="fas fa-folder-open" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
                <h3>Không có dữ liệu</h3>
                <p>Không tìm thấy dữ liệu chấm công trong khoảng thời gian đã chọn.</p>
            </div>
        <?php else: ?>
            <form method="POST" action="index.php?page=gui-bang-cong-phe-duyet" style="margin-bottom: 24px; display: flex; justify-content: flex-end; gap: 15px; align-items: center; background: #fff; padding: 15px 20px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <div class="hrr-form-group" style="flex-direction: row; align-items: center; margin-bottom: 0;">
                    <label style="margin-bottom: 0;">Kỳ chấm công:</label>
                    <input type="month" class="hrr-input" style="padding: 6px 12px; margin-left: 10px;" name="thangNam" value="<?= htmlspecialchars(substr($toDate, 0, 7)) ?>" required>
                </div>
                <button type="submit" class="hrr-btn hrr-btn-warning" onclick="return confirm('Bạn có chắc chắn muốn gửi TẤT CẢ bảng công cho các trưởng phòng phê duyệt?');">
                    <i class="fas fa-paper-plane"></i> Gửi duyệt toàn bộ
                </button>
            </form>

            <?php foreach ($reportByDept as $deptName => $employees): ?>
                <div class="hrr-dept-section">
                    <div class="hrr-dept-header">
                        <div class="hrr-dept-title">
                            <i class="fas fa-building" style="color: #3b82f6;"></i> Phòng: <?= htmlspecialchars($deptName) ?>
                            <span class="hrr-dept-badge"><?= count($employees) ?> nhân viên</span>
                        </div>
                        <form method="POST" action="index.php?page=gui-bang-cong-phe-duyet" style="margin: 0;">
                            <input type="hidden" name="phongBan" value="<?= htmlspecialchars($deptName) ?>">
                            <input type="hidden" name="thangNam" value="<?= htmlspecialchars(substr($toDate, 0, 7)) ?>">
                            <button type="submit" class="hrr-btn hrr-btn-primary" style="padding: 6px 12px; font-size: 0.85rem;" onclick="return confirm('Gửi bảng công phòng <?= htmlspecialchars($deptName) ?> cho Manager duyệt?');">
                                <i class="fas fa-paper-plane"></i> Gửi duyệt phòng này
                            </button>
                        </form>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="hrr-table">
                            <thead>
                                <tr>
                                    <th>Nhân viên</th>
                                    <th>Số ngày công</th>
                                    <th>Lần Check-in</th>
                                    <th>Lần Check-out</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $row): 
                                    $name = $row['hoTen'] ?? 'N/A';
                                    $initials = mb_substr($name, 0, 1);
                                ?>
                                    <tr>
                                        <td>
                                            <div class="hrr-user-cell">
                                                <span class="hrr-avatar-text"><?= htmlspecialchars($initials) ?></span>
                                                <div>
                                                    <strong><?= htmlspecialchars($name) ?></strong><br>
                                                    <span style="font-size: 0.8rem; color: var(--muted);">Mã NV: <?= (int)($row['maND'] ?? 0) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span style="font-weight: 600; color: var(--primary); font-size: 1.1rem;"><?= (float)($row['work_days'] ?? 0) ?></span> ngày</td>
                                        <td><?= (int)($row['checkin_count'] ?? 0) ?></td>
                                        <td><?= (int)($row['checkout_count'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include 'app/views/layouts/footer.php'; return; ?>
<?php endif; ?>

<?php
$managerName = $_SESSION['user']['hoTen'] ?? 'Quản lý';
$managerDept = $_SESSION['user']['phongBan'] ?? '';
$selectedDept = $phongBan !== '' ? $phongBan : ($managerDept ?: ($departments[0] ?? 'Tất cả'));
$fromTs = strtotime($fromDate) ?: strtotime(date('Y-m-01'));
$toTs = strtotime($toDate) ?: strtotime(date('Y-m-d'));
$dayCount = max(1, (int)floor(($toTs - $fromTs) / 86400) + 1);
$totalEmployees = isset($totalEmployees)
    ? (int)$totalEmployees
    : count(array_unique(array_map(function ($row) { return (int)($row['maND'] ?? 0); }, $reportRows)));
$attendanceMetrics = $attendanceMetrics ?? [];
$plannedWorkDays = max((int)($attendanceMetrics['scheduled_days'] ?? 0), 1);
$actualWorkDays = (float)($attendanceMetrics['work_days'] ?? 0);
$dailyPunctuality = $dailyPunctuality ?? [];
$lateDays = array_sum(array_column($dailyPunctuality, 'late'));
$earlyDays = array_sum(array_column($dailyPunctuality, 'early'));
$onTimeDays = max(0, $actualWorkDays - $lateDays);
$absentDays = max(0, round($plannedWorkDays - $actualWorkDays, 1));
$onTimeRate = (float)($attendanceMetrics['attendance_rate'] ?? 0);
$absentRate = (float)($attendanceMetrics['absent_rate'] ?? 0);
$totalOtHours = (float)($attendanceMetrics['total_ot_hours'] ?? 0);

$labels = array_map(function ($row) { return date('d/m', strtotime($row['date'])); }, $dailyPunctuality);
$lateLineValues = array_map(function ($row) { return (int)$row['late']; }, $dailyPunctuality);
$earlyLineValues = array_map(function ($row) { return (int)$row['early']; }, $dailyPunctuality);

$workdayDistribution = $attendanceMetrics['workday_distribution'] ?? ['present' => 0, 'absent' => 0];
$workdayEmployeeTotal = array_sum($workdayDistribution);
$workdayLabels = ['Đã chấm công', 'Vắng mặt'];
$workdayValues = [
    (int)($workdayDistribution['present'] ?? 0),
    (int)($workdayDistribution['absent'] ?? 0),
];
$workdayColors = ['#12b76a', '#94a3b8'];
$workdayPercentages = array_map(function ($value) use ($workdayEmployeeTotal) {
    return $workdayEmployeeTotal > 0 ? round(($value / $workdayEmployeeTotal) * 100, 1) : 0;
}, $workdayValues);

$weekLabels = array_map(function ($week) { return $week['label']; }, $attendanceMetrics['weekly_ot'] ?? []);
$weekOt = array_map(function ($week) { return (float)$week['hours']; }, $attendanceMetrics['weekly_ot'] ?? []);
if (empty($weekLabels)) {
    $weekLabels = ['Không có dữ liệu'];
    $weekOt = [0];
}
$employeePunctuality = $employeePunctuality ?? [];
$topLimit = max(1, min(50, (int)($_GET['top_limit'] ?? 5)));
$topLateRows = $employeePunctuality;
$topEarlyRows = $employeePunctuality;
usort($topLateRows, function ($left, $right) {
    if ((int)$left['late_count'] !== (int)$right['late_count']) {
        return (int)$right['late_count'] <=> (int)$left['late_count'];
    }
    return (int)$right['late_minutes'] <=> (int)$left['late_minutes'];
});
usort($topEarlyRows, function ($left, $right) {
    if ((int)$left['early_count'] !== (int)$right['early_count']) {
        return (int)$right['early_count'] <=> (int)$left['early_count'];
    }
    return (int)$right['early_minutes'] <=> (int)$left['early_minutes'];
});
$topLate = array_slice($topLateRows, 0, $topLimit);
$topEarly = array_slice($topEarlyRows, 0, $topLimit);
$hasLateRows = count($topLate) > 0;
$hasEarlyRows = count($topEarly) > 0;
$updatedAt = date('H:i, d/m/Y');
?>

<style>
.mgr-report-page {
    --text: #172554;
    --muted: #64748b;
    --line: #e5edf7;
    --blue: #2f7cf6;
    --green: #12b76a;
    --orange: #f59e0b;
    --red: #ef4444;
    --purple: #8b5cf6;
    background: #f8fbff;
}
.mgrr-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    margin-bottom: 18px;
}
.mgrr-title {
    margin: 0 0 5px;
    color: #0f172a;
    font-size: 1.55rem;
    font-weight: 800;
}
.mgrr-subtitle {
    margin: 0;
    color: var(--muted);
    font-size: .9rem;
}
.mgrr-head-tools {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.mgrr-chip,
.mgrr-select,
.mgrr-input,
.mgrr-btn {
    min-height: 38px;
    border: 1px solid var(--line);
    border-radius: 7px;
    background: #fff;
    color: #334155;
    font-size: .84rem;
}
.mgrr-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0 12px;
    font-weight: 700;
}
.mgrr-user {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 800;
}
.mgrr-avatar {
    width: 30px;
    height: 30px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #dbeafe, #dcfce7);
    color: #1d4ed8;
    font-size: .75rem;
}
.mgrr-filters {
    display: grid;
    grid-template-columns: 1.1fr 1.25fr 1fr auto auto;
    gap: 14px;
    align-items: end;
    margin-bottom: 18px;
}
.mgrr-field {
    display: grid;
    gap: 6px;
}
.mgrr-field label {
    color: #64748b;
    font-size: .76rem;
    font-weight: 800;
}
.mgrr-select,
.mgrr-input {
    width: 100%;
    padding: 0 11px;
    outline: none;
}
.mgrr-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 14px;
    font-weight: 800;
    cursor: pointer;
    text-decoration: none;
}
.mgrr-btn.primary {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
}
.mgrr-stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 16px;
}
.mgrr-card,
.mgrr-panel {
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
}
.mgrr-card {
    display: flex;
    gap: 13px;
    align-items: center;
    padding: 17px;
    min-height: 92px;
}
.mgrr-icon {
    width: 48px;
    height: 48px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    font-size: 1.25rem;
}
.mgrr-blue { color: #2563eb; background: #dbeafe; }
.mgrr-green { color: #16a34a; background: #dcfce7; }
.mgrr-orange { color: #f59e0b; background: #ffedd5; }
.mgrr-red { color: #ef4444; background: #fee2e2; }
.mgrr-purple { color: #8b5cf6; background: #f3e8ff; }
.mgrr-card small {
    display: block;
    color: #64748b;
    font-weight: 800;
    font-size: .78rem;
}
.mgrr-card strong {
    display: block;
    margin: 3px 0;
    color: #0f172a;
    font-size: 1.55rem;
    line-height: 1;
}
.mgrr-trend.up { color: #12b76a; }
.mgrr-trend.down { color: #ef4444; }
.mgrr-grid {
    display: grid;
    grid-template-columns: 1.4fr .9fr 1fr;
    gap: 14px;
    margin-bottom: 14px;
}
.mgrr-bottom {
    display: grid;
    grid-template-columns: 1.4fr .9fr 1fr;
    gap: 14px;
}
.mgrr-panel {
    padding: 16px;
    min-width: 0;
}
.mgrr-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
}
.mgrr-panel-title {
    color: #0f172a;
    font-weight: 800;
    font-size: .95rem;
}
.mgrr-mini-select {
    min-height: 30px;
    border: 1px solid var(--line);
    border-radius: 7px;
    background: #fff;
    color: #64748b;
    padding: 0 9px;
    font-size: .76rem;
}
.mgrr-chart {
    position: relative;
    height: 230px;
}
.mgrr-chart.short {
    height: 210px;
}
.mgrr-donut-wrap {
    display: grid;
    grid-template-columns: 170px 1fr;
    gap: 8px;
    align-items: center;
}
.mgrr-donut {
    position: relative;
    width: 170px;
    height: 170px;
}
.mgrr-donut-center {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    text-align: center;
    pointer-events: none;
}
.mgrr-donut-center strong {
    display: block;
    color: #172554;
    font-size: 1.5rem;
}
.mgrr-donut-center span {
    color: #64748b;
    font-size: .78rem;
}
.mgrr-legend {
    display: grid;
    gap: 10px;
    font-size: .82rem;
    color: #475569;
}
.mgrr-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 999px;
    margin-right: 7px;
}
.mgrr-table {
    width: 100%;
    border-collapse: collapse;
}
.mgrr-table th,
.mgrr-table td {
    padding: 10px 8px;
    border-bottom: 1px solid #f1f5f9;
    font-size: .8rem;
    color: #334155;
    text-align: left;
}
.mgrr-table th {
    color: #64748b;
    text-transform: uppercase;
    font-size: .68rem;
}
.mgrr-top-table thead tr:first-child th {
    padding-bottom: 6px;
}
.mgrr-top-table thead tr:last-child th {
    color: #475569;
}
.mgrr-top-group td {
    vertical-align: middle;
}
.mgrr-table strong {
    color: #0f172a;
}
.mgrr-rank {
    width: 24px;
    height: 24px;
    border-radius: 999px;
    display: inline-grid;
    place-items: center;
    background: #f1f5f9;
    color: #475569;
    font-weight: 800;
}
.mgrr-person {
    display: flex;
    align-items: center;
    gap: 8px;
}
.mgrr-person .mgrr-avatar {
    width: 28px;
    height: 28px;
    font-size: .68rem;
}
.mgrr-ghiChu {
    margin-top: 14px;
    display: flex;
    gap: 10px;
    align-items: flex-start;
    padding: 13px 14px;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    background: #eff6ff;
    color: #475569;
    font-size: .82rem;
}
.mgrr-updated {
    margin-top: 12px;
    text-align: right;
    color: #64748b;
    font-size: .78rem;
}
@media (max-width: 1280px) {
    .mgrr-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .mgrr-grid,
    .mgrr-bottom { grid-template-columns: 1fr; }
}
@media (max-width: 760px) {
    .mgrr-head,
    .mgrr-head-tools { align-items: flex-start; justify-content: flex-start; }
    .mgrr-head { flex-direction: column; }
    .mgrr-filters { grid-template-columns: 1fr; }
    .mgrr-stats { grid-template-columns: 1fr; }
    .mgrr-donut-wrap { grid-template-columns: 1fr; justify-items: center; }
}

/* ===== EXPORT REPORT MODAL ===== */
.export-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 10000;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(6px);
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.export-modal-overlay.active { display: flex; }
.export-modal {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 32px 80px rgba(15, 23, 42, 0.25);
    width: 100%;
    max-width: 660px;
    overflow: hidden;
    animation: slideUpFade 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes slideUpFade {
    from { opacity: 0; transform: translateY(30px) scale(0.95); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.export-modal-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);
    padding: 24px 28px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.export-modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
}
.export-modal-header p {
    margin: 6px 0 0;
    font-size: .84rem;
    opacity: .8;
}
.export-modal-close {
    background: rgba(255,255,255,.15);
    border: none;
    color: #fff;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.15rem;
    display: grid;
    place-items: center;
    transition: background .2s;
    flex-shrink: 0;
}
.export-modal-close:hover { background: rgba(255,255,255,.3); }
.export-modal-body { padding: 24px 28px; }
.export-section-label {
    font-size: .75rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 7px;
}
.report-types-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 22px;
}
.report-type-card {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px 16px;
    cursor: pointer;
    transition: all .2s;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #fff;
    position: relative;
}
.report-type-card:hover {
    border-color: #93c5fd;
    background: #eff6ff;
    transform: translateY(-1px);
}
.report-type-card.selected {
    border-color: #2563eb;
    background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(37,99,235,.12);
}
.report-type-card input[type=radio] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.report-type-icon {
    width: 38px;
    height: 38px;
    border-radius: 9px;
    display: grid;
    place-items: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    margin-top: 1px;
}
.report-type-info strong {
    display: block;
    font-size: .88rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 2px;
}
.report-type-info span {
    font-size: .76rem;
    color: #64748b;
    line-height: 1.4;
}
.check-indicator {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #cbd5e1;
    background: #fff;
    display: grid;
    place-items: center;
    transition: all .2s;
    font-size: .7rem;
    color: transparent;
}
.report-type-card.selected .check-indicator {
    border-color: #2563eb;
    background: #2563eb;
    color: #fff;
}
.format-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 24px;
}
.format-card {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 16px;
    cursor: pointer;
    transition: all .2s;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fff;
    position: relative;
}
.format-card:hover { border-color: #93c5fd; background: #eff6ff; }
.format-card.selected { border-color: #2563eb; background: #eff6ff; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
.format-card input[type=radio] { position: absolute; opacity: 0; pointer-events: none; }
.format-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.format-card strong {
    font-size: .88rem;
    font-weight: 700;
    color: #0f172a;
}
.format-card span {
    font-size: .75rem;
    color: #64748b;
    display: block;
}
.export-modal-footer {
    padding: 16px 28px 24px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #f1f5f9;
}
.export-btn-cancel {
    padding: 10px 20px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    color: #64748b;
    font-weight: 600;
    font-size: .9rem;
    cursor: pointer;
    transition: all .2s;
}
.export-btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; }
.export-btn-submit {
    padding: 10px 24px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    font-weight: 700;
    font-size: .9rem;
    cursor: pointer;
    transition: all .2s;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(37,99,235,.35);
}
.export-btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(37,99,235,.45); }
.export-btn-submit:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.mgrr-btn.export {
    background: linear-gradient(135deg, #059669, #10b981);
    color: #fff;
    border-color: #059669;
    box-shadow: 0 2px 8px rgba(5,150,105,.3);
}
.mgrr-btn.export:hover { background: linear-gradient(135deg, #047857, #059669); }
@media (max-width: 600px) {
    .report-types-grid { grid-template-columns: 1fr; }
    .format-grid { grid-template-columns: 1fr; }
    .export-modal-body { padding: 16px; }
    .export-modal-header { padding: 18px 16px; }
    .export-modal-footer { padding: 12px 16px 18px; }
}
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container mgr-report-page">
        <div class="mgrr-head">
            <div>
                <h1 class="mgrr-title" style="padding-top: 22px;" >Báo cáo tổng hợp</h1>
                <p class="mgrr-subtitle">Tổng hợp tình hình chấm công và nhân sự của phòng <?= htmlspecialchars($selectedDept) ?></p>
            </div>
        </div>

        <form class="mgrr-filters" method="GET" action="index.php">
            <input type="hidden" name="page" value="bao-cao-tong-hop">
            <div class="mgrr-field">
                <label>Phòng ban của tôi</label>
                <select class="mgrr-select" name="phongBan">
                    <option value="">Tất cả phòng ban</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>" <?= $phongBan === $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mgrr-field">
                <label>Khoảng thời gian</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    <input class="mgrr-input" type="date" name="tuNgay" value="<?= htmlspecialchars($fromDate) ?>">
                    <input class="mgrr-input" type="date" name="denNgay" value="<?= htmlspecialchars($toDate) ?>">
                </div>
            </div>
            <div class="mgrr-field">
                <label>So sánh với kỳ trước</label>
                <select class="mgrr-select" name="compare">
                    <option>Không so sánh</option>
                    <option>Tháng trước</option>
                    <option>Quý trước</option>
                </select>
            </div>
            <button class="mgrr-btn primary" type="submit"><i class="fas fa-filter"></i> Lọc</button>
            <button type="button" class="mgrr-btn export" id="btnOpenExportModal" onclick="openExportModal()">
                <i class="fas fa-file-export"></i> Xuất báo cáo
            </button>
        </form>

        <!-- EXPORT REPORT MODAL -->
        <div id="exportReportModal" class="export-modal-overlay" onclick="if(event.target===this)closeExportModal()">
            <div class="export-modal">
                <div class="export-modal-header">
                    <div>
                        <h3><i class="fas fa-file-export"></i> Xuất báo cáo</h3>
                        <p>Chọn loại báo cáo và định dạng file muốn xuất</p>
                    </div>
                    <button class="export-modal-close" onclick="closeExportModal()">&#x2715;</button>
                </div>
                <div class="export-modal-body">
                    <div class="export-section-label"><i class="fas fa-list-ul"></i> Loại báo cáo</div>
                    <div class="report-types-grid" id="reportTypesGrid">
                        <label class="report-type-card selected" onclick="selectReportType(this, 'tonghop')">
                            <input type="radio" name="reportType" value="tonghop" checked>
                            <div class="report-type-icon" style="background:#dbeafe;color:#1d4ed8"><i class="fas fa-chart-bar"></i></div>
                            <div class="report-type-info">
                                <strong>Tổng hợp chấm công</strong>
                                <span>Phòng ban, ngày công kế hoạch, thực tế, tỷ lệ đi làm, vắng, OT</span>
                            </div>
                            <span class="check-indicator"><i class="fas fa-check"></i></span>
                        </label>
                        <label class="report-type-card" onclick="selectReportType(this, 'chitiet')">
                            <input type="radio" name="reportType" value="chitiet">
                            <div class="report-type-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-user-clock"></i></div>
                            <div class="report-type-info">
                                <strong>Chi tiết chấm công NV</strong>
                                <span>Mã NV, ngày, ca làm, giờ vào/ra, giờ làm, OT, trạng thái</span>
                            </div>
                            <span class="check-indicator"><i class="fas fa-check"></i></span>
                        </label>
                        <label class="report-type-card" onclick="selectReportType(this, 'vipham')">
                            <input type="radio" name="reportType" value="vipham">
                            <div class="report-type-icon" style="background:#ffedd5;color:#c2410c"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="report-type-info">
                                <strong>Đi trễ, về sớm, vắng mặt</strong>
                                <span>Ngày vi phạm, loại vi phạm, giờ theo ca vs thực tế, phút lệch</span>
                            </div>
                            <span class="check-indicator"><i class="fas fa-check"></i></span>
                        </label>
                        <label class="report-type-card" onclick="selectReportType(this, 'yeucau')">
                            <input type="radio" name="reportType" value="yeucau">
                            <div class="report-type-icon" style="background:#f3e8ff;color:#7c3aed"><i class="fas fa-file-alt"></i></div>
                            <div class="report-type-info">
                                <strong>Tổng hợp đơn yêu cầu</strong>
                                <span>Nhân viên, loại đơn, thời gian, nội dung, trạng thái, người duyệt</span>
                            </div>
                            <span class="check-indicator"><i class="fas fa-check"></i></span>
                        </label>
                        <label class="report-type-card" onclick="selectReportType(this, 'tangca')">
                            <input type="radio" name="reportType" value="tangca">
                            <div class="report-type-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-stopwatch"></i></div>
                            <div class="report-type-info">
                                <strong>Báo cáo tăng ca (OT)</strong>
                                <span>Nhân viên, ngày OT, thời gian, tổng giờ OT, trạng thái phê duyệt</span>
                            </div>
                            <span class="check-indicator"><i class="fas fa-check"></i></span>
                        </label>
                    </div>

                    <div class="export-section-label"><i class="fas fa-file"></i> Định dạng file</div>
                    <div class="format-grid">
                        <label class="format-card selected" onclick="selectFormat(this, 'excel')">
                            <input type="radio" name="exportFormat" value="excel" checked>
                            <div class="format-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-file-excel"></i></div>
                            <div>
                                <strong>Excel (.xlsx)</strong>
                                <span>Dữ liệu chi tiết, dễ chỉnh sửa</span>
                            </div>
                            <span class="check-indicator" style="margin-left:auto"><i class="fas fa-check"></i></span>
                        </label>
                        <label class="format-card" onclick="selectFormat(this, 'pdf')">
                            <input type="radio" name="exportFormat" value="pdf">
                            <div class="format-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-file-pdf"></i></div>
                            <div>
                                <strong>PDF (.pdf)</strong>
                                <span>In ấn, lưu trữ chuyên nghiệp</span>
                            </div>
                            <span class="check-indicator" style="margin-left:auto"><i class="fas fa-check"></i></span>
                        </label>
                    </div>
                </div>
                <div class="export-modal-footer">
                    <button type="button" class="export-btn-cancel" onclick="closeExportModal()">Hủy</button>
                    <button type="button" class="export-btn-submit" id="btnDoExport" onclick="doExportReport()">
                        <i class="fas fa-download"></i> Xuất ngay
                    </button>
                </div>
            </div>
        </div>

        <div class="mgrr-stats">
            <div class="mgrr-card">
                <span class="mgrr-icon mgrr-blue"><i class="fas fa-users"></i></span>
                <div><small>Tổng nhân viên</small><strong><?= (int)$totalEmployees ?></strong><small>Nhân viên</small></div>
            </div>
            <div class="mgrr-card">
                <span class="mgrr-icon mgrr-green"><i class="far fa-calendar-check"></i></span>
                <div><small>Ngày công thực tế</small><strong><?= number_format($actualWorkDays, 0) ?></strong><small class="mgrr-trend up"><i class="fas fa-arrow-up"></i> 8.7% so với kỳ trước</small></div>
            </div>
            <div class="mgrr-card">
                <span class="mgrr-icon mgrr-orange"><i class="fas fa-clock"></i></span>
                <div><small>Tỷ lệ đi làm</small><strong><?= number_format($onTimeRate, 1) ?>%</strong><small class="mgrr-trend up"><i class="fas fa-arrow-up"></i> 4.3% so với kỳ trước</small></div>
            </div>
            <div class="mgrr-card">
                <span class="mgrr-icon mgrr-red"><i class="fas fa-user-times"></i></span>
                <div><small>Tỷ lệ vắng mặt</small><strong><?= number_format($absentRate, 1) ?>%</strong><small class="mgrr-trend down"><i class="fas fa-arrow-down"></i> 0.6% so với kỳ trước</small></div>
            </div>
            <div class="mgrr-card">
                <span class="mgrr-icon mgrr-purple"><i class="fas fa-stopwatch"></i></span>
                <div><small>Tổng giờ OT</small><strong><?= number_format($totalOtHours, 1) ?></strong><small class="mgrr-trend up"><i class="fas fa-arrow-up"></i> 12.5% so với kỳ trước</small></div>
            </div>
        </div>

        <div class="mgrr-grid">
            <section class="mgrr-panel">
                <div class="mgrr-panel-head">
                    <div class="mgrr-panel-title">Số lượng đi trễ, về sớm theo ngày</div>
                    <select class="mgrr-mini-select"><option>Theo ngày</option></select>
                </div>
                <div class="mgrr-chart"><canvas id="mgrrLineChart"></canvas></div>
            </section>

            <section class="mgrr-panel">
                <div class="mgrr-panel-head">
                    <div class="mgrr-panel-title">Tỷ lệ chấm công theo ca tính công</div>
                </div>
                <div class="mgrr-donut-wrap">
                    <div class="mgrr-donut">
                        <canvas id="mgrrDonutChart"></canvas>
                        <div class="mgrr-donut-center"><div><strong><?= number_format($workdayEmployeeTotal, 0) ?></strong><span>Lượt ca tính công</span></div></div>
                    </div>
                    <div class="mgrr-legend">
                        <?php foreach ($workdayLabels as $index => $label): ?>
                            <div><span class="mgrr-dot" style="background:<?= $workdayColors[$index] ?>"></span><?= $label ?> <strong><?= (int)$workdayValues[$index] ?></strong> <small>(<?= number_format($workdayPercentages[$index], 1) ?>%)</small></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="mgrr-panel">
                <div class="mgrr-panel-head">
                    <div class="mgrr-panel-title">Tổng giờ OT đã duyệt theo tuần</div>
                    <select class="mgrr-mini-select"><option>Theo tuần</option></select>
                </div>
                <div class="mgrr-chart"><canvas id="mgrrBarChart"></canvas></div>
            </section>
        </div>

        <div class="mgrr-bottom">
            <section class="mgrr-panel">
                <div class="mgrr-panel-head">
                    <div class="mgrr-panel-title">Top nhân viên đi trễ, về sớm</div>
                    <div style="display:flex;align-items:center;gap:8px">
                        <button type="button" onclick="openTopDetail()" style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border:1px solid #2563eb;border-radius:6px;background:#eff6ff;color:#2563eb;font-size:.78rem;font-weight:700;cursor:pointer">
                            <i class="fas fa-expand-alt"></i> Xem chi tiết
                        </button>
                        <form method="GET" action="index.php" style="display:flex;align-items:center;gap:6px">
                            <input type="hidden" name="page" value="<?= htmlspecialchars($reportActionPage) ?>">
                            <input type="hidden" name="tuNgay" value="<?= htmlspecialchars($fromDate) ?>">
                            <input type="hidden" name="denNgay" value="<?= htmlspecialchars($toDate) ?>">
                            <input type="hidden" name="phongBan" value="<?= htmlspecialchars($phongBan) ?>">
                            <label for="top-limit" style="font-size:.78rem;color:#64748b">Top</label>
                            <input id="top-limit" name="top_limit" type="number" min="1" max="50" value="<?= $topLimit ?>" style="width:62px;padding:6px 8px;border:1px solid #dbe4f0;border-radius:6px">
                            <button type="submit" class="mgrr-btn primary" style="padding:7px 10px;font-size:.78rem"><i class="fas fa-filter"></i> Lọc</button>
                        </form>
                    </div>
                </div>
                <table class="mgrr-table mgrr-top-table">
                    <thead>
                        <tr>
                            <th rowspan="2">STT</th>
                            <th colspan="3">Đi trễ</th>
                            <th colspan="3">Về sớm</th>
                        </tr>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Số lần</th>
                            <th>Phút TB</th>
                            <th>Nhân viên</th>
                            <th>Số lần</th>
                            <th>Phút TB</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($idx = 0; $idx < $topLimit; $idx++): ?>
                            <?php
                            $lateRow = $topLate[$idx] ?? null;
                            $earlyRow = $topEarly[$idx] ?? null;
                            $lateName = $lateRow['hoTen'] ?? 'Nhân viên';
                            $earlyName = $earlyRow['hoTen'] ?? 'Nhân viên';
                            ?>
                            <tr class="mgrr-top-group">
                                <td><span class="mgrr-rank"><?= $idx + 1 ?></span></td>
                                <?php if ($lateRow): ?>
                                    <td><div class="mgrr-person"><span class="mgrr-avatar"><?= htmlspecialchars(mb_substr($lateName, 0, 1)) ?></span><strong><?= htmlspecialchars($lateName) ?></strong></div></td>
                                    <td><?= (int)($lateRow['late_count'] ?? 0) ?></td>
                                    <td style="color:#ef4444;font-weight:800"><?php $lc = max(1,(int)($lateRow['late_count']??1)); echo round((int)($lateRow['late_minutes']??0)/$lc); ?> phút</td>
                                <?php elseif (!$hasLateRows && $idx === 0): ?>
                                    <td colspan="3" style="text-align:center;color:#64748b">Không có dữ liệu</td>
                                <?php else: ?>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                <?php endif; ?>
                                <?php if ($earlyRow): ?>
                                    <td><div class="mgrr-person"><span class="mgrr-avatar"><?= htmlspecialchars(mb_substr($earlyName, 0, 1)) ?></span><strong><?= htmlspecialchars($earlyName) ?></strong></div></td>
                                    <td><?= (int)($earlyRow['early_count'] ?? 0) ?></td>
                                    <td style="color:#ef4444;font-weight:800"><?php $ec = max(1,(int)($earlyRow['early_count']??1)); echo round((int)($earlyRow['early_minutes']??0)/$ec); ?> phút</td>
                                <?php elseif (!$hasEarlyRows && $idx === 0): ?>
                                    <td colspan="3" style="text-align:center;color:#64748b">Không có dữ liệu</td>
                                <?php else: ?>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                <?php endif; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </section>

            <section class="mgrr-panel">
                <div class="mgrr-panel-head"><div class="mgrr-panel-title">Tình hình chấm công của <?= htmlspecialchars($selectedDept) ?></div></div>
                <table class="mgrr-table">
                    <thead><tr><th>Chỉ số</th><th>Giá trị</th><th>Tỷ lệ</th><th>So với trước</th></tr></thead>
                    <tbody>
                        <tr><td>Ngày công theo kế hoạch</td><td><strong><?= number_format($plannedWorkDays, 0) ?></strong></td><td>-</td><td>-</td></tr>
                        <tr><td>Ngày công thực tế</td><td><strong><?= number_format($actualWorkDays, 0) ?></strong></td><td><?= number_format(($actualWorkDays / $plannedWorkDays) * 100, 1) ?>%</td><td style="color:#12b76a">↑ 8.7%</td></tr>
                        <tr><td>Đi làm đủ giờ</td><td><strong><?= number_format($onTimeDays, 0) ?></strong></td><td><?= number_format($onTimeRate, 1) ?>%</td><td style="color:#12b76a">↑ 9.2%</td></tr>
                        <tr><td>Đi trễ</td><td><strong><?= number_format($lateDays, 0) ?></strong></td><td><?= $actualWorkDays > 0 ? number_format(($lateDays / $actualWorkDays) * 100, 1) : '0.0' ?>%</td><td style="color:#ef4444">↓ 0.9%</td></tr>
                        <tr><td>Về sớm</td><td><strong><?= number_format($earlyDays, 0) ?></strong></td><td><?= $actualWorkDays > 0 ? number_format(($earlyDays / $actualWorkDays) * 100, 1) : '0.0' ?>%</td><td style="color:#ef4444">↓ 0.3%</td></tr>
                        <tr><td>Vắng mặt</td><td><strong><?= number_format($absentDays, 0) ?></strong></td><td><?= number_format($absentRate, 1) ?>%</td><td style="color:#ef4444">↓ 0.6%</td></tr>
                        <tr><td>Tổng giờ OT</td><td><strong><?= number_format($totalOtHours, 1) ?> giờ</strong></td><td>-</td><td style="color:#12b76a">↑ 12.5%</td></tr>
                    </tbody>
                </table>
            </section>

            <section class="mgrr-panel">
                <div class="mgrr-panel-head"><div class="mgrr-panel-title">Tổng hợp đơn yêu cầu</div></div>
                <?php
                $rs = $requestSummary ?? ['leave'=>['approved'=>0,'rejected'=>0,'pending'=>0],'ot'=>['approved'=>0,'rejected'=>0,'pending'=>0],'correction'=>['approved'=>0,'rejected'=>0,'pending'=>0]];
                $totalApproved = $rs['leave']['approved'] + $rs['ot']['approved'] + $rs['correction']['approved'];
                $totalRejected = $rs['leave']['rejected'] + $rs['ot']['rejected'] + $rs['correction']['rejected'];
                $totalPending  = $rs['leave']['pending']  + $rs['ot']['pending']  + $rs['correction']['pending'];
                ?>
                <table class="mgrr-table">
                    <thead><tr><th>Loại yêu cầu</th><th>Đã duyệt</th><th>Từ chối</th><th>Đang chờ</th></tr></thead>
                    <tbody>
                        <tr><td>Nghỉ phép</td><td style="color:#16a34a;font-weight:700"><?= $rs['leave']['approved'] ?></td><td style="color:#ef4444;font-weight:700"><?= $rs['leave']['rejected'] ?></td><td style="color:#f59e0b;font-weight:700"><?= $rs['leave']['pending'] ?></td></tr>
                        <tr><td>Tăng ca (OT)</td><td style="color:#16a34a;font-weight:700"><?= $rs['ot']['approved'] ?></td><td style="color:#ef4444;font-weight:700"><?= $rs['ot']['rejected'] ?></td><td style="color:#f59e0b;font-weight:700"><?= $rs['ot']['pending'] ?></td></tr>
                        <tr><td>Điều chỉnh công</td><td style="color:#16a34a;font-weight:700"><?= $rs['correction']['approved'] ?></td><td style="color:#ef4444;font-weight:700"><?= $rs['correction']['rejected'] ?></td><td style="color:#f59e0b;font-weight:700"><?= $rs['correction']['pending'] ?></td></tr>
                        <tr style="border-top:2px solid #e2e8f0"><td><strong>Tổng</strong></td><td><strong style="color:#16a34a"><?= $totalApproved ?></strong></td><td><strong style="color:#ef4444"><?= $totalRejected ?></strong></td><td><strong style="color:#f59e0b"><?= $totalPending ?></strong></td></tr>
                    </tbody>
                </table>
            </section>
        </div>

        <div class="mgrr-updated"><i class="far fa-clock"></i> Dữ liệu được cập nhật lúc <?= htmlspecialchars($updatedAt) ?></div>
    </div>
</div>

<!-- ===== POPUP CHI TIẾT TOP ĐI TRỄ / VỀ SỚM ===== -->
<?php
// Chuẩn bị JSON cho toàn bộ danh sách (sắp xếp theo đi trễ)
$fullLateJson  = json_encode($topLateRows,  JSON_UNESCAPED_UNICODE);
$fullEarlyJson = json_encode($topEarlyRows, JSON_UNESCAPED_UNICODE);
$fromDateLabel = date('d/m/Y', strtotime($fromDate));
$toDateLabel   = date('d/m/Y', strtotime($toDate));
?>
<div id="top-detail-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.55);backdrop-filter:blur(3px);overflow-y:auto;padding:30px 16px">
    <div style="max-width:1100px;margin:0 auto;background:#fff;border-radius:14px;box-shadow:0 25px 60px rgba(0,0,0,.2);overflow:hidden">
        <div style="background:linear-gradient(135deg,#1e40af,#2563eb);color:#fff;padding:20px 24px;display:flex;justify-content:space-between;align-items:center">
            <div>
                <h2 style="margin:0;font-size:1.2rem;font-weight:800"><i class="fas fa-chart-bar"></i> Chi tiết đi trễ & về sớm</h2>
                <p style="margin:4px 0 0;font-size:.82rem;opacity:.85">Kỳ: <?= $fromDateLabel ?> — <?= $toDateLabel ?></p>
            </div>
            <div style="display:flex;gap:10px;align-items:center">
                <button onclick="exportTopDetail()" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:7px;border:none;background:#fff;color:#1e40af;font-weight:700;font-size:.82rem;cursor:pointer">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </button>
                <button onclick="closeTopDetail()" style="background:rgba(255,255,255,.2);border:none;color:#fff;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1.1rem">&times;</button>
            </div>
        </div>
        <div style="padding:16px 24px;border-bottom:1px solid #e2e8f0;display:flex;gap:10px">
            <button id="tab-late" onclick="switchTab('late')" style="padding:7px 18px;border-radius:20px;border:none;background:#2563eb;color:#fff;font-weight:700;cursor:pointer;font-size:.85rem">Đi trễ</button>
            <button id="tab-early" onclick="switchTab('early')" style="padding:7px 18px;border-radius:20px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-weight:700;cursor:pointer;font-size:.85rem">Về sớm</button>
        </div>
        <div style="overflow-x:auto;max-height:65vh">
            <table id="detail-table" style="width:100%;border-collapse:collapse;font-size:.875rem">
                <thead style="position:sticky;top:0;z-index:1">
                    <tr style="background:#f8fafc">
                        <th style="padding:12px 16px;text-align:left;color:#64748b;font-size:.75rem;font-weight:700;text-transform:uppercase;border-bottom:2px solid #e2e8f0">STT</th>
                        <th style="padding:12px 16px;text-align:left;color:#64748b;font-size:.75rem;font-weight:700;text-transform:uppercase;border-bottom:2px solid #e2e8f0">Nhân viên</th>
                        <th style="padding:12px 16px;text-align:left;color:#64748b;font-size:.75rem;font-weight:700;text-transform:uppercase;border-bottom:2px solid #e2e8f0">Phòng ban</th>
                        <th id="col-count" style="padding:12px 16px;text-align:center;color:#64748b;font-size:.75rem;font-weight:700;text-transform:uppercase;border-bottom:2px solid #e2e8f0">Số lần</th>
                        <th id="col-mins" style="padding:12px 16px;text-align:center;color:#64748b;font-size:.75rem;font-weight:700;text-transform:uppercase;border-bottom:2px solid #e2e8f0">Tổng phút</th>
                    </tr>
                </thead>
                <tbody id="detail-tbody"></tbody>
            </table>
        </div>
        <div style="padding:14px 24px;border-top:1px solid #e2e8f0;background:#f8fafc;display:flex;justify-content:space-between;align-items:center">
            <span id="detail-count" style="color:#64748b;font-size:.82rem"></span>
            <button onclick="closeTopDetail()" style="padding:8px 20px;border-radius:7px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-weight:600;cursor:pointer">Đóng</button>
        </div>
    </div>
</div>

<script>
var _topLate  = <?= $fullLateJson ?> || [];
var _topEarly = <?= $fullEarlyJson ?> || [];
var _currentTab = 'late';

function openTopDetail() {
    document.getElementById('top-detail-modal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    switchTab('late');
}
function closeTopDetail() {
    document.getElementById('top-detail-modal').style.display = 'none';
    document.body.style.overflow = '';
}
function switchTab(tab) {
    _currentTab = tab;
    var data = tab === 'late' ? _topLate : _topEarly;
    document.getElementById('tab-late').style.cssText  = tab === 'late'  ? 'padding:7px 18px;border-radius:20px;border:none;background:#2563eb;color:#fff;font-weight:700;cursor:pointer;font-size:.85rem' : 'padding:7px 18px;border-radius:20px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-weight:700;cursor:pointer;font-size:.85rem';
    document.getElementById('tab-early').style.cssText = tab === 'early' ? 'padding:7px 18px;border-radius:20px;border:none;background:#ef4444;color:#fff;font-weight:700;cursor:pointer;font-size:.85rem' : 'padding:7px 18px;border-radius:20px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-weight:700;cursor:pointer;font-size:.85rem';
    document.getElementById('col-count').textContent = tab === 'late' ? 'Số lần trễ' : 'Số lần về sớm';
    document.getElementById('col-mins').textContent  = tab === 'late' ? 'Phút TB trễ' : 'Phút TB sớm';
    var tbody = document.getElementById('detail-tbody');
    tbody.innerHTML = '';
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="padding:30px;text-align:center;color:#94a3b8">Không có dữ liệu</td></tr>';
        document.getElementById('detail-count').textContent = 'Không có dữ liệu';
        return;
    }
    var countKey = tab === 'late' ? 'late_count' : 'early_count';
    var minsKey  = tab === 'late' ? 'late_minutes' : 'early_minutes';
    var color    = tab === 'late' ? '#ef4444' : '#f59e0b';
    data.forEach(function(row, i) {
        var name = row.hoTen || 'Nhân viên';
        var initial = name.charAt(0);
        var dept = row.phongBan || '-';
        var cnt  = parseInt(row[countKey] || 0);
        var totalMins = parseInt(row[minsKey] || 0);
        var avgMins = cnt > 0 ? Math.round(totalMins / cnt) : 0;
        var rank = i < 3 ? ['🥇','🥈','🥉'][i] : (i + 1);
        tbody.innerHTML += '<tr style="border-bottom:1px solid #f1f5f9"' + (i % 2 === 0 ? '' : ' style="background:#f8fafc;border-bottom:1px solid #f1f5f9"') + '>'
            + '<td style="padding:11px 16px;font-weight:700;font-size:.9rem">' + rank + '</td>'
            + '<td style="padding:11px 16px"><div style="display:flex;align-items:center;gap:9px">'
            + '<span style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#dbeafe,#dcfce7);color:#1d4ed8;display:grid;place-items:center;font-weight:700;font-size:.8rem">' + initial + '</span>'
            + '<strong>' + esc(name) + '</strong></div></td>'
            + '<td style="padding:11px 16px;color:#64748b">' + esc(dept) + '</td>'
            + '<td style="padding:11px 16px;text-align:center;font-weight:700">' + cnt + '</td>'
            + '<td style="padding:11px 16px;text-align:center;font-weight:800;color:' + color + '">' + avgMins + ' phút</td>'
            + '</tr>';
    });
    document.getElementById('detail-count').textContent = 'Tổng: ' + data.length + ' nhân viên';
}
function esc(s) {
    return String(s || '').replace(/[&<>"]/g, function(c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];
    });
}
function exportTopDetail() {
    var data = _currentTab === 'late' ? _topLate : _topEarly;
    var countKey = _currentTab === 'late' ? 'late_count' : 'early_count';
    var minsKey  = _currentTab === 'late' ? 'late_minutes' : 'early_minutes';
    var header   = _currentTab === 'late' ? ['STT','Ho ten','Phong ban','So lan tre','Phut TB tre'] : ['STT','Ho ten','Phong ban','So lan ve som','Phut TB ve som'];
    var rows = [header];
    data.forEach(function(row, i) {
        var cKey = _currentTab === 'late' ? 'late_count' : 'early_count';
        var mKey = _currentTab === 'late' ? 'late_minutes' : 'early_minutes';
        var cnt = parseInt(row[cKey]||0);
        var avg = cnt > 0 ? Math.round(parseInt(row[mKey]||0) / cnt) : 0;
        rows.push([(i+1), row.hoTen || '', row.phongBan || '', cnt, avg]);
    });
    var csv = rows.map(function(r){ return r.join(','); }).join('\n');
    var bom = '\uFEFF';
    var blob = new Blob([bom + csv], {type:'text/csv;charset=utf-8;'});
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'top_' + _currentTab + '_<?= date('Ymd', strtotime($fromDate)) ?>_<?= date('Ymd', strtotime($toDate)) ?>.csv';
    a.click();
    URL.revokeObjectURL(url);
}
document.getElementById('top-detail-modal').addEventListener('click', function(e) {
    if (e.target === this) closeTopDetail();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var lineLabels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
    var lateLineValues = <?= json_encode($lateLineValues, JSON_UNESCAPED_UNICODE) ?>;
    var earlyLineValues = <?= json_encode($earlyLineValues, JSON_UNESCAPED_UNICODE) ?>;
    var donutValues = <?= json_encode($workdayValues, JSON_UNESCAPED_UNICODE) ?>;
    var weekLabels = <?= json_encode($weekLabels, JSON_UNESCAPED_UNICODE) ?>;
    var weekOt = <?= json_encode($weekOt, JSON_UNESCAPED_UNICODE) ?>;

    new Chart(document.getElementById('mgrrLineChart'), {
        type: 'line',
        data: {
            labels: lineLabels,
            datasets: [{
                label: 'Đi trễ',
                data: lateLineValues,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245,158,11,.08)',
                fill: true,
                tension: .35,
                pointRadius: 3,
                pointBackgroundColor: '#f59e0b'
            }, {
                label: 'Về sớm',
                data: earlyLineValues,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239,68,68,.08)',
                fill: false,
                tension: .35,
                pointRadius: 3,
                pointBackgroundColor: '#ef4444'
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: true, position: 'bottom' } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b' }, grid: { color: '#edf2f7' } },
                x: { ticks: { color: '#64748b' }, grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('mgrrDonutChart'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: donutValues,
                backgroundColor: <?= json_encode($workdayColors, JSON_UNESCAPED_UNICODE) ?>,
                borderWidth: 3,
                borderColor: '#fff'
            }]
        },
        options: { cutout: '68%', plugins: { legend: { display: false } }, maintainAspectRatio: false }
    });

    new Chart(document.getElementById('mgrrBarChart'), {
        type: 'bar',
        data: {
            labels: weekLabels,
            datasets: [{
                label: 'Giờ OT đã duyệt',
                data: weekOt,
                backgroundColor: '#2f7cf6',
                borderRadius: 5,
                maxBarThickness: 28
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: function(v) { return v + 'h'; }, color: '#64748b' }, grid: { color: '#edf2f7' } },
                x: { ticks: { color: '#64748b' }, grid: { display: false } }
            }
        }
    });

    function renderRequestSummary(rows) {
        var map = {
            leave: { label: 'Nghỉ phép', approved: 0, rejected: 0, pending: 0 },
            correction: { label: 'Điều chỉnh công', approved: 0, rejected: 0, pending: 0 },
            ot: { label: 'Tăng ca', approved: 0, rejected: 0, pending: 0 }
        };
        rows.forEach(function (row) {
            var type = row.request_type || 'leave';
            var trangThai = row.trangThai || 'pending';
            if (map[type] && map[type][trangThai] !== undefined) map[type][trangThai]++;
        });
        var total = { approved: 0, rejected: 0, pending: 0 };
        var html = ['leave', 'correction', 'ot'].map(function (type) {
            total.approved += map[type].approved;
            total.rejected += map[type].rejected;
            total.pending += map[type].pending;
            return '<tr><td>' + map[type].label + '</td><td>' + map[type].approved + '</td><td>' + map[type].rejected + '</td><td>' + map[type].pending + '</td></tr>';
        }).join('');
        html += '<tr><td><strong>Tổng</strong></td><td><strong>' + total.approved + '</strong></td><td><strong>' + total.rejected + '</strong></td><td><strong>' + total.pending + '</strong></td></tr>';
        document.getElementById('mgrr-request-summary').innerHTML = html;
    }

    var params = new URLSearchParams({
        page: 'manager-api-requests',
        limit: '300',
        date_from: <?= json_encode($fromDate) ?>,
        date_to: <?= json_encode($toDate) ?>,
        phongBan: <?= json_encode($phongBan) ?>
    });
    fetch('index.php?' + params.toString(), { headers: { Accept: 'application/json' } })
        .then(function (res) { return res.json(); })
        .then(function (json) { renderRequestSummary(json.data || []); })
        .catch(function () { renderRequestSummary([]); });
});
</script>

<script>
/* ===== EXPORT REPORT MODAL JS ===== */
var _selectedReportType = 'tonghop';
var _selectedFormat = 'excel';

function openExportModal() {
    document.getElementById('exportReportModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeExportModal() {
    document.getElementById('exportReportModal').classList.remove('active');
    document.body.style.overflow = '';
}
function selectReportType(el, type) {
    _selectedReportType = type;
    document.querySelectorAll('.report-type-card').forEach(function(c) { c.classList.remove('selected'); });
    el.classList.add('selected');
}
function selectFormat(el, fmt) {
    _selectedFormat = fmt;
    document.querySelectorAll('.format-card').forEach(function(c) { c.classList.remove('selected'); });
    el.classList.add('selected');
}
function doExportReport() {
    var btn = document.getElementById('btnDoExport');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xuất...';

    // Lấy giá trị filter hiện tại từ form
    var tuNgay = '<?= htmlspecialchars($fromDate) ?>';
    var denNgay = '<?= htmlspecialchars($toDate) ?>';
    var phongBan = '<?= htmlspecialchars($phongBan) ?>';

    if (_selectedFormat === 'pdf') {
        // PDF: mở tab mới với trang in
        var url = 'index.php?page=xuat-bao-cao-tong-hop'
            + '&loai=' + encodeURIComponent(_selectedReportType)
            + '&dinh_dang=pdf'
            + '&tuNgay=' + encodeURIComponent(tuNgay)
            + '&denNgay=' + encodeURIComponent(denNgay)
            + '&phongBan=' + encodeURIComponent(phongBan);
        window.open(url, '_blank');
        setTimeout(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download"></i> Xuất ngay';
            closeExportModal();
        }, 800);
    } else {
        // Excel: submit form POST
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?page=xuat-bao-cao-tong-hop';
        var fields = {
            loai: _selectedReportType,
            dinh_dang: 'excel',
            tuNgay: tuNgay,
            denNgay: denNgay,
            phongBan: phongBan,
            export: '1'
        };
        Object.keys(fields).forEach(function(k) {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = k;
            inp.value = fields[k];
            form.appendChild(inp);
        });
        document.body.appendChild(form);
        form.submit();
        setTimeout(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download"></i> Xuất ngay';
            closeExportModal();
        }, 1500);
    }
}
// ESC key close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeExportModal();
});
</script>

<?php include 'app/views/layouts/footer.php'; ?>
