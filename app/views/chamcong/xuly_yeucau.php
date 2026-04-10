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
            <table class="table">
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
                            <tr>
                                <td><i class="fas fa-clock" style="color:#94a3b8;"></i></td>
                                <td><?= htmlspecialchars($row['hoTen'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['attendance_date'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['old_time'] ? substr($row['old_time'], 11, 5) : '--:--') ?></td>
                                <td><?= htmlspecialchars(substr((string)($row['new_time'] ?? ''), 11, 5)) ?></td>
                                <td><?= htmlspecialchars($row['hr_note'] ?? 'Máy vân tay') ?></td>
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
            return '<tr>' +
                '<td><i class="fas fa-clock" style="color:#94a3b8;"></i></td>' +
                '<td>' + escapeHtml(row.hoTen) + '</td>' +
                '<td>' + escapeHtml(row.attendance_date) + '</td>' +
                '<td>' + escapeHtml(row.old_time ? String(row.old_time).slice(11, 16) : '--:--') + '</td>' +
                '<td>' + escapeHtml(row.new_time ? String(row.new_time).slice(11, 16) : '--:--') + '</td>' +
                '<td>' + escapeHtml(row.hr_note || 'Máy vân tay') + '</td>' +
                '</tr>';
        }).join('');
    }

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
            renderPending(pendingJson.data || []);
            renderHistory(historyJson.data || []);
        } catch (e) {}
    }

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        loadRequests();
    });

    focusActiveRequest();

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-request-action');
        if (!btn) return;

        var id = btn.getAttribute('data-id');
        var action = btn.getAttribute('data-action');
        var note = window.prompt(action === 'approve' ? 'Ghi chú HR (nếu có):' : 'Lý do từ chối:', '') ?? '';

        var form = new FormData();
        form.append('correction_id', id);
        form.append('action', action);
        form.append('note', note);

        fetch('index.php?page=hr-api-correction-action', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (!json.success) { alert(json.message || 'Lỗi'); return; }
            loadRequests();
        })
        .catch(function () { alert('Lỗi xử lý yêu cầu.'); });
    });
});
</script>
<?php include 'app/views/layouts/footer.php'; ?>
