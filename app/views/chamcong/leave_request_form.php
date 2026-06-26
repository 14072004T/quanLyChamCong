<?php 
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}
$message = $message ?? '';
$error = $error ?? '';
$myRequests = $myRequests ?? [];

$leaveTypeLabels = [
    'sick'      => 'Nghỉ ốm',
    'personal'  => 'Nghỉ phép cá nhân',
    'emergency' => 'Nghỉ khẩn cấp',
    'wedding'   => 'Nghỉ cưới',
    'funeral'   => 'Nghỉ tang',
    'other'     => 'Khác',
];
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
/* ===== STRICT GRID LEAVE REQUEST FORM ===== */
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

.lr-file-zone {
    border: 1px dashed #cbd5e1; border-radius: 6px; height: 34px; background: #f8fafc;
    display: flex; align-items: center; gap: 8px; padding: 0 10px; cursor: pointer; position: relative; box-sizing: border-box;
}
.lr-file-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
.lr-file-text { font-size: 11px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

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

/* Modal Styles */
.lr-modal { display:none; position:fixed; z-index:9999; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; padding:20px; backdrop-filter:blur(2px); }
.lr-modal-content { background:#fff; border-radius:12px; width:100%; max-width:500px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1); overflow:hidden; animation:modalSlideUp 0.3s ease; }
@keyframes modalSlideUp { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }
.lr-modal-header { padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
.lr-modal-body { padding:20px; }
.lr-modal-footer { padding:12px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; }
.lr-detail-row { display:flex; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:8px; }
.lr-detail-label { width:120px; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; }
.lr-detail-val { flex:1; font-size:13px; color:#1e293b; font-weight:500; }
.lr-history-card { cursor: pointer; }

.lr-loading.active { display: inline-flex; align-items: center; }
.lr-spinner { width: 12px; height: 12px; border: 2px solid #fff; border-top-color: transparent; border-radius: 50%; animation: lrSpin .6s linear infinite; }
@keyframes lrSpin { to { transform: rotate(360deg); } }
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">
        <div class="lr-container">
        <?php if (AuthMiddleware::isMobile()): ?>
            <!-- Mobile UI -->
            <div class="main-content" style="padding: 0 !important; min-height: auto !important;">
                <div class="mb-request-title">Tạo yêu cầu</div>
                <p class="mb-request-subtitle">Gửi yêu cầu nghỉ phép hoặc chỉnh sửa ngày công tới quản lý.</p>

                <!-- Selector tabs -->
                <div class="mb-request-selector-row">
                    <a href="index.php?page=create-leave-request" class="mb-request-selector-btn active">
                        <i class="fa-solid fa-calendar-minus"></i>
                        <span>Nghỉ phép</span>
                    </a>
                    <a href="index.php?page=yeu-cau-chinh-sua-cham-cong" class="mb-request-selector-btn">
                        <i class="fa-solid fa-clock"></i>
                        <span>Chỉnh sửa công</span>
                    </a>
                </div>

                <span class="mb-request-section-title">Thông tin nghỉ phép</span>
                <div class="mb-request-card">
                    <form method="POST" action="index.php?page=store-leave-request" enctype="multipart/form-data" id="leaveForm">
                        <div class="mb-request-form-grid">
                            <div class="mb-request-form-group">
                                <label for="loaiNghiPhep">Loại nghỉ phép <span style="color:#ef4444">*</span></label>
                                <div class="mb-request-input-wrapper">
                                    <i class="fa-solid fa-list" style="left:14px; top:50%; transform:translateY(-50%); position:absolute;"></i>
                                    <select id="loaiNghiPhep" name="loaiNghiPhep" class="mb-request-input-field" required>
                                        <option value="" disabled selected>— Chọn loại nghỉ —</option>
                                        <?php foreach ($leaveTypeLabels as $val => $lbl): ?>
                                            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($lbl) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="error-msg" id="err-leave-type" style="display:none; color:#ef4444; font-size:11px; margin-top:2px;">Vui lòng chọn loại nghỉ phép.</div>
                            </div>

                            <div class="mb-request-form-group">
                                <label for="tuNgay">Từ ngày <span style="color:#ef4444">*</span></label>
                                <div class="mb-request-input-wrapper">
                                    <i class="fa-solid fa-calendar" style="left:14px; top:50%; transform:translateY(-50%); position:absolute;"></i>
                                    <input type="date" id="tuNgay" name="tuNgay" class="mb-request-input-field" required min="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="error-msg" id="err-from-date" style="display:none; color:#ef4444; font-size:11px; margin-top:2px;">Vui lòng chọn ngày bắt đầu.</div>
                            </div>

                            <div class="mb-request-form-group">
                                <label for="denNgay">Đến ngày <span style="color:#ef4444">*</span></label>
                                <div class="mb-request-input-wrapper">
                                    <i class="fa-solid fa-calendar" style="left:14px; top:50%; transform:translateY(-50%); position:absolute;"></i>
                                    <input type="date" id="denNgay" name="denNgay" class="mb-request-input-field" required min="<?= date('Y-m-d') ?>">
                                </div>
                                <div class="error-msg" id="err-to-date" style="display:none; color:#ef4444; font-size:11px; margin-top:2px;">Vui lòng chọn ngày kết thúc hợp lệ.</div>
                            </div>

                            <div class="mb-request-form-group">
                                <label for="lyDo">Lý do nghỉ phép <span style="color:#ef4444">*</span></label>
                                <textarea id="lyDo" name="lyDo" class="mb-request-textarea" placeholder="Nhập lý do chi tiết..." required style="min-height: 80px;"></textarea>
                                <div class="error-msg" id="err-lyDo" style="display:none; color:#ef4444; font-size:11px; margin-top:2px;">Vui lòng nhập lý do.</div>
                            </div>

                            <div class="mb-request-form-group">
                                <label>Đính kèm minh chứng</label>
                                <div class="mb-request-file-zone" id="fileZone" onclick="document.getElementById('tepMinhChung').click()">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span id="fileText">Chọn file minh chứng...</span>
                                    <input type="file" name="tepMinhChung" id="tepMinhChung" accept=".jpg,.jpeg,.png,.pdf" style="display:none">
                                </div>
                            </div>

                            <button type="submit" class="mb-request-submit-btn" id="submitBtn">
                                <span id="submitText"><i class="fa-solid fa-paper-plane"></i> Gửi yêu cầu nghỉ phép</span>
                                <span class="lr-loading" id="submitLoading" style="display:none;"><span class="lr-spinner"></span> Đang gửi...</span>
                            </button>
                        </div>
                    </form>
                </div>

                <span class="mb-request-section-title">Lịch sử nghỉ phép</span>
                <div class="mb-history-list">
                    <?php if (!empty($myRequests) && is_array($myRequests)): ?>
                        <?php foreach ($myRequests as $row): ?>
                            <?php
                                $trangThai = $row['trangThai'] ?? 'pending';
                                $lt = $row['loaiNghiPhep'] ?? 'personal';
                                $badgeClass = $trangThai === 'approved' ? 'hople' : ($trangThai === 'rejected' ? 'dimuon' : 'vesom');
                                $statusText = $statusLabels[$trangThai] ?? 'Chờ duyệt';
                            ?>
                            <div class="mb-history-card-row lr-history-card" data-id="<?= (int)$row['id'] ?>">
                                <div class="mb-history-card-header">
                                    <div>
                                        <span class="day-title"><?= htmlspecialchars($leaveTypeLabels[$lt] ?? $lt) ?></span>
                                        <div class="date-title">
                                            <?= htmlspecialchars(date('d/m/Y', strtotime($row['tuNgay'] ?? ''))) ?> 
                                            — 
                                            <?= htmlspecialchars(date('d/m/Y', strtotime($row['denNgay'] ?? ''))) ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="mb-history-status-badge <?= $badgeClass ?>"><?= $statusText ?></span>
                                    </div>
                                </div>
                                <div class="mb-history-card-details">
                                    <div style="font-size: 13px; color: #475569; width: 100%;">
                                        <?= nl2br(htmlspecialchars($row['lyDo'] ?? '')) ?>
                                    </div>
                                </div>
                                <?php if (!empty($row['tepMinhChung'])): ?>
                                     <?php 
                                         $fileUrl = $row['tepMinhChung'];
                                         if (strpos($fileUrl, 'uploads/') !== 0) {
                                             $fileUrl = 'uploads/leave_evidence/' . $fileUrl;
                                         }
                                     ?>
                                     <div style="margin-top: 8px; font-size: 11px;">
                                         <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" onclick="event.stopPropagation();" style="color: #1e62ec; text-decoration: none; font-weight: 700;">
                                             <i class="fa-solid fa-paperclip"></i> Xem minh chứng
                                         </a>
                                     </div>
                                 <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="ls-empty">
                            <i class="fas fa-inbox"></i>
                            <p>Chưa có đơn nghỉ phép nào.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
        <!-- Page Header -->
        <div class="panel">
            <div class="lr-page-header">
                <div class="lr-icon-circle"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <h2>Đơn Nghỉ Phép</h2>
                    <p>Tạo đơn xin nghỉ phép và theo dõi trạng thái phê duyệt từ HR / Quản lý.</p>
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
                <i class="fas fa-pen-to-square"></i> Tạo Đơn Nghỉ Phép Mới
            </div>

            <form method="POST" action="index.php?page=store-leave-request" enctype="multipart/form-data" id="leaveForm" class="lr-grid-form">
                <!-- Row 1 -->
                <div class="lr-field">
                    <label for="loaiNghiPhep">Loại nghỉ phép <span class="req">*</span></label>
                    <select id="loaiNghiPhep" name="loaiNghiPhep" required>
                        <option value="" disabled selected>— Chọn —</option>
                        <?php foreach ($leaveTypeLabels as $val => $lbl): ?>
                            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lr-field">
                    <label for="tuNgay">Từ ngày <span class="req">*</span></label>
                    <input type="date" id="tuNgay" name="tuNgay" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="lr-field">
                    <label for="denNgay">Đến ngày <span class="req">*</span></label>
                    <input type="date" id="denNgay" name="denNgay" required min="<?= date('Y-m-d') ?>">
                </div>

                <!-- Row 2 -->
                <div class="lr-field col-span-3">
                    <label for="lyDo">Lý do nghỉ phép <span class="req">*</span></label>
                    <textarea id="lyDo" name="lyDo" placeholder="Nhập lý do ngắn gọn..." required></textarea>
                </div>

                <!-- Row 3 -->
                <div class="lr-field col-span-2">
                    <label>Minh chứng (nếu có)</label>
                    <div class="lr-file-zone" id="fileZone">
                        <i class="fas fa-paperclip"></i>
                        <div class="lr-file-text" id="fileText">Chọn file minh chứng...</div>
                        <input type="file" name="tepMinhChung" id="tepMinhChung" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                </div>
                <div class="btn-submit-wrap">
                    <button type="submit" class="btn-success" id="submitBtn">
                        <span id="submitText"><i class="fas fa-paper-plane"></i> Gửi Đơn</span>
                        <span class="lr-loading" id="submitLoading"><span class="lr-spinner"></span> Đang gửi...</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- HISTORY TABLE -->
        <div class="lr-card" style="margin-top: 20px;">
            <div class="lr-card-title">
                <i class="fas fa-clock-rotate-left"></i> Lịch Sử Đơn Nghỉ Phép
            </div>

            <?php if (!empty($myRequests) && is_array($myRequests)): ?>
                <div class="lr-history-grid">
                    <?php foreach ($myRequests as $row): ?>
                        <?php
                            $trangThai = $row['trangThai'] ?? 'pending';
                            $lt = $row['loaiNghiPhep'] ?? 'personal';
                            $badgeClass = 'lr-badge-' . $trangThai;
                            $iconClass = $statusIcons[$trangThai] ?? 'fa-clock';
                            $cardClass = 'trangThai-' . $trangThai;
                        ?>
                        <div class="lr-history-card <?= $cardClass ?>" data-id="<?= (int)$row['id'] ?>">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="font-size: 13px; font-weight: 700; color: #1e293b;">
                                    <?= htmlspecialchars($leaveTypeLabels[$lt] ?? $lt) ?>
                                </div>
                                <span class="lr-badge <?= $badgeClass ?>"><i class="fas <?= $iconClass ?>"></i> <?= $statusLabels[$trangThai] ?></span>
                            </div>
                            
                            <div style="font-size: 12px; color: #475569; display: flex; gap: 8px;">
                                <i class="far fa-calendar-alt" style="margin-top: 2px;"></i>
                                <span style="font-weight: 600;">
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($row['tuNgay'] ?? ''))) ?> 
                                    — 
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($row['denNgay'] ?? ''))) ?>
                                </span>
                            </div>

                            <div style="font-size: 12px; color: #475569; background: rgba(255,255,255,0.6); padding: 6px 10px; border-radius: 4px; border-left: 2px solid rgba(0,0,0,0.05);">
                                <?= nl2br(htmlspecialchars($row['lyDo'] ?? '')) ?>
                            </div>

                            <div style="margin-top: auto; padding-top: 8px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; gap: 10px;">
                                    <?php if (!empty($row['tepMinhChung'])): ?>
                                        <?php 
                                            $fileUrl = $row['tepMinhChung'];
                                            if (strpos($fileUrl, 'uploads/') !== 0) {
                                                $fileUrl = 'uploads/leave_evidence/' . $fileUrl;
                                            }
                                        ?>
                                        <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" style="font-size: 11px; color: #4f6ef7; text-decoration: none; font-weight: 600;">
                                            <i class="fas fa-paperclip"></i> Minh chứng
                                        </a>
                                    <?php endif; ?>
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
                    Chưa có đơn nghỉ phép nào.
                </div>
            <?php endif; ?>
        </div>
        <!-- DETAIL MODAL -->
        <div id="detailModal" class="lr-modal">
            <div class="lr-modal-content">
                <div class="lr-modal-header">
                    <h4 style="margin:0; font-size:15px; font-weight:700; color:#1e293b;"><i class="fas fa-circle-info" style="color:#4f6ef7"></i> Chi tiết đơn nghỉ</h4>
                    <button onclick="closeModal()" style="border:none; background:none; cursor:pointer; color:#94a3b8; font-size:18px;"><i class="fas fa-times"></i></button>
                </div>
                <div class="lr-modal-body" id="modalBody">
                    <p style="text-align:center; color:#64748b;">Đang tải...</p>
                </div>
                <div class="lr-modal-footer">
                    <button class="btn-success" onclick="closeModal()">Đóng</button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('leaveForm');
    var fromDate = document.getElementById('tuNgay');
    var toDate = document.getElementById('denNgay');
    var fileInput = document.getElementById('tepMinhChung');
    var submitBtn = document.getElementById('submitBtn');
    var submitText = document.getElementById('submitText');
    var submitLoading = document.getElementById('submitLoading');

    // Sync min date on tuNgay change
    fromDate.addEventListener('change', function() {
        toDate.min = this.value;
        if (toDate.value && toDate.value < this.value) {
            toDate.value = this.value;
        }
    });

    // Show file name
    fileInput.addEventListener('change', function() {
        var fileTextEl = document.getElementById('fileText');
        if (this.files && this.files.length > 0) {
            fileTextEl.textContent = this.files[0].name;
            fileTextEl.style.color = '#4f6ef7';
            fileTextEl.style.fontWeight = '600';
        } else {
            fileTextEl.textContent = 'Chọn minh chứng...';
            fileTextEl.style.color = '';
            fileTextEl.style.fontWeight = '';
        }
    });

    // Client-side validation
    form.addEventListener('submit', function(e) {
        var valid = true;

        // Leave type
        var leaveType = document.getElementById('loaiNghiPhep');
        if (!leaveType.value) {
            document.getElementById('err-leave-type').style.display = 'block';
            leaveType.style.borderColor = '#ef4444';
            valid = false;
        } else {
            document.getElementById('err-leave-type').style.display = 'none';
            leaveType.style.borderColor = '';
        }

        // Date range
        if (!fromDate.value) {
            document.getElementById('err-from-date').style.display = 'block';
            fromDate.style.borderColor = '#ef4444';
            valid = false;
        } else {
            document.getElementById('err-from-date').style.display = 'none';
            fromDate.style.borderColor = '';
        }

        if (fromDate.value && toDate.value && fromDate.value > toDate.value) {
            document.getElementById('err-to-date').style.display = 'block';
            toDate.style.borderColor = '#ef4444';
            valid = false;
        } else if (!toDate.value) {
            document.getElementById('err-to-date').style.display = 'block';
            toDate.style.borderColor = '#ef4444';
            valid = false;
        } else {
            document.getElementById('err-to-date').style.display = 'none';
            toDate.style.borderColor = '';
        }

        // Reason
        var lyDo = document.getElementById('lyDo');
        if (!lyDo.value.trim()) {
            document.getElementById('err-lyDo').style.display = 'block';
            lyDo.style.borderColor = '#ef4444';
            valid = false;
        } else {
            document.getElementById('err-lyDo').style.display = 'none';
            lyDo.style.borderColor = '';
        }

        if (!valid) {
            e.preventDefault();
            return;
        }

        // Loading state
        submitBtn.disabled = true;
        submitText.style.display = 'none';
        submitLoading.classList.add('active');
    });

    // Auto-dismiss alerts after 5s
    var alerts = document.querySelectorAll('.lr-alert');
    alerts.forEach(function(a) {
        setTimeout(function() { a.style.opacity = '0'; a.style.transform = 'translateY(-8px)'; setTimeout(function() { a.remove(); }, 300); }, 5000);
    });

    // Detail Modal Logic
    window.openModal = function(id) {
        const modal = document.getElementById('detailModal');
        const body = document.getElementById('modalBody');
        modal.style.display = 'flex';
        body.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin" style="font-size:24px; color:#4f6ef7"></i><p style="margin-top:10px; color:#64748b">Đang lấy dữ liệu...</p></div>';

        fetch(`index.php?page=get-leave-detail&id=${id}`)
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const d = res.data;
                    const statusClass = `lr-badge-${d.trangThai}`;
                    const statusText = d.trangThai === 'approved' ? 'Đã duyệt' : (d.trangThai === 'rejected' ? 'Từ chối' : 'Chờ duyệt');
                    
                    body.innerHTML = `
                        <div class="lr-detail-row">
                            <div class="lr-detail-label">Loại nghỉ</div>
                            <div class="lr-detail-val">${d.leave_type_text}</div>
                        </div>
                        <div class="lr-detail-row">
                            <div class="lr-detail-label">Thời gian</div>
                            <div class="lr-detail-val">${d.from_date_fmt} đến ${d.to_date_fmt}</div>
                        </div>
                        <div class="lr-detail-row">
                            <div class="lr-detail-label">Lý do</div>
                            <div class="lr-detail-val">${d.lyDo}</div>
                        </div>
                        <div class="lr-detail-row">
                            <div class="lr-detail-label">Trạng thái</div>
                            <div class="lr-detail-val"><span class="lr-badge ${statusClass}">${statusText}</span></div>
                        </div>
                        ${d.approver_name ? `
                        <div class="lr-detail-row">
                            <div class="lr-detail-label">Người duyệt</div>
                            <div class="lr-detail-val">${d.approver_name}</div>
                        </div>
                        <div class="lr-detail-row">
                            <div class="lr-detail-label">Ngày duyệt</div>
                            <div class="lr-detail-val">${d.approved_at_fmt}</div>
                        </div>
                        ` : ''}
                        ${d.ghiChuNS ? `
                        <div class="lr-detail-row">
                            <div class="lr-detail-label">Phản hồi</div>
                            <div class="lr-detail-val" style="color:#ef4444">${d.ghiChuNS}</div>
                        </div>
                        ` : ''}
                        <div class="lr-detail-row" style="border:none">
                            <div class="lr-detail-label">Ngày gửi</div>
                            <div class="lr-detail-val">${d.created_at_fmt}</div>
                        </div>
                    `;
                } else {
                    body.innerHTML = `<p style="color:#ef4444; text-align:center;">Lỗi: ${res.message}</p>`;
                }
            })
            .catch(err => {
                body.innerHTML = '<p style="color:#ef4444; text-align:center;">Không thể kết nối máy chủ.</p>';
            });
    };

    window.closeModal = function() {
        document.getElementById('detailModal').style.display = 'none';
    };

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('detailModal');
        if (event.target == modal) {
            closeModal();
        }
    });

    // Add click event to cards
    document.querySelectorAll('.lr-history-card').forEach(card => {
        card.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            if (id) openModal(id);
        });
    });
});
</script>
    </div><!-- End lr-container -->
</div>
<?php include 'app/views/layouts/footer.php'; ?>
