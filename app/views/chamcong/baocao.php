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
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">
        
        <div class="panel">
            <h2>Xuất Báo Cáo Chấm Công</h2>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="panel">
            <form method="POST" action="index.php?page=<?= htmlspecialchars($reportActionPage) ?>" style="display:grid;gap:12px;">
                <!-- Dòng 1: Từ ngày + Đến ngày + Phòng ban + Lọc -->
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;align-items:end;">
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
                                       <button type="submit" class="btn btn-primary" style="padding:10px 16px;">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                    </div>
                   

                </div>

                <!-- Dòng 2: Định dạng + Xuất báo cáo + 2 cột trống -->
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;align-items:end;">
                    <div class="form-group">
                        <label>Định dạng:</label>
                        <select name="format">
                            <option value="excel">Excel</option>
                            <option value="html">Xem trên màn hình</option>
                        </select>
                    </div>

                    <div class="form-group">
                     <button type="submit" class="btn btn-success" name="export" value="1" style="padding:10px 16px;grid-column:2;">
                        <i class="fas fa-file-export"></i> Xuất báo cáo
                    </button>
                    </div>
                   
                </div>
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

    </div>
</div>
<?php include 'app/views/layouts/footer.php'; ?>
