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
$exportFromDate = $selectedMonth . '-01';
$exportToDate = date('Y-m-t', strtotime($exportFromDate));
$exportSummary = null;
foreach ($approvalHistory as $row) {
    if (($row['thangNam'] ?? '') === $selectedMonth) {
        $exportSummary = $row;
        break;
    }
}
$canExport = $exportSummary && (int)($exportSummary['pending'] ?? 0) === 0 && (int)($exportSummary['total'] ?? 0) > 0;
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
.payroll-hanhDong-panel {
    padding: 22px 24px;
    border: 1px solid #dbe7f5;
    border-radius: 22px;
    background: linear-gradient(135deg, #f8fbff 0%, #ffffff 65%);
    box-shadow: 0 18px 34px rgba(15,23,42,0.07);
}
.payroll-hanhDong-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
}
.payroll-hanhDong-copy h3 {
    margin: 0 0 8px;
    color: #0f172a;
}
.payroll-hanhDong-copy p {
    margin: 0;
    color: #64748b;
    max-width: 720px;
}
.payroll-hanhDong-buttons {
    display: flex;
    justify-content: flex-end;
}
.payroll-hanhDong-buttons .btn {
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
.scan-history-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 14px;
    margin-top: 16px;
}
.scan-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow: hidden;
    text-align: center;
}
.scan-card .scan-thumb {
    width: 100%;
    aspect-ratio: 1 / 1;
    object-fit: cover;
    cursor: zoom-in;
    display: block;
    background: #e2e8f0;
}
.scan-card .scan-meta {
    padding: 8px 10px 10px;
}
.scan-card .scan-name {
    font-weight: 700;
    font-size: .85em;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.scan-card .scan-time {
    font-size: .78em;
    color: #64748b;
}
.scan-page-btn {
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #1e293b;
    border-radius: 8px;
    padding: 6px 12px;
    cursor: pointer;
    font-size: .85em;
}
.scan-page-btn:disabled {
    opacity: .45;
    cursor: not-allowed;
}
.scan-page-btn.active {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
}
.scan-image-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.85);
    z-index: 1300;
    padding: 24px;
}
.scan-image-modal.open {
    display: flex;
}
.scan-image-modal img {
    max-width: 100%;
    max-height: 100%;
    border-radius: 12px;
    box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
}
.scan-image-modal .scan-image-close {
    position: absolute;
    top: 24px;
    right: 32px;
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    font-size: 22px;
    cursor: pointer;
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
    .payroll-hanhDong-row {
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
    .payroll-hanhDong-panel {
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
            <p style="color:#64748b;margin:0;">Theo dõi dữ liệu công theo tháng, lọc nhanh theo nhân viên và rà soát tổng công trước khi gửi đến nhân viên xác nhận.</p>
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
                </form>
            </div>

            <form id="payroll-export-form" method="POST" action="index.php?page=xuat-bao-cao" style="display:none">
                <input type="hidden" name="tuNgay" id="export-from-date" value="<?= htmlspecialchars($exportFromDate) ?>">
                <input type="hidden" name="denNgay" id="export-to-date" value="<?= htmlspecialchars($exportToDate) ?>">
                <input type="hidden" name="phongBan" value="">
                <input type="hidden" name="format" value="excel">
                <input type="hidden" name="export" value="1">
            </form>

            <div class="panel payroll-board">
                <div class="payroll-board-head">
                    <div class="sub-tabs">
                        <button class="sub-tab active" data-tab="tab-tinhcong"><i class="fas fa-calculator"></i> Tính toán Công & OT</button>
                        <button class="sub-tab" data-tab="tab-bangchamcong"><i class="fas fa-table-list"></i> Bảng Chấm Công</button>
                        <button class="sub-tab" data-tab="tab-lichsuquet"><i class="fas fa-clock-rotate-left"></i> Lịch sử chấm công</button>
                    </div>
                    <p>Dữ liệu dưới đây phản ánh bảng công của kỳ đã chọn. Bạn nên kiểm tra OT, ngày công và số giờ làm trước khi gửi đến nhân viên.</p>
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
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                            <h3 style="margin:0;">Tổng toán tháng <span id="list-month-label"><?= htmlspecialchars($selectedMonth) ?></span></h3>
                            <button class="btn btn-success btn-sm" type="submit" form="payroll-export-form" id="payroll-export-btn" title="<?= $canExport ? 'Tất cả nhân viên đã duyệt, có thể xuất.' : 'Chỉ xuất khi tất cả nhân viên đã duyệt bảng công.' ?>" <?= $canExport ? '' : 'disabled' ?>>
                                Xuất Excel
                            </button>
                        </div>
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
                        
                        <div class="payroll-hanhDong-panel" style="margin-top: 24px;">
                            <div class="payroll-hanhDong-row">
                                <div class="payroll-hanhDong-copy">
                                    <h3>Hoàn tất rà soát bảng công</h3>
                                    <p id="approval-trangThai">
                                        <?php if ($monthlyApproval): ?>
                                            Trạng thái: <span class="trangThai-badge trangThai-<?= strtolower($monthlyApproval['trangThai'] ?? 'draft') ?>"><?= htmlspecialchars($monthlyApproval['trangThai'] ?? 'draft') ?></span>
                                        <?php else: ?>
                                            Chưa gửi bảng công cho tháng này. Sau khi xác nhận, bảng công sẽ được gửi đến từng nhân viên để xác nhận.
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="payroll-hanhDong-buttons">
                                    <button type="button" class="btn btn-success btn-sm" id="submit-payroll-btn"><i class="fas fa-paper-plane"></i> GỬI BẢNG CÔNG CHO NHÂN VIÊN</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="tab-lichsuquet">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                            <h3 style="margin:0;">Lịch sử chấm công tablet - Tháng <span id="scan-month-label"><?= htmlspecialchars($selectedMonth) ?></span></h3>
                            <span id="scan-total-label" style="color:#64748b;font-size:.9em;"></span>
                        </div>
                        <div class="scan-history-grid" id="scan-history-grid">
                            <div class="empty-state">Đang tải dữ liệu...</div>
                        </div>
                        <div class="scan-history-pagination" id="scan-history-pagination" style="display:flex;align-items:center;justify-content:center;gap:10px;margin-top:16px;"></div>
                    </div>
                </div>
            </div>

            <div class="panel approval-history-panel">
                <div class="approval-history-head">
                    <h3>Lịch sử gửi bảng công cho nhân viên</h3>
                    <p>Theo dõi trạng thái nhân viên đã duyệt bảng công theo từng tháng.</p>
                </div>
                <div class="approval-history-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kỳ công</th>
                                <th>Tổng NV</th>
                                <th>Chờ duyệt</th>
                                <th>Đã duyệt</th>
                                <th>Ngày gửi</th>
                                <th>Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody id="approval-history-body">
                            <?php if (!empty($approvalHistory)): ?>
                                <?php foreach ($approvalHistory as $row): ?>
                                    <?php
                                    $total = (int)($row['total'] ?? 0);
                                    $pending = (int)($row['pending'] ?? 0);
                                    $approved = (int)($row['approved'] ?? 0);
                                    $allApproved = $pending === 0 && $total > 0;
                                    ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars((string)($row['thangNam'] ?? '')) ?></strong></td>
                                        <td><?= $total ?></td>
                                        <td>
                                            <div style="cursor:pointer;" onclick="showApprovalDetails('<?= htmlspecialchars((string)($row['thangNam'] ?? '')) ?>')">
                                                <?php if ($pending > 0): ?>
                                                    <span class="trangThai-badge trangThai-pending"><?= $pending ?> chờ</span>
                                                <?php else: ?>
                                                    <span style="color:#22c55e">0</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="cursor:pointer;" onclick="showApprovalDetails('<?= htmlspecialchars((string)($row['thangNam'] ?? '')) ?>')">
                                                <?php if ($allApproved): ?>
                                                    <span class="trangThai-badge trangThai-approved">✓ Tất cả</span>
                                                <?php else: ?>
                                                    <span><?= $approved ?>/<?= $total ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars((string)($row['last_submitted'] ?? '')) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-info btn-xs" onclick="showApprovalDetails('<?= htmlspecialchars((string)($row['thangNam'] ?? '')) ?>')">
                                                <i class="fas fa-eye"></i> Xem danh sách
                                            </button>
                                        </td>
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

<div class="scan-image-modal" id="scanImageModal" aria-hidden="true">
    <button type="button" class="scan-image-close" id="scan-image-close" aria-label="Đóng">×</button>
    <img id="scan-image-full" src="" alt="Ảnh minh chứng chấm công">
</div>

<div class="payroll-detail-modal" id="employeeApprovalModal" aria-hidden="true">
    <div class="payroll-detail-card" style="width: min(800px, 100%);">
        <div class="payroll-detail-head">
            <div>
                <h3>Trạng thái nhân viên duyệt bảng công</h3>
                <p id="emp-approval-month-label">Tháng ...</p>
            </div>
            <button type="button" class="payroll-detail-close" onclick="closeEmpApprovalModal()">×</button>
        </div>
        <div class="payroll-detail-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Phòng ban</th>
                            <th>Trạng thái</th>
                            <th>Thời gian duyệt</th>
                        </tr>
                    </thead>
                    <tbody id="emp-approval-grid-body">
                        <tr><td colspan="4" class="empty-state">Đang tải...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<div class="payroll-detail-modal" id="dailyDetailModal" aria-hidden="true">
    <div class="payroll-detail-card" style="width: 400px;">
        <div class="payroll-detail-head">
            <div>
                <h3>Chi tiết chấm công</h3>
                <p id="daily-detail-date-label"></p>
            </div>
            <button type="button" class="payroll-detail-close" onclick="closeDailyDetailModal()">×</button>
        </div>
        <div class="payroll-detail-body">
            <div class="payroll-meta-box" style="margin-bottom:12px;">
                <strong>Nhân viên</strong>
                <div id="daily-detail-name"></div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="payroll-meta-box">
                    <strong>Giờ vào</strong>
                    <div id="daily-detail-in" style="font-size:1.2rem; font-weight:700; color:#22c55e;"></div>
                </div>
                <div class="payroll-meta-box">
                    <strong>Giờ ra</strong>
                    <div id="daily-detail-out" style="font-size:1.2rem; font-weight:700; color:#3b82f6;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

</script>
<script>
    function escapeHtml(val) {
        return String(val ?? '').replace(/[&<>\"]/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
        });
    }

    function formatDateTime(value) {
        if (!value) return '-';
        return String(value).replace('T', ' ');
    }

    function getApprovalStatusMeta(trangThai) {
        var normalized = String(trangThai || 'submitted').toLowerCase();
        if (normalized === 'approved') {
            return { label: 'Đã duyệt', cls: 'trangThai-approved' };
        }
        if (normalized === 'rejected') {
            return { label: 'Đã trả về', cls: 'trangThai-rejected' };
        }
        return { label: 'Chờ duyệt', cls: 'trangThai-pending' };
    }

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
    var monthInput = filterForm ? filterForm.querySelector('[name="month"]') : null;
    var exportBtn = document.getElementById('payroll-export-btn');
    var exportFromInput = document.getElementById('export-from-date');
    var exportToInput = document.getElementById('export-to-date');
    var gridBody = document.getElementById('attendance-grid-body');
    var tableBody = document.getElementById('payroll-table-body');
    var approvalStatus = document.getElementById('approval-trangThai');
    var approvalHistoryBody = document.getElementById('approval-history-body');
    var detailModal = document.getElementById('payrollDetailModal');
    var detailTitle = document.getElementById('payroll-detail-title');
    var detailSubtitle = document.getElementById('payroll-detail-subtitle');
    var detailSummary = document.getElementById('payroll-detail-summary');
    var detailMeta = document.getElementById('payroll-detail-meta');
    var detailGridBody = document.getElementById('payroll-detail-grid-body');
    var detailCloseBtn = document.getElementById('payroll-detail-close');

    function currentMonth() {
        return filterForm.querySelector('[name="month"]').value;
    }

    function currentEmployeeQuery() {
        var val = employeeInput ? employeeInput.value.trim() : '';
        return (val === 'Tất cả') ? '' : val;
    }

    function pad2(num) {
        return num < 10 ? '0' + num : String(num);
    }

    function updateExportRange(monthKey) {
        if (!exportFromInput || !exportToInput) return;
        var parts = String(monthKey || '').split('-');
        if (parts.length !== 2) return;
        var year = Number(parts[0]);
        var month = Number(parts[1]);
        if (!year || !month) return;
        var lastDay = new Date(year, month, 0).getDate();
        exportFromInput.value = year + '-' + pad2(month) + '-01';
        exportToInput.value = year + '-' + pad2(month) + '-' + pad2(lastDay);
    }

    function updateExportState(summary) {
        if (!exportBtn) return;
        var row = Array.isArray(summary) ? summary[0] : summary;
        var total = row ? Number(row.total || 0) : 0;
        var pending = row ? Number(row.pending || 0) : total;
        var canExport = total > 0 && pending === 0;
        exportBtn.disabled = !canExport;
        exportBtn.title = canExport
            ? 'Tất cả nhân viên đã duyệt, có thể xuất.'
            : 'Chỉ xuất khi tất cả nhân viên đã duyệt bảng công.';
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
                    var checkIn = dayData && dayData.check_in ? dayData.check_in : '';
                    var checkOut = dayData && dayData.check_out ? dayData.check_out : '';
                    cells += '<td class="' + cls + '" style="cursor:pointer" onclick="showDailyDetail(\'' + escapeHtml(row.hoTen) + '\', \'' + dateStr + '\', \'' + checkIn + '\', \'' + checkOut + '\')">' + valStr + '</td>';
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
            var total = Number(row.total || 0);
            var pending = Number(row.pending || 0);
            var approved = Number(row.approved || 0);
            var allApproved = pending === 0 && total > 0;
            var monthKey = escapeHtml(row.thangNam || '');

            var pendingHtml = pending > 0
                ? '<span class="trangThai-badge trangThai-pending">' + pending + ' chờ</span>'
                : '<span style="color:#22c55e">0</span>';
            var approvedHtml = allApproved
                ? '<span class="trangThai-badge trangThai-approved">✓ Tất cả</span>'
                : '<span>' + approved + '/' + total + '</span>';

            return '<tr>' +
                '<td><strong>' + monthKey + '</strong></td>' +
                '<td>' + total + '</td>' +
                '<td><div style="cursor:pointer" onclick="showApprovalDetails(\'' + monthKey + '\')">' + pendingHtml + '</div></td>' +
                '<td><div style="cursor:pointer" onclick="showApprovalDetails(\'' + monthKey + '\')">' + approvedHtml + '</div></td>' +
                '<td>' + escapeHtml(formatDateTime(row.last_submitted)) + '</td>' +
                '<td><button type="button" class="btn btn-info btn-xs" onclick="showApprovalDetails(\'' + monthKey + '\')"><i class="fas fa-eye"></i> Xem danh sách</button></td>' +
                '</tr>';
        }).join('');
    }

    window.showApprovalDetails = function(monthKey) {
        var modal = document.getElementById('employeeApprovalModal');
        var body = document.getElementById('emp-approval-grid-body');
        var label = document.getElementById('emp-approval-month-label');
        
        label.textContent = 'Kỳ công: ' + monthKey;
        body.innerHTML = '<tr><td colspan="4" class="empty-state">Đang tải dữ liệu...</td></tr>';
        modal.classList.add('open');

        fetch('index.php?page=hr-api-timesheet-approval-details&month=' + encodeURIComponent(monthKey), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function(r) {
                if (!r.ok) throw new Error('Mã lỗi: ' + r.trangThai);
                return r.text();
            })
            .then(function(text) {
                var json;
                try {
                    json = JSON.parse(text);
                } catch(e) {
                    console.error('Invalid JSON:', text);
                    var snippet = text.substring(0, 100).replace(/</g, '&lt;');
                    throw new Error('Dữ liệu không đúng định dạng JSON. Phản hồi: ' + snippet + '...');
                }

                if (!json.success) {
                    body.innerHTML = '<tr><td colspan="4" class="empty-state">' + escapeHtml(json.message || 'Lỗi từ máy chủ') + '</td></tr>';
                    return;
                }
                var rows = json.data || [];
                if (!rows.length) {
                    body.innerHTML = '<tr><td colspan="4" class="empty-state">Không có dữ liệu nhân viên.</td></tr>';
                    return;
                }
                body.innerHTML = rows.map(function(row) {
                    var statusMeta = getApprovalStatusMeta(row.trangThai);
                    return '<tr>' +
                        '<td><strong>' + escapeHtml(row.hoTen) + '</strong><br><small>#' + row.maND + '</small></td>' +
                        '<td>' + escapeHtml(row.phongBan || '-') + '</td>' +
                        '<td><span class="trangThai-badge ' + statusMeta.cls + '">' + statusMeta.label + '</span></td>' +
                        '<td>' + (row.ngayDuyet ? escapeHtml(formatDateTime(row.ngayDuyet)) : '-') + '</td>' +
                        '</tr>';
                }).join('');
            })
            .catch(function(err) {
                console.error(err);
                body.innerHTML = '<tr><td colspan="4" class="empty-state">Lỗi: ' + escapeHtml(err.message) + '</td></tr>';
            });
    };

    window.closeEmpApprovalModal = function() {
        document.getElementById('employeeApprovalModal').classList.remove('open');
    };

    window.showDailyDetail = function(name, date, checkIn, checkOut) {
        var modal = document.getElementById('dailyDetailModal');
        document.getElementById('daily-detail-name').textContent = name;
        document.getElementById('daily-detail-date-label').textContent = 'Ngày: ' + date;
        document.getElementById('daily-detail-in').textContent = checkIn ? checkIn.substring(11, 19) : '--:--';
        document.getElementById('daily-detail-out').textContent = checkOut ? checkOut.substring(11, 19) : '--:--';
        modal.classList.add('open');
    };

    window.closeDailyDetailModal = function() {
        document.getElementById('dailyDetailModal').classList.remove('open');
    };

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
            var statusMeta = getApprovalStatusMeta(approval.trangThai);

            detailTitle.textContent = 'Chi tiết bảng công kỳ ' + (approval.thangNam || '');
            detailSubtitle.textContent = 'HR gửi: ' + escapeHtml(approval.hr_name || 'Chưa xác định') + ' | Ngày gửi: ' + escapeHtml(formatDateTime(approval.ngayGui));
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
                { label: 'Ghi chú', value: approval.ghiChu || 'Không có ghi chú' }
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
            updateExportRange(currentMonth());
            updateExportState(json.approvalSummary || []);
            
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
                var approvalMeta = getApprovalStatusMeta(approval.trangThai);
                approvalStatus.innerHTML = 'Trạng thái: <span class="trangThai-badge ' + approvalMeta.cls + '">' + escapeHtml(approvalMeta.label) + '</span>';
            } else {
                approvalStatus.textContent = 'Chưa gửi bảng công cho tháng này.';
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

    if (monthInput) {
        monthInput.addEventListener('change', function () {
            loadPayroll();
        });
    }

    submitBtn.addEventListener('click', function () {
        if (!window.confirm('Bạn có chắc muốn gửi bảng công đến từng nhân viên không?')) {
            return;
        }
        var form = new FormData();
        form.append('thangNam', currentMonth());
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

    updateExportRange(currentMonth());
    updateExportState(<?= json_encode($exportSummary ?? new stdClass()) ?>);
    loadEmployeeSuggestions(currentEmployeeQuery());
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var scanGrid = document.getElementById('scan-history-grid');
    var scanPagination = document.getElementById('scan-history-pagination');
    var scanMonthLabel = document.getElementById('scan-month-label');
    var scanTotalLabel = document.getElementById('scan-total-label');
    var scanTab = document.querySelector('.sub-tab[data-tab="tab-lichsuquet"]');
    var monthInputEl = document.querySelector('#payroll-filter-form [name="month"]');
    var scanImageModal = document.getElementById('scanImageModal');
    var scanImageFull = document.getElementById('scan-image-full');
    var scanImageClose = document.getElementById('scan-image-close');
    var scanHistoryLoaded = false;
    var scanCurrentPage = 1;

    function scanMonth() {
        return monthInputEl ? monthInputEl.value : '<?= htmlspecialchars($selectedMonth) ?>';
    }

    function escapeHtmlLocal(val) {
        return String(val ?? '').replace(/[&<>"]/g, function (c) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c];
        });
    }

    function openScanImage(src) {
        if (!scanImageModal || !scanImageFull) return;
        scanImageFull.src = src;
        scanImageModal.classList.add('open');
    }

    function closeScanImage() {
        if (!scanImageModal) return;
        scanImageModal.classList.remove('open');
        scanImageFull.src = '';
    }

    if (scanImageClose) scanImageClose.addEventListener('click', closeScanImage);
    if (scanImageModal) {
        scanImageModal.addEventListener('click', function (e) {
            if (e.target === scanImageModal) closeScanImage();
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeScanImage();
    });

    function renderScanCards(rows) {
        if (!rows || rows.length === 0) {
            scanGrid.innerHTML = '<div class="empty-state">Không có dữ liệu quét trong tháng này.</div>';
            return;
        }
        scanGrid.innerHTML = rows.map(function (row) {
            var img = row.anhMinhChung ? ('uploads/attendance_faces/' + encodeURIComponent(row.anhMinhChung)) : '';
            var thumb = img
                ? '<img class="scan-thumb" src="' + img + '" loading="lazy" onclick="window.__openScanImage(\'' + img + '\')">'
                : '<div class="scan-thumb" style="display:flex;align-items:center;justify-content:center;color:#94a3b8;">Không có ảnh</div>';
            return '<div class="scan-card">' + thumb +
                '<div class="scan-meta">' +
                '<div class="scan-name">' + escapeHtmlLocal(row.hoTen || ('NV #' + row.maND)) + '</div>' +
                '<div class="scan-time">' + escapeHtmlLocal(formatDateTime(row.thoiGianQuet)) + '</div>' +
                '</div></div>';
        }).join('');
    }

    function renderScanPagination(page, totalPages) {
        if (!scanPagination) return;
        if (totalPages <= 1) {
            scanPagination.innerHTML = '';
            return;
        }
        var html = '';
        html += '<button type="button" class="scan-page-btn" data-page="' + (page - 1) + '" ' + (page <= 1 ? 'disabled' : '') + '><i class="fas fa-chevron-left"></i></button>';
        var start = Math.max(1, page - 2);
        var end = Math.min(totalPages, start + 4);
        start = Math.max(1, end - 4);
        for (var p = start; p <= end; p++) {
            html += '<button type="button" class="scan-page-btn ' + (p === page ? 'active' : '') + '" data-page="' + p + '">' + p + '</button>';
        }
        html += '<button type="button" class="scan-page-btn" data-page="' + (page + 1) + '" ' + (page >= totalPages ? 'disabled' : '') + '><i class="fas fa-chevron-right"></i></button>';
        scanPagination.innerHTML = html;
    }

    function loadScanHistory(page) {
        scanCurrentPage = page || 1;
        var url = 'index.php?page=hr-api-tablet-scans&month=' + encodeURIComponent(scanMonth()) + '&pg=' + scanCurrentPage;

        if (scanMonthLabel) scanMonthLabel.textContent = scanMonth();
        scanGrid.innerHTML = '<div class="empty-state">Đang tải dữ liệu...</div>';

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (!json.success) {
                    scanGrid.innerHTML = '<div class="empty-state">' + escapeHtmlLocal(json.message || 'Lỗi tải dữ liệu.') + '</div>';
                    if (scanPagination) scanPagination.innerHTML = '';
                    return;
                }
                renderScanCards(json.data || []);
                renderScanPagination(json.page || 1, json.totalPages || 1);
                if (scanTotalLabel) scanTotalLabel.textContent = 'Tổng ' + (json.total || 0) + ' lượt quét';
            })
            .catch(function () {
                scanGrid.innerHTML = '<div class="empty-state">Không thể kết nối máy chủ.</div>';
            });
    }

    if (scanPagination) {
        scanPagination.addEventListener('click', function (e) {
            var btn = e.target.closest('.scan-page-btn');
            if (!btn || btn.disabled) return;
            var page = parseInt(btn.getAttribute('data-page'), 10);
            if (!isNaN(page) && page >= 1) loadScanHistory(page);
        });
    }

    if (scanTab) {
        scanTab.addEventListener('click', function () {
            if (!scanHistoryLoaded) {
                scanHistoryLoaded = true;
                loadScanHistory(1);
            }
        });
    }

    if (monthInputEl) {
        monthInputEl.addEventListener('change', function () {
            if (scanHistoryLoaded) loadScanHistory(1);
        });
    }

    window.__openScanImage = openScanImage;
});
</script>
<?php include 'app/views/layouts/footer.php'; ?>
