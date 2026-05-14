<?php
if (!isset($_SESSION['user'])) { header('Location: index.php?page=login'); exit(); }
$userName = htmlspecialchars($_SESSION['user']['hoTen'] ?? 'Nhân viên');
$timesheetList = $timesheetList ?? [];
$pendingCount = 0;
foreach ($timesheetList as $ts) {
    if (($ts['status'] ?? '') === 'submitted') $pendingCount++;
}
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<style>
.ts-page { padding: 0; }
.ts-hero {
    padding: 28px 32px;
    background: linear-gradient(135deg, #f0f7ff 0%, #e8f4fd 50%, #f8fbff 100%);
    border-bottom: 1px solid #dbe7f5;
}
.ts-hero h2 { margin: 0 0 6px; color: #0f172a; font-size: 1.5rem; }
.ts-hero p { margin: 0; color: #64748b; }
.ts-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-top: 18px;
}
.ts-stat {
    padding: 18px;
    border-radius: 18px;
    background: rgba(255,255,255,0.92);
    border: 1px solid #dbe7f5;
    box-shadow: 0 8px 24px rgba(15,23,42,0.05);
}
.ts-stat-label {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 6px;
}
.ts-stat-value { font-size: 1.6rem; font-weight: 800; color: #0f172a; }
.ts-stat-value.pending { color: #f59e0b; }
.ts-stat-value.approved { color: #22c55e; }

.ts-list-wrap { padding: 24px 32px; }
.ts-list-wrap h3 { margin: 0 0 16px; color: #0f172a; }

.ts-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    margin-bottom: 12px;
    background: #fff;
    transition: all 0.2s;
    cursor: pointer;
}
.ts-card:hover {
    border-color: #93c5fd;
    box-shadow: 0 8px 24px rgba(59,130,246,0.1);
    transform: translateY(-1px);
}
.ts-card-left { display: flex; align-items: center; gap: 16px; }
.ts-card-icon {
    width: 48px; height: 48px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem;
}
.ts-card-icon.pending { background: #fef3c7; color: #d97706; }
.ts-card-icon.approved { background: #dcfce7; color: #16a34a; }
.ts-card-month { font-weight: 700; font-size: 1.1rem; color: #0f172a; }
.ts-card-meta { font-size: 0.82rem; color: #64748b; margin-top: 2px; }
.ts-card-right { display: flex; align-items: center; gap: 12px; }
.ts-badge {
    padding: 5px 14px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
}
.ts-badge.pending { background: #fef3c7; color: #92400e; }
.ts-badge.approved { background: #dcfce7; color: #166534; }
.ts-card-arrow { color: #94a3b8; font-size: 1rem; }

/* Detail Modal */
.ts-modal {
    position: fixed; inset: 0;
    display: none; align-items: center; justify-content: center;
    background: rgba(15,23,42,0.55);
    z-index: 1200; padding: 24px;
}
.ts-modal.open { display: flex; }
.ts-modal-card {
    width: min(960px, 100%);
    max-height: calc(100vh - 48px);
    overflow: auto;
    border-radius: 22px;
    background: #fff;
    box-shadow: 0 30px 80px rgba(15,23,42,0.24);
}
.ts-modal-head {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 24px 28px 18px;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(180deg, #f8fbff 0%, #fff 100%);
}
.ts-modal-head h3 { margin: 0 0 6px; }
.ts-modal-head p { margin: 0; color: #64748b; font-size: 0.88rem; }
.ts-modal-close {
    border: none; background: #e2e8f0; color: #0f172a;
    width: 38px; height: 38px; border-radius: 999px; cursor: pointer; font-size: 18px;
}
.ts-modal-body { padding: 24px 28px; }
.ts-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 22px;
}
.ts-summary-item {
    padding: 16px;
    border: 1px solid #dbe7f5;
    border-radius: 16px;
    background: #f8fbff;
    text-align: center;
}
.ts-summary-item span { display: block; font-size: 0.78rem; color: #64748b; font-weight: 600; margin-bottom: 6px; }
.ts-summary-item strong { font-size: 1.35rem; color: #0f172a; }
.ts-approve-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border: 2px solid #bbf7d0;
    border-radius: 18px;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    margin-top: 22px;
}
.ts-approve-bar p { margin: 0; color: #166534; font-weight: 600; }
.ts-approve-btn {
    padding: 10px 28px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: #fff;
    font-weight: 700;
    font-size: 0.92rem;
    cursor: pointer;
    transition: all 0.2s;
}
.ts-approve-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(34,197,94,0.3); }
.ts-approve-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.ts-approved-bar {
    display: flex; align-items: center; gap: 12px;
    padding: 18px 22px;
    border: 2px solid #bbf7d0;
    border-radius: 18px;
    background: #f0fdf4;
    margin-top: 22px;
    color: #166534; font-weight: 700;
}
.ts-empty {
    padding: 48px 24px;
    text-align: center;
    color: #94a3b8;
}
.ts-empty i { font-size: 3rem; margin-bottom: 16px; display: block; color: #cbd5e1; }

@media (max-width: 768px) {
    .ts-stats { grid-template-columns: 1fr; }
    .ts-summary-grid { grid-template-columns: repeat(2, 1fr); }
    .ts-summary-grid { grid-template-columns: repeat(2, 1fr); }
    .ts-hero, .ts-list-wrap { padding: 18px; }
}
.yc-btn-edit {
    padding: 4px 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 6px;
    font-size: 0.75rem;
    color: #2563eb;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 4px;
}
.yc-btn-edit:hover {
    background: #eff6ff;
    border-color: #3b82f6;
    transform: translateY(-1px);
}
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">
        <div class="panel ts-page">
            <div class="ts-hero">
                <h2><i class="fas fa-file-invoice" style="color:#2563eb;margin-right:8px"></i> Bảng công tháng</h2>
                <p>Xem và xác nhận bảng công hàng tháng được HR gửi đến bạn.</p>
                <div class="ts-stats">
                    <div class="ts-stat">
                        <div class="ts-stat-label">Tổng bảng công</div>
                        <div class="ts-stat-value"><?= count($timesheetList) ?></div>
                    </div>
                    <div class="ts-stat">
                        <div class="ts-stat-label">Chờ duyệt</div>
                        <div class="ts-stat-value pending"><?= $pendingCount ?></div>
                    </div>
                    <div class="ts-stat">
                        <div class="ts-stat-label">Đã duyệt</div>
                        <div class="ts-stat-value approved"><?= count($timesheetList) - $pendingCount ?></div>
                    </div>
                </div>
            </div>

            <div class="ts-list-wrap">
                <h3>Danh sách bảng công</h3>
                <?php if (empty($timesheetList)): ?>
                    <div class="ts-empty">
                        <i class="fas fa-inbox"></i>
                        <p>Chưa có bảng công nào được gửi đến bạn.<br>HR sẽ gửi bảng công vào cuối mỗi tháng.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($timesheetList as $ts):
                        $status = strtolower($ts['status'] ?? 'submitted');
                        $isPending = $status === 'submitted';
                        $statusLabel = $isPending ? 'Chờ duyệt' : 'Đã duyệt';
                        $monthDisplay = $ts['month_key'] ?? '';
                        // Convert 2026-05 to "Tháng 05/2026"
                        $parts = explode('-', $monthDisplay);
                        $monthText = count($parts) === 2 ? "Tháng {$parts[1]}/{$parts[0]}" : $monthDisplay;
                    ?>
                        <div class="ts-card" onclick="loadTimesheetDetail(<?= (int)$ts['id'] ?>)">
                            <div class="ts-card-left">
                                <div class="ts-card-icon <?= $isPending ? 'pending' : 'approved' ?>">
                                    <i class="fas <?= $isPending ? 'fa-hourglass-half' : 'fa-check-circle' ?>"></i>
                                </div>
                                <div>
                                    <div class="ts-card-month"><?= htmlspecialchars($monthText) ?></div>
                                    <div class="ts-card-meta">
                                        HR gửi: <?= htmlspecialchars($ts['hr_name'] ?? 'HR') ?>
                                        &bull; <?= htmlspecialchars($ts['submitted_at'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                            <div class="ts-card-right">
                                <span class="ts-badge <?= $isPending ? 'pending' : 'approved' ?>"><?= $statusLabel ?></span>
                                <i class="fas fa-chevron-right ts-card-arrow"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="ts-modal" id="tsModal">
    <div class="ts-modal-card">
        <div class="ts-modal-head">
            <div>
                <h3 id="ts-modal-title">Chi tiết bảng công</h3>
                <p id="ts-modal-subtitle">Đang tải...</p>
            </div>
            <button class="ts-modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="ts-modal-body">
            <div class="ts-summary-grid" id="ts-summary"></div>
            <div class="table-responsive">
                <table class="table" id="ts-detail-table">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Giờ vào</th>
                            <th>Giờ ra</th>
                            <th>Giờ làm</th>
                            <th>OT</th>
                            <th>Đi trễ</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="ts-detail-body">
                        <tr><td colspan="7" class="empty-state">Đang tải...</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="ts-action-bar"></div>
        </div>
    </div>
</div>

<script>
var currentTimesheetId = null;

function escHtml(v) {
    return String(v || '').replace(/[&<>"]/g, function(c) {
        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
    });
}

function formatTime(dt) {
    if (!dt) return '--:--';
    var parts = String(dt).split(' ');
    return parts.length > 1 ? parts[1].substring(0, 5) : dt.substring(0, 5);
}

function formatMinutes(m) {
    m = parseInt(m) || 0;
    if (m <= 0) return '0p';
    var h = Math.floor(m / 60);
    var p = m % 60;
    if (h > 0) return h + 'h ' + (p > 0 ? p + 'p' : '');
    return p + 'p';
}

function statusBadge(st) {
    var map = {
        'working': '<span style="color:#22c55e">✓ Bình thường</span>',
        'late':    '<span style="color:#f59e0b">⚠ Đi trễ</span>',
        'absent':  '<span style="color:#ef4444">✗ Vắng</span>',
        'leave':   '<span style="color:#6366f1">📋 Nghỉ phép</span>',
        'holiday': '<span style="color:#f97316">🎉 Ngày lễ</span>',
        'weekend': '<span style="color:#94a3b8">🏠 Cuối tuần</span>'
    };
    return map[st] || st;
}

function openModal() {
    document.getElementById('tsModal').classList.add('open');
}
function closeModal() {
    document.getElementById('tsModal').classList.remove('open');
    currentTimesheetId = null;
}

function requestCorrection(date, cin, cout) {
    window.location.href = 'index.php?page=yeu-cau-chinh-sua-cham-cong&date=' + date + '&in=' + encodeURIComponent(cin) + '&out=' + encodeURIComponent(cout);
}

function loadTimesheetDetail(id) {
    currentTimesheetId = id;
    document.getElementById('ts-modal-title').textContent = 'Chi tiết bảng công';
    document.getElementById('ts-modal-subtitle').textContent = 'Đang tải dữ liệu...';
    document.getElementById('ts-summary').innerHTML = '';
    document.getElementById('ts-detail-body').innerHTML = '<tr><td colspan="8" class="empty-state">Đang tải...</td></tr>';
    document.getElementById('ts-action-bar').innerHTML = '';
    openModal();

    fetch('index.php?page=nv-api-monthly-timesheet&id=' + id, {
        headers: { 'Accept': 'application/json' }
    })
    .then(function(r) { return r.json(); })
    .then(function(json) {
        if (!json.success) {
            document.getElementById('ts-detail-body').innerHTML = '<tr><td colspan="7" class="empty-state">' + escHtml(json.message) + '</td></tr>';
            return;
        }

        var data = json.data;
        var approval = data.approval || {};
        var daily = data.daily || [];
        var summary = data.summary || {};

        // Title
        var parts = (approval.month_key || '').split('-');
        var monthText = parts.length === 2 ? 'Tháng ' + parts[1] + '/' + parts[0] : approval.month_key;
        document.getElementById('ts-modal-title').textContent = 'Bảng công ' + monthText;
        document.getElementById('ts-modal-subtitle').textContent = 'HR gửi: ' + escHtml(approval.hr_name || 'HR') + ' | Ngày gửi: ' + escHtml(approval.submitted_at || '');

        // Summary
        document.getElementById('ts-summary').innerHTML = [
            { label: 'Ngày công', value: summary.work_days || 0 },
            { label: 'Tổng giờ làm', value: (summary.total_work_hours || 0) + 'h' },
            { label: 'Tổng OT', value: (summary.total_ot_hours || 0) + 'h' },
            { label: 'Tổng đi trễ', value: (summary.total_late_hours || 0) + 'h' }
        ].map(function(item) {
            return '<div class="ts-summary-item"><span>' + escHtml(item.label) + '</span><strong>' + escHtml(item.value) + '</strong></div>';
        }).join('');

        // Daily table
        if (!daily.length) {
            document.getElementById('ts-detail-body').innerHTML = '<tr><td colspan="8" class="empty-state">Không có dữ liệu chấm công</td></tr>';
        } else {
            document.getElementById('ts-detail-body').innerHTML = daily.map(function(row) {
                var dateStr = row.work_date || '';
                var d = new Date(dateStr + 'T00:00:00');
                var dayNames = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
                var dayLabel = dateStr.substring(8, 10) + '/' + dateStr.substring(5, 7);
                var dayName = dayNames[d.getDay()] || '';
                var showEditBtn = approval.status === 'submitted';

                return '<tr' + (row.status === 'absent' ? ' style="opacity:0.5"' : '') + '>' +
                    '<td><strong>' + dayLabel + '</strong> <small style="color:#94a3b8">' + dayName + '</small></td>' +
                    '<td>' + formatTime(row.first_in) + '</td>' +
                    '<td>' + formatTime(row.last_out) + '</td>' +
                    '<td>' + formatMinutes(row.work_minutes) + '</td>' +
                    '<td style="color:#2563eb;font-weight:600">' + formatMinutes(row.overtime_minutes) + '</td>' +
                    '<td style="color:#f59e0b">' + formatMinutes(row.late_minutes) + '</td>' +
                    '<td>' + statusBadge(row.late_minutes > 0 ? 'late' : row.status) + '</td>' +
                    '<td>' + 
                        (showEditBtn ? 
                        '<button class="yc-btn-edit" onclick="requestCorrection(\'' + row.work_date + '\', \'' + (row.first_in || '') + '\', \'' + (row.last_out || '') + '\')">' +
                            '<i class="fas fa-edit"></i> Sửa' +
                        '</button>' : '') +
                    '</td>' +
                    '</tr>';
            }).join('');
        }

        // Action bar
        var actionBar = document.getElementById('ts-action-bar');
        if (approval.status === 'submitted') {
            actionBar.innerHTML =
                '<div class="ts-approve-bar">' +
                    '<p><i class="fas fa-info-circle"></i> Vui lòng kiểm tra và xác nhận bảng công của bạn.</p>' +
                    '<button class="ts-approve-btn" id="approveBtn" onclick="approveTimesheet(' + approval.id + ')"><i class="fas fa-check"></i> Xác nhận duyệt</button>' +
                '</div>';
        } else {
            actionBar.innerHTML =
                '<div class="ts-approved-bar">' +
                    '<i class="fas fa-check-circle" style="font-size:1.3rem"></i>' +
                    '<span>Bạn đã xác nhận duyệt bảng công này' + (approval.approved_at ? ' vào ' + escHtml(approval.approved_at) : '') + '</span>' +
                '</div>';
        }
    })
    .catch(function() {
        document.getElementById('ts-detail-body').innerHTML = '<tr><td colspan="7" class="empty-state">Lỗi tải dữ liệu</td></tr>';
    });
}

function approveTimesheet(id) {
    if (!confirm('Bạn xác nhận duyệt bảng công này?\nSau khi duyệt sẽ không thể thay đổi.')) return;

    var btn = document.getElementById('approveBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Đang xử lý...'; }

    var formData = new FormData();
    formData.append('timesheet_id', id);

    fetch('index.php?page=nv-api-approve-timesheet', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(json) {
        if (json.success) {
            alert('Đã xác nhận duyệt bảng công thành công!');
            closeModal();
            location.reload();
        } else {
            alert(json.message || 'Không thể duyệt');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Xác nhận duyệt'; }
        }
    })
    .catch(function() {
        alert('Lỗi kết nối');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Xác nhận duyệt'; }
    });
}

// Close modal on backdrop click
document.getElementById('tsModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
<?php include 'app/views/layouts/footer.php'; ?>
