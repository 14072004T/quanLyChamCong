<?php 
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}
$message = $message ?? '';
$error = $error ?? '';
$myRequests = $myRequests ?? [];
$leaveQuota = $leaveQuota ?? [
    'total'     => 12,
    'used'      => 0,
    'pending'   => 0,
    'remaining' => 12,
    'year'      => date('Y'),
    'updatedAt' => date('d/m/Y H:i')
];

$leaveTypeLabels = [
    'sick'      => 'Nghỉ ốm',
    'personal'  => 'Nghỉ việc riêng',
    'emergency' => 'Nghỉ khẩn cấp',
    'wedding'   => 'Nghỉ cưới',
    'funeral'   => 'Nghỉ tang',
    'annual'    => 'Nghỉ phép thường niên (Annual)',
    'other'     => 'Khác',
];

// Các loại nghỉ phép có lương (CP) theo yêu cầu: tất cả các loại trừ "Khác"
$paidTypes = ['sick', 'personal', 'emergency', 'wedding', 'funeral', 'annual'];

$statusLabels = [
    'pending'  => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Từ chối',
];

function calcLeaveDays2($from, $to) {
    if (!$from || !$to) return 0;
    return max(1, (int)round((strtotime($to) - strtotime($from)) / 86400) + 1);
}
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>

<style>
/* ========================================================
   DON NGHI PHEP - REDESIGN TO EXACT USER SPECIFICATION
======================================================== */
.lrf-page {
    max-width: 980px;
    margin: 0 auto;
    padding: 0 16px 40px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #1e293b;
}

/* Page Header */
.lrf-page-header {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.03);
}
.lrf-header-icon {
    width: 44px;
    height: 44px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2563eb;
    font-size: 20px;
    flex-shrink: 0;
}
.lrf-page-header h1 {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px;
}
.lrf-page-header p {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}

/* Alerts */
.lrf-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 14px;
    font-size: 13px;
    font-weight: 500;
}
.lrf-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
.lrf-alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

/* 1. THỐNG KÊ QUỸ NGHỈ PHÉP */
.lrf-quota-panel {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.03);
}
.lrf-quota-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 10px;
}
.lrf-quota-title-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.lrf-quota-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}
.lrf-quota-title::before {
    content: '';
    width: 8px;
    height: 8px;
    background: #2563eb;
    border-radius: 50%;
    display: inline-block;
}
.lrf-quota-badge {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #dbeafe;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 16px;
}
.lrf-quota-updated {
    font-size: 11px;
    color: #94a3b8;
}

.lrf-quota-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 18px;
}
@media (max-width: 640px) {
    .lrf-quota-cards { grid-template-columns: repeat(2, 1fr); }
}

.lrf-quota-card {
    padding: 14px 16px;
    border-radius: 10px;
    border: 1px solid transparent;
}
.lrf-quota-card-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 6px;
}
.lrf-quota-card-val-row {
    display: flex;
    align-items: baseline;
    gap: 5px;
    margin-bottom: 4px;
}
.lrf-quota-card-val {
    font-size: 28px;
    font-weight: 800;
    line-height: 1;
}
.lrf-quota-card-unit {
    font-size: 12px;
    font-weight: 500;
}
.lrf-quota-card-sub {
    font-size: 11px;
}

/* Card 1: Tổng được cấp */
.lrf-qc-total {
    background: #f8fafc;
    border-color: #f1f5f9;
}
.lrf-qc-total .lrf-quota-card-label { color: #64748b; }
.lrf-qc-total .lrf-quota-card-val   { color: #0f172a; }
.lrf-qc-total .lrf-quota-card-unit  { color: #64748b; }
.lrf-qc-total .lrf-quota-card-sub   { color: #94a3b8; }

/* Card 2: Đã sử dụng */
.lrf-qc-used {
    background: #f0fdf4;
    border-color: #dcfce7;
}
.lrf-qc-used .lrf-quota-card-label { color: #15803d; }
.lrf-qc-used .lrf-quota-card-val   { color: #16a34a; }
.lrf-qc-used .lrf-quota-card-unit  { color: #16a34a; }
.lrf-qc-used .lrf-quota-card-sub   { color: #16a34a; }

/* Card 3: Đang chờ duyệt */
.lrf-qc-pending {
    background: #fffbeb;
    border-color: #fef3c7;
}
.lrf-qc-pending .lrf-quota-card-label { color: #b45309; }
.lrf-qc-pending .lrf-quota-card-val   { color: #d97706; }
.lrf-qc-pending .lrf-quota-card-unit  { color: #d97706; }
.lrf-qc-pending .lrf-quota-card-sub   { color: #d97706; }

/* Card 4: Quỹ phép khả dụng */
.lrf-qc-avail {
    background: #eff6ff;
    border-color: #dbeafe;
}
.lrf-qc-avail .lrf-quota-card-label { color: #1d4ed8; }
.lrf-qc-avail .lrf-quota-card-val   { color: #2563eb; }
.lrf-qc-avail .lrf-quota-card-unit  { color: #2563eb; }
.lrf-qc-avail .lrf-quota-card-sub   { color: #2563eb; }

/* Progress bar */
.lrf-quota-progress-wrap { margin-bottom: 4px; }
.lrf-quota-progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    margin-bottom: 8px;
}
.lrf-quota-progress-label-left { color: #64748b; font-weight: 500; }
.lrf-quota-progress-label-right { color: #0f172a; font-weight: 700; }

.lrf-quota-bar {
    height: 8px;
    background: #f1f5f9;
    border-radius: 999px;
    overflow: hidden;
    display: flex;
}
.lrf-quota-bar-used    { background: #10b981; }
.lrf-quota-bar-pending { background: #f59e0b; }
.lrf-quota-bar-avail   { background: #3b82f6; }

.lrf-quota-legend {
    display: flex;
    gap: 16px;
    margin-top: 8px;
    flex-wrap: wrap;
}
.lrf-quota-legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #64748b;
}
.lrf-legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 2px;
}

/* 2. TẠO ĐƠN NGHỈ PHÉP MỚI */
.lrf-form-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 22px 24px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.03);
}
.lrf-section-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.lrf-section-title i {
    color: #2563eb;
    font-size: 15px;
}

.lrf-form-row-1 {
    display: grid;
    grid-template-columns: 2.2fr 1.3fr 1.3fr 100px;
    gap: 14px;
    margin-bottom: 14px;
    align-items: end;
}
@media (max-width: 768px) {
    .lrf-form-row-1 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 480px) {
    .lrf-form-row-1 { grid-template-columns: 1fr; }
}

.lrf-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.lrf-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
    color: #475569;
}
.lrf-req { color: #ef4444; }

.lrf-input {
    height: 42px;
    padding: 0 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 13.5px;
    font-family: inherit;
    color: #0f172a;
    background: #fff;
    box-sizing: border-box;
    transition: border-color .2s, box-shadow .2s;
    outline: none;
    width: 100%;
}
.lrf-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.12);
}

.lrf-duration-box {
    height: 42px;
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    text-align: center;
    padding: 2px 8px;
}
.lrf-duration-title {
    font-size: 9.5px;
    font-weight: 700;
    color: #2563eb;
    text-transform: uppercase;
    letter-spacing: .4px;
    line-height: 1;
    margin-bottom: 2px;
}
.lrf-duration-val {
    font-size: 13.5px;
    font-weight: 800;
    color: #1d4ed8;
    line-height: 1.1;
}

.lrf-form-row-2 {
    margin-bottom: 14px;
}
.lrf-form-row-2 input {
    height: 42px;
}

.lrf-form-row-3 {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.lrf-action-group {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 14px;
    align-items: center;
}
@media (max-width: 580px) {
    .lrf-action-group { grid-template-columns: 1fr; }
}

.lrf-file-box {
    height: 42px;
    border: 1px dashed #cbd5e1;
    border-radius: 8px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 14px;
    cursor: pointer;
    box-sizing: border-box;
    transition: border-color .2s, background .2s;
    position: relative;
    overflow: hidden;
}
.lrf-file-box:hover {
    border-color: #2563eb;
    background: #f0f7ff;
}
.lrf-file-box input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}
.lrf-file-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #64748b;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
}
.lrf-file-info i { color: #94a3b8; font-size: 14px; flex-shrink: 0; }
.lrf-file-btn {
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    flex-shrink: 0;
    pointer-events: none;
}

.lrf-submit-btn {
    height: 42px;
    padding: 0 24px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    white-space: nowrap;
    transition: background .2s, transform .1s;
    font-family: inherit;
    box-shadow: 0 2px 6px rgba(37,99,235,.25);
}
.lrf-submit-btn:hover { background: #1d4ed8; }
.lrf-submit-btn:active { transform: translateY(1px); }
.lrf-submit-btn:disabled { opacity: .7; cursor: not-allowed; }

.lrf-spinner {
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: lrfSpin .6s linear infinite;
}
@keyframes lrfSpin { to { transform: rotate(360deg); } }

.lrf-err {
    font-size: 11.5px;
    color: #ef4444;
    margin-top: 3px;
    display: none;
}

/* Weekend Warning */
.lrf-weekend-warning {
    display: none;
    margin-top: 14px;
    padding: 12px 16px;
    border-radius: 10px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    font-size: 12.5px;
    line-height: 1.5;
    color: #991b1b;
    animation: lrfShake .4s ease-in-out;
}
.lrf-weekend-warning i {
    color: #ef4444;
    margin-right: 6px;
}
.lrf-weekend-warning .lrf-ww-title {
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.lrf-weekend-warning .lrf-ww-dates {
    background: #fff5f5;
    border: 1px solid #fed7d7;
    border-radius: 6px;
    padding: 8px 12px;
    margin-top: 8px;
    font-size: 12px;
    color: #c53030;
    font-weight: 500;
}
.lrf-weekend-warning .lrf-ww-hint {
    margin-top: 8px;
    font-size: 11.5px;
    color: #b91c1c;
    font-style: italic;
}
@keyframes lrfShake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-4px); }
    40% { transform: translateX(4px); }
    60% { transform: translateX(-3px); }
    80% { transform: translateX(3px); }
}

.lrf-type-note {
    display: none;
    margin-top: 12px;
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 12.5px;
    line-height: 1.4;
    border: 1px solid transparent;
}

/* 3. LỊCH SỬ ĐƠN NGHỈ PHÉP */
.lrf-history-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 22px 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,.03);
}
.lrf-history-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 12px;
}

.lrf-filter-tabs {
    background: #f1f5f9;
    padding: 3px;
    border-radius: 8px;
    display: inline-flex;
    gap: 4px;
}
.lrf-tab {
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    border-radius: 6px;
    cursor: pointer;
    border: none;
    background: transparent;
    transition: all .15s;
    user-select: none;
}
.lrf-tab:hover { color: #0f172a; }
.lrf-tab.active {
    background: #fff;
    color: #0f172a;
    font-weight: 700;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
}

.lrf-hist-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
}
@media (max-width: 768px) {
    .lrf-hist-grid { grid-template-columns: 1fr; }
}

.lrf-hist-item {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
    box-shadow: 0 1px 3px rgba(0,0,0,.02);
}
.lrf-hist-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,.06);
}

.lrf-hist-item.status-approved { border-left: 4px solid #10b981; }
.lrf-hist-item.status-pending  { border-left: 4px solid #f59e0b; }
.lrf-hist-item.status-rejected { border-left: 4px solid #ef4444; }

.lrf-hi-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}
.lrf-hi-title {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}

.lrf-stat-badge {
    font-size: 11px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}
.lrf-stat-badge.approved { background: #dcfce7; color: #15803d; }
.lrf-stat-badge.pending  { background: #fef3c7; color: #b45309; }
.lrf-stat-badge.rejected { background: #fee2e2; color: #b91c1c; }
.lrf-dot { font-size: 8px; line-height: 1; }

.lrf-hi-dates {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #475569;
    flex-wrap: wrap;
}
.lrf-hi-dates i { color: #94a3b8; }
.lrf-hi-dates-txt { font-weight: 500; }

.lrf-tag-paid   { color: #2563eb; font-weight: 600; }
.lrf-tag-unpaid { color: #64748b; font-weight: 600; }

.lrf-hi-reason-box {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 12.5px;
    color: #475569;
    line-height: 1.45;
}

.lrf-hi-rejection-box {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 12.5px;
    line-height: 1.45;
    color: #991b1b;
}

.lrf-hi-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
    padding-top: 8px;
    border-top: 1px solid #f1f5f9;
    font-size: 11.5px;
}
.lrf-hi-attachment {
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.lrf-hi-attachment:hover { text-decoration: underline; }
.lrf-hi-no-file { color: #94a3b8; font-size: 11px; }
.lrf-hi-time { color: #94a3b8; font-size: 11px; }

.lrf-empty {
    text-align: center;
    padding: 36px 20px;
    color: #94a3b8;
}
.lrf-empty i { font-size: 32px; display: block; margin-bottom: 10px; }
.lrf-empty p { font-size: 13.5px; margin: 0; }

/* Modal Chi tiết */
.lrf-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    inset: 0;
    background: rgba(15,23,42,.45);
    align-items: center;
    justify-content: center;
    padding: 20px;
    backdrop-filter: blur(2px);
}
.lrf-modal.open { display: flex; }
.lrf-modal-content {
    background: #fff;
    border-radius: 14px;
    width: 100%;
    max-width: 520px;
    box-shadow: 0 20px 40px rgba(0,0,0,.15);
    overflow: hidden;
    animation: lrfModalIn .2s ease-out;
}
@keyframes lrfModalIn {
    from { transform: translateY(16px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
.lrf-modal-header {
    padding: 16px 22px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.lrf-modal-header h4 {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 8px;
}
.lrf-modal-close {
    border: none;
    background: none;
    cursor: pointer;
    color: #94a3b8;
    font-size: 18px;
    padding: 0;
}
.lrf-modal-close:hover { color: #0f172a; }
.lrf-modal-body { padding: 22px; }
.lrf-modal-footer {
    padding: 14px 22px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
}
.lrf-detail-row {
    display: flex;
    margin-bottom: 11px;
    padding-bottom: 11px;
    border-bottom: 1px solid #f1f5f9;
    gap: 14px;
}
.lrf-detail-row:last-child { border: none; margin: 0; padding: 0; }
.lrf-detail-label {
    width: 115px;
    flex-shrink: 0;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .3px;
    padding-top: 2px;
}
.lrf-detail-val {
    flex: 1;
    font-size: 13.5px;
    color: #0f172a;
    font-weight: 500;
}
.lrf-modal-loading {
    text-align: center;
    padding: 30px;
    color: #94a3b8;
}

/* Mobile responsive styles */
@media (max-width: 640px) {
    .lrf-page { padding: 0 10px 30px; }
    .lrf-page-header { padding: 14px 16px; }
    .lrf-quota-panel, .lrf-form-card, .lrf-history-card { padding: 16px; }
    .lrf-quota-cards { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">

    <div class="lrf-page">

        <!-- Page Header -->
        <div class="lrf-page-header">
            <div class="lrf-header-icon">
                <i class="fa-regular fa-calendar-check"></i>
            </div>
            <div>
                <h1>Đơn Nghỉ Phép</h1>
                <p>Tạo đơn xin nghỉ phép và theo dõi trạng thái phê duyệt từ HR / Quản lý.</p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($message)): ?>
            <div class="lrf-alert lrf-alert-success" id="lrfAlert">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="lrf-alert lrf-alert-error" id="lrfAlert">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- 1. THỐNG KÊ QUỸ NGHỈ PHÉP NĂM -->
        <?php
            $totalQ     = max(1, (int)$leaveQuota['total']);
            $usedQ      = (int)$leaveQuota['used'];
            $pendingQ   = (int)$leaveQuota['pending'];
            $remainingQ = (int)$leaveQuota['remaining'];
            $usedPct    = round($usedQ / $totalQ * 100);
            $pendingPct = round($pendingQ / $totalQ * 100);
            $availPct   = max(0, 100 - $usedPct - $pendingPct);
        ?>
        <div class="lrf-quota-panel">
            <div class="lrf-quota-top">
                <div class="lrf-quota-title-wrap">
                    <div class="lrf-quota-title">
                        Thống Kê Quỹ Nghỉ Phép Năm <?= htmlspecialchars($leaveQuota['year']) ?>
                    </div>
                    <span class="lrf-quota-badge"><?= $totalQ ?> ngày hưởng lương/năm</span>
                </div>
                <div class="lrf-quota-updated">Cập nhật lúc: <?= htmlspecialchars($leaveQuota['updatedAt']) ?></div>
            </div>

            <div class="lrf-quota-cards">
                <!-- Card 1: Tổng được cấp -->
                <div class="lrf-quota-card lrf-qc-total">
                    <div class="lrf-quota-card-label">Tổng được cấp</div>
                    <div class="lrf-quota-card-val-row">
                        <span class="lrf-quota-card-val"><?= $totalQ ?></span>
                        <span class="lrf-quota-card-unit">ngày</span>
                    </div>
                    <div class="lrf-quota-card-sub">Phép năm tiêu chuẩn</div>
                </div>

                <!-- Card 2: Đã sử dụng -->
                <div class="lrf-quota-card lrf-qc-used">
                    <div class="lrf-quota-card-label">Đã sử dụng</div>
                    <div class="lrf-quota-card-val-row">
                        <span class="lrf-quota-card-val"><?= $usedQ ?></span>
                        <span class="lrf-quota-card-unit">ngày</span>
                    </div>
                    <div class="lrf-quota-card-sub">Đã được phê duyệt</div>
                </div>

                <!-- Card 3: Đang chờ duyệt -->
                <div class="lrf-quota-card lrf-qc-pending">
                    <div class="lrf-quota-card-label">Đang chờ duyệt</div>
                    <div class="lrf-quota-card-val-row">
                        <span class="lrf-quota-card-val"><?= $pendingQ ?></span>
                        <span class="lrf-quota-card-unit">ngày</span>
                    </div>
                    <div class="lrf-quota-card-sub">Hồ sơ đang xử lý</div>
                </div>

                <!-- Card 4: Quỹ phép khả dụng -->
                <div class="lrf-quota-card lrf-qc-avail">
                    <div class="lrf-quota-card-label">Quỹ phép khả dụng</div>
                    <div class="lrf-quota-card-val-row">
                        <span class="lrf-quota-card-val"><?= $remainingQ ?></span>
                        <span class="lrf-quota-card-unit">ngày</span>
                    </div>
                    <div class="lrf-quota-card-sub">Có lương tối đa</div>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="lrf-quota-progress-wrap">
                <div class="lrf-quota-progress-label">
                    <span class="lrf-quota-progress-label-left">Tỷ lệ sử dụng quỹ phép</span>
                    <span class="lrf-quota-progress-label-right"><?= $availPct ?>% còn lại</span>
                </div>
                <div class="lrf-quota-bar">
                    <div class="lrf-quota-bar-used"    style="width:<?= $usedPct ?>%"></div>
                    <div class="lrf-quota-bar-pending" style="width:<?= $pendingPct ?>%"></div>
                    <div class="lrf-quota-bar-avail"   style="width:<?= $availPct ?>%"></div>
                </div>
                <div class="lrf-quota-legend">
                    <div class="lrf-quota-legend-item">
                        <div class="lrf-legend-dot" style="background:#10b981"></div> Đã dùng (<?= $usedQ ?>)
                    </div>
                    <div class="lrf-quota-legend-item">
                        <div class="lrf-legend-dot" style="background:#f59e0b"></div> Chờ duyệt (<?= $pendingQ ?>)
                    </div>
                    <div class="lrf-quota-legend-item">
                        <div class="lrf-legend-dot" style="background:#3b82f6"></div> Khả dụng (<?= $remainingQ ?>)
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. TẠO ĐƠN NGHỈ PHÉP MỚI -->
        <div class="lrf-form-card">
            <div class="lrf-section-title">
                <i class="fa-regular fa-pen-to-square"></i> Tạo Đơn Nghỉ Phép Mới
            </div>

            <form method="POST" action="index.php?page=store-leave-request" enctype="multipart/form-data" id="leaveForm">

                <!-- Row 1: Loại nghỉ + Từ ngày + Đến ngày + Thời gian -->
                <div class="lrf-form-row-1">
                    <div class="lrf-field">
                        <label class="lrf-label" for="loaiNghiPhep">Loại nghỉ phép <span class="lrf-req">*</span></label>
                        <select id="loaiNghiPhep" name="loaiNghiPhep" class="lrf-input" required>
                            <option value="" disabled selected>— Chọn —</option>
                            <?php foreach ($leaveTypeLabels as $val => $lbl): ?>
                                <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="lrf-err" id="err-loai">Vui lòng chọn loại nghỉ phép.</div>
                    </div>

                    <div class="lrf-field">
                        <label class="lrf-label" for="tuNgay">Từ ngày <span class="lrf-req">*</span></label>
                        <input type="date" id="tuNgay" name="tuNgay" class="lrf-input" required min="<?= date('Y-m-d') ?>">
                        <div class="lrf-err" id="err-tu">Chọn ngày bắt đầu.</div>
                    </div>

                    <div class="lrf-field">
                        <label class="lrf-label" for="denNgay">Đến ngày <span class="lrf-req">*</span></label>
                        <input type="date" id="denNgay" name="denNgay" class="lrf-input" required min="<?= date('Y-m-d') ?>">
                        <div class="lrf-err" id="err-den">Ngày kết thúc không hợp lệ.</div>
                    </div>

                    <div class="lrf-field">
                        <label class="lrf-label" style="visibility:hidden;">Thời gian</label>
                        <div class="lrf-duration-box" id="durationBadge">
                            <span class="lrf-duration-title">Thời gian</span>
                            <span class="lrf-duration-val"><span id="durationNum">—</span> ngày</span>
                        </div>
                    </div>
                </div>

                <!-- Weekend Warning -->
                <div class="lrf-weekend-warning" id="weekendWarning"></div>

                <!-- Overlap Warning -->
                <div class="lrf-weekend-warning" id="overlapWarning"></div>

                <!-- Row 2: Lý do -->
                <div class="lrf-form-row-2">
                    <div class="lrf-field">
                        <label class="lrf-label" for="lyDo">Lý do nghỉ phép <span class="lrf-req">*</span></label>
                        <input type="text" id="lyDo" name="lyDo" class="lrf-input" placeholder="Sốt cảm cúm cần đi khám bác sĩ và nghỉ ngơi điều trị" required>
                        <div class="lrf-err" id="err-lydo">Vui lòng nhập lý do.</div>
                    </div>
                </div>

                <!-- Row 3: Minh chứng + Nút gửi -->
                <div class="lrf-form-row-3">
                    <label class="lrf-label">Minh chứng (nếu có)</label>
                    <div class="lrf-action-group">
                        <div class="lrf-file-box" onclick="document.getElementById('tepMinhChung').click()">
                            <div class="lrf-file-info">
                                <i class="fa-solid fa-paperclip"></i>
                                <span id="fileText">Chọn file minh chứng...</span>
                            </div>
                            <span class="lrf-file-btn" id="fileBtnTxt">Chọn file...</span>
                            <input type="file" name="tepMinhChung" id="tepMinhChung" accept=".jpg,.jpeg,.png,.pdf">
                        </div>

                        <button type="submit" class="lrf-submit-btn" id="submitBtn">
                            <span id="submitText"><i class="fa-solid fa-paper-plane"></i> Gửi Đơn Xin Phép</span>
                            <span id="submitLoading" style="display:none;align-items:center;gap:8px;">
                                <span class="lrf-spinner"></span> Đang gửi...
                            </span>
                        </button>
                    </div>
                </div>

                <div id="leaveTypeNote" class="lrf-type-note"></div>
            </form>
        </div>

        <!-- 3. LỊCH SỬ ĐƠN NGHỈ PHÉP -->
        <div class="lrf-history-card">
            <div class="lrf-history-header">
                <div class="lrf-section-title" style="margin:0;">
                    <i class="fa-regular fa-clock"></i> Lịch Sử Đơn Nghỉ Phép
                </div>
                <div class="lrf-filter-tabs">
                    <?php
                        $cntAll = count($myRequests);
                        $cntApp = count(array_filter($myRequests, function($r){ return ($r['trangThai']??'')==='approved'; }));
                        $cntPen = count(array_filter($myRequests, function($r){ return ($r['trangThai']??'')==='pending'; }));
                        $cntRej = count(array_filter($myRequests, function($r){ return ($r['trangThai']??'')==='rejected'; }));
                    ?>
                    <div class="lrf-tab active" data-filter="all">Tất cả (<?= $cntAll ?>)</div>
                    <div class="lrf-tab" data-filter="approved">Đã duyệt (<?= $cntApp ?>)</div>
                    <div class="lrf-tab" data-filter="pending">Chờ duyệt (<?= $cntPen ?>)</div>
                    <div class="lrf-tab" data-filter="rejected">Từ chối (<?= $cntRej ?>)</div>
                </div>
            </div>

            <?php if (!empty($myRequests)): ?>
            <div class="lrf-hist-grid" id="histGrid">
                <?php foreach ($myRequests as $row): ?>
                <?php
                    $st      = $row['trangThai'] ?? 'pending';
                    $lt      = $row['loaiNghiPhep'] ?? 'personal';
                    $isPaidR = in_array($lt, $paidTypes, true);
                    $days    = calcLeaveDays2($row['tuNgay'], $row['denNgay']);
                    $fileUrl = $row['tepMinhChung'] ?? '';
                    if ($fileUrl && strpos($fileUrl, 'uploads/') !== 0) {
                        $fileUrl = 'uploads/leave_evidence/' . $fileUrl;
                    }
                    $stLabel = $statusLabels[$st] ?? 'Chờ duyệt';
                ?>
                <div class="lrf-hist-item status-<?= $st ?>"
                     data-status="<?= $st ?>"
                     onclick="openLeaveModal(<?= (int)$row['id'] ?>)">

                    <!-- Card Header -->
                    <div class="lrf-hi-top">
                        <div class="lrf-hi-title"><?= htmlspecialchars($leaveTypeLabels[$lt] ?? $lt) ?></div>
                        <span class="lrf-stat-badge <?= $st ?>">
                            <span class="lrf-dot">●</span> <?= $stLabel ?>
                        </span>
                    </div>

                    <!-- Dates & Paid Status -->
                    <div class="lrf-hi-dates">
                        <i class="fa-regular fa-calendar"></i>
                        <span class="lrf-hi-dates-txt">
                            <?= date('d/m/Y', strtotime($row['tuNgay'])) ?> — <?= date('d/m/Y', strtotime($row['denNgay'])) ?> · 
                        </span>
                        <?php if ($st === 'rejected'): ?>
                            <span class="lrf-tag-unpaid"><?= $days ?> ngày</span>
                        <?php elseif ($isPaidR): ?>
                            <span class="lrf-tag-paid"><?= $days ?> ngày (Có lương)</span>
                        <?php else: ?>
                            <span class="lrf-tag-unpaid"><?= $days ?> ngày (Không lương)</span>
                        <?php endif; ?>
                    </div>

                    <!-- Reason box -->
                    <div class="lrf-hi-reason-box">
                        <?php if ($st === 'rejected'): ?>
                            <strong>Lý do xin nghỉ:</strong> <?= htmlspecialchars($row['lyDo'] ?? '') ?>
                        <?php else: ?>
                            <strong>Lý do:</strong> <?= htmlspecialchars($row['lyDo'] ?? '') ?>
                        <?php endif; ?>
                    </div>

                    <!-- Rejection box if rejected -->
                    <?php if ($st === 'rejected'): ?>
                        <div class="lrf-hi-rejection-box">
                            <strong>Lý do từ chối từ Trưởng phòng:</strong> <?= htmlspecialchars($row['ghiChuNS'] ?? 'Chưa có lý do chi tiết từ người duyệt.') ?>
                        </div>
                    <?php endif; ?>

                    <!-- Footer -->
                    <div class="lrf-hi-footer" onclick="event.stopPropagation()">
                        <div>
                            <?php if ($fileUrl): ?>
                                <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" class="lrf-hi-attachment" onclick="event.stopPropagation()">
                                    <i class="fa-solid fa-paperclip"></i> Minh chứng
                                </a>
                            <?php else: ?>
                                <span class="lrf-hi-no-file">Không có minh chứng đính kèm</span>
                            <?php endif; ?>
                        </div>
                        <div class="lrf-hi-time">
                            Gửi lúc: <?= date('d/m/Y H:i', strtotime($row['ngayTao'] ?? 'now')) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="lrf-empty">
                <i class="fa-regular fa-folder-open"></i>
                <p>Chưa có đơn nghỉ phép nào.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Detail Modal -->
        <div id="leaveDetailModal" class="lrf-modal">
            <div class="lrf-modal-content">
                <div class="lrf-modal-header">
                    <h4><i class="fa-solid fa-circle-info" style="color:#2563eb;"></i> Chi tiết đơn nghỉ phép</h4>
                    <button class="lrf-modal-close" onclick="closeLeaveModal()"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="lrf-modal-body" id="leaveModalBody">
                    <div class="lrf-modal-loading"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải...</div>
                </div>
                <div class="lrf-modal-footer">
                    <button class="lrf-submit-btn" style="padding:0 18px;height:38px;box-shadow:none;" onclick="closeLeaveModal()">
                        <i class="fa-solid fa-xmark"></i> Đóng
                    </button>
                </div>
            </div>
        </div>

    </div><!-- End lrf-page -->

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var loaiSel    = document.getElementById('loaiNghiPhep');
    var tuEl       = document.getElementById('tuNgay');
    var denEl      = document.getElementById('denNgay');
    var durNum     = document.getElementById('durationNum');
    var fileInput  = document.getElementById('tepMinhChung');
    var fileText   = document.getElementById('fileText');
    var fileBtnTxt = document.getElementById('fileBtnTxt');
    var submitBtn  = document.getElementById('submitBtn');
    var submitTxt  = document.getElementById('submitText');
    var submitLoad = document.getElementById('submitLoading');
    var noteBox    = document.getElementById('leaveTypeNote');
    var paidTypes  = ['sick', 'personal', 'emergency', 'wedding', 'funeral', 'annual'];

    var weekendWarn = document.getElementById('weekendWarning');
    var overlapWarn = document.getElementById('overlapWarning');
    var dayNames = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
    var hasWeekendError = false;
    var hasOverlapError = false;

    // Danh sách các đơn nghỉ phép đang có của nhân viên (chờ duyệt hoặc đã duyệt)
    var activeLeaveRequests = <?= json_encode(array_values(array_map(function($r) {
        return [
            'id'           => (int)($r['id'] ?? 0),
            'tuNgay'       => $r['tuNgay'] ?? '',
            'denNgay'      => $r['denNgay'] ?? '',
            'trangThai'    => $r['trangThai'] ?? '',
            'loaiNghiPhep' => $r['loaiNghiPhep'] ?? '',
        ];
    }, array_filter($myRequests ?? [], function($r) {
        return in_array($r['trangThai'] ?? '', ['approved', 'pending'], true);
    })))) ?>;

    function calcDays(from, to) {
        if (!from || !to) return 0;
        var diff = Math.round((new Date(to) - new Date(from)) / 86400000) + 1;
        return diff > 0 ? diff : 0;
    }

    function getWeekendDays(from, to) {
        var weekends = [];
        if (!from || !to) return weekends;
        var start = new Date(from);
        var end = new Date(to);
        var cur = new Date(start);
        while (cur <= end) {
            var dow = cur.getDay(); // 0=Sun, 6=Sat
            if (dow === 0 || dow === 6) {
                var dd = String(cur.getDate()).padStart(2, '0');
                var mm = String(cur.getMonth() + 1).padStart(2, '0');
                var yyyy = cur.getFullYear();
                weekends.push({
                    date: dd + '/' + mm + '/' + yyyy,
                    dayName: dayNames[dow]
                });
            }
            cur.setDate(cur.getDate() + 1);
        }
        return weekends;
    }

    function countWorkingDays(from, to) {
        if (!from || !to) return 0;
        var start = new Date(from);
        var end = new Date(to);
        var count = 0;
        var cur = new Date(start);
        while (cur <= end) {
            var dow = cur.getDay();
            if (dow !== 0 && dow !== 6) count++;
            cur.setDate(cur.getDate() + 1);
        }
        return count;
    }

    function checkWeekendWarning() {
        if (!weekendWarn) return;
        var from = tuEl && tuEl.value;
        var to = denEl && denEl.value;
        if (!from || !to) {
            weekendWarn.style.display = 'none';
            hasWeekendError = false;
            return;
        }
        var weekends = getWeekendDays(from, to);
        var totalDays = calcDays(from, to);
        if (weekends.length > 0 && weekends.length >= totalDays) {
            // ALL selected days are weekends - block entirely
            hasWeekendError = true;
            var dateList = weekends.map(function(w) { return w.dayName + ' (' + w.date + ')'; }).join(', ');
            weekendWarn.innerHTML = '<div class="lrf-ww-title"><i class="fa-solid fa-triangle-exclamation"></i> Không thể đăng ký nghỉ phép</div>' +
                '<div>Tất cả các ngày bạn chọn đều rơi vào <strong>ngày nghỉ cuối tuần</strong> (Thứ 7 / Chủ nhật) — không có lịch làm việc.</div>' +
                '<div class="lrf-ww-dates"><i class="fa-regular fa-calendar-xmark"></i> ' + dateList + '</div>' +
                '<div class="lrf-ww-hint"><i class="fa-solid fa-lightbulb"></i> Vui lòng chọn các ngày làm việc trong tuần (Thứ 2 — Thứ 6) để đăng ký nghỉ phép.</div>';
            weekendWarn.style.display = 'block';
        } else if (weekends.length > 0) {
            // Some days are weekends - warn but still allow (weekend days won't be counted)
            hasWeekendError = true;
            var dateList = weekends.map(function(w) { return w.dayName + ' (' + w.date + ')'; }).join(', ');
            weekendWarn.innerHTML = '<div class="lrf-ww-title"><i class="fa-solid fa-triangle-exclamation"></i> Khoảng thời gian chứa ngày cuối tuần</div>' +
                '<div>Khoảng nghỉ phép bạn chọn có chứa <strong>ngày nghỉ cuối tuần</strong> (Thứ 7 / Chủ nhật) — những ngày này không có lịch làm việc nên không thể đăng ký nghỉ phép.</div>' +
                '<div class="lrf-ww-dates"><i class="fa-regular fa-calendar-xmark"></i> Ngày cuối tuần: ' + dateList + '</div>' +
                '<div class="lrf-ww-hint"><i class="fa-solid fa-lightbulb"></i> Vui lòng điều chỉnh lại khoảng thời gian sao cho chỉ bao gồm các ngày làm việc (Thứ 2 — Thứ 6).</div>';
            weekendWarn.style.display = 'block';
        } else {
            hasWeekendError = false;
            weekendWarn.style.display = 'none';
        }
    }

    function checkOverlapWarning() {
        if (!overlapWarn) return;
        var from = tuEl && tuEl.value;
        var to = denEl && denEl.value;
        if (!from || !to) {
            overlapWarn.style.display = 'none';
            hasOverlapError = false;
            return;
        }

        var conflict = null;
        for (var i = 0; i < activeLeaveRequests.length; i++) {
            var req = activeLeaveRequests[i];
            if (!req.tuNgay || !req.denNgay) continue;
            // Overlap: from <= req.denNgay && to >= req.tuNgay
            if (from <= req.denNgay && to >= req.tuNgay) {
                conflict = req;
                break;
            }
        }

        if (conflict) {
            hasOverlapError = true;
            var isApproved = conflict.trangThai === 'approved';
            var stText = isApproved ? 'Đã được duyệt' : 'Đang chờ duyệt';
            var badgeStyle = isApproved 
                ? 'background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;' 
                : 'background:#fef3c7;color:#b45309;border:1px solid #fde68a;';
            
            var cTuParts = conflict.tuNgay.split('-');
            var cDenParts = conflict.denNgay.split('-');
            var cTu = cTuParts.length === 3 ? (cTuParts[2] + '/' + cTuParts[1] + '/' + cTuParts[0]) : conflict.tuNgay;
            var cDen = cDenParts.length === 3 ? (cDenParts[2] + '/' + cDenParts[1] + '/' + cDenParts[0]) : conflict.denNgay;

            overlapWarn.innerHTML = '<div class="lrf-ww-title"><i class="fa-solid fa-calendar-xmark"></i> Trùng lặp ngày nghỉ phép đã đăng ký</div>' +
                '<div>Bạn đã có đơn nghỉ phép <span style="display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:700;' + badgeStyle + '">' + stText + '</span> ' +
                'từ ngày <strong>' + cTu + '</strong> đến ngày <strong>' + cDen + '</strong>.</div>' +
                '<div class="lrf-ww-dates"><i class="fa-solid fa-circle-exclamation"></i> Khoảng thời gian bạn chọn đang bị trùng lặp với đơn nghỉ phép này. Không thể đăng ký trùng lặp.</div>' +
                '<div class="lrf-ww-hint"><i class="fa-solid fa-lightbulb"></i> Vui lòng chọn khoảng thời gian khác hoặc kiểm tra lại lịch sử đơn xin phép bên dưới.</div>';
            overlapWarn.style.display = 'block';
        } else {
            hasOverlapError = false;
            overlapWarn.style.display = 'none';
        }
    }

    function refreshDuration() {
        if (!durNum) return;
        var from = tuEl && tuEl.value;
        var to = denEl && denEl.value;
        var d = calcDays(from, to);
        durNum.textContent = d > 0 ? d : '—';
        checkWeekendWarning();
        checkOverlapWarning();
    }

    function updateNote() {
        if (!noteBox || !loaiSel) return;
        var v = loaiSel.value;
        if (!v) { noteBox.style.display = 'none'; return; }
        var paid = paidTypes.indexOf(v) !== -1;
        noteBox.style.display = 'block';
        if (paid) {
            noteBox.style.background = '#eff6ff';
            noteBox.style.borderColor = '#bfdbfe';
            noteBox.style.color = '#1d4ed8';
            noteBox.innerHTML = '<i class="fa-solid fa-circle-info"></i> Loại nghỉ phép này được tính là <strong>có hưởng lương (CP)</strong> — nhân viên vẫn được tính công ngày nghỉ này trong bảng công HR.';
        } else {
            noteBox.style.background = '#fef3c7';
            noteBox.style.borderColor = '#fcd34d';
            noteBox.style.color = '#92400e';
            noteBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Loại "Khác" được tính là <strong>không hưởng lương (KP)</strong> — ngày nghỉ này sẽ không được tính công.';
        }
    }

    if (tuEl) {
        var onTuChange = function () {
            if (denEl) {
                denEl.min = this.value;
                if (denEl.value && denEl.value < this.value) {
                    denEl.value = this.value;
                }
            }
            refreshDuration();
        };
        tuEl.addEventListener('change', onTuChange);
        tuEl.addEventListener('input', onTuChange);
    }
    if (denEl) {
        denEl.addEventListener('change', refreshDuration);
        denEl.addEventListener('input', refreshDuration);
    }
    if (loaiSel) loaiSel.addEventListener('change', updateNote);

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                var f = this.files[0];
                var sz = (f.size / (1024 * 1024)).toFixed(1);
                if (fileText) {
                    fileText.textContent = f.name + ' (' + sz + ' MB)';
                    fileText.style.color = '#2563eb';
                    fileText.style.fontWeight = '600';
                }
                if (fileBtnTxt) fileBtnTxt.textContent = 'Chọn lại file...';
            } else {
                if (fileText) {
                    fileText.textContent = 'Chọn file minh chứng...';
                    fileText.style.color = '#64748b';
                    fileText.style.fontWeight = '400';
                }
                if (fileBtnTxt) fileBtnTxt.textContent = 'Chọn file...';
            }
        });
    }

    var form = document.getElementById('leaveForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            var ok = true;
            function se(id) { var el = document.getElementById(id); if (el) el.style.display = 'block'; }
            function he(id) { var el = document.getElementById(id); if (el) el.style.display = 'none'; }

            if (!loaiSel || !loaiSel.value) { se('err-loai'); ok = false; } else he('err-loai');
            if (!tuEl || !tuEl.value) { se('err-tu'); ok = false; } else he('err-tu');
            if (!denEl || !denEl.value || (tuEl && tuEl.value && denEl.value < tuEl.value)) { se('err-den'); ok = false; } else he('err-den');
            var lyDo = document.getElementById('lyDo');
            if (!lyDo || !lyDo.value.trim()) { se('err-lydo'); ok = false; } else he('err-lydo');

            // Check weekend validation
            if (hasWeekendError) {
                ok = false;
                if (weekendWarn) {
                    weekendWarn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    weekendWarn.style.animation = 'none';
                    weekendWarn.offsetHeight; // trigger reflow
                    weekendWarn.style.animation = 'lrfShake .4s ease-in-out';
                }
            } else if (hasOverlapError) {
                ok = false;
                if (overlapWarn) {
                    overlapWarn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    overlapWarn.style.animation = 'none';
                    overlapWarn.offsetHeight; // trigger reflow
                    overlapWarn.style.animation = 'lrfShake .4s ease-in-out';
                }
            }

            if (!ok) {
                e.preventDefault();
                return;
            }
            if (submitBtn) submitBtn.disabled = true;
            if (submitTxt) submitTxt.style.display = 'none';
            if (submitLoad) submitLoad.style.display = 'inline-flex';
        });
    }

    var alertEl = document.getElementById('lrfAlert');
    if (alertEl) {
        setTimeout(function () {
            alertEl.style.opacity = '0';
            alertEl.style.transition = 'opacity .3s';
            setTimeout(function () { alertEl.remove(); }, 350);
        }, 5000);
    }

    // Filter tabs
    document.querySelectorAll('.lrf-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.lrf-tab').forEach(function (t) { t.classList.remove('active'); });
            this.classList.add('active');
            var f = this.getAttribute('data-filter');
            document.querySelectorAll('.lrf-hist-item').forEach(function (item) {
                item.style.display = (f === 'all' || item.getAttribute('data-status') === f) ? '' : 'none';
            });
        });
    });
});

window.openLeaveModal = function (id) {
    var modal = document.getElementById('leaveDetailModal');
    var body = document.getElementById('leaveModalBody');
    if (!modal) return;
    modal.classList.add('open');
    body.innerHTML = '<div class="lrf-modal-loading"><i class="fa-solid fa-spinner fa-spin"></i> Đang tải...</div>';
    fetch('index.php?page=get-leave-detail&id=' + id)
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (!res.success) {
                body.innerHTML = '<p style="color:#ef4444;text-align:center;">' + (res.message || 'Lỗi') + '</p>';
                return;
            }
            var d = res.data;
            var stCls = d.trangThai === 'approved' ? 'approved' : d.trangThai === 'rejected' ? 'rejected' : 'pending';
            var stTxt = d.trangThai === 'approved' ? 'Đã duyệt' : d.trangThai === 'rejected' ? 'Từ chối' : 'Chờ duyệt';
            var paidBadge = d.isPaid
                ? '<span style="background:#dbeafe;color:#1e40af;font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px;"><i class="fa-solid fa-circle-check"></i> Có lương (CP)</span>'
                : '<span style="background:#fee2e2;color:#991b1b;font-size:11px;font-weight:700;padding:2px 8px;border-radius:10px;"><i class="fa-solid fa-circle-xmark"></i> Không lương (KP)</span>';
            var html = '';
            html += '<div class="lrf-detail-row"><div class="lrf-detail-label">Loại nghỉ</div><div class="lrf-detail-val">' + (d.leave_type_text || d.loaiNghiPhep) + ' ' + paidBadge + '</div></div>';
            html += '<div class="lrf-detail-row"><div class="lrf-detail-label">Thời gian</div><div class="lrf-detail-val">' + d.from_date_fmt + ' đến ' + d.to_date_fmt + '</div></div>';
            html += '<div class="lrf-detail-row"><div class="lrf-detail-label">Lý do</div><div class="lrf-detail-val">' + (d.lyDo || '').replace(/\n/g, '<br>') + '</div></div>';
            html += '<div class="lrf-detail-row"><div class="lrf-detail-label">Trạng thái</div><div class="lrf-detail-val"><span class="lrf-stat-badge ' + stCls + '"><span class="lrf-dot">●</span> ' + stTxt + '</span></div></div>';
            if (d.approver_name) html += '<div class="lrf-detail-row"><div class="lrf-detail-label">Người duyệt</div><div class="lrf-detail-val">' + d.approver_name + '</div></div>';
            if (d.approved_at_fmt) html += '<div class="lrf-detail-row"><div class="lrf-detail-label">Ngày duyệt</div><div class="lrf-detail-val">' + d.approved_at_fmt + '</div></div>';
            if (d.ghiChuNS) html += '<div class="lrf-detail-row"><div class="lrf-detail-label">Phản hồi</div><div class="lrf-detail-val" style="color:#ef4444;font-weight:600;">' + d.ghiChuNS + '</div></div>';
            html += '<div class="lrf-detail-row" style="border:none"><div class="lrf-detail-label">Ngày gửi</div><div class="lrf-detail-val">' + d.created_at_fmt + '</div></div>';
            body.innerHTML = html;
        })
        .catch(function () {
            body.innerHTML = '<p style="color:#ef4444;text-align:center;">Không thể kết nối máy chủ.</p>';
        });
};

window.closeLeaveModal = function () {
    var m = document.getElementById('leaveDetailModal');
    if (m) m.classList.remove('open');
};

window.addEventListener('click', function (e) {
    var m = document.getElementById('leaveDetailModal');
    if (m && e.target === m) closeLeaveModal();
});
</script>

<?php include 'app/views/layouts/footer.php'; ?>
