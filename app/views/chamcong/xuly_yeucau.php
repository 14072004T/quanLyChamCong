<?php
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'hr') {
    header('Location: index.php?page=home');
    exit();
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$filters = $filters ?? ['q' => '', 'date' => '', 'type' => ''];
$pendingCorrections = $pendingCorrections ?? [];
$processedCorrections = $processedCorrections ?? [];
$activeRequestId = (int)($_GET['request_id'] ?? 0);
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<style>
.request-row-highlight {
    animation: requestRowPulse 2.4s ease;
    background: #fff7d6 !important;
}
@keyframes requestRowPulse {
    0% { background: #ffe58f; }
    100% { background: #fff7d6; }
}
/* Modal Styles */
.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(3px);
    z-index: 10000;
    display: flex; align-items: center; justify-content: center;
    opacity: 0; visibility: hidden;
    transition: all 0.2s ease;
}
.modal-overlay.show { opacity: 1; visibility: visible; }
.modal-box {
    background: #ffffff; border-radius: 12px; width: 100%; max-width: 480px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    transform: translateY(20px); transition: transform 0.25s ease;
}
.modal-overlay.show .modal-box { transform: translateY(0); }
.modal-head {
    padding: 16px 20px; border-bottom: 1px solid #e2e8f0;
    display: flex; justify-content: space-between; align-items: center;
    background: #f8fafc; border-radius: 12px 12px 0 0;
}
.modal-head h3 { margin: 0; font-size: 1.1em; color: #0f172a; font-weight: 700; }
.modal-body { padding: 20px; }
.modal-detail-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9em; }
.modal-detail-row strong { color: #64748b; width: 100px; flex-shrink: 0; }
.modal-detail-row span { color: #0f172a; font-weight: 600; text-align: right; }
</style>
<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">

        <!-- Page Header -->
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 style="border:none;padding:0;margin:0;">TRUNG TÂM XỬ LÝ YÊU CẦU</h2>
                </div>
                <div class="panel-header-actions">
                    <a href="index.php?page=tinh-cong" class="btn btn-success btn-sm"><i class="fas fa-paper-plane"></i> GỬI PHÊ DUYỆT</a>
                    <a href="index.php?page=quan-ly-nhanvien" class="btn btn-warning btn-sm"><i class="fas fa-pen"></i> CHỈNH SỬA</a>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Filter -->
        <div class="panel">
            <h3>LỌC YÊU CẦU</h3>
            <form id="request-filter-form" class="filter-row">
                <div class="form-group" style="flex:1;max-width:260px;">
                    <label>Nhân viên</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Nhân viên/name/dept">
                </div>
                <div class="form-group">
                    <label>Ngày</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($filters['date'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Loại yêu cầu</label>
                    <select name="type">
                        <option value="">Quên chấm công</option>
                        <option value="quên chấm công" <?= (($filters['type'] ?? '') === 'quên chấm công') ? 'selected' : '' ?>>Quên chấm công</option>
                        <option value="ot" <?= (($filters['type'] ?? '') === 'ot') ? 'selected' : '' ?>>Xin OT</option>
                        <option value="đi trễ" <?= (($filters['type'] ?? '') === 'đi trễ') ? 'selected' : '' ?>>Đi trễ</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
                </div>
            </form>
        </div>

        <!-- Pending Requests -->
        <div class="panel">
            <h3>Yêu cầu chờ duyệt</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Loại yêu cầu</th>
                        <th>Nhân viên</th>
                        <th>Ngày</th>
                        <th>Chi tiết</th>
                        <th>Lý do</th>
                        <th style="min-width:160px;">Hành động</th>
                    </tr>
                </thead>
                <tbody id="pending-requests-body">
                    <?php if (!empty($pendingCorrections)): ?>
                        <?php foreach ($pendingCorrections as $row):
                            $reason = strtolower($row['reason'] ?? '');
                            $typeClass = 'type-other';
                            $typeIcon = 'fa-circle-question';
                            $typeLabel = $row['reason'] ?? 'Khác';
                            if (strpos($reason, 'quên') !== false || strpos($reason, 'forget') !== false) {
                                $typeClass = 'type-forget'; $typeIcon = 'fa-clock'; $typeLabel = 'Quên chấm công';
                            } elseif (strpos($reason, 'ot') !== false || strpos($reason, 'overtime') !== false) {
                                $typeClass = 'type-ot'; $typeIcon = 'fa-hourglass-half'; $typeLabel = 'Xin OT';
                            } elseif (strpos($reason, 'trễ') !== false || strpos($reason, 'late') !== false) {
                                $typeClass = 'type-late'; $typeIcon = 'fa-triangle-exclamation'; $typeLabel = 'Đi trễ';
                            }
                        ?>
                            <tr id="request-<?= (int)$row['id'] ?>" data-id="<?= (int)$row['id'] ?>" class="<?= $activeRequestId === (int)$row['id'] ? 'request-row-highlight' : '' ?>">
                                <td>
                                    <span class="req-type-badge <?= $typeClass ?>">
                                        <span class="req-icon"><i class="fas <?= $typeIcon ?>"></i></span>
                                        <?= htmlspecialchars($typeLabel) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['hoTen'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['attendance_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars(($row['old_time'] ? substr($row['old_time'], 11, 5) : '--:--') . ' -> ' . substr((string)($row['new_time'] ?? ''), 11, 5)) ?></td>
                                <td style="max-width:220px;"><?= htmlspecialchars($row['reason'] ?? '') ?></td>
                                <td>
                                    <div style="display:flex;gap:8px;">
                                        <button type="button" class="btn-approve js-request-action" data-action="approve" data-id="<?= (int)$row['id'] ?>"><i class="fas fa-check"></i> Duyệt</button>
                                        <button type="button" class="btn-return js-request-action" data-action="reject" data-id="<?= (int)$row['id'] ?>"><i class="fas fa-times"></i> Từ chối</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="empty-state">Không có yêu cầu chờ duyệt.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- History -->
        <div class="panel">
            <h3>Lịch sử xử lý</h3>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th></th>
                        <th>Nhân viên</th>
                        <th>Ngày</th>
                        <th>Giờ vào</th>
                        <th>Giờ ra</th>
                        <th>Nguồn</th>
                    </tr>
                </thead>
                <tbody id="history-requests-body">
                    <?php if (!empty($processedCorrections)): ?>
                        <?php foreach ($processedCorrections as $row): ?>
                            <tr class="js-history-row" style="cursor:pointer;"
                                data-status="<?= htmlspecialchars($row['status'] ?? '') ?>"
                                data-hoten="<?= htmlspecialchars($row['hoTen'] ?? '') ?>"
                                data-date="<?= htmlspecialchars($row['attendance_date'] ?? '') ?>"
                                data-old="<?= htmlspecialchars($row['old_time'] ? substr($row['old_time'], 11, 5) : '--:--') ?>"
                                data-new="<?= htmlspecialchars(substr((string)($row['new_time'] ?? ''), 11, 5)) ?>"
                                data-reason="<?= htmlspecialchars($row['reason'] ?? '') ?>"
                                data-hrnote="<?= htmlspecialchars($row['hr_note'] ?? 'Không') ?>">
                                <td><i class="fas fa-clock" style="color:#94a3b8;"></i></td>
                                <td><?= htmlspecialchars($row['hoTen'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['attendance_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['old_time'] ? substr($row['old_time'], 11, 5) : '--:--') ?></td>
                                <td><?= htmlspecialchars(substr((string)($row['new_time'] ?? ''), 11, 5)) ?></td>
                                <td>
                                    <?php if(($row['status'] ?? '') === 'approved'): ?>
                                        <span class="req-type-badge type-ot"><i class="fas fa-check"></i> Đã duyệt</span>
                                    <?php elseif(($row['status'] ?? '') === 'rejected'): ?>
                                        <span class="req-type-badge type-late"><i class="fas fa-times"></i> Từ chối</span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($row['hr_note'] ?? 'Hệ thống') ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="empty-state">Chưa có lịch sử xử lý.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- History Detail Modal -->
<div id="historyModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-head">
            <h3>Chi tiết Lịch sử Xử lý</h3>
            <button type="button" id="historyModalBtnClose" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.2em;transition:color 0.2s;"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div style="background:#f1f5f9;padding:14px;border-radius:8px;margin-bottom:18px;border:1px solid #e2e8f0;">
                <div class="modal-detail-row"><strong>Trạng thái:</strong> <span id="histModalStatus"></span></div>
                <div class="modal-detail-row"><strong>Nhân viên:</strong> <span id="histModalEmpName"></span></div>
                <div class="modal-detail-row"><strong>Ngày:</strong> <span id="histModalDate"></span></div>
                <div class="modal-detail-row"><strong>Từ -> Mới:</strong> <span id="histModalTimeDetail"></span></div>
                <div class="modal-detail-row"><strong>Lý do gửi:</strong> <span id="histModalReason"></span></div>
                <div class="modal-detail-row" style="margin-bottom:0;border-top:1px solid #e2e8f0;padding-top:8px;margin-top:8px;"><strong>Ghi chú HR:</strong> <span id="histModalHRNote" style="color:#0f172a;font-weight:700;"></span></div>
            </div>
            <div class="btn-group" style="display:flex;justify-content:center;margin-top:10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('historyModal').classList.remove('show');" style="width:120px;">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div id="actionModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-head">
            <h3 id="modalTitle">Xử lý yêu cầu</h3>
            <button type="button" id="modalBtnClose" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.2em;transition:color 0.2s;"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div style="background:#f1f5f9;padding:14px;border-radius:8px;margin-bottom:18px;border:1px solid #e2e8f0;">
                <div class="modal-detail-row"><strong>Nhân viên:</strong> <span id="modalEmpName"></span></div>
                <div class="modal-detail-row"><strong>Ngày:</strong> <span id="modalDate"></span></div>
                <div class="modal-detail-row"><strong>Chi tiết:</strong> <span id="modalTimeDetail"></span></div>
                <div class="modal-detail-row" style="margin-bottom:0;"><strong>Lý do:</strong> <span id="modalReason"></span></div>
            </div>
            <div class="form-group">
                <label id="modalNoteLabel" style="font-weight:600;margin-bottom:6px;display:block;color:#334155;">Ghi chú HR</label>
                <textarea id="modalNoteInput" rows="3" style="width:100%;border-radius:8px;border:1.5px solid #cbd5e1;padding:10px;font-family:'Inter', sans-serif;font-size:0.9em;outline:none;" placeholder="Nhập ghi chú..."></textarea>
            </div>
            <div class="btn-group" style="display:flex;gap:12px;margin-top:20px;">
                <button type="button" id="modalBtnConfirm" class="btn" style="flex:1;">Xác nhận</button>
                <button type="button" id="modalBtnCancel" class="btn btn-secondary" style="flex:1;">Hủy</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var activeRequestId = <?= (int)$activeRequestId ?>;
    var filterForm = document.getElementById('request-filter-form');
    var pendingBody = document.getElementById('pending-requests-body');
    var historyBody = document.getElementById('history-requests-body');

    function focusActiveRequest() {
        if (!activeRequestId) return;
        var targetRow = document.getElementById('request-' + activeRequestId);
        if (!targetRow) return;
        targetRow.classList.add('request-row-highlight');
        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function escapeHtml(val) {
        return String(val ?? '').replace(/[&<>"]/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
        });
    }

    function currentFilters() {
        return new URLSearchParams(new FormData(filterForm));
    }

    function getTypeInfo(reason) {
        var r = (reason || '').toLowerCase();
        if (r.indexOf('quên') !== -1 || r.indexOf('forget') !== -1) return { cls: 'type-forget', icon: 'fa-clock', label: 'Quên chấm công' };
        if (r.indexOf('ot') !== -1 || r.indexOf('overtime') !== -1) return { cls: 'type-ot', icon: 'fa-hourglass-half', label: 'Xin OT' };
        if (r.indexOf('trễ') !== -1 || r.indexOf('late') !== -1) return { cls: 'type-late', icon: 'fa-triangle-exclamation', label: 'Đi trễ' };
        return { cls: 'type-other', icon: 'fa-circle-question', label: reason || 'Khác' };
    }

    function renderPending(rows) {
        if (!rows.length) {
            pendingBody.innerHTML = '<tr><td colspan="6" class="empty-state">Không có yêu cầu chờ duyệt.</td></tr>';
            return;
        }
        pendingBody.innerHTML = rows.map(function (row) {
            var t = getTypeInfo(row.reason);
            var detail = (row.old_time ? String(row.old_time).slice(11, 16) : '--:--') + ' -> ' + (row.new_time ? String(row.new_time).slice(11, 16) : '--:--');
            var rowId = Number(row.id);
            var rowClass = rowId === activeRequestId ? ' class="request-row-highlight"' : '';
            return '<tr id="request-' + rowId + '" data-id="' + rowId + '"' + rowClass + '>' +
                '<td><span class="req-type-badge ' + t.cls + '"><span class="req-icon"><i class="fas ' + t.icon + '"></i></span> ' + escapeHtml(t.label) + '</span></td>' +
                '<td>' + escapeHtml(row.hoTen) + '</td>' +
                '<td>' + escapeHtml(row.attendance_date) + '</td>' +
                '<td>' + escapeHtml(detail) + '</td>' +
                '<td style="max-width:220px;">' + escapeHtml(row.reason) + '</td>' +
                '<td><div style="display:flex;gap:8px;">' +
                '<button type="button" class="btn-approve js-request-action" data-action="approve" data-id="' + Number(row.id) + '"><i class="fas fa-check"></i> Duyệt</button>' +
                '<button type="button" class="btn-return js-request-action" data-action="reject" data-id="' + Number(row.id) + '"><i class="fas fa-times"></i> Từ chối</button>' +
                '</div></td>' +
                '</tr>';
        }).join('');
        focusActiveRequest();
    }

    function renderHistory(rows) {
        if (!rows.length) {
            historyBody.innerHTML = '<tr><td colspan="6" class="empty-state">Chưa có lịch sử xử lý.</td></tr>';
            return;
        }
        historyBody.innerHTML = rows.map(function (row) {
            var oldT = row.old_time ? String(row.old_time).slice(11, 16) : '--:--';
            var newT = row.new_time ? String(row.new_time).slice(11, 16) : '--:--';
            var st = row.status || '';
            var stBadge = '';
            
            if (st === 'approved') stBadge = '<span class="req-type-badge type-ot"><i class="fas fa-check"></i> Đã duyệt</span>';
            else if (st === 'rejected') stBadge = '<span class="req-type-badge type-late"><i class="fas fa-times"></i> Từ chối</span>';
            else stBadge = escapeHtml(row.hr_note || 'Hệ thống');

            return '<tr class="js-history-row" style="cursor:pointer;"' +
                ' data-status="' + escapeHtml(st) + '"' +
                ' data-hoten="' + escapeHtml(row.hoTen) + '"' +
                ' data-date="' + escapeHtml(row.attendance_date) + '"' +
                ' data-old="' + escapeHtml(oldT) + '"' +
                ' data-new="' + escapeHtml(newT) + '"' +
                ' data-reason="' + escapeHtml(row.reason) + '"' +
                ' data-hrnote="' + escapeHtml(row.hr_note || 'Không') + '">' +
                '<td><i class="fas fa-clock" style="color:#94a3b8;"></i></td>' +
                '<td>' + escapeHtml(row.hoTen) + '</td>' +
                '<td>' + escapeHtml(row.attendance_date) + '</td>' +
                '<td>' + escapeHtml(oldT) + '</td>' +
                '<td>' + escapeHtml(newT) + '</td>' +
                '<td>' + stBadge + '</td>' +
                '</tr>';
        }).join('');
    }

    var activeActionData = null;
    var modalOverlay = document.getElementById('actionModal');
    var modalNoteInput = document.getElementById('modalNoteInput');
    
    // Extracted cached pending data for modal use
    var cachedPendingRows = [];

    async function loadRequests() {
        var pendingParams = currentFilters();
        pendingParams.set('page', 'hr-api-corrections');
        pendingParams.set('scope', 'pending');

        var historyParams = currentFilters();
        historyParams.set('page', 'hr-api-corrections');
        historyParams.set('scope', 'history');

        try {
            var [pendingRes, historyRes] = await Promise.all([
                fetch('index.php?' + pendingParams.toString(), { headers: { 'Accept': 'application/json' } }),
                fetch('index.php?' + historyParams.toString(), { headers: { 'Accept': 'application/json' } })
            ]);
            var pendingJson = await pendingRes.json();
            var historyJson = await historyRes.json();
            
            cachedPendingRows = pendingJson.data || [];
            
            renderPending(cachedPendingRows);
            renderHistory(historyJson.data || []);
        } catch (e) {}
    }

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        loadRequests();
    });

    focusActiveRequest();
    
    function closeModal() {
        modalOverlay.classList.remove('show');
        activeActionData = null;
        modalNoteInput.value = '';
    }
    
    document.getElementById('modalBtnClose').addEventListener('click', closeModal);
    document.getElementById('modalBtnCancel').addEventListener('click', closeModal);
    
    document.getElementById('modalBtnConfirm').addEventListener('click', function() {
        if (!activeActionData) return;
        
        var form = new FormData();
        form.append('correction_id', activeActionData.id);
        form.append('action', activeActionData.action);
        form.append('note', modalNoteInput.value);

        var btnConfirm = document.getElementById('modalBtnConfirm');
        var oldBtnText = btnConfirm.innerHTML;
        btnConfirm.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        btnConfirm.disabled = true;

        fetch('index.php?page=hr-api-correction-action', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            btnConfirm.innerHTML = oldBtnText;
            btnConfirm.disabled = false;
            if (!json.success) { alert(json.message || 'Lỗi'); return; }
            closeModal();
            loadRequests();
        })
        .catch(function () { 
            btnConfirm.innerHTML = oldBtnText;
            btnConfirm.disabled = false;
            alert('Lỗi xử lý yêu cầu.'); 
        });
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-request-action');
        if (!btn) return;

        var id = Number(btn.getAttribute('data-id'));
        var action = btn.getAttribute('data-action');
        
        // Find row data for modal details directly from the DOM
        var tr = btn.closest('tr');
        if (tr) {
            var tds = tr.querySelectorAll('td');
            if (tds.length >= 5) {
                document.getElementById('modalEmpName').textContent = tds[1].textContent.trim();
                document.getElementById('modalDate').textContent = tds[2].textContent.trim();
                document.getElementById('modalTimeDetail').textContent = tds[3].textContent.trim();
                document.getElementById('modalReason').textContent = tds[4].textContent.trim();
            }
        } else {
             // Fallback
             document.getElementById('modalEmpName').textContent = 'ID: ' + id;
             document.getElementById('modalDate').textContent = '--';
             document.getElementById('modalTimeDetail').textContent = '--';
             document.getElementById('modalReason').textContent = '--';
        }

        var lbl = document.getElementById('modalNoteLabel');
        var title = document.getElementById('modalTitle');
        var btnConfirm = document.getElementById('modalBtnConfirm');
        
        btnConfirm.className = 'btn'; // reset class
        
        if (action === 'approve') {
            title.textContent = 'Phê duyệt yêu cầu';
            lbl.textContent = 'Ghi chú phê duyệt (nếu có):';
            btnConfirm.classList.add('btn-success');
            btnConfirm.innerHTML = '<i class="fas fa-check"></i> Phê duyệt';
        } else {
            title.textContent = 'Từ chối yêu cầu';
            lbl.textContent = 'Lý do từ chối (bắt buộc):';
            btnConfirm.classList.add('btn-danger');
            btnConfirm.innerHTML = '<i class="fas fa-times"></i> Từ chối';
        }

        activeActionData = { id: id, action: action };
        modalNoteInput.value = '';
        modalOverlay.classList.add('show');
        setTimeout(function() { modalNoteInput.focus(); }, 100);
    });

    // Handle History Row Click
    document.getElementById('historyModalBtnClose').addEventListener('click', function() {
        document.getElementById('historyModal').classList.remove('show');
    });

    document.addEventListener('click', function (e) {
        var tr = e.target.closest('.js-history-row');
        if (!tr) return;

        var st = tr.getAttribute('data-status');
        var statusHtml = '&nbsp;';
        if (st === 'approved') statusHtml = '<span style="color:#10b981;font-weight:600;"><i class="fas fa-check"></i> Đã phê duyệt</span>';
        else if (st === 'rejected') statusHtml = '<span style="color:#ef4444;font-weight:600;"><i class="fas fa-times"></i> Đã từ chối</span>';

        document.getElementById('histModalStatus').innerHTML = statusHtml;
        document.getElementById('histModalEmpName').textContent = tr.getAttribute('data-hoten');
        document.getElementById('histModalDate').textContent = tr.getAttribute('data-date');
        document.getElementById('histModalTimeDetail').textContent = tr.getAttribute('data-old') + ' -> ' + tr.getAttribute('data-new');
        document.getElementById('histModalReason').textContent = tr.getAttribute('data-reason');
        document.getElementById('histModalHRNote').textContent = tr.getAttribute('data-hrnote');

        document.getElementById('historyModal').classList.add('show');
    });
});
</script>
<?php include 'app/views/layouts/footer.php'; ?>
