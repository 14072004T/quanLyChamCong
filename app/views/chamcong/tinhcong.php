<?php
if (!isset($_SESSION['user'])) { header('Location: index.php?page=login'); exit(); }
if (($_SESSION['role'] ?? '') !== 'hr') { header('Location: index.php?page=home'); exit(); }

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
$monthlyApproval = $monthlyApproval ?? null;
$approvalHistory = $approvalHistory ?? [];
$employeeKeyword = $employeeKeyword ?? '';
$selectedMonth = $selectedMonth ?? date('Y-m');
$summaryEmployees = count($salaryRows ?? []);
$summaryWorkDays = 0;
$summaryWorkHours = 0;
$summaryOtHours = 0;
foreach (($salaryRows ?? []) as $summaryRow) {
    $summaryWorkDays += (float)($summaryRow['work_days'] ?? 0);
    $summaryWorkHours += (float)($summaryRow['work_hours'] ?? 0);
    $summaryOtHours += (float)($summaryRow['overtime_hours'] ?? 0);
}
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<style>
.payroll-toolbar {
    padding: 24px 28px;
    background:
        radial-gradient(circle at top right, rgba(59,130,246,0.12), transparent 28%),
        linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.payroll-toolbar-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    margin-bottom: 18px;
}
.payroll-toolbar-head p {
    margin: 0;
    color: #64748b;
    max-width: 720px;
}
.payroll-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}
.payroll-metric {
    padding: 16px 18px;
    border: 1px solid #dbe7f5;
    border-radius: 18px;
    background: rgba(255,255,255,0.92);
    box-shadow: 0 12px 32px rgba(15,23,42,0.06);
}
.payroll-metric-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.payroll-metric strong {
    font-size: 1.6rem;
    color: #0f172a;
}
.payroll-filter-grid {
    display: grid;
    grid-template-columns: minmax(220px, 280px) minmax(190px, 235px) auto;
    gap: 16px;
    align-items: end;
}
.payroll-filter-grid .form-group {
    margin-bottom: 0;
}
.payroll-filter-actions {
    display: flex;
    align-items: flex-end;
}
.payroll-filter-actions .btn {
    min-width: 150px;
}
.payroll-board {
    padding: 0;
    overflow: hidden;
}
.payroll-board-head {
    padding: 22px 24px 12px;
    border-bottom: 1px solid #dbe7f5;
    background: linear-gradient(180deg, rgba(248,250,252,0.95) 0%, rgba(255,255,255,0.95) 100%);
}
.payroll-board-head h3 {
    margin: 0 0 6px;
}
.payroll-board-head p {
    margin: 0;
    color: #64748b;
}
.payroll-board-body {
    padding: 18px 24px 24px;
}
.payroll-action-panel {
    padding: 22px 24px;
    border: 1px solid #dbe7f5;
    border-radius: 22px;
    background: linear-gradient(135deg, #f8fbff 0%, #ffffff 65%);
    box-shadow: 0 18px 34px rgba(15,23,42,0.07);
}
.payroll-action-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
}
.payroll-action-copy h3 {
    margin: 0 0 8px;
    color: #0f172a;
}
.payroll-action-copy p {
    margin: 0;
    color: #64748b;
    max-width: 720px;
}
.payroll-action-buttons {
    display: flex;
    justify-content: flex-end;
}
.payroll-action-buttons .btn {
    min-width: 170px;
    justify-content: center;
}
.approval-history-panel {
    padding: 0;
    overflow: hidden;
}
.approval-history-head {
    padding: 22px 24px 12px;
    border-bottom: 1px solid #dbe7f5;
    background: linear-gradient(180deg, rgba(248,250,252,0.95) 0%, rgba(255,255,255,0.95) 100%);
}
.approval-history-head h3 {
    margin: 0 0 6px;
}
.approval-history-head p {
    margin: 0;
    color: #64748b;
}
.approval-history-body {
    padding: 18px 24px 24px;
}
.history-detail-trigger {
    border: none;
    background: transparent;
    color: #2563eb;
    font-weight: 700;
    cursor: pointer;
    padding: 0;
}
.history-detail-trigger:hover {
    text-decoration: underline;
}
.payroll-detail-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.55);
    z-index: 1200;
    padding: 24px;
}
.payroll-detail-modal.open {
    display: flex;
}
.payroll-detail-card {
    width: min(1100px, 100%);
    max-height: calc(100vh - 48px);
    overflow: auto;
    border-radius: 22px;
    background: #ffffff;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.24);
}
.payroll-detail-head {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 24px 26px 18px;
    border-bottom: 1px solid #dbe7f5;
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}
.payroll-detail-head h3 {
    margin: 0 0 8px;
    color: #0f172a;
}
.payroll-detail-head p {
    margin: 0;
    color: #64748b;
}
.payroll-detail-close {
    border: none;
    background: #e2e8f0;
    color: #0f172a;
    width: 38px;
    height: 38px;
    border-radius: 999px;
    cursor: pointer;
    font-size: 18px;
}
.payroll-detail-body {
    padding: 22px 26px 26px;
}
.payroll-detail-summary,
.payroll-detail-meta {
    display: grid;
    gap: 14px;
    margin-bottom: 18px;
}
.payroll-detail-summary {
    grid-template-columns: repeat(4, minmax(0, 1fr));
}
.payroll-detail-meta {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}
.payroll-summary-item,
.payroll-meta-box {
    padding: 16px;
    border: 1px solid #dbe7f5;
    border-radius: 18px;
    background: #f8fbff;
}
.payroll-summary-item span,
.payroll-meta-box strong {
    display: block;
    margin-bottom: 6px;
    color: #64748b;
}
.payroll-summary-item strong {
    color: #0f172a;
    font-size: 1.35rem;
}
@media (max-width: 1100px) {
    .payroll-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .payroll-filter-grid {
        grid-template-columns: 1fr 1fr;
    }
    .payroll-filter-actions {
        grid-column: 1 / -1;
    }
    .payroll-action-row {
        flex-direction: column;
        align-items: stretch;
    }
    .payroll-detail-summary,
    .payroll-detail-meta {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 720px) {
    .payroll-toolbar,
    .payroll-board-head,
    .payroll-board-body,
    .payroll-action-panel {
        padding-left: 18px;
        padding-right: 18px;
    }
    .payroll-metrics,
    .payroll-filter-grid {
        grid-template-columns: 1fr;
    }
    .payroll-toolbar-head {
        flex-direction: column;
    }
    .payroll-detail-summary,
    .payroll-detail-meta {
        grid-template-columns: 1fr;
    }
}
</style>
<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">

        <div class="panel">
            <h2 style="border:none;padding:0;margin:0 0 6px;">TÍNH CÔNG & BÁO CÁO</h2>
            <p style="color:#64748b;margin:0;">Theo dõi dữ liệu công theo tháng, lọc nhanh theo nhân viên và rà soát tổng công trước khi gửi cho quản lý phê duyệt.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="panel payroll-toolbar">
                <div class="payroll-metrics">
                    <div class="payroll-metric">
                        <span class="payroll-metric-label">Kỳ công</span>
                        <strong id="metric-month"><?= htmlspecialchars($selectedMonth) ?></strong>
                    </div>
                    <div class="payroll-metric">
                        <span class="payroll-metric-label">Nhân sự hiển thị</span>
                        <strong id="metric-employees"><?= (int)$summaryEmployees ?></strong>
                    </div>
                    <div class="payroll-metric">
                        <span class="payroll-metric-label">Tổng ngày công</span>
                        <strong id="metric-work-days"><?= number_format($summaryWorkDays, 0) ?></strong>
                    </div>
                    <div class="payroll-metric">
                        <span class="payroll-metric-label">Tổng OT hợp lệ</span>
                        <strong id="metric-ot"><?= number_format($summaryOtHours, 2) ?>h</strong>
                    </div>
                </div>
                <form id="payroll-filter-form" class="payroll-filter-grid">
                    <input type="hidden" name="page" value="tinh-cong">
                    <div class="form-group">
                        <label>Nhân viên</label>
                        <select name="employee_q" id="employee-search-input" style="padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 4px; width: 100%;">
                            <option value="Tất cả">Hiển thị tất cả nhân viên</option>
                            <?php foreach ($filterEmployees as $emp): ?>
                                <?php $optVal = $emp['hoTen']; ?>
                                <option value="<?= htmlspecialchars($optVal) ?>" <?= $employeeKeyword === $optVal ? 'selected' : '' ?>>
                                    <?= htmlspecialchars("#{$emp['maND']} - {$emp['hoTen']} - " . ($emp['phongBan'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tháng</label>
                        <input type="month" name="month" value="<?= htmlspecialchars($selectedMonth) ?>" required>
                    </div>
                    <div class="payroll-filter-actions">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-calculator"></i> TÍNH CÔNG</button>
                    </div>
                </form>
            </div>

            <div class="panel payroll-board">
                <div class="payroll-board-head">
                    <div class="sub-tabs">
                        <button class="sub-tab active" data-tab="tab-tinhcong"><i class="fas fa-calculator"></i> Tính toán Công & OT</button>
                        <button class="sub-tab" data-tab="tab-bangchamcong"><i class="fas fa-table-list"></i> Bảng Chấm Công</button>
                    </div>
                    <p>Dữ liệu dưới đây phản ánh bảng công của kỳ đã chọn. Bạn nên kiểm tra OT, ngày công và số giờ làm trước khi gửi phê duyệt.</p>
                </div>
                <div class="payroll-board-body">
                    <div class="tab-content active" id="tab-tinhcong">
                        <h3>BẢNG CÔNG CHI TIẾT - THÁNG <span id="grid-month-label"><?= htmlspecialchars($selectedMonth) ?></span></h3>
                        <div class="attendance-grid-wrapper">
                            <table class="attendance-grid" id="attendance-detail-grid">
                                <thead id="attendance-grid-head">
                                    <tr>
                                        <th>Nhân viên<br>Name/Dept</th>
                                        <?php for ($d = 1; $d <= 31; $d++): ?>
                                            <th><?= $d ?></th>
                                        <?php endfor; ?>
                                        <th>TỔNG CÔNG</th>
                                        <th>TỔNG OT HỢP LỆ</th>
                                    </tr>
                                </thead>
                                <tbody id="attendance-grid-body">
                                    <?php if (!empty($salaryRows)): ?>
                                        <?php foreach ($salaryRows as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['hoTen'] ?? '') ?></td>
                                                <?php 
                                                $yearMonth = $selectedMonth;
                                                $lastDay = (int)date('t', strtotime($yearMonth . '-01'));
                                                for ($d = 1; $d <= 31; $d++): 
                                                    if ($d > $lastDay) {
                                                        echo '<td class="day-n"></td>';
                                                    } else {
                                                        $dateStr = sprintf('%s-%02d', $yearMonth, $d);
                                                        $dayData = $row['daily_breakdown'][$dateStr] ?? null;
                                                        $workValue = $dayData ? (float)($dayData['work_value'] ?? 0) : 0;
                                                        $valStr = $workValue > 0 ? ($workValue == 1.0 ? '1.0' : '0.5') : '';
                                                        $cls = $workValue > 0 ? 'day-val' : 'day-n';
                                                        echo '<td class="' . $cls . '">' . htmlspecialchars($valStr) . '</td>';
                                                    }
                                                endfor; 
                                                ?>
                                                <td class="col-total"><?= htmlspecialchars((string)($row['work_days'] ?? 0)) ?></td>
                                                <td class="col-total"><?= htmlspecialchars((string)($row['overtime_hours'] ?? 0)) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="33" class="empty-state">Không có dữ liệu chấm công.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-content" id="tab-bangchamcong">
                        <h3>Tổng toán tháng <span id="list-month-label"><?= htmlspecialchars($selectedMonth) ?></span></h3>
                        <table class="table" id="payroll-summary-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Mã NV</th>
                                    <th>Họ và Tên</th>
                                    <th>Phòng ban</th>
                                    <th>Ngày Công</th>
                                    <th>Giờ Làm</th>
                                    <th>Giờ OT</th>
                                </tr>
                            </thead>
                            <tbody id="payroll-table-body">
                                <?php if (!empty($salaryRows)): ?>
                                    <?php $idx = 1; foreach ($salaryRows as $row): ?>
                                        <tr>
                                            <td><?= $idx++ ?></td>
                                            <td><?= (int)($row['maND'] ?? 0) ?></td>
                                            <td><?= htmlspecialchars($row['hoTen'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row['phongBan'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars((string)($row['work_days'] ?? 0)) ?></td>
                                            <td><?= htmlspecialchars((string)($row['work_hours'] ?? 0)) ?></td>
                                            <td><?= htmlspecialchars((string)($row['overtime_hours'] ?? 0)) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="empty-state">Không có dữ liệu.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        
                        <div class="payroll-action-panel" style="margin-top: 24px;">
                            <div class="payroll-action-row">
                                <div class="payroll-action-copy">
                                    <h3>Hoàn tất rà soát bảng công</h3>
                                    <p id="approval-status">
                                        <?php if ($monthlyApproval): ?>
                                            Trạng thái gửi duyệt: <span class="status-badge status-<?= strtolower($monthlyApproval['status'] ?? 'draft') ?>"><?= htmlspecialchars($monthlyApproval['status'] ?? 'draft') ?></span>
                                        <?php else: ?>
                                            Chưa gửi phê duyệt cho tháng này. Sau khi xác nhận, bảng tính công sẽ được chuyển đến quản lý để xét duyệt.
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="payroll-action-buttons">
                                    <button type="button" class="btn btn-success btn-sm" id="submit-payroll-btn"><i class="fas fa-paper-plane"></i> GỬI PHÊ DUYỆT</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel approval-history-panel">
                <div class="approval-history-head">
                    <h3>Lịch sử gửi bảng công</h3>
                    <p>Lưu lại các kỳ HR đã gửi và trạng thái manager xử lý để theo dõi lại khi cần.</p>
                </div>
                <div class="approval-history-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kỳ công</th>
                                <th>Trạng thái</th>
                                <th>Ngày gửi</th>
                                <th>Ngày xử lý</th>
                                <th>Manager</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody id="approval-history-body">
                            <?php if (!empty($approvalHistory)): ?>
                                <?php foreach ($approvalHistory as $row): ?>
                                    <?php
                                    $status = strtolower((string)($row['status'] ?? 'submitted'));
                                    $statusClass = $status === 'approved' ? 'status-approved' : ($status === 'rejected' ? 'status-rejected' : 'status-pending');
                                    $statusLabel = $status === 'approved' ? 'Đã duyệt' : ($status === 'rejected' ? 'Đã trả về' : 'Chờ duyệt');
                                    ?>
                                    <tr>
                                        <td>
                                            <button type="button" class="history-detail-trigger js-history-detail" data-id="<?= (int)($row['id'] ?? 0) ?>">
                                                <?= htmlspecialchars((string)($row['month_key'] ?? '')) ?>
                                            </button>
                                        </td>
                                        <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                                        <td><?= htmlspecialchars((string)($row['submitted_at'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['approved_at'] ?? '-')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['approver_name'] ?? '-')) ?></td>
                                        <td><?= htmlspecialchars((string)($row['note'] ?? '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="empty-state">Chưa có lịch sử gửi bảng công.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="payroll-detail-modal" id="payrollDetailModal" aria-hidden="true">
    <div class="payroll-detail-card">
        <div class="payroll-detail-head">
            <div>
                <h3 id="payroll-detail-title">Chi tiết kỳ công</h3>
                <p id="payroll-detail-subtitle">Đang tải dữ liệu...</p>
            </div>
            <button type="button" class="payroll-detail-close" id="payroll-detail-close" aria-label="Đóng">×</button>
        </div>
        <div class="payroll-detail-body">
            <div class="payroll-detail-summary" id="payroll-detail-summary"></div>
            <div class="payroll-detail-meta" id="payroll-detail-meta"></div>
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
                    <tbody id="payroll-detail-grid-body">
                        <tr><td colspan="6" class="empty-state">Đang tải dữ liệu...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sub-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.sub-tab').forEach(function (t) { t.classList.remove('active'); });
            document.querySelectorAll('.tab-content').forEach(function (c) { c.classList.remove('active'); });
            tab.classList.add('active');
            var target = document.getElementById(tab.getAttribute('data-tab'));
            if (target) target.classList.add('active');
        });
    });

    var filterForm = document.getElementById('payroll-filter-form');
    var employeeInput = document.getElementById('employee-search-input');
    var employeeSuggestions = document.getElementById('employee-suggestions');
    var submitBtn = document.getElementById('submit-payroll-btn');
    var gridBody = document.getElementById('attendance-grid-body');
    var tableBody = document.getElementById('payroll-table-body');
    var approvalStatus = document.getElementById('approval-status');
    var approvalHistoryBody = document.getElementById('approval-history-body');
    var detailModal = document.getElementById('payrollDetailModal');
    var detailTitle = document.getElementById('payroll-detail-title');
    var detailSubtitle = document.getElementById('payroll-detail-subtitle');
    var detailSummary = document.getElementById('payroll-detail-summary');
    var detailMeta = document.getElementById('payroll-detail-meta');
    var detailGridBody = document.getElementById('payroll-detail-grid-body');
    var detailCloseBtn = document.getElementById('payroll-detail-close');

    function escapeHtml(val) {
        return String(val ?? '').replace(/[&<>\"]/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
        });
    }

    function currentMonth() {
        return filterForm.querySelector('[name="month"]').value;
    }

    function currentEmployeeQuery() {
        var val = employeeInput ? employeeInput.value.trim() : '';
        return (val === 'Tất cả') ? '' : val;
    }

    function formatDateTime(value) {
        if (!value) return '-';
        return String(value).replace('T', ' ');
    }

    function getApprovalStatusMeta(status) {
        var normalized = String(status || 'submitted').toLowerCase();
        if (normalized === 'approved') {
            return { label: 'Đã duyệt', cls: 'status-approved' };
        }
        if (normalized === 'rejected') {
            return { label: 'Đã trả về', cls: 'status-rejected' };
        }
        return { label: 'Chờ duyệt', cls: 'status-pending' };
    }

    function openDetailModal() {
        if (!detailModal) return;
        detailModal.classList.add('open');
        detailModal.setAttribute('aria-hidden', 'false');
    }

    function closeDetailModal() {
        if (!detailModal) return;
        detailModal.classList.remove('open');
        detailModal.setAttribute('aria-hidden', 'true');
    }

    // Native select dropdown handles options now, no need for AJAX suggestions

    function renderGridRows(rows) {
        if (!rows.length) {
            gridBody.innerHTML = '<tr><td colspan="33" class="empty-state">Không có dữ liệu chấm công.</td></tr>';
            return;
        }
        gridBody.innerHTML = rows.map(function (row) {
            var cells = '<td>' + escapeHtml(row.hoTen) + '</td>';
            var yearMonth = currentMonth();
            var dt = new Date(yearMonth + '-01T00:00:00');
            var lastDay = new Date(dt.getFullYear(), dt.getMonth() + 1, 0).getDate();

            for (var d = 1; d <= 31; d++) {
                if (d > lastDay) {
                    cells += '<td class="day-n"></td>';
                } else {
                    var dateStr = yearMonth + '-' + (d < 10 ? '0' + d : d);
                    var dayData = row.daily_breakdown ? row.daily_breakdown[dateStr] : null;
                    var workValue = dayData ? Number(dayData.work_value || 0) : 0;
                    var valStr = workValue > 0 ? (workValue === 1.0 ? '1.0' : '0.5') : '';
                    var cls = workValue > 0 ? 'day-val' : 'day-n';
                    cells += '<td class="' + cls + '">' + valStr + '</td>';
                }
            }
            cells += '<td class="col-total">' + Number(row.work_days || 0) + '</td>';
            cells += '<td class="col-total">' + Number(row.overtime_hours || 0) + '</td>';
            return '<tr>' + cells + '</tr>';
        }).join('');
    }

    function renderTableRows(rows) {
        if (!rows.length) {
            tableBody.innerHTML = '<tr><td colspan="7" class="empty-state">Không có dữ liệu.</td></tr>';
            return;
        }
        tableBody.innerHTML = rows.map(function (row, i) {
            return '<tr>' +
                '<td>' + (i + 1) + '</td>' +
                '<td>' + Number(row.maND || 0) + '</td>' +
                '<td>' + escapeHtml(row.hoTen) + '</td>' +
                '<td>' + escapeHtml(row.phongBan || '-') + '</td>' +
                '<td>' + Number(row.work_days || 0) + '</td>' +
                '<td>' + Number(row.work_hours || 0) + '</td>' +
                '<td>' + Number(row.overtime_hours || 0) + '</td>' +
                '</tr>';
        }).join('');
    }

    function renderApprovalHistory(rows) {
        if (!approvalHistoryBody) return;
        if (!rows.length) {
            approvalHistoryBody.innerHTML = '<tr><td colspan="6" class="empty-state">Chưa có lịch sử gửi bảng công.</td></tr>';
            return;
        }

        approvalHistoryBody.innerHTML = rows.map(function (row) {
            var meta = getApprovalStatusMeta(row.status);
            return '<tr>' +
                '<td><button type="button" class="history-detail-trigger js-history-detail" data-id="' + Number(row.id || 0) + '">' + escapeHtml(row.month_key || '') + '</button></td>' +
                '<td><span class="status-badge ' + meta.cls + '">' + escapeHtml(meta.label) + '</span></td>' +
                '<td>' + escapeHtml(formatDateTime(row.submitted_at)) + '</td>' +
                '<td>' + escapeHtml(formatDateTime(row.approved_at)) + '</td>' +
                '<td>' + escapeHtml(row.approver_name || '-') + '</td>' +
                '<td>' + escapeHtml(row.note || '-') + '</td>' +
                '</tr>';
        }).join('');
    }

    function loadApprovalDetail(approvalId) {
        if (!approvalId) return;
        detailTitle.textContent = 'Chi tiết kỳ công';
        detailSubtitle.textContent = 'Đang tải dữ liệu bảng công...';
        detailSummary.innerHTML = '';
        detailMeta.innerHTML = '';
        detailGridBody.innerHTML = '<tr><td colspan="6" class="empty-state">Đang tải dữ liệu...</td></tr>';
        openDetailModal();

        fetch('index.php?page=hr-api-approval-detail&approval_id=' + encodeURIComponent(approvalId), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (!json.success) {
                detailGridBody.innerHTML = '<tr><td colspan="6" class="empty-state">' + escapeHtml(json.message || 'Không thể tải chi tiết.') + '</td></tr>';
                return;
            }

            var detail = json.data || {};
            var approval = detail.approval || {};
            var summary = detail.summary || {};
            var rows = detail.rows || [];
            var statusMeta = getApprovalStatusMeta(approval.status);

            detailTitle.textContent = 'Chi tiết bảng công kỳ ' + (approval.month_key || '');
            detailSubtitle.textContent = 'HR gửi: ' + escapeHtml(approval.hr_name || 'Chưa xác định') + ' | Ngày gửi: ' + escapeHtml(formatDateTime(approval.submitted_at));
            detailSummary.innerHTML = [
                { label: 'Nhân sự', value: Number(summary.employees || 0) },
                { label: 'Tổng ngày công', value: Number(summary.total_work_days || 0).toLocaleString() },
                { label: 'Tổng giờ làm', value: Number(summary.total_work_hours || 0).toLocaleString() },
                { label: 'Tổng OT', value: Number(summary.total_overtime_hours || 0).toLocaleString() + 'h' }
            ].map(function (item) {
                return '<div class="payroll-summary-item"><span>' + escapeHtml(item.label) + '</span><strong>' + escapeHtml(item.value) + '</strong></div>';
            }).join('');

            detailMeta.innerHTML = [
                { label: 'Trạng thái', value: statusMeta.label },
                { label: 'Manager', value: approval.approver_name || 'Chưa xử lý' },
                { label: 'Ghi chú', value: approval.note || 'Không có ghi chú' }
            ].map(function (item) {
                return '<div class="payroll-meta-box"><strong>' + escapeHtml(item.label) + '</strong><div>' + escapeHtml(item.value) + '</div></div>';
            }).join('');

            if (!rows.length) {
                detailGridBody.innerHTML = '<tr><td colspan="6" class="empty-state">Không có dữ liệu chi tiết.</td></tr>';
                return;
            }

            detailGridBody.innerHTML = rows.map(function (row, index) {
                return '<tr>' +
                    '<td>' + (index + 1) + '</td>' +
                    '<td>' + escapeHtml(row.hoTen || '') + '</td>' +
                    '<td>' + escapeHtml(row.phongBan || '-') + '</td>' +
                    '<td>' + Number(row.work_days || 0) + '</td>' +
                    '<td>' + Number(row.work_hours || 0).toLocaleString() + '</td>' +
                    '<td>' + Number(row.overtime_hours || 0).toLocaleString() + '</td>' +
                    '</tr>';
            }).join('');
        })
        .catch(function () {
            detailGridBody.innerHTML = '<tr><td colspan="6" class="empty-state">Lỗi tải dữ liệu chi tiết.</td></tr>';
        });
    }

    function loadPayroll() {
        var params = new URLSearchParams({
            page: 'hr-api-payroll',
            month: currentMonth()
        });
        var employeeQuery = currentEmployeeQuery();

        if (employeeQuery) {
            params.set('employee_q', employeeQuery);
        }

        document.getElementById('grid-month-label').textContent = currentMonth();
        document.getElementById('list-month-label').textContent = currentMonth();

        fetch('index.php?' + params.toString(), {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            if (!json.success) { alert(json.message || 'Lỗi'); return; }
            renderGridRows(json.data || []);
            renderTableRows(json.data || []);
            
            // Update metrics
            if (json.summary) {
                var monthEl = document.getElementById('metric-month');
                var empEl = document.getElementById('metric-employees');
                var daysEl = document.getElementById('metric-work-days');
                var otEl = document.getElementById('metric-ot');
                
                if (monthEl) monthEl.textContent = currentMonth();
                if (empEl) empEl.textContent = Number(json.summary.employees || 0);
                if (daysEl) daysEl.textContent = Number(json.summary.total_work_days || 0).toLocaleString();
                if (otEl) otEl.textContent = Number(json.summary.total_overtime_hours || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + 'h';
            }
            var approval = json.approval;
            if (approval) {
                var approvalMeta = getApprovalStatusMeta(approval.status);
                var approverText = approval.approver_name ? (' | Manager: ' + approval.approver_name) : '';
                approvalStatus.innerHTML = 'Trạng thái gửi duyệt: <span class="status-badge ' + approvalMeta.cls + '">' + escapeHtml(approvalMeta.label) + '</span>' + escapeHtml(approverText);
            } else {
                approvalStatus.textContent = 'Chưa gửi phê duyệt cho tháng này.';
            }
            renderApprovalHistory(json.approvalHistory || []);
        })
        .catch(function () { alert('Có lỗi khi tải dữ liệu bảng công.'); });
    }

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        loadPayroll();
    });

    if (employeeInput) {
        employeeInput.addEventListener('change', function () {
            loadPayroll();
        });
    }

    submitBtn.addEventListener('click', function () {
        if (!window.confirm('Bạn có chắc muốn gửi bảng tính công đến quản lý hay không?')) {
            return;
        }
        var form = new FormData();
        form.append('month_key', currentMonth());
        fetch('index.php?page=hr-api-payroll-submit', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        })
        .then(function (r) { return r.json(); })
        .then(function (json) {
            alert(json.message || 'Hoàn tất');
            if (json.success) loadPayroll();
        })
        .catch(function () { alert('Lỗi gửi bảng công.'); });
    });

    document.addEventListener('click', function (e) {
        var detailBtn = e.target.closest('.js-history-detail');
        if (detailBtn) {
            loadApprovalDetail(detailBtn.getAttribute('data-id'));
        }
    });

    if (detailCloseBtn) {
        detailCloseBtn.addEventListener('click', closeDetailModal);
    }

    if (detailModal) {
        detailModal.addEventListener('click', function (e) {
            if (e.target === detailModal) {
                closeDetailModal();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeDetailModal();
        }
    });

    loadEmployeeSuggestions(currentEmployeeQuery());
});
</script>
<?php include 'app/views/layouts/footer.php'; ?>
