<?php 
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}
$message = $message ?? '';
$error = $error ?? '';
$myRequests = $myRequests ?? [];
$managers = $managers ?? [];

$statusLabels = [
    'pending'  => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Từ chối',
];
$statusIcons = [
    'pending'  => 'fa-clock',
    'approved' => 'fa-circle-check',
    'rejected' => 'fa-circle-xmark',
];
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>

<style>
/* ===== STRICT GRID OT REQUEST FORM ===== */
.lr-container { max-width: 1000px; margin: 0 auto; padding: 0 10px; font-family: 'Inter', sans-serif; }
.lr-card { background: #fff; border-radius: 8px; padding: 16px; border: 1px solid #e2e8f0; margin-bottom: 12px; box-shadow: 0 1px 2px rgba(0,0,0,.03); }
.lr-card-title { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0 0 12px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px; }
.lr-card-title i { color: #4f6ef7; font-size: 13px; }

/* FORCE GRID LAYOUT */
.lr-grid-form {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    align-items: end;
}

.lr-field { display: flex; flex-direction: column; gap: 4px; }
.lr-field label { font-weight: 600; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }
.lr-field label .req { color: #ef4444; }

.lr-field input, .lr-field select, .lr-field textarea {
    width: 100%; height: 34px; padding: 0 10px; border: 1px solid #cbd5e1; border-radius: 6px;
    font-size: 13px; font-family: 'Inter', sans-serif; color: #1e293b; background: #fff; box-sizing: border-box;
}
.lr-field textarea { height: 34px; min-height: 34px; resize: none; padding: 6px 10px; line-height: 20px; }

/* Grid positioning */
.col-span-3 { grid-column: span 3; }
.col-span-2 { grid-column: span 2; }

.btn-submit-wrap { display: flex; justify-content: flex-end; }
.btn-success {
    background: #4f6ef7; color: #fff; border: none; height: 34px; padding: 0 20px; border-radius: 6px;
    font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s;
}
.btn-success:hover { background: #3b5de7; }

/* Status Badges */
.lr-badge { padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px; }
/* History Grid */
.lr-history-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 10px;
}
@media (max-width: 768px) {
    .lr-history-grid { grid-template-columns: 1fr; }
    .lr-grid-form { grid-template-columns: 1fr; }
}

.lr-history-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px;
    display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease;
}
.lr-history-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

/* Status-specific card styles */
.lr-history-card.trangThai-approved { background: #f0fdf4; border-left: 4px solid #10b981; }
.lr-history-card.trangThai-pending  { background: #fffbeb; border-left: 4px solid #f59e0b; }
.lr-history-card.trangThai-rejected { background: #fef2f2; border-left: 4px solid #ef4444; }

/* Refined Badge Colors */
.lr-badge-approved { background: #10b981; color: #fff; }
.lr-badge-pending  { background: #f59e0b; color: #fff; }
.lr-badge-rejected { background: #ef4444; color: #fff; }

.lr-alert {
    padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500;
}
.lr-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
.lr-alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">
        <div class="lr-container">
            <!-- Page Header -->
            <div class="panel">
                <div class="lr-page-header" style="display:flex; align-items:center; gap:16px;">
                    <div class="lr-icon-circle" style="background:#eff6ff; color:#3b82f6; width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px;">
                        <i class="fas fa-business-time"></i>
                    </div>
                    <div>
                        <h2 style="margin:0; font-size:20px; font-weight:600; color:#1e293b;">Đăng ký Làm Thêm Giờ (OT)</h2>
                        <p style="margin:4px 0 0; font-size:14px; color:#64748b;">Gửi yêu cầu làm thêm giờ cho quản lý để được phê duyệt.</p>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($message)): ?>
                <div class="lr-alert lr-alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="lr-alert lr-alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- FORM CARD -->
            <div class="lr-card">
                <div class="lr-card-title">
                    <i class="fas fa-pen-to-square"></i> Tạo Đơn Đăng Ký Mới
                </div>

                <form method="POST" action="index.php?page=store-ot-request" id="otForm" class="lr-grid-form">
                    <!-- Row 1 -->
                    <div class="lr-field">
                        <label for="ngayOT">Ngày OT (Hôm nay / Ngày mai) <span class="req">*</span></label>
                        <select id="ngayOT" name="ngayOT" required>
                            <option value="<?= date('Y-m-d') ?>">Hôm nay (<?= date('d/m/Y') ?>)</option>
                            <option value="<?= date('Y-m-d', strtotime('+1 day')) ?>">Ngày mai (<?= date('d/m/Y', strtotime('+1 day')) ?>)</option>
                        </select>
                    </div>
                    <div class="lr-field">
                        <label for="soGioOT">Số giờ OT <span class="req">*</span></label>
                        <input type="number" id="soGioOT" name="soGioOT" min="0.5" step="0.5" max="24" required placeholder="VD: 2.5">
                    </div>
                    <div class="lr-field">
                        <label for="nguoiDuyet">Người duyệt <span class="req">*</span></label>
                        <select id="nguoiDuyet" name="nguoiDuyet" required>
                            <option value="" disabled selected>— Chọn quản lý —</option>
                            <?php foreach ($managers as $mgr): ?>
                                <option value="<?= htmlspecialchars($mgr['maND']) ?>"><?= htmlspecialchars($mgr['hoTen'] . ' - ' . $mgr['phongBan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Row 2 -->
                    <div class="lr-field col-span-3">
                        <label for="ghiChu">Lý do / Công việc cụ thể <span class="req">*</span></label>
                        <textarea id="ghiChu" name="ghiChu" placeholder="Nhập chi tiết công việc cần làm thêm..." required></textarea>
                    </div>

                    <div class="btn-submit-wrap col-span-3">
                        <button type="submit" class="btn-success" id="submitBtn">
                            <i class="fas fa-paper-plane"></i> Gửi Đơn Đăng Ký
                        </button>
                    </div>
                </form>
            </div>

            <!-- HISTORY TABLE -->
            <div class="lr-card" style="margin-top: 20px;">
                <div class="lr-card-title">
                    <i class="fas fa-clock-rotate-left"></i> Lịch Sử Đăng Ký OT
                </div>

                <?php if (!empty($myRequests) && is_array($myRequests)): ?>
                    <div class="lr-history-grid">
                        <?php foreach ($myRequests as $row): ?>
                            <?php
                                $trangThai = $row['trangThai'] ?? 'pending';
                                $badgeClass = 'lr-badge-' . $trangThai;
                                $iconClass = $statusIcons[$trangThai] ?? 'fa-clock';
                                $cardClass = 'trangThai-' . $trangThai;
                            ?>
                            <div class="lr-history-card <?= $cardClass ?>" data-id="<?= (int)$row['id'] ?>">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div style="font-size: 13px; font-weight: 700; color: #1e293b;">
                                        Ngày: <?= htmlspecialchars(date('d/m/Y', strtotime($row['ngayOT']))) ?>
                                    </div>
                                    <span class="lr-badge <?= $badgeClass ?>"><i class="fas <?= $iconClass ?>"></i> <?= $statusLabels[$trangThai] ?></span>
                                </div>
                                
                                <div style="font-size: 12px; color: #475569; display: flex; gap: 8px;">
                                    <i class="far fa-clock" style="margin-top: 2px;"></i>
                                    <span style="font-weight: 600;">Số giờ: <?= htmlspecialchars($row['soGioOT']) ?> giờ</span>
                                </div>

                                <div style="font-size: 12px; color: #475569; background: rgba(255,255,255,0.6); padding: 6px 10px; border-radius: 4px; border-left: 2px solid rgba(0,0,0,0.05);">
                                    <?= nl2br(htmlspecialchars($row['ghiChu'] ?? '')) ?>
                                </div>

                                <div style="margin-top: auto; padding-top: 8px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center;">
                                    <div style="font-size: 11px; color: #64748b;">
                                        <i class="fas fa-user-tie"></i> Duyệt bởi: <strong><?= htmlspecialchars($row['manager_name'] ?? 'Không rõ') ?></strong>
                                    </div>
                                    <div style="font-size: 10px; color: #64748b; font-style: italic;">
                                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['ngayTao'] ?? ''))) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; color: #94a3b8;">
                        <i class="fas fa-inbox" style="font-size: 20px; margin-bottom: 8px; display: block;"></i>
                        Chưa có lịch sử đăng ký OT nào.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var alerts = document.querySelectorAll('.lr-alert');
    alerts.forEach(function(a) {
        setTimeout(function() { 
            a.style.opacity = '0'; 
            a.style.transform = 'translateY(-8px)'; 
            setTimeout(function() { a.remove(); }, 300); 
        }, 5000);
    });
});
</script>

<?php include 'app/views/layouts/footer.php'; ?>
