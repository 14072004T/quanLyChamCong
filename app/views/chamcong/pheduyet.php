<?php
if (!isset($_SESSION['user'])) { header('Location: index.php?page=login'); exit(); }
if ($_SESSION['role'] !== 'manager') { header('Location: index.php?page=home'); exit(); }

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
$activeApprovalId = (int)($_GET['approval_id'] ?? 0);
$managerDepartment = trim($_SESSION['user']['phongBan'] ?? '');
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<style>
.approval-hero {
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    background: linear-gradient(135deg, #f8fbff 0%, #ffffff 60%);
    border: 1px solid #dbe7f5;
}
.approval-hero h2 {
    margin: 0 0 6px;
    font-size: 1.35rem;
    color: #0f172a;
}
.approval-hero p {
    margin: 0;
    color: #64748b;
}
.approval-hero-meta {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.approval-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #1e293b;
    font-weight: 600;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
}
.approval-metrics {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.approval-metric {
    display: flex;
    align-items: center;
    gap: 14px;
    min-height: 92px;
    padding: 18px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #ffffff;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
}
.approval-metric.warn {
    border-color: #fdba74;
    border-left: 4px solid #f97316;
    background: linear-gradient(90deg, #fff7ed, #ffffff);
}
.approval-metric-icon {
    width: 42px;
    height: 42px;
    display: inline-grid;
    place-items: center;
    border-radius: 999px;
    font-size: 1.05rem;
}
.tone-blue { color: #2563eb; background: #dbeafe; }
.tone-orange { color: #f97316; background: #ffedd5; }
.tone-green { color: #16a34a; background: #dcfce7; }
.tone-red { color: #ef4444; background: #fee2e2; }
.tone-purple { color: #7c3aed; background: #f3e8ff; }
.approval-metric small {
    display: block;
    color: #64748b;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 0.72rem;
}
.approval-metric strong {
    display: block;
    margin: 3px 0;
    color: #0f172a;
    font-size: 1.6rem;
    line-height: 1;
}
.approval-metric .approval-metric-sub {
    display: block;
    font-size: 0.85rem;
    color: #94a3b8;
}
.approval-filter-panel {
    padding: 18px 22px;
}
.approval-filter-grid {
    display: grid;
    grid-template-columns: minmax(220px, 1.4fr) repeat(4, minmax(160px, 1fr));
    gap: 14px;
    align-items: end;
}
.approval-filter-grid .form-group {
    margin-bottom: 0;
}
.approval-filter-grid input[type="text"],
.approval-filter-grid select {
    width: 100%;
}
.approval-table-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.status-pill {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
}
.status-pill.pending {
    background: #fff7ed;
    color: #c2410c;
}
.status-pill.approved {
    background: #ecfdf3;
    color: #16a34a;
}
.status-pill.rejected {
    background: #fef2f2;
    color: #dc2626;
}
.status-pill.processing {
    background: #eef2ff;
    color: #4338ca;
}
.approval-table-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}
.approval-table-head h3 {
    margin: 0;
}
.approval-table-note {
    color: #64748b;
    margin: 0;
}
.approval-row-highlight {
    animation: approvalRowPulse 2.4s ease;
    background: #fff7d6 !important;
}
@keyframes approvalRowPulse {
    0% { background: #ffe58f; }
    100% { background: #fff7d6; }
}
.approval-detail-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.55);
    z-index: 1200;
    padding: 24px;
}
.approval-detail-modal.open {
    display: flex;
}
.approval-detail-card {
    width: min(1100px, 100%);
    max-height: calc(100vh - 48px);
    overflow: auto;
    border-radius: 22px;
    background: #ffffff;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.24);
}
.approval-detail-head {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 24px 26px 18px;
    border-bottom: 1px solid #dbe7f5;
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}
.approval-detail-head h3 {
    margin: 0 0 8px;
    color: #0f172a;
}
.approval-detail-head p {
    margin: 0;
    color: #64748b;
}
.approval-detail-close {
    border: none;
    background: #e2e8f0;
    color: #0f172a;
    width: 38px;
    height: 38px;
    border-radius: 999px;
    cursor: pointer;
    font-size: 18px;
}
.approval-detail-body {
    padding: 22px 26px 26px;
}
.approval-detail-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}
.approval-summary-item {
    padding: 16px;
    border: 1px solid #dbe7f5;
    border-radius: 18px;
    background: #f8fbff;
}
.approval-summary-item span {
    display: block;
    margin-bottom: 6px;
    font-size: 0.8rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
}
.approval-summary-item strong {
    color: #0f172a;
    font-size: 1.35rem;
}
.approval-detail-meta {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}
.approval-meta-box {
    padding: 14px 16px;
    border-radius: 16px;
    background: #ffffff;
    border: 1px solid #dbe7f5;
}
.approval-meta-box strong {
    display: block;
    color: #0f172a;
    margin-bottom: 6px;
}
.approval-detail-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: 18px;
}
.approval-detail-actions .btn-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.approval-input-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.55);
    z-index: 1300;
    padding: 24px;
}
.approval-input-modal.open {
    display: flex;
}
.approval-input-card {
    width: min(500px, 100%);
    border-radius: 22px;
    background: #ffffff;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.24);
    overflow: hidden;
}
.approval-input-head {
    padding: 24px 26px 18px;
    border-bottom: 1px solid #dbe7f5;
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}
.approval-input-head h3 {
    margin: 0;
    color: #0f172a;
}
.approval-input-body {
    padding: 22px 26px;
}
.approval-input-body label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #0f172a;
}
.approval-input-body textarea {
    width: 100%;
    min-height: 120px;
    padding: 12px;
    border: 1px solid #dbe7f5;
    border-radius: 8px;
    font-size: 1rem;
    font-family: 'Inter', sans-serif;
    resize: vertical;
}
.approval-input-body textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.approval-input-footer {
    padding: 18px 26px;
    border-top: 1px solid #dbe7f5;
    background: #f8fbff;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
@media (max-width: 900px) {
    .approval-hero {
        flex-direction: column;
        align-items: stretch;
    }
    .approval-hero-meta {
        justify-content: flex-start;
    }
    .approval-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .approval-filter-grid {
        grid-template-columns: 1fr 1fr;
    }
    .approval-detail-summary,
    .approval-detail-meta {
        grid-template-columns: 1fr 1fr;
    }
    .approval-detail-actions {
        flex-direction: column;
        align-items: stretch;
    }
}
@media (max-width: 640px) {
    .approval-metrics {
        grid-template-columns: 1fr;
    }
    .approval-filter-grid {
        grid-template-columns: 1fr;
    }
    .approval-detail-summary,
    .approval-detail-meta {
        grid-template-columns: 1fr;
    }
    .approval-detail-modal {
        padding: 12px;
    }
    .approval-detail-head,
    .approval-detail-body {
        padding-left: 18px;
        padding-right: 18px;
    }
}
</style>
<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">

        <div class="panel approval-hero">
            <div>
                <h2>Phê duyệt bảng công</h2>
                <p>Manager chỉ xem và phê duyệt bảng công của phòng ban mình quản lý.</p>
            </div>
            <!-- <div class="approval-hero-meta">
                <div class="approval-pill"><i class="fas fa-calendar"></i><span id="approval-range-label">--/--/---- - --/--/----</span></div>
                <div class="approval-pill"><i class="fas fa-building"></i><span><?= htmlspecialchars($managerDepartment ?: 'Chưa phân phòng') ?></span></div>
            </div> -->
        </div>
        

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="approval-metrics">
            <div class="approval-metric">
                <span class="approval-metric-icon tone-blue"><i class="fas fa-list-check"></i></span>
                <div>
                    <small>Tổng bảng công</small>
                    <strong id="metric-total">0</strong>
                    <span class="approval-metric-sub">Bảng công</span>
                </div>
            </div>
            <div class="approval-metric">
                <span class="approval-metric-icon tone-orange"><i class="fas fa-hourglass-half"></i></span>
                <div>
                    <small>Chờ phê duyệt</small>
                    <strong id="metric-pending">0</strong>
                    <span class="approval-metric-sub">Bảng công</span>
                </div>
            </div>
            <div class="approval-metric">
                <span class="approval-metric-icon tone-green"><i class="fas fa-check"></i></span>
                <div>
                    <small>Đã phê duyệt</small>
                    <strong id="metric-approved">0</strong>
                    <span class="approval-metric-sub">Bảng công</span>
                </div>
            </div>
            <div class="approval-metric">
                <span class="approval-metric-icon tone-red"><i class="fas fa-xmark"></i></span>
                <div>
                    <small>Đã từ chối</small>
                    <strong id="metric-rejected">0</strong>
                    <span class="approval-metric-sub">Bảng công</span>
                </div>
            </div>
            <div class="approval-metric warn">
                <span class="approval-metric-icon tone-purple"><i class="fas fa-bell"></i></span>
                <div>
                    <small>Sắp đến hạn xử lý</small>
                    <strong id="metric-due">0</strong>
                    <span class="approval-metric-sub">Bảng công</span>
                </div>
            </div>
        </div>

        <div class="panel approval-filter-panel">
            <form id="approval-filter-form" class="approval-filter-grid">
                <div class="form-group">
                    <label>Tìm kiếm</label>
                    <input type="text" name="q" id="approval-search" placeholder="Tìm theo kỳ công, phòng ban, HR...">
                </div>
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" id="approval-status">
                        <option value="submitted" selected>Chờ phê duyệt</option>
                        <option value="approved">Đã phê duyệt</option>
                        <option value="rejected">Đã từ chối</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Năm</label>
                    <select name="year" id="approval-year">
                        <option value="">Tất cả</option>
                        <?php for ($y = (int)date('Y'); $y >= 2023; $y--): ?>
                            <option value="<?= $y ?>" <?= $y === (int)date('Y') ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Phòng ban</label>
                    <input type="text" value="<?= htmlspecialchars($managerDepartment ?: 'Chưa phân phòng') ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Sắp xếp</label>
                    <select name="sort" id="approval-sort">
                        <option value="newest" selected>Mới nhất</option>
                        <option value="oldest">Cũ nhất</option>
                    </select>
                </div>
            </form>
        </div>

        <div class="panel">
            <div class="approval-table-head">
                <h3 id="approval-pending-title">Danh sách bảng công chờ phê duyệt</h3>
                <p class="approval-table-note">Dữ liệu hiển thị theo phòng ban bạn quản lý.</p>
            </div>
            <table class="table" id="approval-pending-table">
                <thead>
                    <tr>
                        <th>Kỳ bảng công</th>
                        <th>Phòng ban</th>
                        <th>Nhân viên</th>
                        <th>Tổng công chuẩn</th>
                        <th>Tổng giờ OT</th>
                        <th>Tỷ lệ vi phạm</th>
                        <th>Ngày HR gửi</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="approval-pending-body">
                    <tr><td colspan="9" class="empty-state">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <div class="approval-table-head">
                <h3>Lịch sử phê duyệt gần đây</h3>
                <p class="approval-table-note">Theo dõi lại các kỳ công đã xử lý.</p>
            </div>
            <table class="table" id="approval-history-table">
                <thead>
                    <tr>
                        <th>Kỳ bảng công</th>
                        <th>Phòng ban</th>
                        <th>Nhân viên</th>
                        <th>Tổng công chuẩn</th>
                        <th>Tổng giờ OT</th>
                        <th>Tỷ lệ vi phạm</th>
                        <th>Ngày xử lý</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="approval-history-body">
                    <tr><td colspan="9" class="empty-state">Đang tải dữ liệu...</td></tr>
                </tbody>
            </table>
        </div>

    </div>
</div>

<div class="approval-detail-modal" id="approvalDetailModal" aria-hidden="true">
    <div class="approval-detail-card">
        <div class="approval-detail-head">
            <div>
                <h3 id="approval-detail-title">Chi tiết kỳ công</h3>
                <p id="approval-detail-subtitle">Đang tải dữ liệu...</p>
            </div>
            <button type="button" class="approval-detail-close" id="approval-detail-close" aria-label="Đóng">×</button>
        </div>
        <div class="approval-detail-body">
            <div class="approval-detail-summary" id="approval-detail-summary"></div>
            <div class="approval-detail-meta" id="approval-detail-meta"></div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nhân viên</th>
                            <th>Phòng ban</th>
                            <th>Ngày công</th>
                            <th>Giờ làm</th>
                            <th>Giờ OT</th>
                        </tr>
                    </thead>
                    <tbody id="approval-detail-body">
                        <tr><td colspan="6" class="empty-state">Đang tải dữ liệu...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="approval-detail-actions">
                <div id="approval-detail-status" style="color:#64748b;font-weight:600;">Xem kỹ bảng tính công trước khi ra quyết định.</div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary btn-sm" id="approval-detail-cancel">Đóng</button>
                    <button type="button" class="btn btn-warning btn-sm" id="approval-detail-reject"><i class="fas fa-arrow-left"></i> Trả về HR</button>
                    <button type="button" class="btn btn-success btn-sm" id="approval-detail-approve"><i class="fas fa-check"></i> Phê duyệt kỳ công</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="approval-input-modal" id="approvalInputModal" aria-hidden="true">
    <div class="approval-input-card">
        <div class="approval-input-head">
            <h3 id="approval-input-title">Nhập thông tin</h3>
        </div>
        <form id="approval-input-form">
            <div class="approval-input-body">
                <label for="approval-input-note" id="approval-input-label">Ghi chú:</label>
                <textarea id="approval-input-note" name="note" placeholder="Nhập thông tin..." required title="Vui lòng nhập lý do không bỏ trống"></textarea>
            </div>
            <div class="approval-input-footer">
                <button type="button" class="btn btn-secondary btn-sm" id="approval-input-cancel">Hủy</button>
                <button type="submit" class="btn btn-primary btn-sm">Xác nhận</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var autoOpenApprovalId = <?= (int)$activeApprovalId ?>;
    var filterForm = document.getElementById('approval-filter-form');
    var searchInput = document.getElementById('approval-search');
    var statusSelect = document.getElementById('approval-status');
    var yearSelect = document.getElementById('approval-year');
    var sortSelect = document.getElementById('approval-sort');
    var pendingBody = document.getElementById('approval-pending-body');
    var pendingTitle = document.getElementById('approval-pending-title');
    var historyBody = document.getElementById('approval-history-body');
    var rangeLabel = document.getElementById('approval-range-label');
    var metricTotal = document.getElementById('metric-total');
    var metricPending = document.getElementById('metric-pending');
    var metricApproved = document.getElementById('metric-approved');
    var metricRejected = document.getElementById('metric-rejected');
    var metricDue = document.getElementById('metric-due');
    var managerDepartment = <?= json_encode($managerDepartment) ?>;
    var detailModal = document.getElementById('approvalDetailModal');
    var detailTitle = document.getElementById('approval-detail-title');
    var detailSubtitle = document.getElementById('approval-detail-subtitle');
    var detailSummary = document.getElementById('approval-detail-summary');
    var detailMeta = document.getElementById('approval-detail-meta');
    var detailBody = document.getElementById('approval-detail-body');
    var detailStatus = document.getElementById('approval-detail-status');
    var detailActions = document.querySelector('.approval-detail-actions');
    var detailBtnGroup = document.querySelector('.approval-detail-actions .btn-group');
    var closeDetailBtn = document.getElementById('approval-detail-close');
    var cancelDetailBtn = document.getElementById('approval-detail-cancel');
    var approveDetailBtn = document.getElementById('approval-detail-approve');
    var rejectDetailBtn = document.getElementById('approval-detail-reject');

    var inputModal = document.getElementById('approvalInputModal');
    var inputForm = document.getElementById('approval-input-form');
    var inputTitle = document.getElementById('approval-input-title');
    var inputLabel = document.getElementById('approval-input-label');
    var inputNote = document.getElementById('approval-input-note');
    var inputCancelBtn = document.getElementById('approval-input-cancel');

    var activeApprovalId = 0;
    var activeApprovalStatus = '';
    var activeApprovalSource = 'pending'; // pending hoặc history
    var pendingApprovalAction = '';

    function escapeHtml(val) {
        return String(val ?? '').replace(/[&<>"]/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
        });
    }

    function getFormValues() {
        var data = new FormData(filterForm);
        return {
            status: data.get('status') || 'submitted',
            year: data.get('year') || '',
            q: (data.get('q') || '').trim(),
            sort: data.get('sort') || 'newest'
        };
    }

    function formatMonthLabel(monthKey) {
        if (!monthKey) return '';
        var parts = monthKey.split('-');
        if (parts.length !== 2) return monthKey;
        var m = parseInt(parts[1], 10);
        var y = parts[0];
        var prevM = m > 1 ? m - 1 : 12;
        var prevY = m > 1 ? y : String(parseInt(y, 10) - 1);
        return 'Tháng ' + String(prevM).padStart(2, '0') + '/' + prevY + ' - T' + String(m).padStart(2, '0') + '-' + y;
    }

    function formatDate(dateTime) {
        if (!dateTime) return 'Chưa có';
        var parts = String(dateTime).split(' ');
        var dateParts = (parts[0] || '').split('-');
        if (dateParts.length !== 3) return String(dateTime);
        return dateParts.reverse().join('/') + (parts[1] ? (' ' + parts[1].slice(0, 5)) : '');
    }

    function formatRangeLabel(date) {
        var year = date.getFullYear();
        var month = date.getMonth();
        var first = new Date(year, month, 1);
        var last = new Date(year, month + 1, 0);
        var pad = function (n) { return String(n).padStart(2, '0'); };
        return pad(first.getDate()) + '/' + pad(first.getMonth() + 1) + '/' + first.getFullYear() +
            ' - ' + pad(last.getDate()) + '/' + pad(last.getMonth() + 1) + '/' + last.getFullYear();
    }

    function getStatusMeta(status) {
        if (status === 'approved') {
            return { label: 'Đã phê duyệt', cls: 'approved' };
        }
        if (status === 'rejected') {
            return { label: 'Đã từ chối', cls: 'rejected' };
        }
        return { label: 'Chờ phê duyệt', cls: 'pending' };
    }

    function applyFilters(rows, params) {
        var filtered = rows.slice();
        if (params.q) {
            var keyword = params.q.toLowerCase();
            filtered = filtered.filter(function (row) {
                return [row.month_key, row.department, row.hr_name]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase()
                    .indexOf(keyword) !== -1;
            });
        }

        filtered.sort(function (a, b) {
            var aTime = String(a.approved_at || a.submitted_at || '').localeCompare(String(b.approved_at || b.submitted_at || ''));
            return params.sort === 'oldest' ? aTime : -aTime;
        });
        return filtered;
    }

    function isDueSoon(row) {
        if (!row || row.status !== 'submitted') return false;
        var monthKey = row.month_key || '';
        var parts = monthKey.split('-');
        if (parts.length !== 2) return false;
        var year = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10) - 1;
        if (Number.isNaN(year) || Number.isNaN(month)) return false;
        var endDate = new Date(year, month + 1, 0);
        var today = new Date();
        var diffDays = Math.round((endDate - today) / 86400000);
        return diffDays >= 0 && diffDays <= 5;
    }

    function openDetailModal() {
        detailModal.classList.add('open');
        detailModal.setAttribute('aria-hidden', 'false');
    }

    function closeDetailModal() {
        detailModal.classList.remove('open');
        detailModal.setAttribute('aria-hidden', 'true');
        activeApprovalId = 0;
        activeApprovalStatus = '';
        activeApprovalSource = 'pending';
        // Reset btn-group visibility
        detailBtnGroup.style.display = 'flex';
        approveDetailBtn.hidden = false;
        rejectDetailBtn.hidden = false;
    }

    function openInputModal(title, label) {
        inputTitle.textContent = title;
        inputLabel.textContent = label;
        inputNote.value = '';
        inputModal.classList.add('open');
        inputModal.setAttribute('aria-hidden', 'false');
        inputNote.focus();
    }

    function closeInputModal() {
        inputModal.classList.remove('open');
        inputModal.setAttribute('aria-hidden', 'true');
        pendingApprovalAction = '';
        inputNote.value = '';
    }

    function updateDetailActionState(status) {
        var isSubmitted = status === 'submitted';
        approveDetailBtn.hidden = !isSubmitted;
        rejectDetailBtn.hidden = !isSubmitted;
        detailStatus.textContent = isSubmitted
            ? 'Sau khi kiểm tra đầy đủ dữ liệu công, bạn có thể phê duyệt hoặc trả về HR.'
            : 'Kỳ công này đã được xử lý. Bạn có thể xem lại dữ liệu chi tiết.';
    }

    function renderPendingRows(rows) {
        if (!rows.length) {
            pendingBody.innerHTML = '<tr><td colspan="9" class="empty-state">Không có bảng công chờ phê duyệt.</td></tr>';
            return;
        }

        pendingBody.innerHTML = rows.map(function (row) {
            var monthLabel = formatMonthLabel(row.month_key);
            var otDisplay = '<span class="ot-warning"><i class="fas fa-triangle-exclamation"></i></span> ' + Number(row.total_ot_hours || 0).toLocaleString();
            var dateDisplay = formatDate(row.submitted_at);
            var deptLabel = row.department || managerDepartment || 'Chưa phân phòng';
            var statusMeta = getStatusMeta(row.status);

            var approvalId = Number(row.id || 0);
            var rowClass = approvalId === autoOpenApprovalId ? ' class="approval-row-highlight"' : '';
            return '<tr id="approval-' + approvalId + '"' + rowClass + '>' +
                '<td>' + escapeHtml(monthLabel) + '</td>' +
                '<td>' + escapeHtml(deptLabel) + '</td>' +
                '<td>' + Number(row.total_employees || 0) + '</td>' +
                '<td>' + Number(row.total_work_days || 0).toLocaleString() + '</td>' +
                '<td>' + otDisplay + '</td>' +
                '<td>' + Number(row.violation_rate || 0) + '%</td>' +
                '<td>' + escapeHtml(dateDisplay) + '</td>' +
                '<td><span class="status-pill ' + statusMeta.cls + '">' + escapeHtml(statusMeta.label) + '</span></td>' +
                '<td><div class="approval-table-actions">' +
                    '<button type="button" class="btn btn-primary btn-sm js-view-approval-detail" data-id="' + approvalId + '"><i class="fas fa-eye"></i> Xem chi tiết</button>' +
                    '<button type="button" class="btn btn-success btn-sm js-approval-action" data-action="approve" data-id="' + approvalId + '"><i class="fas fa-check"></i> Phê duyệt</button>' +
                    '<button type="button" class="btn btn-warning btn-sm js-approval-action" data-action="reject" data-id="' + approvalId + '"><i class="fas fa-times"></i> Từ chối</button>' +
                '</div></td>' +
                '</tr>';
        }).join('');

        if (autoOpenApprovalId) {
            var targetRow = document.getElementById('approval-' + autoOpenApprovalId);
            if (targetRow) {
                targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }

    function renderHistoryRows(rows) {
        if (!rows.length) {
            historyBody.innerHTML = '<tr><td colspan="9" class="empty-state">Không có lịch sử phê duyệt.</td></tr>';
            return;
        }

        historyBody.innerHTML = rows.map(function (row) {
            var monthLabel = formatMonthLabel(row.month_key);
            var otDisplay = '<span class="ot-warning"><i class="fas fa-triangle-exclamation"></i></span> ' + Number(row.total_ot_hours || 0).toLocaleString();
            var dateDisplay = row.approved_at ? formatDate(row.approved_at) : formatDate(row.submitted_at);
            var statusMeta = getStatusMeta(row.status);
            var approvalId = Number(row.id || 0);
            var deptLabel = row.department || managerDepartment || 'Chưa phân phòng';

            return '<tr>' +
                '<td>' + escapeHtml(monthLabel) + '</td>' +
                '<td>' + escapeHtml(deptLabel) + '</td>' +
                '<td>' + Number(row.total_employees || 0) + '</td>' +
                '<td>' + Number(row.total_work_days || 0).toLocaleString() + '</td>' +
                '<td>' + otDisplay + '</td>' +
                '<td>' + Number(row.violation_rate || 0) + '%</td>' +
                '<td>' + escapeHtml(dateDisplay) + '</td>' +
                '<td><span class="status-pill ' + statusMeta.cls + '">' + escapeHtml(statusMeta.label) + '</span></td>' +
                '<td><div class="approval-table-actions"><button type="button" class="btn btn-primary btn-sm js-view-approval-detail" data-id="' + approvalId + '" data-source="history"><i class="fas fa-eye"></i> Xem</button></div></td>' +
                '</tr>';
        }).join('');
    }

    function loadApprovals() {
        var params = getFormValues();

        var statusTitles = {
            'submitted': 'Danh sách bảng công chờ phê duyệt',
            'approved': 'Danh sách bảng công đã phê duyệt',
            'rejected': 'Danh sách bảng công đã từ chối'
        };
        pendingTitle.textContent = statusTitles[params.status] || 'DANH SÁCH BẢNG CÔNG';

        fetch('index.php?page=manager-api-approvals&status=' + encodeURIComponent(params.status) + '&year=' + encodeURIComponent(params.year), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            var filtered = applyFilters(json.data || [], params);
            renderPendingRows(filtered);
            if (autoOpenApprovalId) {
                loadApprovalDetail(autoOpenApprovalId);
                autoOpenApprovalId = 0;
            }
        })
        .catch(function () {
            pendingBody.innerHTML = '<tr><td colspan="9" class="empty-state">Lỗi tải dữ liệu.</td></tr>';
        });

        fetch('index.php?page=manager-api-approvals&status=history&year=' + encodeURIComponent(params.year), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            var filtered = applyFilters(json.data || [], params);
            renderHistoryRows(filtered);
        })
        .catch(function () {
            historyBody.innerHTML = '<tr><td colspan="9" class="empty-state">Lỗi tải dữ liệu.</td></tr>';
        });

        loadMetrics(params);
    }

    function loadMetrics(params) {
        if (!metricTotal || !metricPending || !metricApproved || !metricRejected || !metricDue) {
            return;
        }

        Promise.all([
            fetch('index.php?page=manager-api-approvals&status=submitted&year=' + encodeURIComponent(params.year), { headers: { 'Accept': 'application/json' } }),
            fetch('index.php?page=manager-api-approvals&status=approved&year=' + encodeURIComponent(params.year), { headers: { 'Accept': 'application/json' } }),
            fetch('index.php?page=manager-api-approvals&status=rejected&year=' + encodeURIComponent(params.year), { headers: { 'Accept': 'application/json' } })
        ])
        .then(function (responses) { return Promise.all(responses.map(function (r) { return r.json(); })); })
        .then(function (payloads) {
            var pendingRows = payloads[0].data || [];
            var approvedRows = payloads[1].data || [];
            var rejectedRows = payloads[2].data || [];
            var total = pendingRows.length + approvedRows.length + rejectedRows.length;
            var dueSoon = pendingRows.filter(isDueSoon).length;

            metricTotal.textContent = total;
            metricPending.textContent = pendingRows.length;
            metricApproved.textContent = approvedRows.length;
            metricRejected.textContent = rejectedRows.length;
            metricDue.textContent = dueSoon;
        })
        .catch(function () {
            metricTotal.textContent = '0';
            metricPending.textContent = '0';
            metricApproved.textContent = '0';
            metricRejected.textContent = '0';
            metricDue.textContent = '0';
        });
    }

    function loadApprovalDetail(approvalId, source) {
        source = source || 'pending';
        activeApprovalId = Number(approvalId || 0);
        activeApprovalSource = source;
        detailTitle.textContent = 'Chi tiết kỳ công';
        detailSubtitle.textContent = 'Đang tải dữ liệu bảng tính công từ HR...';
        detailSummary.innerHTML = '';
        detailMeta.innerHTML = '';
        detailBody.innerHTML = '<tr><td colspan="6" class="empty-state">Đang tải dữ liệu...</td></tr>';

        // Nếu là history, ẩn ngay buttons
        if (source === 'history') {
            approveDetailBtn.hidden = true;
            rejectDetailBtn.hidden = true;
            detailBtnGroup.style.display = 'none';
            detailStatus.textContent = 'Kỳ công này đã được xử lý. Bạn chỉ có thể xem lại dữ liệu chi tiết.';
        } else {
            detailBtnGroup.style.display = 'flex';
            updateDetailActionState('submitted');
        }
        openDetailModal();

        fetch('index.php?page=manager-api-approval-detail&approval_id=' + encodeURIComponent(activeApprovalId), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (!json.success) {
                detailBody.innerHTML = '<tr><td colspan="6" class="empty-state">' + escapeHtml(json.message || 'Không thể tải chi tiết kỳ công.') + '</td></tr>';
                detailStatus.textContent = 'Không thể tải chi tiết để phê duyệt.';
                approveDetailBtn.hidden = true;
                rejectDetailBtn.hidden = true;
                return;
            }

            var detail = json.data || {};
            var approval = detail.approval || {};
            var summary = detail.summary || {};
            var rows = detail.rows || [];
            activeApprovalStatus = approval.status || '';

            detailTitle.textContent = 'Chi tiết bảng tính công kỳ ' + (approval.month_key || '');
            detailSubtitle.textContent = 'HR gửi: ' + escapeHtml(approval.hr_name || 'Chưa xác định') + ' | Ngày gửi: ' + escapeHtml(formatDate(approval.submitted_at));
            detailSummary.innerHTML = [
                { label: 'Nhân sự', value: Number(summary.employees || 0) },
                { label: 'Tổng ngày công', value: Number(summary.total_work_days || 0).toLocaleString() },
                { label: 'Tổng giờ làm', value: Number(summary.total_work_hours || 0).toLocaleString() },
                { label: 'Tổng OT', value: Number(summary.total_overtime_hours || 0).toLocaleString() + 'h' }
            ].map(function (item) {
                return '<div class="approval-summary-item"><span>' + escapeHtml(item.label) + '</span><strong>' + escapeHtml(item.value) + '</strong></div>';
            }).join('');

            detailMeta.innerHTML = [
                { label: 'Kỳ công', value: formatMonthLabel(approval.month_key || '') },
                { label: 'Phòng ban', value: approval.department || managerDepartment || 'Chưa phân phòng' },
                { label: 'Người gửi HR', value: approval.hr_name || 'Chưa xác định' },
                { label: 'Trạng thái', value: getStatusMeta(approval.status || 'submitted').label },
                { label: 'Ghi chú', value: approval.note || 'Chưa có ghi chú' }
            ].map(function (item) {
                return '<div class="approval-meta-box"><strong>' + escapeHtml(item.label) + '</strong><div>' + escapeHtml(item.value) + '</div></div>';
            }).join('');

            if (!rows.length) {
                detailBody.innerHTML = '<tr><td colspan="6" class="empty-state">Không có dữ liệu chi tiết cho kỳ công này.</td></tr>';
            } else {
                detailBody.innerHTML = rows.map(function (row, index) {
                    return '<tr>' +
                        '<td>' + (index + 1) + '</td>' +
                        '<td>' + escapeHtml(row.hoTen || '') + '</td>' +
                        '<td>' + escapeHtml(row.phongBan || '-') + '</td>' +
                        '<td>' + Number(row.work_days || 0) + '</td>' +
                        '<td>' + Number(row.work_hours || 0).toLocaleString() + '</td>' +
                        '<td>' + Number(row.overtime_hours || 0).toLocaleString() + '</td>' +
                        '</tr>';
                }).join('');
            }

            // Chỉ update action state nếu là pending, không phải history
            if (activeApprovalSource !== 'history') {
                updateDetailActionState(activeApprovalStatus);
            }
        })
        .catch(function () {
            detailBody.innerHTML = '<tr><td colspan="6" class="empty-state">Lỗi tải dữ liệu chi tiết.</td></tr>';
            detailStatus.textContent = 'Không thể tải chi tiết để phê duyệt.';
            approveDetailBtn.hidden = true;
            rejectDetailBtn.hidden = true;
        });
    }

    function processApprovalWithNote(note) {
        if (!activeApprovalId || !pendingApprovalAction) return;

        var form = new FormData();
        form.append('approval_id', activeApprovalId);
        form.append('action', pendingApprovalAction);
        form.append('note', note);

        fetch('index.php?page=manager-api-approve', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            alert(json.message || (json.success ? 'Đã xử lý' : 'Lỗi'));
            if (json.success) {
                closeDetailModal();
                closeInputModal();
                loadApprovals();
            }
        })
        .catch(function () { alert('Lỗi xử lý phê duyệt.'); });
    }

    function showApprovalInputModal(action) {
        if (!activeApprovalId) return;
        pendingApprovalAction = action;

        if (action === 'approve') {
            openInputModal('Phê duyệt kỳ công', 'Ghi chú phê duyệt (nếu có):');
        } else if (action === 'reject') {
            openInputModal('Trả về HR', 'Lý do trả về:');
        }
    }

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        loadApprovals();
    });

    if (searchInput) {
        var searchTimer = null;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(loadApprovals, 250);
        });
    }
    if (statusSelect) {
        statusSelect.addEventListener('change', loadApprovals);
    }
    if (yearSelect) {
        yearSelect.addEventListener('change', loadApprovals);
    }
    if (sortSelect) {
        sortSelect.addEventListener('change', loadApprovals);
    }

    document.addEventListener('click', function (e) {
        var viewBtn = e.target.closest('.js-view-approval-detail');
        if (viewBtn) {
            var approvalId = viewBtn.getAttribute('data-id');
            var source = viewBtn.getAttribute('data-source') || 'pending';
            loadApprovalDetail(approvalId, source);
        }

        var actionBtn = e.target.closest('.js-approval-action');
        if (actionBtn) {
            var actionApprovalId = Number(actionBtn.getAttribute('data-id'));
            var action = actionBtn.getAttribute('data-action');
            if (actionApprovalId && (action === 'approve' || action === 'reject')) {
                activeApprovalId = actionApprovalId;
                showApprovalInputModal(action);
            }
        }
    });

    closeDetailBtn.addEventListener('click', closeDetailModal);
    cancelDetailBtn.addEventListener('click', closeDetailModal);
    approveDetailBtn.addEventListener('click', function () { showApprovalInputModal('approve'); });
    rejectDetailBtn.addEventListener('click', function () { showApprovalInputModal('reject'); });

    inputCancelBtn.addEventListener('click', closeInputModal);
    inputNote.addEventListener('invalid', function (e) {
        if (this.validity.valueMissing) {
            this.setCustomValidity('Vui lòng nhập lý do không bỏ trống');
        }
    });
    inputNote.addEventListener('input', function () {
        this.setCustomValidity('');
    });
    inputForm.addEventListener('submit', function (e) {
        e.preventDefault();
        var note = inputNote.value.trim();
        processApprovalWithNote(note);
    });

    inputModal.addEventListener('click', function (e) {
        if (e.target === inputModal) {
            closeInputModal();
        }
    });

    detailModal.addEventListener('click', function (e) {
        if (e.target === detailModal) {
            closeDetailModal();
        }
    });

    if (rangeLabel) {
        rangeLabel.textContent = formatRangeLabel(new Date());
    }

    loadApprovals();
});
</script>
<?php include 'app/views/layouts/footer.php'; ?>
