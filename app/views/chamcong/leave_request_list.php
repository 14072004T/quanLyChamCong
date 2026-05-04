<?php 
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}
$leaveRequests = $leaveRequests ?? [];
$successMsg = $successMsg ?? '';
$errorMsg = $errorMsg ?? '';

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

// Count by status
$countPending  = 0;
$countApproved = 0;
$countRejected = 0;
foreach ($leaveRequests as $r) {
    $s = $r['status'] ?? 'pending';
    if ($s === 'pending')  $countPending++;
    if ($s === 'approved') $countApproved++;
    if ($s === 'rejected') $countRejected++;
}
$countTotal = count($leaveRequests);
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>

<style>
/* ===== LEAVE LIST STYLES ===== */
.ll-header { display: flex; align-items: center; gap: 16px; }
.ll-header .ll-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 22px; flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(99,102,241,.3);
}
.ll-header h2 { margin: 0; font-size: 1.35em; color: #1e293b; }
.ll-header p { margin: 4px 0 0; color: #64748b; font-size: .9em; }

.ll-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 16px; }
@media (max-width: 768px) { .ll-stats { grid-template-columns: repeat(2, 1fr); } }
.ll-stat-card {
    background: #fff; border-radius: 12px; padding: 16px 20px;
    border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 14px;
}
.ll-stat-icon {
    width: 42px; height: 42px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: 16px;
}
.ll-stat-icon.total   { background: #ede9fe; color: #7c3aed; }
.ll-stat-icon.pending  { background: #fef3c7; color: #d97706; }
.ll-stat-icon.approved { background: #d1fae5; color: #059669; }
.ll-stat-icon.rejected { background: #fecaca; color: #dc2626; }
.ll-stat-val { font-size: 1.5em; font-weight: 700; color: #1e293b; line-height: 1; }
.ll-stat-lbl { font-size: .78em; color: #94a3b8; margin-top: 2px; }

.ll-card {
    background: #fff; border-radius: 14px; padding: 28px;
    border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.ll-card-title {
    font-size: 1.05em; font-weight: 600; color: #1e293b; margin: 0 0 20px;
    padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: 10px;
}
.ll-card-title i { color: #6366f1; }

.ll-table { width: 100%; border-collapse: collapse; }
.ll-table thead th {
    background: #f8fafc; color: #64748b; font-size: .78em; font-weight: 600;
    text-transform: uppercase; letter-spacing: .4px; padding: 10px 12px;
    text-align: left; border-bottom: 2px solid #e2e8f0; white-space: nowrap;
}
.ll-table tbody td {
    padding: 14px 12px; border-bottom: 1px solid #f1f5f9;
    font-size: .9em; color: #334155; vertical-align: middle;
}
.ll-table tbody tr:hover { background: #fafbff; }
.ll-table tbody tr:last-child td { border-bottom: none; }

.ll-employee { display: flex; flex-direction: column; gap: 2px; }
.ll-employee strong { font-size: .92em; color: #1e293b; }
.ll-employee small { font-size: .78em; color: #94a3b8; }

.ll-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px; font-size: .78em; font-weight: 600;
    white-space: nowrap;
}
.ll-badge-pending  { background: #fef3c7; color: #92400e; }
.ll-badge-approved { background: #d1fae5; color: #065f46; }
.ll-badge-rejected { background: #fee2e2; color: #991b1b; }

.ll-type-chip {
    display: inline-block; padding: 3px 10px; border-radius: 6px;
    font-size: .78em; font-weight: 500; background: #ede9fe; color: #6d28d9;
    white-space: nowrap;
}

.ll-reason {
    max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    cursor: help;
}

.ll-date-range { white-space: nowrap; font-size: .88em; }
.ll-date-range i { color: #94a3b8; margin: 0 4px; font-size: .7em; }

.ll-approver { font-size: .8em; color: #64748b; margin-top: 4px; }
.ll-approver i { margin-right: 3px; font-size: .85em; }

.ll-actions { display: flex; gap: 6px; }
.ll-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px; border-radius: 8px; font-size: .82em; font-weight: 600;
    border: none; cursor: pointer; transition: all .2s;
    text-decoration: none; white-space: nowrap;
}
.ll-btn-approve {
    background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;
}
.ll-btn-approve:hover { background: #10b981; color: #fff; }
.ll-btn-reject {
    background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;
}
.ll-btn-reject:hover { background: #ef4444; color: #fff; }
.ll-btn-done {
    background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0;
    cursor: default; pointer-events: none;
}

.ll-empty {
    text-align: center; padding: 50px 20px; color: #94a3b8;
}
.ll-empty i { font-size: 2.5em; margin-bottom: 12px; display: block; color: #cbd5e1; }

.ll-alert {
    padding: 14px 18px; border-radius: 10px; margin-bottom: 16px;
    display: flex; align-items: center; gap: 10px; font-size: .92em;
    animation: llSlideIn .3s ease;
}
@keyframes llSlideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
.ll-alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
.ll-alert-success i { color: #10b981; }
.ll-alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.ll-alert-error i { color: #ef4444; }

/* Confirm dialog */
.ll-overlay {
    display: none; position: fixed; inset: 0; background: rgba(15,23,42,.45);
    z-index: 9999; align-items: center; justify-content: center;
    backdrop-filter: blur(3px);
}
.ll-overlay.active { display: flex; }
.ll-dialog {
    background: #fff; border-radius: 16px; padding: 32px; max-width: 420px; width: 90%;
    box-shadow: 0 20px 60px rgba(0,0,0,.15); text-align: center;
    animation: llDialogIn .25s ease;
}
@keyframes llDialogIn { from { transform: scale(.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.ll-dialog-icon {
    width: 56px; height: 56px; border-radius: 50%; margin: 0 auto 16px;
    display: flex; align-items: center; justify-content: center; font-size: 24px;
}
.ll-dialog-icon.approve-icon { background: #d1fae5; color: #10b981; }
.ll-dialog-icon.reject-icon  { background: #fee2e2; color: #ef4444; }
.ll-dialog h3 { margin: 0 0 8px; font-size: 1.1em; color: #1e293b; }
.ll-dialog p { margin: 0 0 24px; font-size: .9em; color: #64748b; }
.ll-dialog-actions { display: flex; gap: 10px; justify-content: center; }
.ll-dialog-actions .btn { min-width: 110px; border-radius: 10px; padding: 10px 20px; }
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">

        <!-- Page Header -->
        <div class="panel">
            <div class="ll-header">
                <div class="ll-icon"><i class="fas fa-list-check"></i></div>
                <div>
                    <h2>Quản lý Đơn Nghỉ Phép</h2>
                    <p>Xem xét và xử lý đơn nghỉ phép của toàn bộ nhân viên.</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="ll-stats">
                <div class="ll-stat-card">
                    <div class="ll-stat-icon total"><i class="fas fa-file-lines"></i></div>
                    <div><div class="ll-stat-val"><?= $countTotal ?></div><div class="ll-stat-lbl">Tổng đơn</div></div>
                </div>
                <div class="ll-stat-card">
                    <div class="ll-stat-icon pending"><i class="fas fa-clock"></i></div>
                    <div><div class="ll-stat-val"><?= $countPending ?></div><div class="ll-stat-lbl">Chờ duyệt</div></div>
                </div>
                <div class="ll-stat-card">
                    <div class="ll-stat-icon approved"><i class="fas fa-circle-check"></i></div>
                    <div><div class="ll-stat-val"><?= $countApproved ?></div><div class="ll-stat-lbl">Đã duyệt</div></div>
                </div>
                <div class="ll-stat-card">
                    <div class="ll-stat-icon rejected"><i class="fas fa-circle-xmark"></i></div>
                    <div><div class="ll-stat-val"><?= $countRejected ?></div><div class="ll-stat-lbl">Từ chối</div></div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($successMsg)): ?>
            <div class="ll-alert ll-alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($successMsg) ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($errorMsg)): ?>
            <div class="ll-alert ll-alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($errorMsg) ?></span>
            </div>
        <?php endif; ?>

        <!-- Table -->
        <div class="ll-card">
            <div class="ll-card-title">
                <i class="fas fa-table-list"></i> Danh Sách Đơn Nghỉ Phép
            </div>

            <?php if (!empty($leaveRequests)): ?>
                <div style="overflow-x: auto;">
                <table class="ll-table">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Loại phép</th>
                            <th>Thời gian nghỉ</th>
                            <th>Lý do</th>
                            <th>Minh chứng</th>
                            <th>Trạng thái</th>
                            <th>Ngày gửi</th>
                            <th style="text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaveRequests as $row): ?>
                            <?php
                                $st = $row['status'] ?? 'pending';
                                $lt = $row['leave_type'] ?? 'personal';
                                $rowId = (int)($row['id'] ?? 0);
                                $empName = htmlspecialchars($row['hoTen'] ?? 'N/A');
                                $empDept = htmlspecialchars($row['phongBan'] ?? '');
                            ?>
                            <tr>
                                <td>
                                    <div class="ll-employee">
                                        <strong><?= $empName ?></strong>
                                        <?php if ($empDept): ?>
                                            <small><?= $empDept ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="ll-type-chip"><?= htmlspecialchars($leaveTypeLabels[$lt] ?? $lt) ?></span>
                                </td>
                                <td>
                                    <div class="ll-date-range">
                                        <?= htmlspecialchars($row['from_date'] ?? '') ?>
                                        <i class="fas fa-arrow-right"></i>
                                        <?= htmlspecialchars($row['to_date'] ?? '') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="ll-reason" title="<?= htmlspecialchars($row['reason'] ?? '') ?>">
                                        <?= htmlspecialchars($row['reason'] ?? '') ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($row['evidence_file'])): ?>
                                        <a href="<?= htmlspecialchars($row['evidence_file']) ?>" target="_blank"
                                           style="color: #6366f1; font-size: .85em; text-decoration: none;">
                                            <i class="fas fa-paperclip"></i> Tải file
                                        </a>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="ll-badge ll-badge-<?= $st ?>">
                                        <i class="fas <?= $statusIcons[$st] ?? 'fa-clock' ?>"></i>
                                        <?= htmlspecialchars($statusLabels[$st] ?? $st) ?>
                                    </span>
                                    <?php if ($st !== 'pending' && !empty($row['approver_name'])): ?>
                                        <div class="ll-approver">
                                            <i class="fas fa-user-check"></i>
                                            <?= htmlspecialchars($row['approver_name']) ?>
                                            <?php if (!empty($row['approved_at'])): ?>
                                                · <?= htmlspecialchars(substr($row['approved_at'], 0, 16)) ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: .85em; color: #64748b; white-space: nowrap;">
                                    <?= htmlspecialchars(substr($row['created_at'] ?? '', 0, 16)) ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($st === 'pending'): ?>
                                        <div class="ll-actions">
                                            <button type="button" class="ll-btn ll-btn-approve"
                                                    onclick="confirmAction(<?= $rowId ?>, 'approved', '<?= $empName ?>')"
                                                    title="Phê duyệt">
                                                <i class="fas fa-check"></i> Duyệt
                                            </button>
                                            <button type="button" class="ll-btn ll-btn-reject"
                                                    onclick="confirmAction(<?= $rowId ?>, 'rejected', '<?= $empName ?>')"
                                                    title="Từ chối">
                                                <i class="fas fa-xmark"></i> Từ chối
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="ll-btn ll-btn-done">
                                            <i class="fas fa-check-double"></i> Đã xử lý
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php else: ?>
                <div class="ll-empty">
                    <i class="fas fa-inbox"></i>
                    <p>Chưa có đơn nghỉ phép nào.</p>
                    <p style="font-size: .85em;">Các đơn nghỉ phép của nhân viên sẽ hiển thị tại đây.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Back button -->
        <div class="panel text-center" style="margin-top: 16px;">
            <a href="index.php?page=home" class="btn btn-secondary" style="padding: 12px 28px;">
                <i class="fas fa-arrow-left"></i> Quay lại Trang chủ
            </a>
        </div>

    </div>
</div>

<!-- Confirmation Dialog -->
<div class="ll-overlay" id="confirmOverlay">
    <div class="ll-dialog">
        <div class="ll-dialog-icon" id="dialogIcon"></div>
        <h3 id="dialogTitle"></h3>
        <p id="dialogMsg"></p>
        <form method="POST" action="index.php?page=approve-leave-request" id="confirmForm">
            <input type="hidden" name="id" id="confirmId">
            <input type="hidden" name="status" id="confirmStatus">
            <div class="ll-dialog-actions">
                <button type="button" class="btn btn-secondary" onclick="closeConfirm()">Hủy</button>
                <button type="submit" class="btn" id="confirmBtn">Xác nhận</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmAction(id, status, name) {
    var overlay = document.getElementById('confirmOverlay');
    var icon = document.getElementById('dialogIcon');
    var title = document.getElementById('dialogTitle');
    var msg = document.getElementById('dialogMsg');
    var btn = document.getElementById('confirmBtn');

    document.getElementById('confirmId').value = id;
    document.getElementById('confirmStatus').value = status;

    if (status === 'approved') {
        icon.className = 'll-dialog-icon approve-icon';
        icon.innerHTML = '<i class="fas fa-circle-check"></i>';
        title.textContent = 'Phê duyệt đơn nghỉ phép?';
        msg.textContent = 'Xác nhận phê duyệt đơn nghỉ phép của ' + name + '?';
        btn.className = 'btn btn-success';
        btn.innerHTML = '<i class="fas fa-check"></i> Phê duyệt';
    } else {
        icon.className = 'll-dialog-icon reject-icon';
        icon.innerHTML = '<i class="fas fa-circle-xmark"></i>';
        title.textContent = 'Từ chối đơn nghỉ phép?';
        msg.textContent = 'Xác nhận từ chối đơn nghỉ phép của ' + name + '?';
        btn.className = 'btn btn-danger';
        btn.innerHTML = '<i class="fas fa-xmark"></i> Từ chối';
    }

    overlay.classList.add('active');
}

function closeConfirm() {
    document.getElementById('confirmOverlay').classList.remove('active');
}

// Close on overlay click
document.getElementById('confirmOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeConfirm();
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeConfirm();
});

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.ll-alert').forEach(function(a) {
        setTimeout(function() {
            a.style.transition = 'opacity .3s, transform .3s';
            a.style.opacity = '0';
            a.style.transform = 'translateY(-8px)';
            setTimeout(function() { a.remove(); }, 300);
        }, 5000);
    });
});
</script>

<?php include 'app/views/layouts/footer.php'; ?>
