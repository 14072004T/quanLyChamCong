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
/* ===== LEAVE REQUEST FORM STYLES ===== */
.lr-page-header { display: flex; align-items: center; gap: 16px; }
.lr-page-header .lr-icon-circle {
    width: 52px; height: 52px; border-radius: 14px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 22px; flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(99,102,241,.3);
}
.lr-page-header h2 { margin: 0; font-size: 1.35em; color: #1e293b; }
.lr-page-header p { margin: 4px 0 0; color: #64748b; font-size: .9em; }

.lr-card {
    background: #fff; border-radius: 14px; padding: 28px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.lr-card-title {
    font-size: 1.05em; font-weight: 600; color: #1e293b; margin: 0 0 20px;
    padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: 10px;
}
.lr-card-title i { color: #6366f1; font-size: .95em; }

.lr-field { margin-bottom: 20px; }
.lr-field label {
    display: block; font-weight: 500; color: #334155; margin-bottom: 6px; font-size: .9em;
}
.lr-field label .req { color: #ef4444; margin-left: 2px; }
.lr-field input, .lr-field select, .lr-field textarea {
    width: 100%; padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: .92em; font-family: 'Inter', sans-serif; color: #1e293b;
    background: #f8fafc; transition: border-color .2s, box-shadow .2s;
    box-sizing: border-box;
}
.lr-field input:focus, .lr-field select:focus, .lr-field textarea:focus {
    outline: none; border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.12); background: #fff;
}
.lr-field textarea { resize: vertical; min-height: 90px; }
.lr-field .lr-hint { font-size: .8em; color: #94a3b8; margin-top: 4px; display: block; }
.lr-field .lr-error-hint { font-size: .8em; color: #ef4444; margin-top: 4px; display: none; }

.lr-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.lr-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
@media (max-width: 640px) {
    .lr-grid-2, .lr-grid-3 { grid-template-columns: 1fr; }
}

.lr-file-zone {
    border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px;
    text-align: center; cursor: pointer; transition: border-color .2s, background .2s;
    position: relative; background: #f8fafc;
}
.lr-file-zone:hover { border-color: #6366f1; background: #f1f0ff; }
.lr-file-zone input[type="file"] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.lr-file-zone .lr-file-icon { font-size: 28px; color: #94a3b8; margin-bottom: 8px; }
.lr-file-zone .lr-file-text { font-size: .88em; color: #64748b; }
.lr-file-zone .lr-file-text strong { color: #6366f1; }
.lr-file-zone .lr-file-formats { font-size: .78em; color: #94a3b8; margin-top: 6px; }
.lr-file-name {
    font-size: .85em; color: #6366f1; margin-top: 8px; font-weight: 500; display: none;
}

.lr-actions { display: flex; gap: 12px; margin-top: 24px; }
.lr-actions .btn { flex: 1; padding: 12px 20px; border-radius: 10px; font-size: .95em; font-weight: 600; }

.lr-alert {
    padding: 14px 18px; border-radius: 10px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px; font-size: .92em;
    animation: lrSlideIn .3s ease;
}
@keyframes lrSlideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
.lr-alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
.lr-alert-success i { color: #10b981; }
.lr-alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.lr-alert-error i { color: #ef4444; }

/* History table */
.lr-table { width: 100%; border-collapse: collapse; }
.lr-table thead th {
    background: #f8fafc; color: #64748b; font-size: .82em; font-weight: 600;
    text-transform: uppercase; letter-spacing: .4px; padding: 10px 14px;
    text-align: left; border-bottom: 2px solid #e2e8f0;
}
.lr-table tbody td {
    padding: 12px 14px; border-bottom: 1px solid #f1f5f9;
    font-size: .9em; color: #334155; vertical-align: middle;
}
.lr-table tbody tr:hover { background: #f8fafc; }
.lr-table tbody tr:last-child td { border-bottom: none; }

.lr-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px; font-size: .8em; font-weight: 600;
    white-space: nowrap;
}
.lr-badge-pending  { background: #fef3c7; color: #92400e; }
.lr-badge-approved { background: #d1fae5; color: #065f46; }
.lr-badge-rejected { background: #fecaca; color: #991b1b; }

.lr-type-chip {
    display: inline-block; padding: 2px 10px; border-radius: 6px;
    font-size: .8em; font-weight: 500; background: #ede9fe; color: #6d28d9;
}

.lr-empty {
    text-align: center; padding: 40px 20px; color: #94a3b8;
}
.lr-empty i { font-size: 2.5em; margin-bottom: 12px; display: block; color: #cbd5e1; }

.lr-approver-info { font-size: .78em; color: #64748b; margin-top: 3px; }
.lr-approver-info i { font-size: .85em; margin-right: 2px; }

.lr-info-box {
    background: linear-gradient(135deg, #eff6ff 0%, #f0f9ff 100%);
    border: 1px solid #bfdbfe; border-radius: 12px; padding: 18px 22px;
}
.lr-info-box h4 { margin: 0 0 10px; color: #1e40af; font-size: .95em; }
.lr-info-box ul { margin: 0; padding-left: 20px; color: #1e40af; font-size: .88em; }
.lr-info-box ul li { margin-bottom: 5px; }

.lr-loading { display: none; }
.lr-loading.active {
    display: inline-flex; align-items: center; gap: 8px;
}
.lr-spinner {
    width: 16px; height: 16px; border: 2px solid #fff; border-top-color: transparent;
    border-radius: 50%; animation: lrSpin .6s linear infinite;
}
@keyframes lrSpin { to { transform: rotate(360deg); } }
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">

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

            <form method="POST" action="index.php?page=store-leave-request" enctype="multipart/form-data" id="leaveForm">
                <!-- Row 1: Leave type -->
                <div class="lr-field">
                    <label for="leave_type">Loại nghỉ phép <span class="req">*</span></label>
                    <select id="leave_type" name="leave_type" required>
                        <option value="" disabled selected>— Chọn loại nghỉ phép —</option>
                        <?php foreach ($leaveTypeLabels as $val => $lbl): ?>
                            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="lr-error-hint" id="err-leave-type">Vui lòng chọn loại nghỉ phép</span>
                </div>

                <!-- Row 2: Date range -->
                <div class="lr-grid-2">
                    <div class="lr-field">
                        <label for="from_date">Từ ngày <span class="req">*</span></label>
                        <input type="date" id="from_date" name="from_date" required min="<?= date('Y-m-d') ?>">
                        <span class="lr-hint">Ngày bắt đầu nghỉ</span>
                        <span class="lr-error-hint" id="err-from-date">Vui lòng chọn ngày bắt đầu</span>
                    </div>
                    <div class="lr-field">
                        <label for="to_date">Đến ngày <span class="req">*</span></label>
                        <input type="date" id="to_date" name="to_date" required min="<?= date('Y-m-d') ?>">
                        <span class="lr-hint">Ngày kết thúc nghỉ</span>
                        <span class="lr-error-hint" id="err-to-date">Ngày kết thúc phải sau ngày bắt đầu</span>
                    </div>
                </div>

                <!-- Row 3: Reason -->
                <div class="lr-field">
                    <label for="reason">Lý do chi tiết <span class="req">*</span></label>
                    <textarea id="reason" name="reason" rows="4"
                              placeholder="Mô tả chi tiết lý do xin nghỉ phép. VD: Nghỉ phép năm theo kế hoạch, khám sức khỏe định kỳ..." required></textarea>
                    <span class="lr-hint">Giải thích rõ ràng giúp đơn được phê duyệt nhanh hơn</span>
                    <span class="lr-error-hint" id="err-reason">Vui lòng nhập lý do nghỉ phép</span>
                </div>

                <!-- Row 4: Evidence file -->
                <div class="lr-field">
                    <label>Minh chứng đính kèm <span style="color:#94a3b8; font-weight:400;">(không bắt buộc)</span></label>
                    <div class="lr-file-zone" id="fileZone">
                        <input type="file" name="evidence_file" id="evidence_file" accept=".jpg,.jpeg,.png,.pdf">
                        <div class="lr-file-icon"><i class="fas fa-cloud-arrow-up"></i></div>
                        <div class="lr-file-text"><strong>Kéo thả</strong> hoặc nhấn để chọn file</div>
                        <div class="lr-file-formats">JPG, PNG hoặc PDF — tối đa 5MB</div>
                    </div>
                    <div class="lr-file-name" id="fileName"></div>
                </div>

                <!-- Actions -->
                <div class="lr-actions">
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <span id="submitText"><i class="fas fa-paper-plane"></i> Gửi Đơn Nghỉ Phép</span>
                        <span class="lr-loading" id="submitLoading"><span class="lr-spinner"></span> Đang gửi...</span>
                    </button>
                    <a href="index.php?page=cham-cong-dashboard" class="btn btn-secondary" style="text-align: center;">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>
            </form>
        </div>

        <!-- HISTORY TABLE -->
        <div class="lr-card" style="margin-top: 20px;">
            <div class="lr-card-title">
                <i class="fas fa-clock-rotate-left"></i> Lịch Sử Đơn Nghỉ Phép
            </div>

            <?php if (!empty($myRequests) && is_array($myRequests)): ?>
                <div style="overflow-x: auto;">
                <table class="lr-table">
                    <thead>
                        <tr>
                            <th>Loại phép</th>
                            <th>Từ ngày</th>
                            <th>Đến ngày</th>
                            <th>Lý do</th>
                            <th>Minh chứng</th>
                            <th>Trạng thái</th>
                            <th>Ngày gửi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myRequests as $row): ?>
                            <?php
                                $st = $row['status'] ?? 'pending';
                                $lt = $row['leave_type'] ?? 'personal';
                            ?>
                            <tr>
                                <td><span class="lr-type-chip"><?= htmlspecialchars($leaveTypeLabels[$lt] ?? $lt) ?></span></td>
                                <td><strong><?= htmlspecialchars($row['from_date'] ?? '') ?></strong></td>
                                <td><strong><?= htmlspecialchars($row['to_date'] ?? '') ?></strong></td>
                                <td title="<?= htmlspecialchars($row['reason'] ?? '') ?>">
                                    <?= htmlspecialchars(mb_strimwidth($row['reason'] ?? '', 0, 50, '...')) ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['evidence_file'])): ?>
                                        <a href="<?= htmlspecialchars($row['evidence_file']) ?>" target="_blank" style="color:#6366f1; font-size:.85em;">
                                            <i class="fas fa-paperclip"></i> Xem file
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="lr-badge lr-badge-<?= $st ?>">
                                        <i class="fas <?= $statusIcons[$st] ?? 'fa-clock' ?>"></i>
                                        <?= htmlspecialchars($statusLabels[$st] ?? $st) ?>
                                    </span>
                                    <?php if ($st !== 'pending' && !empty($row['approver_name'])): ?>
                                        <div class="lr-approver-info">
                                            <i class="fas fa-user-check"></i>
                                            <?= htmlspecialchars($row['approver_name']) ?>
                                            <?php if (!empty($row['approved_at'])): ?>
                                                · <?= htmlspecialchars(substr($row['approved_at'], 0, 16)) ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:.88em; color:#64748b;">
                                    <?= htmlspecialchars(substr($row['created_at'] ?? '', 0, 10)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <div class="lr-empty">
                    <i class="fas fa-inbox"></i>
                    <p>Chưa có đơn nghỉ phép nào.</p>
                    <p style="font-size:.85em;">Điền form bên trên để gửi đơn đầu tiên.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info box -->
        <div class="lr-info-box" style="margin-top: 20px;">
            <h4><i class="fas fa-lightbulb"></i> Hướng dẫn gửi đơn</h4>
            <ul>
                <li>Chọn đúng loại nghỉ phép để HR xử lý chính xác</li>
                <li>Ngày bắt đầu không được sau ngày kết thúc</li>
                <li>Đính kèm minh chứng (giấy khám bệnh, giấy mời...) giúp phê duyệt nhanh hơn</li>
                <li>Đơn sẽ được HR / Quản lý xem xét trong vòng 1-2 ngày làm việc</li>
            </ul>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('leaveForm');
    var fromDate = document.getElementById('from_date');
    var toDate = document.getElementById('to_date');
    var fileInput = document.getElementById('evidence_file');
    var fileNameEl = document.getElementById('fileName');
    var submitBtn = document.getElementById('submitBtn');
    var submitText = document.getElementById('submitText');
    var submitLoading = document.getElementById('submitLoading');

    // Sync min date on from_date change
    fromDate.addEventListener('change', function() {
        toDate.min = this.value;
        if (toDate.value && toDate.value < this.value) {
            toDate.value = this.value;
        }
    });

    // Show file name
    fileInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            fileNameEl.textContent = '📎 ' + this.files[0].name;
            fileNameEl.style.display = 'block';
        } else {
            fileNameEl.style.display = 'none';
        }
    });

    // Client-side validation
    form.addEventListener('submit', function(e) {
        var valid = true;

        // Leave type
        var leaveType = document.getElementById('leave_type');
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
        var reason = document.getElementById('reason');
        if (!reason.value.trim()) {
            document.getElementById('err-reason').style.display = 'block';
            reason.style.borderColor = '#ef4444';
            valid = false;
        } else {
            document.getElementById('err-reason').style.display = 'none';
            reason.style.borderColor = '';
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
});
</script>

<?php include 'app/views/layouts/footer.php'; ?>
