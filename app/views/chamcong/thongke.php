<?php 
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}

if ($_SESSION['role'] !== 'manager') {
    header('Location: index.php?page=home');
    exit();
}
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<?php
$deptLabels = [];
$deptHours = [];
$deptEmployees = [];
if (!empty($departmentSummary)) {
    foreach ($departmentSummary as $dept => $summary) {
        $deptLabels[] = $dept;
        $deptHours[] = round((float)($summary['hours'] ?? 0), 2);
        $deptEmployees[] = (int)($summary['employees'] ?? 0);
    }
}

$attendanceChart = [
    (int)($statsSummary['checked_employees'] ?? 0),
    (int)($statsSummary['unchecked_employees'] ?? 0),
];
?>
<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">
        
        <div class="panel">
            <h2>Thống kê & Báo cáo</h2>
            <p>Xem tổng quan chấm công theo kỳ tháng và theo phòng ban.</p>
            <form method="GET" action="index.php" style="display:flex;gap:10px;align-items:end;max-width:420px;">
                <input type="hidden" name="page" value="thong-ke-bieu-do">
                <div class="form-group" style="margin-bottom:0;flex:1;">
                    <label>Kỳ tháng</label>
                    <input type="month" name="month" value="<?= htmlspecialchars($selectedMonth ?? date('Y-m')) ?>" required>
                </div>
                <button class="btn btn-primary" type="submit">Lọc</button>
            </form>
        </div>

        <div class="dashboard-stats">
            <div class="card">
                <h3>Tổng NV</h3>
                <p><?= (int)($statsSummary['total_employees'] ?? 0) ?></p>
            </div>
            <div class="card">
                <h3>Đã chấm</h3>
                <p><?= (int)($statsSummary['checked_employees'] ?? 0) ?></p>
            </div>
            <div class="card">
                <h3>Chưa chấm</h3>
                <p><?= (int)($statsSummary['unchecked_employees'] ?? 0) ?></p>
            </div>
            <div class="card">
                <h3>Tỷ lệ chấm công</h3>
                <p><?= htmlspecialchars((string)($statsSummary['attendance_rate'] ?? 0)) ?>%</p>
            </div>
        </div>

        <div class="panel">
            <h3>Tổng giờ làm theo phòng ban</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Phòng ban</th>
                        <th>Số nhân viên</th>
                        <th>Tổng giờ làm</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($departmentSummary)): ?>
                        <?php foreach ($departmentSummary as $dept => $summary): ?>
                            <tr>
                                <td><?= htmlspecialchars($dept) ?></td>
                                <td><?= (int)$summary['employees'] ?></td>
                                <td><?= round((float)$summary['hours'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="empty-state">Không có dữ liệu cho kỳ đã chọn.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h3>Biểu đồ trực quan</h3>
            <div class="chart-grid">
                <div class="chart-card">
                    <h4>Phân bổ trạng thái chấm công</h4>
                    <div class="chart-canvas-wrap">
                        <canvas id="attendancePieChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h4>Tổng giờ làm theo phòng ban</h4>
                    <div class="chart-canvas-wrap">
                        <canvas id="departmentHoursChart"></canvas>
                    </div>
                </div>
                <div class="chart-card chart-span-2">
                    <h4>So sánh số nhân sự giữa các phòng ban</h4>
                    <div class="chart-canvas-wrap chart-canvas-short">
                        <canvas id="departmentEmployeesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const deptLabels = <?= json_encode($deptLabels, JSON_UNESCAPED_UNICODE) ?>;
    const deptHours = <?= json_encode($deptHours, JSON_UNESCAPED_UNICODE) ?>;
    const deptEmployees = <?= json_encode($deptEmployees, JSON_UNESCAPED_UNICODE) ?>;
    const attendanceData = <?= json_encode($attendanceChart, JSON_UNESCAPED_UNICODE) ?>;

    const commonLegend = {
        labels: {
            usePointStyle: true,
            boxWidth: 8,
            font: { family: 'Poppins', size: 12 }
        }
    };

    new Chart(document.getElementById('attendancePieChart'), {
        type: 'doughnut',
        data: {
            labels: ['Đã chấm', 'Chưa chấm'],
            datasets: [{
                data: attendanceData,
                backgroundColor: ['#22c55e', '#ef4444'],
                borderColor: ['#16a34a', '#dc2626'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: commonLegend,
                tooltip: { mode: 'index', intersect: false }
            },
            cutout: '62%'
        }
    });

    new Chart(document.getElementById('departmentHoursChart'), {
        type: 'bar',
        data: {
            labels: deptLabels,
            datasets: [{
                label: 'Giờ làm',
                data: deptHours,
                backgroundColor: '#3b82f6',
                borderRadius: 8,
                maxBarThickness: 36
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => `${ctx.parsed.y} giờ` } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Poppins' } } },
                y: { beginAtZero: true, ticks: { font: { family: 'Poppins' } } }
            }
        }
    });

    new Chart(document.getElementById('departmentEmployeesChart'), {
        type: 'line',
        data: {
            labels: deptLabels,
            datasets: [{
                label: 'Số nhân viên',
                data: deptEmployees,
                borderColor: '#0ea5e9',
                backgroundColor: 'rgba(14, 165, 233, 0.18)',
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointBackgroundColor: '#0284c7'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: commonLegend,
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Poppins' } } },
                y: { beginAtZero: true, ticks: { precision: 0, font: { family: 'Poppins' } } }
            }
        }
    });
})();
</script>
<?php include 'app/views/layouts/footer.php'; ?>
