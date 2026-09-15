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
    'approved' => 'fa-check-circle',
    'rejected' => 'fa-times-circle',
];
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>

<style>
/* ===== PREMIUM MODERN UI - OT REQUEST FORM ===== */
.ot-page-wrapper {
    min-height: 100vh;
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    padding: 20px 10px;
    font-family: 'Inter', sans-serif;
}

.ot-container { 
    max-width: 900px; 
    margin: 0 auto; 
}

/* Page Header */
.ot-page-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    padding: 20px;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.5);
}

.ot-icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
}

.ot-page-header h2 {
    margin: 0 0 4px;
    font-size: 22px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.5px;
}

.ot-page-header p {
    margin: 0;
    font-size: 14px;
    color: #64748b;
}

/* Form Card */
.ot-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
    border: 1px solid rgba(255, 255, 255, 0.8);
    margin-bottom: 24px;
    transition: transform 0.3s ease;
}

.ot-card:hover {
    transform: translateY(-2px);
}

.ot-card-title {
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 15px;
    border-bottom: 2px dashed #e2e8f0;
}

.ot-card-title i { color: #3b82f6; }

/* Grid Layout */
.ot-grid-form {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 20px;
}

.ot-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.col-4 { grid-column: span 4; }
.col-12 { grid-column: span 12; }

@media (max-width: 768px) {
    .col-4 { grid-column: span 12; }
    .ot-card { padding: 20px; }
    .ot-page-header { flex-direction: column; text-align: center; }
}

.ot-field label {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    letter-spacing: 0.3px;
}

.ot-field label .req { color: #ef4444; }

.ot-field input, .ot-field select, .ot-field textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    background: #f8fafc;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.ot-field input:focus, .ot-field select:focus, .ot-field textarea:focus {
    outline: none;
    border-color: #3b82f6;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.ot-field textarea {
    min-height: 100px;
    resize: vertical;
}

/* Submit Button */
.btn-submit-wrap {
    grid-column: span 12;
    display: flex;
    justify-content: flex-end;
    margin-top: 10px;
}

.btn-ot-submit {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
    border: none;
    padding: 14px 28px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

.btn-ot-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
}

/* History Cards */
.ot-history-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.ot-history-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    gap: 12px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.ot-history-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 4px;
}

.ot-history-card.trangThai-approved::before { background: #10b981; }
.ot-history-card.trangThai-pending::before  { background: #f59e0b; }
.ot-history-card.trangThai-rejected::before { background: #ef4444; }

.ot-history-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 20px -5px rgba(0,0,0,0.08);
}

.ot-card-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ot-date {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 6px;
}

.ot-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-approved { background: #dcfce7; color: #16a34a; }
.badge-pending  { background: #fef3c7; color: #d97706; }
.badge-rejected { background: #fee2e2; color: #dc2626; }

.ot-details {
    background: #f8fafc;
    padding: 12px;
    border-radius: 12px;
    font-size: 13px;
    color: #475569;
    line-height: 1.5;
}

.ot-footer-row {
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px dashed #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11px;
    color: #64748b;
}

/* Alerts */
.ot-alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 600;
    animation: slideDown 0.4s ease forwards;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container ot-page-wrapper">
        <div class="ot-container">
            <!-- Header -->
            <div class="ot-page-header">
                <div class="ot-icon-circle">
                    <i class="fas fa-business-time"></i>
                </div>
                <div>
                    <h2>Đăng ký Làm Thêm Giờ (OT)</h2>
                    <p>Khởi tạo và theo dõi các yêu cầu làm thêm giờ của bạn.</p>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($message)): ?>
                <div class="ot-alert alert-success">
                    <i class="fas fa-check-circle" style="font-size: 20px;"></i>
                    <span><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="ot-alert alert-error">
                    <i class="fas fa-exclamation-triangle" style="font-size: 20px;"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <div class="ot-card">
                <div class="ot-card-title">
                    <i class="fas fa-paper-plane"></i> Tạo Yêu Cầu Mới
                </div>

                <form method="POST" action="index.php?page=store-ot-request" id="otForm" class="ot-grid-form">
                    <div class="ot-field col-4">
                        <label for="ngayOT">Ngày OT <span class="req">*</span></label>
                        <select id="ngayOT" name="ngayOT" required>
                            <option value="<?= date('Y-m-d') ?>">Hôm nay (<?= date('d/m/Y') ?>)</option>
                            <option value="<?= date('Y-m-d', strtotime('-1 day')) ?>">Hôm qua (<?= date('d/m/Y', strtotime('-1 day')) ?>)</option>
                            <option value="<?= date('Y-m-d', strtotime('-2 days')) ?>">Hôm kia (<?= date('d/m/Y', strtotime('-2 days')) ?>)</option>
                            <option value="<?= date('Y-m-d', strtotime('-3 days')) ?>">3 ngày trước (<?= date('d/m/Y', strtotime('-3 days')) ?>)</option>
                        </select>
                    </div>
                    
                    <div class="ot-field col-4">
                        <label for="soGioOT">Số giờ OT <span class="req">*</span></label>
                        <input type="number" id="soGioOT" name="soGioOT" min="0.5" step="0.5" max="24" required placeholder="VD: 2.5">
                    </div>
                    
                    <div class="ot-field col-4">
                        <label for="nguoiDuyet">Người duyệt <span class="req">*</span></label>
                        <select id="nguoiDuyet" name="nguoiDuyet" required>
                            <option value="" disabled selected>— Chọn quản lý —</option>
                            <?php foreach ($managers as $mgr): ?>
                                <option value="<?= htmlspecialchars($mgr['maND']) ?>"><?= htmlspecialchars($mgr['hoTen'] . ' - ' . $mgr['phongBan']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ot-field col-12">
                        <label for="ghiChu">Lý do / Mô tả công việc <span class="req">*</span></label>
                        <textarea id="ghiChu" name="ghiChu" placeholder="Vui lòng mô tả chi tiết công việc thực hiện trong thời gian OT..." required></textarea>
                    </div>

                    <div class="btn-submit-wrap">
                        <button type="submit" class="btn-ot-submit">
                            <i class="fas fa-arrow-right"></i> Gửi Yêu Cầu
                        </button>
                    </div>
                </form>
            </div>

            <!-- History -->
            <div class="ot-card">
                <div class="ot-card-title">
                    <i class="fas fa-history"></i> Lịch Sử Yêu Cầu
                </div>

                <?php if (!empty($myRequests) && is_array($myRequests)): ?>
                    <div class="ot-history-grid">
                        <?php foreach ($myRequests as $row): ?>
                            <?php
                                $trangThai = $row['trangThai'] ?? 'pending';
                                $badgeClass = 'badge-' . $trangThai;
                                $iconClass = $statusIcons[$trangThai] ?? 'fa-clock';
                                $cardClass = 'trangThai-' . $trangThai;
                            ?>
                            <div class="ot-history-card <?= $cardClass ?>">
                                <div class="ot-card-header-row">
                                    <div class="ot-date">
                                        <i class="far fa-calendar-alt" style="color: #64748b;"></i> 
                                        <?= date('d/m/Y', strtotime($row['ngayOT'])) ?>
                                    </div>
                                    <span class="ot-badge <?= $badgeClass ?>">
                                        <i class="fas <?= $iconClass ?>"></i> <?= $statusLabels[$trangThai] ?>
                                    </span>
                                </div>
                                
                                <div style="font-size: 13px; font-weight: 600; color: #3b82f6;">
                                    <i class="fas fa-hourglass-half"></i> Thời lượng: <?= htmlspecialchars($row['soGioOT']) ?> giờ
                                </div>

                                <div class="ot-details">
                                    <?= nl2br(htmlspecialchars($row['ghiChu'] ?? '')) ?>
                                </div>

                                <div class="ot-footer-row">
                                    <div><i class="fas fa-user-tie"></i> <?= htmlspecialchars($row['manager_name'] ?? 'Không rõ') ?></div>
                                    <div><?= date('d/m/Y H:i', strtotime($row['ngayTao'] ?? '')) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; color: #94a3b8;">
                        <div style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                            <i class="fas fa-box-open" style="font-size: 32px; color: #cbd5e1;"></i>
                        </div>
                        <p style="font-size: 15px;">Chưa có lịch sử đăng ký OT nào.</p>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var alerts = document.querySelectorAll('.ot-alert');
    alerts.forEach(function(a) {
        setTimeout(function() { 
            a.style.opacity = '0'; 
            a.style.transform = 'translateY(-10px)'; 
            a.style.transition = 'all 0.4s ease';
            setTimeout(function() { a.remove(); }, 400); 
        }, 5000);
    });
});
</script>

<?php include 'app/views/layouts/footer.php'; ?>
