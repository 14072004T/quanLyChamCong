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

$deptAggregates = [];
$employeeLabels = [];
$checkinSeries = [];
$checkoutSeries = [];
if (!empty($reportRows)) {
    $rowsForChart = array_slice($reportRows, 0, 10);
    foreach ($rowsForChart as $row) {
        $employeeLabels[] = (string)($row['hoTen'] ?? 'N/A');
        $checkinSeries[] = (int)($row['checkin_count'] ?? 0);
        $checkoutSeries[] = (int)($row['checkout_count'] ?? 0);
    }

    foreach ($reportRows as $row) {
        $dept = (string)($row['phongBan'] ?? 'Chưa phân phòng');
        if (!isset($deptAggregates[$dept])) {
            $deptAggregates[$dept] = 0;
        }
        $deptAggregates[$dept] += (int)($row['work_days'] ?? 0);
    }
}

$deptLabels = array_keys($deptAggregates);
$deptWorkdays = array_values($deptAggregates);
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">
        
        <div class="panel">
            <h2>Xuất Báo Cáo Chấm Công</h2>
            <p>Lọc dữ liệu theo thời gian, phòng ban và xuất tệp CSV.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="panel">
            <form method="POST" action="index.php?page=<?= htmlspecialchars($reportActionPage) ?>" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;">
                <div class="form-group">
                    <label>Từ ngày:</label>
                    <input type="date" name="from_date" value="<?= htmlspecialchars($fromDate ?? date('Y-m-01')) ?>" required>
                </div>

                <div class="form-group">
                    <label>Đến ngày:</label>
                    <input type="date" name="to_date" value="<?= htmlspecialchars($toDate ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="form-group">
                    <label>Phòng ban:</label>
                    <select name="department">
                        <option value="">Tất cả phòng ban</option>
                        <?php foreach (($departments ?? []) as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>" <?= ($department ?? '') === $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Định dạng:</label>
                    <select name="format">
                        <option value="excel">Excel</option>
                        <option value="html">Xem trên màn hình</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-success" style="align-self:end;">
                    <i class="fas fa-file-export"></i> Xuất báo cáo
                </button>
            </form>
        </div>

        <?php if ($isHr): ?>
        <div class="panel">
            <h3>Gửi bảng công để phê duyệt</h3>
            <form method="POST" action="index.php?page=gui-bang-cong-phe-duyet" style="display:flex;gap:10px;align-items:end;">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Kỳ chấm công</label>
                    <input type="month" name="month_key" value="<?= htmlspecialchars(substr($toDate ?? date('Y-m-d'), 0, 7)) ?>" required>
                </div>
                <button type="submit" class="btn btn-secondary">Gửi phê duyệt</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="panel">
            <h3>Dữ liệu báo cáo</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã NV</th>
                        <th>Họ tên</th>
                        <th>Phòng ban</th>
                        <th>Số ngày có chấm công</th>
                        <th>Số lần check-in</th>
                        <th>Số lần check-out</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($reportRows)): ?>
                        <?php foreach ($reportRows as $row): ?>
                            <tr>
                                <td><?= (int)($row['maND'] ?? 0) ?></td>
                                <td><?= htmlspecialchars($row['hoTen'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['phongBan'] ?? '') ?></td>
                                <td><?= (int)($row['work_days'] ?? 0) ?></td>
                                <td><?= (int)($row['checkin_count'] ?? 0) ?></td>
                                <td><?= (int)($row['checkout_count'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">Không có dữ liệu trong khoảng thời gian đã chọn.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h3>Biểu đồ báo cáo</h3>
            <div class="chart-grid">
                <div class="chart-card">
                    <h4>Ngày công theo phòng ban</h4>
                    <div class="chart-canvas-wrap">
                        <canvas id="deptWorkdaysChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h4>Top nhân viên theo số lần chấm công</h4>
                    <div class="chart-canvas-wrap">
                        <canvas id="employeeCheckChart"></canvas>
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
    const deptWorkdays = <?= json_encode($deptWorkdays, JSON_UNESCAPED_UNICODE) ?>;
    const employeeLabels = <?= json_encode($employeeLabels, JSON_UNESCAPED_UNICODE) ?>;
    const checkinSeries = <?= json_encode($checkinSeries, JSON_UNESCAPED_UNICODE) ?>;
    const checkoutSeries = <?= json_encode($checkoutSeries, JSON_UNESCAPED_UNICODE) ?>;

    new Chart(document.getElementById('deptWorkdaysChart'), {
        type: 'bar',
        data: {
            labels: deptLabels,
            datasets: [{
                label: 'Tổng ngày công',
                data: deptWorkdays,
                backgroundColor: '#6366f1',
                borderRadius: 8,
                maxBarThickness: 34
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Poppins' } } },
                y: { beginAtZero: true, ticks: { precision: 0, font: { family: 'Poppins' } } }
            }
        }
    });

    new Chart(document.getElementById('employeeCheckChart'), {
        type: 'bar',
        data: {
            labels: employeeLabels,
            datasets: [
                {
                    label: 'Check-in',
                    data: checkinSeries,
                    backgroundColor: '#22c55e',
                    borderRadius: 6,
                    maxBarThickness: 24
                },
                {
                    label: 'Check-out',
                    data: checkoutSeries,
                    backgroundColor: '#f59e0b',
                    borderRadius: 6,
                    maxBarThickness: 24
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { usePointStyle: true, boxWidth: 8, font: { family: 'Poppins', size: 12 } }
                }
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
