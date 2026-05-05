<?php 
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}

$message = $message ?? '';
$messageType = $messageType ?? '';
$requests = $requests ?? [];
$attendanceRecords = $attendanceRecords ?? [];
$todayShiftStatus = $todayShiftStatus ?? [];
$userShift = $userShift ?? null;
$activeRequestId = (int)($_GET['request_id'] ?? 0);

// Flash messages from storeEditRequest
$flashSuccess = $_SESSION['edit_request_success'] ?? '';
$flashError = $_SESSION['edit_request_error'] ?? '';
unset($_SESSION['edit_request_success'], $_SESSION['edit_request_error']);

if ($flashSuccess) { $message = $flashSuccess; $messageType = 'success'; }
if ($flashError) { $message = $flashError; $messageType = 'error'; }

// Shift status data
$shiftInfo = $todayShiftStatus['shift'] ?? null;
$shiftStatus = $todayShiftStatus['status'] ?? null;
$todayIn = $todayShiftStatus['first_in'] ?? null;
$todayOut = $todayShiftStatus['last_out'] ?? null;
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<style>
:root { --yc-primary: #4f6ef7; --yc-success: #10b981; --yc-danger: #ef4444; --yc-border: #e2e8f0; --yc-bg: #f8fafc; --yc-text: #475569; --yc-dark: #1e293b; }
.yc-container { max-width: 1000px; margin: 0 auto; padding: 0 10px; font-family: 'Inter', sans-serif; }
.yc-card { background:#fff; border-radius:8px; padding:16px; box-shadow:0 1px 3px rgba(0,0,0,0.05); margin-bottom:12px; border:1px solid var(--yc-border); }
.yc-card-title { font-size:14px; font-weight:600; color:var(--yc-dark); margin:0 0 12px; display:flex; align-items:center; gap:8px; padding-bottom:10px; border-bottom:1px solid #f1f5f9; }
.yc-card-title i { color:var(--yc-primary); font-size:14px; }

/* STRICT GRID FORM */
.yc-grid-form {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    align-items: end;
}

.yc-form-group { display: flex; flex-direction: column; gap: 4px; }
.yc-label { font-weight: 600; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }

.yc-input { width:100%; height:34px; padding:0 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-family:'Inter',sans-serif; background:#fff; transition:all .15s; box-sizing:border-box; }
.yc-input[readonly] { background:#f1f5f9; color:#64748b; cursor:not-allowed; }

.yc-grid-cell-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px; }
.yc-grid-cell-box.proposed { background: #f0fdf4; border-color: #bbf7d0; }
.yc-cell-title { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; gap: 4px; }

.col-span-3 { grid-column: span 3; }
.col-span-2 { grid-column: span 2; }

/* File upload */
.yc-file-zone { border:1px dashed #cbd5e1; border-radius:6px; height:34px; background:#f8fafc; display:flex; align-items:center; gap:8px; padding:0 10px; cursor:pointer; position:relative; box-sizing:border-box; }
.yc-file-zone:hover { border-color:var(--yc-primary); background:#f1f5f9; }
.yc-file-zone p { font-size:11px; color:#64748b; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* Buttons */
.yc-btn-wrap { display: flex; justify-content: flex-end; }
.yc-btn-primary { background:var(--yc-primary); color:#fff; height:34px; padding:0 20px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; border:none; display:flex; align-items:center; gap:6px; transition:all .2s; }
.yc-btn-primary:hover { background:#3b5de7; }

/* Alert */
.yc-alert { padding:10px 16px; border-radius:6px; margin-bottom:16px; display:flex; align-items:center; gap:10px; font-size:13px; font-weight:500; }
.yc-alert-success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
.yc-alert-error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }

/* History Grid */
.yc-history-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 10px;
}
@media (max-width: 768px) {
    .yc-history-grid { grid-template-columns: 1fr; }
}

.yc-history-card {
    background: #fff; border: 1px solid var(--yc-border); border-radius: 8px; padding: 12px;
    display: flex; flex-direction: column; gap: 8px; transition: all 0.2s ease;
}
.yc-history-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

/* Status-specific card styles */
.yc-history-card.status-approved { background: #f0fdf4; border-left: 4px solid #10b981; }
.yc-history-card.status-pending  { background: #fffbeb; border-left: 4px solid #f59e0b; }
.yc-history-card.status-rejected { background: #fef2f2; border-left: 4px solid #ef4444; }

/* Refined Badge Colors */
.yc-badge-approved { background: #10b981; color: #fff; }
.yc-badge-pending  { background: #f59e0b; color: #fff; }
.yc-badge-rejected { background: #ef4444; color: #fff; }

/* Modal Styles */
.yc-modal { display:none; position:fixed; z-index:9999; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; padding:20px; backdrop-filter:blur(2px); }
.yc-modal-content { background:#fff; border-radius:12px; width:100%; max-width:500px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1); overflow:hidden; animation:modalSlideUp 0.3s ease; }
@keyframes modalSlideUp { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }
.yc-modal-header { padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
.yc-modal-body { padding:20px; }
.yc-modal-footer { padding:12px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; }
.yc-detail-row { display:flex; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:8px; }
.yc-detail-label { width:120px; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; }
.yc-detail-val { flex:1; font-size:13px; color:#1e293b; font-weight:500; }
.yc-history-card { cursor: pointer; }

@media(max-width:640px) { .yc-grid2 { grid-template-columns:1fr; } .yc-container { padding: 0 5px; } }
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">
        <div class="yc-container">
            <!-- COMPACT HEADER BAR -->
            <div class="yc-card" style="padding: 10px 16px; margin-top: 10px; border-left: 4px solid var(--yc-primary);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #eef2ff; color: var(--yc-primary); display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <div style="font-size: 15px; font-weight: 700; color: var(--yc-dark);">Điều Chỉnh Công</div>
                        <div style="font-size: 11px; color: #64748b;">Gửi yêu cầu chỉnh sửa giờ vào/ra khi có sai sót</div>
                    </div>
                </div>
            </div>

            <!-- ALERTS -->
            <?php if ($message): ?>
                <div class="yc-alert <?= $messageType === 'success' ? 'yc-alert-success' : 'yc-alert-error' ?>">
                    <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- EDIT REQUEST FORM -->
            <div class="yc-card" style="padding: 16px;">
                <form method="POST" action="index.php?page=store-edit-request" enctype="multipart/form-data" id="editRequestForm">
                    <!-- Date Selection Row -->
                    <div style="margin-bottom: 16px; display: flex; align-items: center; gap: 12px;">
                        <label class="yc-label" style="margin-bottom: 0; white-space: nowrap;">Ngày cần điều chỉnh:</label>
                        <input type="date" id="attendance-date" name="attendance_date" class="yc-input" required max="<?= date('Y-m-d') ?>" style="width: 180px;">
                    </div>

                    <!-- Comparison Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <!-- Current Data -->
                        <div style="background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px;">
                            <div style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-database"></i> Dữ liệu hiện tại
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div class="yc-form-group">
                                    <label class="yc-label">Giờ vào</label>
                                    <input type="text" id="original-checkin" class="yc-input" readonly value="--:--" style="height: 32px;">
                                </div>
                                <div class="yc-form-group">
                                    <label class="yc-label">Giờ ra</label>
                                    <input type="text" id="original-checkout" class="yc-input" readonly value="--:--" style="height: 32px;">
                                </div>
                            </div>
                        </div>

                        <!-- Proposed Data -->
                        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px;">
                            <div style="font-size: 11px; font-weight: 700; color: #1d4ed8; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-magic"></i> Dữ liệu đề xuất
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div class="yc-form-group">
                                    <label class="yc-label">Giờ vào mới</label>
                                    <input type="datetime-local" id="proposed-checkin" name="proposed_checkin" class="yc-input" style="height: 32px;">
                                </div>
                                <div class="yc-form-group">
                                    <label class="yc-label">Giờ ra mới</label>
                                    <input type="datetime-local" id="proposed-checkout" name="proposed_checkout" class="yc-input" style="height: 32px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom row: Reason + Evidence -->
                    <div style="display: grid; grid-template-columns: 1fr 280px; gap: 16px; align-items: end; margin-bottom: 16px;">
                        <div class="yc-form-group">
                            <label class="yc-label" for="reason">Lý do điều chỉnh <span style="color:var(--yc-danger)">*</span></label>
                            <input type="text" id="reason" name="reason" class="yc-input" required placeholder="VD: Quên bấm thẻ, Đi công tác..." style="height: 36px;">
                        </div>
                        <div class="yc-form-group">
                            <label class="yc-label">Minh chứng (Ảnh/PDF)</label>
                            <div class="yc-file-zone" id="fileZone" onclick="document.getElementById('evidenceFile').click()" style="height: 36px;">
                                <i class="fas fa-paperclip"></i>
                                <p id="fileText">Chọn file minh chứng...</p>
                                <input type="file" id="evidenceFile" name="evidence_file" accept=".jpg,.jpeg,.png,.pdf" style="display:none">
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button aligned right -->
                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" class="yc-btn-primary" style="height: 38px; padding: 0 24px;">
                            <i class="fas fa-paper-plane"></i> Gửi yêu cầu phê duyệt
                        </button>
                    </div>
                </form>
            </div>

            <!-- REQUEST HISTORY -->
            <div class="yc-card">
                <div class="yc-card-title"><i class="fas fa-history"></i> Lịch Sử Yêu Cầu</div>
                <?php if (!empty($requests) && is_array($requests)): ?>
                    <div class="yc-history-grid">
                        <?php foreach ($requests as $row): ?>
                            <?php
                                $status = $row['status'] ?? 'pending';
                                $badgeClass = 'yc-badge-' . $status; $statusText = 'Chờ duyệt'; $iconClass = 'fa-clock';
                                if ($status === 'approved') { $statusText = 'Đã duyệt'; $iconClass = 'fa-check-circle'; }
                                elseif ($status === 'rejected') { $statusText = 'Từ chối'; $iconClass = 'fa-times-circle'; }
                                $cardClass = 'status-' . $status;
                            ?>
                            <div class="yc-history-card <?= $cardClass ?>" id="request-<?= (int)($row['id'] ?? 0) ?>" data-id="<?= (int)($row['id'] ?? 0) ?>">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div style="font-size: 13px; font-weight: 700; color: var(--yc-dark);">
                                        <i class="far fa-calendar-alt" style="color:#64748b;margin-right:4px"></i> <?= htmlspecialchars($row['attendance_date'] ?? '') ?>
                                    </div>
                                    <span class="yc-badge <?= $badgeClass ?>"><i class="fas <?= $iconClass ?>"></i> <?= $statusText ?></span>
                                </div>

                                <div style="font-size: 11px; color: #475569; background: rgba(255,255,255,0.6); padding: 4px 8px; border-radius: 4px; display: inline-block;">
                                    Sửa thành: <span style="font-weight: 700; color: var(--yc-dark);">
                                        <?= !empty($row['proposed_checkin']) ? date('H:i', strtotime($row['proposed_checkin'])) : '--:--' ?> 
                                        - 
                                        <?= !empty($row['proposed_checkout']) ? date('H:i', strtotime($row['proposed_checkout'])) : '--:--' ?>
                                    </span>
                                </div>

                                <div style="font-size: 12px; color: var(--yc-text); background: rgba(255,255,255,0.4); padding: 6px 10px; border-radius: 6px; border-left: 2px solid rgba(0,0,0,0.05);">
                                    <?= nl2br(htmlspecialchars($row['reason'] ?? '')) ?>
                                </div>

                                <div style="margin-top: auto; padding-top: 8px; border-top: 1px solid rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <?php if (!empty($row['evidence_file'])): ?>
                                            <a href="uploads/attendance_evidence/<?= htmlspecialchars($row['evidence_file']) ?>" target="_blank" style="font-size: 11px; color: var(--yc-primary); text-decoration: none; font-weight: 600;"><i class="fas fa-file-alt"></i> Minh chứng</a>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 10px; color: #64748b; font-style: italic;">Gửi: <?= date('d/m/Y H:i', strtotime($row['created_at'] ?? '')) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; color: #94a3b8;"><i class="fas fa-inbox" style="font-size: 20px; margin-bottom: 8px; display: block;"></i> Chưa có yêu cầu nào</div>
                <?php endif; ?>
            </div>

            <div style="text-align:center;margin-top:20px">
                <a href="index.php?page=cham-cong-dashboard" class="yc-btn yc-btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
        </div>
    </div>
</div>

<!-- DETAIL MODAL -->
<div id="detailModal" class="yc-modal">
    <div class="yc-modal-content">
        <div class="yc-modal-header">
            <h4 style="margin:0; font-size:15px; font-weight:700; color:#1e293b;"><i class="fas fa-circle-info" style="color:var(--yc-primary)"></i> Chi tiết yêu cầu</h4>
            <button onclick="closeModal()" style="border:none; background:none; cursor:pointer; color:#94a3b8; font-size:18px;"><i class="fas fa-times"></i></button>
        </div>
        <div class="yc-modal-body" id="modalBody">
            <p style="text-align:center; color:#64748b;">Đang tải...</p>
        </div>
        <div class="yc-modal-footer">
            <button class="yc-btn-primary" onclick="closeModal()">Đóng</button>
        </div>
    </div>
</div>

<script>
// Attendance records data from PHP
var attendanceData = <?= json_encode($attendanceRecords ?? []) ?>;

// Auto-load original data when date changes
document.getElementById('attendance-date').addEventListener('change', function() {
    var selectedDate = this.value;
    var inField = document.getElementById('original-checkin');
    var outField = document.getElementById('original-checkout');
    inField.value = '--:--';
    outField.value = '--:--';

    for (var i = 0; i < attendanceData.length; i++) {
        if (attendanceData[i].work_date === selectedDate) {
            if (attendanceData[i].first_in) {
                inField.value = attendanceData[i].first_in.substring(11, 16) + ' (' + attendanceData[i].first_in.substring(0, 10) + ')';
            }
            if (attendanceData[i].last_out) {
                outField.value = attendanceData[i].last_out.substring(11, 16) + ' (' + attendanceData[i].last_out.substring(0, 10) + ')';
            }
            break;
        }
    }
});

// File preview
document.getElementById('evidenceFile').addEventListener('change', function() {
    var fileText = document.getElementById('fileText');
    if (this.files.length > 0) {
        var f = this.files[0];
        if (f.size > 5 * 1024 * 1024) {
            alert('File quá lớn. Tối đa 5MB.');
            this.value = '';
            fileText.textContent = 'Chọn file...';
            return;
        }
        fileText.textContent = f.name;
        fileText.style.color = 'var(--yc-primary)';
    } else {
        fileText.textContent = 'Chọn file...';
        fileText.style.color = '';
    }
});

// Drag & drop
var zone = document.getElementById('fileZone');
zone.addEventListener('dragover', function(e) { e.preventDefault(); this.style.borderColor = '#6366f1'; });
zone.addEventListener('dragleave', function() { this.style.borderColor = ''; });
zone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.style.borderColor = '';
    var input = document.getElementById('evidenceFile');
    if (e.dataTransfer.files.length > 0) {
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
    }
});

// Scroll to active request
var activeId = <?= (int)$activeRequestId ?>;
if (activeId) {
    var el = document.getElementById('request-' + activeId);
    if (el) { el.scrollIntoView({behavior:'smooth',block:'center'}); el.style.background='#fffbeb'; }
}

// Form validation and prevent multiple submissions
document.getElementById('editRequestForm').addEventListener('submit', function(e) {
    var fileInput = document.getElementById('evidenceFile');
    if (fileInput.files.length === 0) {
        e.preventDefault();
        alert('Vui lòng đính kèm minh chứng (ảnh hoặc PDF)!');
        return;
    }
    
    var submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) {
        setTimeout(function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        }, 0);
    }
});

// DETAIL MODAL LOGIC
window.openModal = function(id) {
    const modal = document.getElementById('detailModal');
    const body = document.getElementById('modalBody');
    modal.style.display = 'flex';
    body.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin" style="font-size:24px; color:var(--yc-primary)"></i><p style="margin-top:10px; color:#64748b">Đang lấy dữ liệu...</p></div>';

    fetch(`index.php?page=get-correction-detail&id=${id}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                const d = res.data;
                const statusClass = `yc-badge-${d.status}`;
                const statusText = d.status === 'approved' ? 'Đã duyệt' : (d.status === 'rejected' ? 'Từ chối' : 'Chờ duyệt');
                
                body.innerHTML = `
                    <div class="yc-detail-row">
                        <div class="yc-detail-label">Ngày điều chỉnh</div>
                        <div class="yc-detail-val">${d.attendance_date_fmt}</div>
                    </div>
                    <div class="yc-detail-row">
                        <div class="yc-detail-label">Giờ gốc</div>
                        <div class="yc-detail-val">${d.old_time_fmt}</div>
                    </div>
                    <div class="yc-detail-row">
                        <div class="yc-detail-label">Giờ đề xuất</div>
                        <div class="yc-detail-val">${d.proposed_checkin_fmt} - ${d.proposed_checkout_fmt}</div>
                    </div>
                    <div class="yc-detail-row">
                        <div class="yc-detail-label">Lý do</div>
                        <div class="yc-detail-val">${d.reason}</div>
                    </div>
                    <div class="yc-detail-row">
                        <div class="yc-detail-label">Trạng thái</div>
                        <div class="yc-detail-val"><span class="yc-badge ${statusClass}">${statusText}</span></div>
                    </div>
                    ${d.approver_name ? `
                    <div class="yc-detail-row">
                        <div class="yc-detail-label">Người duyệt</div>
                        <div class="yc-detail-val">${d.approver_name}</div>
                    </div>
                    <div class="yc-detail-row">
                        <div class="yc-detail-label">Ngày duyệt</div>
                        <div class="yc-detail-val">${d.approved_at_fmt}</div>
                    </div>
                    ` : ''}
                    ${d.hr_note ? `
                    <div class="yc-detail-row">
                        <div class="yc-detail-label">Phản hồi</div>
                        <div class="yc-detail-val" style="color:var(--yc-danger)">${d.hr_note}</div>
                    </div>
                    ` : ''}
                    <div class="yc-detail-row" style="border:none">
                        <div class="yc-detail-label">Ngày gửi</div>
                        <div class="yc-detail-val">${d.created_at_fmt}</div>
                    </div>
                `;
            } else {
                body.innerHTML = `<p style="color:var(--yc-danger); text-align:center;">Lỗi: ${res.message}</p>`;
            }
        })
        .catch(err => {
            body.innerHTML = '<p style="color:var(--yc-danger); text-align:center;">Không thể kết nối máy chủ.</p>';
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
document.querySelectorAll('.yc-history-card').forEach(card => {
    card.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        if (id) openModal(id);
    });
});
</script>
<?php include 'app/views/layouts/footer.php'; ?>
