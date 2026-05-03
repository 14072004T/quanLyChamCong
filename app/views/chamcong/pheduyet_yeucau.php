<?php
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'manager') {
    header('Location: index.php?page=home');
    exit();
}

$activeRequestId = (int)($_GET['request_id'] ?? 0);
$managerName = $_SESSION['user']['hoTen'] ?? 'Quản lý';
$fromDate = date('Y-m-d');
$toDate = date('Y-m-d', strtotime('+7 days'));
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<style>
.manager-requests-page {
    --ink: #172554;
    --muted: #64748b;
    --line: #e2e8f0;
    --blue: #2563eb;
    --green: #10b981;
    --red: #ef4444;
    --orange: #f59e0b;
    --purple: #8b5cf6;
    background: #f8fbff;
}
.mgrreq-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    padding: 22px 0 20px;
    border-bottom: 1px solid var(--line);
}
.mgrreq-title {
    margin: 0 0 6px;
    color: #0f172a;
    font-size: 1.65rem;
    font-weight: 800;
    letter-spacing: 0;
}
.mgrreq-subtitle {
    margin: 0;
    color: var(--muted);
    font-size: .93rem;
}
.mgrreq-tools {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}
.mgrreq-date,
.mgrreq-user,
.mgrreq-icon-btn,
.mgrreq-outline-btn,
.mgrreq-select,
.mgrreq-search {
    height: 42px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #fff;
    color: #334155;
}
.mgrreq-date {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 12px;
    font-weight: 600;
    font-size: .86rem;
}
.mgrreq-outline-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 14px;
    cursor: pointer;
    font-weight: 700;
}
.mgrreq-icon-btn {
    position: relative;
    width: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.mgrreq-noti-dot {
    position: absolute;
    top: -6px;
    right: -4px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 99px;
    background: #ef4444;
    color: #fff;
    font-size: .68rem;
    font-weight: 800;
    line-height: 18px;
}
.mgrreq-user {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 0 10px;
    font-weight: 700;
    font-size: .86rem;
}
.mgrreq-avatar,
.mgrreq-person-avatar {
    display: grid;
    place-items: center;
    overflow: hidden;
    border-radius: 999px;
    background: linear-gradient(135deg, #dbeafe, #dcfce7);
    color: #1e40af;
    font-weight: 800;
    flex: 0 0 auto;
}
.mgrreq-avatar {
    width: 30px;
    height: 30px;
    font-size: .72rem;
}
.mgrreq-tabs {
    display: flex;
    gap: 6px;
    margin: 18px 0 18px;
    border-bottom: 1px solid var(--line);
    overflow-x: auto;
}
.mgrreq-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 16px;
    border: 0;
    border-bottom: 2px solid transparent;
    background: transparent;
    color: #64748b;
    font-weight: 800;
    cursor: pointer;
    white-space: nowrap;
}
.mgrreq-tab.active {
    color: var(--blue);
    border-bottom-color: var(--blue);
}
.mgrreq-badge {
    min-width: 22px;
    padding: 2px 7px;
    border-radius: 999px;
    background: #ef4444;
    color: #fff;
    font-size: .72rem;
    line-height: 1.25;
    text-align: center;
}
.mgrreq-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.mgrreq-stat {
    display: flex;
    align-items: center;
    gap: 14px;
    min-height: 92px;
    padding: 18px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #fff;
}
.mgrreq-stat.warn {
    border-color: #fdba74;
    border-left: 4px solid #f97316;
    background: linear-gradient(90deg, #fff7ed, #fff);
}
.mgrreq-stat-icon,
.mgrreq-type-icon,
.mgrreq-flow-icon {
    display: inline-grid;
    place-items: center;
    border-radius: 999px;
}
.mgrreq-stat-icon {
    width: 42px;
    height: 42px;
    font-size: 1.05rem;
}
.tone-blue { color: #2563eb; background: #dbeafe; }
.tone-orange { color: #f97316; background: #ffedd5; }
.tone-green { color: #16a34a; background: #dcfce7; }
.tone-red { color: #ef4444; background: #fee2e2; }
.tone-purple { color: #7c3aed; background: #f3e8ff; }
.mgrreq-stat small {
    display: block;
    color: var(--muted);
    font-weight: 700;
}
.mgrreq-stat strong {
    display: block;
    margin: 3px 0;
    color: var(--ink);
    font-size: 1.65rem;
    line-height: 1;
}
.mgrreq-panel {
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #fff;
    overflow: hidden;
}
.mgrreq-filters {
    display: flex;
    align-items: end;
    gap: 14px;
    padding: 18px 18px 14px;
    flex-wrap: wrap;
}
.mgrreq-search {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 0 12px;
    min-width: 280px;
    flex: 1;
}
.mgrreq-search input {
    width: 100%;
    border: 0;
    outline: 0;
    font: inherit;
}
.mgrreq-field {
    display: grid;
    gap: 6px;
}
.mgrreq-field label {
    color: #64748b;
    font-size: .76rem;
    font-weight: 800;
}
.mgrreq-select {
    min-width: 150px;
    padding: 0 11px;
    outline: 0;
}
.mgrreq-table-wrap {
    overflow-x: auto;
}
.mgrreq-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 980px;
}
.mgrreq-table th {
    padding: 12px 18px;
    background: #f8fafc;
    color: #475569;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: 0;
    text-align: left;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid var(--line);
}
.mgrreq-table td {
    padding: 14px 18px;
    border-bottom: 1px solid #f1f5f9;
    color: #172554;
    font-size: .86rem;
    vertical-align: middle;
}
.mgrreq-person {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 180px;
}
.mgrreq-person-avatar {
    width: 42px;
    height: 42px;
    font-size: .78rem;
}
.mgrreq-person strong,
.mgrreq-type strong {
    display: block;
    color: #0f172a;
    margin-bottom: 3px;
}
.mgrreq-person span,
.mgrreq-type span,
.mgrreq-time-note {
    color: #64748b;
    font-size: .8rem;
    line-height: 1.45;
}
.mgrreq-type {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.mgrreq-type-icon {
    width: 28px;
    height: 28px;
    margin-top: 1px;
}
.mgrreq-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 9px;
    border-radius: 7px;
    font-size: .75rem;
    font-weight: 800;
    white-space: nowrap;
}
.status-pending { color: #b45309; background: #fef3c7; }
.status-approved { color: #047857; background: #d1fae5; }
.status-rejected { color: #b91c1c; background: #fee2e2; }
.mgrreq-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    white-space: nowrap;
}
.mgrreq-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 32px;
    padding: 0 11px;
    border: 1px solid transparent;
    border-radius: 6px;
    font-weight: 800;
    font-size: .78rem;
    cursor: pointer;
}
.mgrreq-action.approve {
    background: #10b981;
    color: #fff;
}
.mgrreq-action.reject {
    border-color: #fecaca;
    background: #fff;
    color: #ef4444;
}
.mgrreq-action.more {
    width: 32px;
    padding: 0;
    justify-content: center;
    background: #fff;
    color: #64748b;
}
.mgrreq-action:disabled {
    opacity: .55;
    cursor: not-allowed;
}
.mgrreq-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 14px 18px;
    color: var(--muted);
    font-size: .86rem;
    background: #fff;
}
.mgrreq-pages {
    display: flex;
    align-items: center;
    gap: 7px;
}
.mgrreq-page-btn {
    width: 34px;
    height: 34px;
    border: 1px solid var(--line);
    border-radius: 7px;
    background: #fff;
    color: #334155;
    cursor: pointer;
}
.mgrreq-page-btn.active {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
}
.mgrreq-empty {
    padding: 34px 18px !important;
    text-align: center;
    color: #64748b !important;
}
.mgrreq-flow {
    margin-top: 28px;
    padding: 18px;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    background: linear-gradient(90deg, #eff6ff, #ffffff);
}
.mgrreq-flow h3 {
    margin: 0 0 18px;
    color: #172554;
    font-size: .98rem;
}
.mgrreq-flow-steps {
    display: grid;
    grid-template-columns: 1fr 34px 1fr 34px 1fr 34px 1fr;
    align-items: center;
    gap: 14px;
}
.mgrreq-flow-step {
    display: flex;
    align-items: center;
    gap: 12px;
}
.mgrreq-flow-icon {
    width: 48px;
    height: 48px;
    flex: 0 0 auto;
    border: 1px solid currentColor;
}
.mgrreq-flow-step strong {
    display: block;
    color: #172554;
    font-size: .86rem;
    margin-bottom: 3px;
}
.mgrreq-flow-step span {
    color: #64748b;
    font-size: .78rem;
    line-height: 1.45;
}
.mgrreq-flow-arrow {
    color: #94a3b8;
    text-align: center;
}
.mgrreq-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 22px;
    background: rgba(15, 23, 42, .5);
    z-index: 10000;
}
.mgrreq-modal.open {
    display: flex;
}
.mgrreq-modal-card {
    width: min(520px, 100%);
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 24px 70px rgba(15, 23, 42, .25);
}
.mgrreq-modal-head,
.mgrreq-modal-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px;
    background: #f8fafc;
    border-bottom: 1px solid var(--line);
}
.mgrreq-modal-foot {
    justify-content: flex-end;
    border-top: 1px solid var(--line);
    border-bottom: 0;
}
.mgrreq-modal-head h3 {
    margin: 0;
    color: #0f172a;
    font-size: 1.05rem;
}
.mgrreq-modal-body {
    padding: 18px;
}
.mgrreq-detail-box {
    display: grid;
    gap: 8px;
    padding: 12px;
    margin-bottom: 14px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #f8fafc;
}
.mgrreq-detail-box div {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    color: #334155;
}
.mgrreq-detail-box strong {
    color: #64748b;
}
.mgrreq-modal textarea {
    width: 100%;
    min-height: 104px;
    padding: 11px;
    border: 1px solid var(--line);
    border-radius: 8px;
    font: inherit;
    outline: none;
    resize: vertical;
}
.mgrreq-modal-close {
    border: 0;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    font-size: 1.1rem;
}
@media (max-width: 1280px) {
    .mgrreq-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .mgrreq-flow-steps { grid-template-columns: 1fr; }
    .mgrreq-flow-arrow { display: none; }
}
@media (max-width: 760px) {
    .mgrreq-top { flex-direction: column; }
    .mgrreq-tools { justify-content: flex-start; }
    .mgrreq-grid { grid-template-columns: 1fr; }
    .mgrreq-search { min-width: 100%; }
    .mgrreq-select { width: 100%; }
    .mgrreq-field { flex: 1 1 100%; }
    .mgrreq-footer { flex-direction: column; align-items: flex-start; }
}
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container manager-requests-page">
        <div class="mgrreq-top">
            <div>
                <h1 class="mgrreq-title">Phê duyệt yêu cầu</h1>
                <p class="mgrreq-subtitle">Xem và phê duyệt các yêu cầu của nhân viên trong phạm vi quản lý</p>
            </div>
        </div>

        <div class="mgrreq-tabs" role="tablist">
            <button type="button" class="mgrreq-tab active" data-type="">Tất cả yêu cầu <span class="mgrreq-badge" id="count-all">0</span></button>
            <button type="button" class="mgrreq-tab" data-type="leave"><i  style="color:#f59e0b"></i> Nghỉ phép <span class="mgrreq-badge" id="count-leave">0</span></button>
            <button type="button" class="mgrreq-tab" data-type="ot"><i  style="color:#2563eb"></i> Làm thêm giờ <span class="mgrreq-badge" id="count-ot">0</span></button>
            <button type="button" class="mgrreq-tab" data-type="shift"><i style="color:#8b5cf6"></i> Đổi ca <span class="mgrreq-badge" id="count-shift">0</span></button>
        </div>

        <div class="mgrreq-grid">
            <div class="mgrreq-stat">
                <span class="mgrreq-stat-icon tone-blue"><i class="far fa-clock"></i></span>
                <div><small>Tổng yêu cầu</small><strong id="stat-total">0</strong><small>Trong kỳ</small></div>
            </div>
            <div class="mgrreq-stat">
                <span class="mgrreq-stat-icon tone-orange"><i class="far fa-clock"></i></span>
                <div><small>Chờ phê duyệt</small><strong id="stat-pending">0</strong><small id="stat-pending-rate">0%</small></div>
            </div>
            <div class="mgrreq-stat">
                <span class="mgrreq-stat-icon tone-green"><i class="fas fa-check"></i></span>
                <div><small>Đã phê duyệt</small><strong id="stat-approved">0</strong><small id="stat-approved-rate">0%</small></div>
            </div>
            <div class="mgrreq-stat">
                <span class="mgrreq-stat-icon tone-red"><i class="fas fa-times"></i></span>
                <div><small>Đã từ chối</small><strong id="stat-rejected">0</strong><small id="stat-rejected-rate">0%</small></div>
            </div>
            <div class="mgrreq-stat warn">
                <span class="mgrreq-stat-icon tone-orange"><i class="far fa-clock"></i></span>
                <div><small>Sắp đến hạn xử lý</small><strong id="stat-due">0</strong><small>Trước 24h</small></div>
            </div>
        </div>

        <div class="mgrreq-panel">
            <form class="mgrreq-filters" id="mgrreq-filter-form">
                <div class="mgrreq-search">
                    <i class="fas fa-search" style="color:#64748b"></i>
                    <input type="search" name="q" id="mgrreq-search-input" placeholder="Tìm kiếm theo tên, phòng ban...">
                </div>
                <div class="mgrreq-field">
                    <label>Loại yêu cầu</label>
                    <select class="mgrreq-select" name="type" id="filter-type">
                        <option value="">Tất cả</option>
                        <option value="leave">Nghỉ phép</option>
                        <option value="ot">Làm thêm giờ</option>
                        <option value="shift">Đổi ca</option>
                    </select>
                </div>
                <div class="mgrreq-field">
                    <label>Trạng thái</label>
                    <select class="mgrreq-select" name="status" id="filter-status">
                        <option value="pending">Chờ phê duyệt</option>
                        <option value="">Tất cả</option>
                        <option value="approved">Đã phê duyệt</option>
                        <option value="rejected">Đã từ chối</option>
                    </select>
                </div>
                <div class="mgrreq-field">
                    <label>Phòng ban</label>
                    <select class="mgrreq-select" name="department" id="filter-department">
                        <option value="">Tất cả</option>
                    </select>
                </div>
                <div class="mgrreq-field">
                    <label>Sắp xếp</label>
                    <select class="mgrreq-select" name="sort" id="filter-sort">
                        <option value="newest">Mới nhất</option>
                        <option value="oldest">Cũ nhất</option>
                        <option value="employee">Theo nhân viên</option>
                    </select>
                </div>
                <button type="button" class="mgrreq-outline-btn" id="mgrreq-export" style="margin-left:auto"><i class="fas fa-download"></i> Xuất Excel</button>
            </form>

            <div class="mgrreq-table-wrap">
                <table class="mgrreq-table">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Loại yêu cầu</th>
                            <th>Thời gian</th>
                            <th>Nội dung</th>
                            <th>Trạng thái</th>
                            <th>Thời gian gửi</th>
                            <th style="text-align:right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="mgrreq-table-body">
                        <tr><td colspan="7" class="mgrreq-empty">Đang tải dữ liệu...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="mgrreq-footer">
                <div id="mgrreq-page-info">Hiển thị 0 yêu cầu</div>
                <div class="mgrreq-pages">
                    <select class="mgrreq-select" id="mgrreq-page-size" style="height:34px;min-width:98px">
                        <option value="5">5 / trang</option>
                        <option value="10" selected>10 / trang</option>
                        <option value="20">20 / trang</option>
                    </select>
                    <button type="button" class="mgrreq-page-btn" id="mgrreq-prev"><i class="fas fa-chevron-left"></i></button>
                    <div id="mgrreq-page-buttons" class="mgrreq-pages"></div>
                    <button type="button" class="mgrreq-page-btn" id="mgrreq-next"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>

        <div class="mgrreq-flow">
            <h3>Quy trình phê duyệt yêu cầu</h3>
            <div class="mgrreq-flow-steps">
                <div class="mgrreq-flow-step">
                    <span class="mgrreq-flow-icon tone-blue"><i class="far fa-clipboard"></i></span>
                    <div><strong>Nhân viên gửi yêu cầu</strong><span>Nhân viên tạo và gửi yêu cầu nghỉ phép, OT hoặc đổi ca</span></div>
                </div>
                <div class="mgrreq-flow-arrow"><i class="fas fa-arrow-right"></i></div>
                <div class="mgrreq-flow-step">
                    <span class="mgrreq-flow-icon tone-green"><i class="fas fa-user-check"></i></span>
                    <div><strong>Quản lý phê duyệt</strong><span>Bạn xem xét và phê duyệt hoặc từ chối yêu cầu</span></div>
                </div>
                <div class="mgrreq-flow-arrow"><i class="fas fa-arrow-right"></i></div>
                <div class="mgrreq-flow-step">
                    <span class="mgrreq-flow-icon tone-purple"><i class="far fa-building"></i></span>
                    <div><strong>HR xác nhận</strong><span>Yêu cầu được chuyển đến HR để xác nhận cuối cùng</span></div>
                </div>
                <div class="mgrreq-flow-arrow"><i class="fas fa-arrow-right"></i></div>
                <div class="mgrreq-flow-step">
                    <span class="mgrreq-flow-icon tone-blue"><i class="far fa-clock"></i></span>
                    <div><strong>Thông báo kết quả</strong><span>Nhân viên nhận thông báo kết quả yêu cầu</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mgrreq-modal" id="mgrreq-action-modal" aria-hidden="true">
    <div class="mgrreq-modal-card">
        <div class="mgrreq-modal-head">
            <h3 id="mgrreq-modal-title">Xử lý yêu cầu</h3>
            <button type="button" class="mgrreq-modal-close" id="mgrreq-modal-close" aria-label="Đóng"><i class="fas fa-times"></i></button>
        </div>
        <div class="mgrreq-modal-body">
            <div class="mgrreq-detail-box">
                <div><strong>Nhân viên</strong><span id="modal-employee">--</span></div>
                <div><strong>Thời gian</strong><span id="modal-time">--</span></div>
                <div><strong>Nội dung</strong><span id="modal-reason">--</span></div>
            </div>
            <label for="mgrreq-note" id="mgrreq-note-label" style="display:block;font-weight:800;color:#334155;margin-bottom:7px">Ghi chú</label>
            <textarea id="mgrreq-note" placeholder="Nhập ghi chú..."></textarea>
        </div>
        <div class="mgrreq-modal-foot">
            <button type="button" class="mgrreq-outline-btn" id="mgrreq-modal-cancel">Hủy</button>
            <button type="button" class="mgrreq-action approve" id="mgrreq-modal-confirm"><i class="fas fa-check"></i> Xác nhận</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var activeRequestId = <?= (int)$activeRequestId ?>;
    var allRows = [];
    var filteredRows = [];
    var currentPage = 1;
    var activeType = '';
    var activeAction = null;

    var tbody = document.getElementById('mgrreq-table-body');
    var filterForm = document.getElementById('mgrreq-filter-form');
    var searchInput = document.getElementById('mgrreq-search-input');
    var typeSelect = document.getElementById('filter-type');
    var statusSelect = document.getElementById('filter-status');
    var deptSelect = document.getElementById('filter-department');
    var sortSelect = document.getElementById('filter-sort');
    var pageSizeSelect = document.getElementById('mgrreq-page-size');
    var modal = document.getElementById('mgrreq-action-modal');
    var noteInput = document.getElementById('mgrreq-note');
    var confirmBtn = document.getElementById('mgrreq-modal-confirm');

    function escapeHtml(val) {
        return String(val == null ? '' : val).replace(/[&<>"]/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
        });
    }

    function formatDate(dateTime) {
        if (!dateTime) return '--';
        var text = String(dateTime);
        var parts = text.split(' ');
        var d = parts[0].split('-');
        if (d.length !== 3) return text;
        return d[2] + '/' + d[1] + '/' + d[0] + (parts[1] ? '<br><span class="mgrreq-time-note">' + escapeHtml(parts[1].slice(0, 5)) + '</span>' : '');
    }

    function plainDate(date) {
        if (!date) return '--';
        var d = String(date).split('-');
        return d.length === 3 ? d[2] + '/' + d[1] + '/' + d[0] : String(date);
    }

    function initials(name) {
        var words = String(name || 'NV').trim().split(/\s+/);
        return words.slice(-2).map(function (w) { return w.charAt(0).toUpperCase(); }).join('') || 'NV';
    }

    function classify(row) {
        if (row.request_type === 'ot') {
            return { key: 'ot', label: 'Làm thêm giờ (OT)', sub: Number(row.hours || 0) > 0 ? (Number(row.hours || 0) + ' giờ') : 'OT ngày thường', icon: 'far fa-clock', tone: 'tone-blue' };
        }
        if (row.request_type === 'shift') {
            return { key: 'shift', label: 'Đổi ca', sub: [row.current_shift_name || 'Ca hiện tại', row.requested_shift_name || 'Ca mới'].join(' → '), icon: 'far fa-calendar-alt', tone: 'tone-purple' };
        }
        return { key: 'leave', label: 'Nghỉ phép', sub: row.is_half_day == 1 ? 'Nghỉ nửa ngày' : 'Nghỉ cả ngày', icon: 'fas fa-umbrella-beach', tone: 'tone-orange' };
    }

    function normalizeRow(row) {
        var type = classify(row);
        var dateText = plainDate(row.request_date);
        var timeNote = '';
        if (row.request_type === 'ot') {
            timeNote = [row.start_time ? String(row.start_time).slice(0, 5) : '', row.end_time ? String(row.end_time).slice(0, 5) : ''].filter(Boolean).join(' - ');
            if (Number(row.hours || 0) > 0) timeNote += (timeNote ? ' ' : '') + '(' + Number(row.hours || 0) + ' giờ)';
        } else if (row.request_type === 'shift') {
            timeNote = [row.current_shift_name || 'Ca hiện tại', row.requested_shift_name || 'Ca mới'].join(' → ');
        } else {
            timeNote = row.leave_type || 'annual';
            if (row.is_half_day == 1) timeNote += ' (nửa ngày)';
        }
        return Object.assign({}, row, {
            _type: type,
            _uid: row.uid || (String(row.request_type || 'request') + ':' + String(row.id || '0')),
            _employee: row.hoTen || 'Nhân viên',
            _department: row.phongBan || 'Phòng ban',
            _position: row.chucVu || 'Nhân viên',
            _timeHtml: dateText + '<br><span class="mgrreq-time-note">' + escapeHtml(timeNote || '--') + '</span>',
            _timeText: dateText + ' ' + (timeNote || '')
        });
    }

    function statusInfo(status) {
        if (status === 'approved') return { label: 'Đã phê duyệt', cls: 'status-approved', icon: 'fas fa-check' };
        if (status === 'rejected') return { label: 'Đã từ chối', cls: 'status-rejected', icon: 'fas fa-times' };
        return { label: 'Chờ phê duyệt', cls: 'status-pending', icon: 'far fa-clock' };
    }

    function percentage(value, total) {
        return total > 0 ? Math.round((value / total) * 100) + '%' : '0%';
    }

    function setText(id, value) {
        document.getElementById(id).textContent = value;
    }

    function updateStats() {
        var total = allRows.length;
        var pending = allRows.filter(function (r) { return r.status === 'pending'; }).length;
        var approved = allRows.filter(function (r) { return r.status === 'approved'; }).length;
        var rejected = allRows.filter(function (r) { return r.status === 'rejected'; }).length;
        var due = allRows.filter(function (r) {
            if (r.status !== 'pending' || !r.created_at) return false;
            var created = new Date(String(r.created_at).replace(' ', 'T'));
            return !isNaN(created.getTime()) && (Date.now() - created.getTime()) >= 24 * 60 * 60 * 1000;
        }).length;

        setText('count-all', total);
        setText('count-leave', allRows.filter(function (r) { return r._type.key === 'leave'; }).length);
        setText('count-ot', allRows.filter(function (r) { return r._type.key === 'ot'; }).length);
        setText('count-shift', allRows.filter(function (r) { return r._type.key === 'shift'; }).length);
        setText('stat-total', total);
        setText('stat-pending', pending);
        setText('stat-approved', approved);
        setText('stat-rejected', rejected);
        setText('stat-due', due);
        setText('stat-pending-rate', percentage(pending, total));
        setText('stat-approved-rate', percentage(approved, total));
        setText('stat-rejected-rate', percentage(rejected, total));
    }

    function updateDepartments() {
        var current = deptSelect.value;
        var names = Array.from(new Set(allRows.map(function (r) { return r._department; }).filter(Boolean))).sort();
        deptSelect.innerHTML = '<option value="">Tất cả</option>' + names.map(function (name) {
            return '<option value="' + escapeHtml(name) + '">' + escapeHtml(name) + '</option>';
        }).join('');
        deptSelect.value = names.indexOf(current) !== -1 ? current : '';
    }

    function applyFilters(resetPage) {
        var q = searchInput.value.trim().toLowerCase();
        var typeValue = typeSelect.value || activeType;
        var statusValue = statusSelect.value;
        var deptValue = deptSelect.value;

        filteredRows = allRows.filter(function (row) {
            var haystack = [row._employee, row._department, row._position, row.reason].join(' ').toLowerCase();
            var matchType = !typeValue || row._type.key === typeValue;
            var matchStatus = !statusValue || row.status === statusValue;
            var matchDept = !deptValue || row._department === deptValue;
            return (!q || haystack.indexOf(q) !== -1) && matchType && matchStatus && matchDept;
        });

        if (sortSelect.value === 'oldest') {
            filteredRows.sort(function (a, b) { return String(a.created_at || '').localeCompare(String(b.created_at || '')); });
        } else if (sortSelect.value === 'employee') {
            filteredRows.sort(function (a, b) { return a._employee.localeCompare(b._employee); });
        } else {
            filteredRows.sort(function (a, b) { return String(b.created_at || '').localeCompare(String(a.created_at || '')); });
        }

        if (resetPage) currentPage = 1;
        renderTable();
    }

    function renderTable() {
        var pageSize = Number(pageSizeSelect.value || 10);
        var total = filteredRows.length;
        var pages = Math.max(1, Math.ceil(total / pageSize));
        currentPage = Math.min(Math.max(1, currentPage), pages);
        var start = (currentPage - 1) * pageSize;
        var rows = filteredRows.slice(start, start + pageSize);

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="mgrreq-empty">Không có yêu cầu phù hợp.</td></tr>';
        } else {
            tbody.innerHTML = rows.map(function (row) {
                var st = statusInfo(row.status);
                var disabled = row.status !== 'pending' ? ' disabled' : '';
                var highlight = Number(row.id) === activeRequestId ? ' style="background:#fff7d6"' : '';
                return '<tr id="request-' + escapeHtml(row._uid.replace(':', '-')) + '"' + highlight + '>' +
                    '<td><div class="mgrreq-person"><span class="mgrreq-person-avatar">' + escapeHtml(initials(row._employee)) + '</span><div><strong>' + escapeHtml(row._employee) + '</strong><span>' + escapeHtml(row._position) + '<br>' + escapeHtml(row._department) + '</span></div></div></td>' +
                    '<td><div class="mgrreq-type"><span class="mgrreq-type-icon ' + row._type.tone + '"><i class="' + row._type.icon + '"></i></span><div><strong>' + escapeHtml(row._type.label) + '</strong><span>' + escapeHtml(row._type.sub) + '</span></div></div></td>' +
                    '<td>' + row._timeHtml + '</td>' +
                    '<td style="max-width:230px">' + escapeHtml(row.reason || 'Không có nội dung') + '</td>' +
                    '<td><span class="mgrreq-status ' + st.cls + '"><i class="' + st.icon + '"></i> ' + escapeHtml(st.label) + '</span></td>' +
                    '<td>' + formatDate(row.created_at) + '</td>' +
                    '<td><div class="mgrreq-actions">' +
                        '<button type="button" class="mgrreq-action approve js-action" data-action="approve" data-uid="' + escapeHtml(row._uid) + '"' + disabled + '><i class="fas fa-check"></i> Phê duyệt</button>' +
                        '<button type="button" class="mgrreq-action reject js-action" data-action="reject" data-uid="' + escapeHtml(row._uid) + '"' + disabled + '><i class="fas fa-times"></i> Từ chối</button>' +
                        '<button type="button" class="mgrreq-action more js-detail" data-uid="' + escapeHtml(row._uid) + '"><i class="fas fa-ellipsis-v"></i></button>' +
                    '</div></td>' +
                '</tr>';
            }).join('');
        }

        var from = total ? start + 1 : 0;
        var to = Math.min(start + pageSize, total);
        document.getElementById('mgrreq-page-info').textContent = 'Hiển thị ' + from + ' đến ' + to + ' trong tổng số ' + total + ' yêu cầu';
        document.getElementById('mgrreq-prev').disabled = currentPage <= 1;
        document.getElementById('mgrreq-next').disabled = currentPage >= pages;
        document.getElementById('mgrreq-page-buttons').innerHTML = Array.from({ length: pages }, function (_, i) {
            var page = i + 1;
            return '<button type="button" class="mgrreq-page-btn ' + (page === currentPage ? 'active' : '') + '" data-page="' + page + '">' + page + '</button>';
        }).slice(0, 5).join('');

        if (activeRequestId) {
            var target = document.querySelector('[id$="-' + activeRequestId + '"]');
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            activeRequestId = 0;
        }
    }

    function findRow(uid) {
        return allRows.find(function (row) { return row._uid === uid; });
    }

    function openActionModal(row, action) {
        activeAction = { id: Number(row.id), type: row._type.key, action: action };
        var isApprove = action === 'approve';
        var readOnly = action === 'detail' || row.status !== 'pending';
        document.getElementById('mgrreq-modal-title').textContent = readOnly ? 'Chi tiết yêu cầu' : (isApprove ? 'Phê duyệt yêu cầu' : 'Từ chối yêu cầu');
        document.getElementById('mgrreq-note-label').textContent = readOnly ? 'Ghi chú xử lý' : (isApprove ? 'Ghi chú phê duyệt (nếu có)' : 'Lý do từ chối');
        document.getElementById('modal-employee').textContent = row._employee;
        document.getElementById('modal-time').textContent = row._timeText;
        document.getElementById('modal-reason').textContent = row.reason || 'Không có nội dung';
        confirmBtn.className = 'mgrreq-action ' + (isApprove ? 'approve' : 'reject');
        confirmBtn.innerHTML = '<i class="fas ' + (isApprove ? 'fa-check' : 'fa-times') + '"></i> ' + (isApprove ? 'Phê duyệt' : 'Từ chối');
        confirmBtn.hidden = readOnly;
        noteInput.value = readOnly ? (row.manager_note || 'Chưa có ghi chú xử lý') : '';
        noteInput.readOnly = readOnly;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        if (!readOnly) setTimeout(function () { noteInput.focus(); }, 80);
    }

    function closeActionModal() {
        activeAction = null;
        confirmBtn.hidden = false;
        noteInput.readOnly = false;
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function loadRequests() {
        tbody.innerHTML = '<tr><td colspan="7" class="mgrreq-empty">Đang tải dữ liệu...</td></tr>';
        var params = new URLSearchParams();
        params.set('page', 'manager-api-requests');
        params.set('scope', 'all');
        params.set('limit', '300');

        fetch('index.php?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                allRows = (json.data || []).map(normalizeRow);
                updateDepartments();
                updateStats();
                applyFilters(true);
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="7" class="mgrreq-empty">Không thể tải dữ liệu yêu cầu.</td></tr>';
            });
    }

    filterForm.addEventListener('submit', function (event) {
        event.preventDefault();
        applyFilters(true);
    });
    [searchInput, typeSelect, statusSelect, deptSelect, sortSelect].forEach(function (el) {
        el.addEventListener(el === searchInput ? 'input' : 'change', function () { applyFilters(true); });
    });
    pageSizeSelect.addEventListener('change', function () { applyFilters(true); });

    document.querySelectorAll('.mgrreq-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.mgrreq-tab').forEach(function (x) { x.classList.remove('active'); });
            tab.classList.add('active');
            activeType = tab.getAttribute('data-type') || '';
            typeSelect.value = activeType;
            applyFilters(true);
        });
    });

    document.getElementById('mgrreq-prev').addEventListener('click', function () { currentPage--; renderTable(); });
    document.getElementById('mgrreq-next').addEventListener('click', function () { currentPage++; renderTable(); });
    document.getElementById('mgrreq-page-buttons').addEventListener('click', function (event) {
        var btn = event.target.closest('[data-page]');
        if (!btn) return;
        currentPage = Number(btn.getAttribute('data-page'));
        renderTable();
    });
    document.addEventListener('click', function (event) {
        var actionBtn = event.target.closest('.js-action');
        if (actionBtn) {
            var row = findRow(actionBtn.getAttribute('data-uid'));
            if (row) openActionModal(row, actionBtn.getAttribute('data-action'));
            return;
        }
        var detailBtn = event.target.closest('.js-detail');
        if (detailBtn) {
            var detailRow = findRow(detailBtn.getAttribute('data-uid'));
            if (detailRow) openActionModal(detailRow, 'detail');
        }
    });

    confirmBtn.addEventListener('click', function () {
        if (!activeAction) return;
        if (activeAction.action === 'reject' && !noteInput.value.trim()) {
            alert('Vui lòng nhập lý do từ chối.');
            noteInput.focus();
            return;
        }

        var oldHtml = confirmBtn.innerHTML;
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        var form = new FormData();
        form.append('request_id', activeAction.id);
        form.append('type', activeAction.type);
        form.append('action', activeAction.action);
        form.append('note', noteInput.value.trim());

        fetch('index.php?page=manager-api-request-action', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        })
        .then(function (res) { return res.json(); })
        .then(function (json) {
            if (!json.success) {
                alert(json.message || 'Không thể xử lý yêu cầu.');
                return;
            }
            closeActionModal();
            loadRequests();
        })
        .catch(function () {
            alert('Lỗi kết nối khi xử lý yêu cầu.');
        })
        .finally(function () {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = oldHtml;
        });
    });

    document.getElementById('mgrreq-modal-close').addEventListener('click', closeActionModal);
    document.getElementById('mgrreq-modal-cancel').addEventListener('click', closeActionModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeActionModal();
    });

    document.getElementById('mgrreq-export').addEventListener('click', function () {
        var header = ['Nhan vien', 'Phong ban', 'Loai yeu cau', 'Thoi gian', 'Noi dung', 'Trang thai', 'Thoi gian gui'];
        var csvRows = [header].concat(filteredRows.map(function (row) {
            return [row._employee, row._department, row._type.label, row._timeText, row.reason || '', statusInfo(row.status).label, row.created_at || ''];
        }));
        var csv = csvRows.map(function (cols) {
            return cols.map(function (value) { return '"' + String(value).replace(/"/g, '""') + '"'; }).join(',');
        }).join('\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'phe_duyet_yeu_cau.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    });

    loadRequests();
});
</script>
<?php include 'app/views/layouts/footer.php'; ?>
