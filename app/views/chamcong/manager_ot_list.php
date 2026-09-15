<?php 
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['chucVu'], ['manager', 'Quản lý / Ban lãnh đạo', 'HR', 'bo phan nhan su'])) {
    // Assuming manager access via AuthMiddleware check but adding an extra UI protection if needed.
    // Actually AuthMiddleware handles this already.
}

$otRequests = $otRequests ?? [];
$successMsg = $successMsg ?? '';
$errorMsg = $errorMsg ?? '';

$statusLabels = [
    'pending'  => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Từ chối',
];
$statusIcons = [
    'pending'  => 'fa-clock',
    'approved' => 'fa-check',
    'rejected' => 'fa-times',
];
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>

<style>
.mgr-container { max-width: 1200px; margin: 0 auto; padding: 20px; font-family: 'Inter', sans-serif; }
.mgr-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.mgr-title { font-size: 20px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px; }
.mgr-title i { color: #4f6ef7; background: #eff6ff; padding: 10px; border-radius: 8px; }

.mgr-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; }
.mgr-table { width: 100%; border-collapse: collapse; }
.mgr-table th { background: #f8fafc; padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
.mgr-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; vertical-align: middle; }
.mgr-table tr:last-child td { border-bottom: none; }
.mgr-table tr:hover { background: #f8fafc; }

.badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
.badge-pending { background: #fef3c7; color: #d97706; }
.badge-approved { background: #dcfce7; color: #16a34a; }
.badge-rejected { background: #fee2e2; color: #dc2626; }

.btn-action { border: none; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 4px; }
.btn-approve { background: #10b981; color: white; }
.btn-approve:hover { background: #059669; }
.btn-reject { background: #ef4444; color: white; }
.btn-reject:hover { background: #dc2626; }

.emp-info { display: flex; align-items: center; gap: 10px; }
.emp-avatar { width: 36px; height: 36px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #64748b; }
.emp-details { display: flex; flex-direction: column; }
.emp-name { font-weight: 600; color: #0f172a; }
.emp-dept { font-size: 12px; color: #64748b; }

.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
.alert-success { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
.alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">
        <div class="mgr-container">
            <div class="mgr-header">
                <div class="mgr-title">
                    <i class="fas fa-business-time"></i>
                    Duyệt Đơn Đăng Ký Làm Thêm Giờ (OT)
                </div>
            </div>

            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <div class="mgr-card">
                <?php if (empty($otRequests)): ?>
                    <div style="padding: 40px; text-align: center; color: #64748b;">
                        <i class="fas fa-inbox" style="font-size: 32px; margin-bottom: 12px; color: #cbd5e1;"></i>
                        <p>Hiện không có đơn OT nào đang chờ duyệt.</p>
                    </div>
                <?php else: ?>
                    <table class="mgr-table">
                        <thead>
                            <tr>
                                <th>Nhân viên</th>
                                <th>Ngày OT</th>
                                <th>Số giờ</th>
                                <th>Lý do</th>
                                <th>Ngày gửi</th>
                                <th style="text-align: right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($otRequests as $req): ?>
                                <tr>
                                    <td>
                                        <div class="emp-info">
                                            <div class="emp-avatar"><?= mb_substr(htmlspecialchars($req['employee_name']), 0, 1) ?></div>
                                            <div class="emp-details">
                                                <span class="emp-name"><?= htmlspecialchars($req['employee_name']) ?></span>
                                                <span class="emp-dept"><?= htmlspecialchars($req['phongBan']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong><?= date('d/m/Y', strtotime($req['ngayOT'])) ?></strong></td>
                                    <td><span style="background: #eff6ff; color: #2563eb; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 12px;"><?= $req['soGioOT'] ?> giờ</span></td>
                                    <td style="max-width: 250px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?= htmlspecialchars($req['ghiChu']) ?>">
                                        <?= htmlspecialchars($req['ghiChu']) ?>
                                    </td>
                                    <td style="color: #64748b; font-size: 12px;"><?= date('d/m/Y H:i', strtotime($req['ngayTao'])) ?></td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="index.php?page=approve-ot-request" style="display: inline-flex; gap: 8px;">
                                            <input type="hidden" name="id" value="<?= $req['id'] ?>">
                                            <button type="submit" name="trangThai" value="approve" class="btn-action btn-approve" onclick="return confirm('Bạn chắc chắn muốn DUYỆT đơn OT này?');">
                                                <i class="fas fa-check"></i> Duyệt
                                            </button>
                                            <button type="submit" name="trangThai" value="reject" class="btn-action btn-reject" onclick="return confirm('Bạn chắc chắn muốn TỪ CHỐI đơn OT này?');">
                                                <i class="fas fa-times"></i> Từ chối
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(a) {
        setTimeout(function() { 
            a.style.opacity = '0'; 
            a.style.transition = 'opacity 0.3s';
            setTimeout(function() { a.remove(); }, 300); 
        }, 5000);
    });
});
</script>

<?php include 'app/views/layouts/footer.php'; ?>
