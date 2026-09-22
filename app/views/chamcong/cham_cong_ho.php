<?php
if (!isset($_SESSION['user'])) { header('Location: index.php?page=login'); exit(); }
if (($_SESSION['role'] ?? '') !== 'hr') { header('Location: index.php?page=home'); exit(); }

$hrName = htmlspecialchars($_SESSION['user']['hoTen'] ?? 'HR');
$selectedMonth = $selectedMonth ?? date('Y-m');
$keyword = $keyword ?? '';
$employees = $employees ?? [];
$shifts = $shifts ?? [];
$history = $history ?? [];
$stats = $stats ?? [
    'total' => count($history),
    'face_error' => 0,
    'accident_face' => 0,
    'tablet_power' => 0,
    'wifi_loss' => 0,
];

include 'app/views/layouts/header.php';
include 'app/views/layouts/nav.php';
?>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>

    <div class="dashboard-container">
        <div class="cham-cong-ho-page">

            <!-- Top Header & Breadcrumb -->
            <div class="cch-page-header">
                <div>
                    <div class="cch-breadcrumb">
                        <span>HR</span> <i class="fa-solid fa-chevron-right"></i> <span>Chấm công hộ nhân viên</span>
                    </div>
                    <h1 class="cch-page-title">Chấm công hộ nhân viên</h1>
                    <p class="cch-page-subtitle">Ghi nhận công cho nhân sự khi gặp sự cố máy quét FaceID, lỗi tablet, mất kết nối hoặc tai nạn/sự cố khuôn mặt</p>
                </div>
                <div class="cch-header-actions">
                    <button type="button" class="cch-btn-create" id="btnOpenOverrideModal">
                        <i class="fa-solid fa-plus"></i>
                        <span>Tạo lượt chấm công hộ mới</span>
                    </button>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="cch-stats-grid">
                <div class="cch-stat-card card-total">
                    <div class="cch-stat-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                    <div class="cch-stat-info">
                        <span class="cch-stat-label">Tổng lượt trong tháng</span>
                        <div class="cch-stat-value" id="statTotal"><?= (int)$stats['total'] ?></div>
                    </div>
                </div>
                <div class="cch-stat-card card-face">
                    <div class="cch-stat-icon"><i class="fa-solid fa-user-slash"></i></div>
                    <div class="cch-stat-info">
                        <span class="cch-stat-label">Sự cố máy quét FaceID</span>
                        <div class="cch-stat-value" id="statFace"><?= (int)$stats['face_error'] ?></div>
                    </div>
                </div>
                <div class="cch-stat-card card-accident">
                    <div class="cch-stat-icon"><i class="fa-solid fa-user-injured"></i></div>
                    <div class="cch-stat-info">
                        <span class="cch-stat-label">Tai nạn / Sự cố khuôn mặt</span>
                        <div class="cch-stat-value" id="statAccident"><?= (int)($stats['accident_face'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="cch-stat-card card-device">
                    <div class="cch-stat-icon"><i class="fa-solid fa-network-wired"></i></div>
                    <div class="cch-stat-info">
                        <span class="cch-stat-label">Lỗi Tablet / Wi-Fi</span>
                        <div class="cch-stat-value" id="statDevice"><?= (int)(($stats['tablet_power'] ?? 0) + ($stats['wifi_loss'] ?? 0)) ?></div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar & History Table -->
            <div class="cch-table-card">
                <div class="cch-filter-bar">
                    <div class="cch-filter-left">
                        <div class="cch-month-box">
                            <i class="fa-regular fa-calendar"></i>
                            <input type="month" id="cchFilterMonth" value="<?= htmlspecialchars($selectedMonth) ?>">
                        </div>
                        <div class="cch-search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="cchSearchInput" placeholder="Tìm kiếm theo tên, mã NV, phòng ban, lý do..." value="<?= htmlspecialchars($keyword) ?>">
                        </div>
                    </div>
                    <div class="cch-filter-right">
                        <button type="button" class="cch-btn-refresh" id="btnRefreshHistory" title="Làm mới">
                            <i class="fa-solid fa-arrow-rotate-right"></i> Làm mới
                        </button>
                    </div>
                </div>

                <!-- History Table Container -->
                <div class="cch-table-responsive">
                    <table class="cch-data-table">
                        <thead>
                            <tr>
                                <th>Nhân viên</th>
                                <th>Ngày công & Ca</th>
                                <th>Check-in / Out</th>
                                <th>Số công</th>
                                <th>Lý do sự cố</th>
                                <th>Ghi chú xác minh</th>
                                <th>Người thực hiện</th>
                                <th>Thời gian tạo</th>
                            </tr>
                        </thead>
                        <tbody id="cchHistoryTbody">
                            <?php if (empty($history)): ?>
                                <tr>
                                    <td colspan="8" class="cch-empty-cell">
                                        <div class="cch-empty-state">
                                            <i class="fa-regular fa-folder-open"></i>
                                            <p>Chưa có lượt chấm công hộ nào trong tháng <?= htmlspecialchars($selectedMonth) ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history as $row): 
                                    $empName = $row['tenNhanVien'] ?? 'Nhân viên';
                                    $empDept = $row['phongBan'] ?? 'N/A';
                                    $empCode = $row['maNhanVienCode'] ?? ('NV' . str_pad($row['maNguoiDuocChamHo'], 4, '0', STR_PAD_LEFT));
                                    $initials = mb_strtoupper(mb_substr($empName, 0, 1, 'UTF-8'), 'UTF-8');
                                    $parts = explode(' ', $empName);
                                    if (count($parts) > 1) {
                                        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1, 'UTF-8') . mb_substr(end($parts), 0, 1, 'UTF-8'), 'UTF-8');
                                    }
                                    $r = $row['lyDo'] ?? '';
                                    $badgeClass = 'badge-other';
                                    if (stripos($r, 'tai nạn') !== false || stripos($r, 'tổn thương') !== false || stripos($r, 'chấn thương') !== false || stripos($r, 'dị ứng') !== false || stripos($r, 'sự cố khuôn mặt') !== false) {
                                        $badgeClass = 'badge-accident';
                                    } elseif (stripos($r, 'FaceID') !== false || stripos($r, 'nhận diện') !== false) {
                                        $badgeClass = 'badge-face';
                                    } elseif (stripos($r, 'Tablet') !== false || stripos($r, 'nguồn') !== false || stripos($r, 'treo') !== false) {
                                        $badgeClass = 'badge-tablet';
                                    } elseif (stripos($r, 'Wi-Fi') !== false || stripos($r, 'wifi') !== false || stripos($r, 'mạng') !== false) {
                                        $badgeClass = 'badge-wifi';
                                    }
                                    $timeIn = $row['gioVao'] ? date('H:i', strtotime($row['gioVao'])) : '--:--';
                                    $timeOut = $row['gioRa'] ? date('H:i', strtotime($row['gioRa'])) : '--:--';
                                ?>
                                <tr>
                                    <td>
                                        <div class="cch-emp-cell">
                                            <div class="cch-avatar-box"><?= htmlspecialchars($initials) ?></div>
                                            <div>
                                                <div class="cch-emp-name"><?= htmlspecialchars($empName) ?></div>
                                                <div class="cch-emp-meta">
                                                    <span><?= htmlspecialchars($empDept) ?></span>
                                                    <span class="cch-code-pill"><?= htmlspecialchars($empCode) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="cch-date-val"><?= date('d/m/Y', strtotime($row['ngayChamHo'])) ?></div>
                                        <div class="cch-shift-val"><?= htmlspecialchars($row['tenCa'] ?? 'Ca Hành chính') ?></div>
                                    </td>
                                    <td>
                                        <div class="cch-time-val">
                                            <span class="time-in"><?= $timeIn ?></span>
                                            <i class="fa-solid fa-arrow-right-long time-arrow"></i>
                                            <span class="time-out"><?= $timeOut ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="cch-cong-badge"><?= number_format((float)($row['congChuan'] ?? 1.0), 1) ?> công</span>
                                    </td>
                                    <td>
                                        <div class="cch-reason-badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($row['lyDo']) ?>
                                        </div>
                                        <?php if (!empty($row['mienTruDiTre'])): ?>
                                            <div class="cch-exempt-badge"><i class="fa-solid fa-check"></i> Miễn trừ phạt trễ</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="cch-note-text" title="<?= htmlspecialchars($row['ghiChuHR'] ?? '') ?>">
                                            <?= !empty($row['ghiChuHR']) ? htmlspecialchars($row['ghiChuHR']) : '<span class="cch-muted">Không có ghi chú</span>' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="cch-hr-name"><i class="fa-solid fa-shield-halved"></i> <?= htmlspecialchars($row['tenHR'] ?? 'HR') ?></div>
                                    </td>
                                    <td>
                                        <div class="cch-created-time"><?= date('d/m/Y H:i', strtotime($row['ngayTao'])) ?></div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- MODAL: TẠO LƯỢT CHẤM CÔNG HỘ MỚI (HR OVERRIDE MODAL)       -->
<!-- ========================================================= -->
<div class="cch-modal-overlay" id="cchOverrideModal" aria-hidden="true">
    <div class="cch-modal-card">
        <!-- Modal Head -->
        <div class="cch-modal-head">
            <div class="cch-head-left">
                <div class="cch-head-icon">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                    <div class="cch-title-wrap">
                        <h3>Tạo lượt chấm công hộ mới</h3>
                        <span class="cch-override-tag">HR OVERRIDE</span>
                    </div>
                    <p class="cch-head-subtitle">Ghi nhận công cho nhân sự khi gặp sự cố máy quét FaceID, lỗi thiết bị hoặc sự cố khuôn mặt</p>
                </div>
            </div>
            <button type="button" class="cch-btn-close" id="btnCloseModal" aria-label="Đóng">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="cch-modal-body">
            <form id="cchOverrideForm" autocomplete="off">
                <!-- Mode Switcher (Pill tabs) -->
                <div class="cch-mode-switch">
                    <button type="button" class="cch-mode-btn active" data-mode="single" id="btnModeSingle">
                        <span class="dot"></span> Chấm đơn lẻ (1 nhân viên)
                    </button>
                    <button type="button" class="cch-mode-btn" data-mode="batch" id="btnModeBatch">
                        <span class="dot"></span> Chấm hàng loạt (Mất điện / Sự cố toàn bộ)
                    </button>
                </div>

                <!-- Single Employee Picker -->
                <div class="cch-form-group" id="groupSingleEmployee">
                    <label class="cch-label">Nhân viên cần chấm hộ <span class="cch-req">*</span></label>
                    <div class="cch-select-wrap">
                        <select id="cchSingleEmpSelect" class="cch-select">
                            <option value="" disabled selected>-- Chọn nhân viên cần chấm công hộ --</option>
                            <?php foreach ($employees as $emp): 
                                $empCode = $emp['maNV'] ?? $emp['tenDangNhap'] ?? ('NV' . str_pad($emp['maND'], 4, '0', STR_PAD_LEFT));
                                $empTitle = $emp['chucVu'] ?? ($emp['phongBan'] ?? 'Nhân viên');
                            ?>
                                <option value="<?= (int)$emp['maND'] ?>" 
                                        data-name="<?= htmlspecialchars($emp['hoTen']) ?>" 
                                        data-dept="<?= htmlspecialchars($empTitle) ?>" 
                                        data-code="<?= htmlspecialchars($empCode) ?>">
                                    <?= htmlspecialchars($emp['hoTen']) ?> • <?= htmlspecialchars($empTitle) ?> [<?= htmlspecialchars($empCode) ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-magnifying-glass cch-select-search-icon"></i>
                        <i class="fa-solid fa-chevron-down cch-select-arrow"></i>
                    </div>

                    <!-- Visual Selected Employee Card Preview -->
                    <div class="cch-selected-emp-card" id="cchSelectedEmpCard" style="display:none;">
                        <div class="cch-card-avatar" id="cchEmpCardAvatar">LC</div>
                        <div class="cch-card-info">
                            <span class="cch-card-name" id="cchEmpCardName">Lê Hoàng Châu</span>
                            <span class="cch-card-sub" id="cchEmpCardDept">• Senior Backend Dev</span>
                            <span class="cch-card-code" id="cchEmpCardCode">NV0142</span>
                        </div>
                        <i class="fa-solid fa-circle-check cch-card-check"></i>
                    </div>
                </div>

                <!-- Batch Employee Picker (Hidden by default) -->
                <div class="cch-form-group" id="groupBatchEmployee" style="display:none;">
                    <div class="cch-batch-header">
                        <label class="cch-label" style="margin-bottom:0;">Chọn danh sách nhân viên áp dụng <span class="cch-req">*</span></label>
                        <div class="cch-batch-actions">
                            <button type="button" class="cch-btn-link" id="btnSelectAllBatch">Chọn tất cả (<?= count($employees) ?>)</button>
                            <span class="cch-sep">•</span>
                            <button type="button" class="cch-btn-link" id="btnUnselectAllBatch">Bỏ chọn</button>
                        </div>
                    </div>
                    <div class="cch-batch-list-box">
                        <?php foreach ($employees as $emp): 
                            $empCode = $emp['maNV'] ?? $emp['tenDangNhap'] ?? ('NV' . str_pad($emp['maND'], 4, '0', STR_PAD_LEFT));
                            $empTitle = $emp['chucVu'] ?? ($emp['phongBan'] ?? 'Nhân viên');
                        ?>
                            <label class="cch-batch-item">
                                <input type="checkbox" name="batch_emp[]" value="<?= (int)$emp['maND'] ?>" class="cch-batch-check">
                                <span class="cch-batch-name"><?= htmlspecialchars($emp['hoTen']) ?></span>
                                <span class="cch-batch-dept"><?= htmlspecialchars($empTitle) ?></span>
                                <span class="cch-code-pill"><?= htmlspecialchars($empCode) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="cch-batch-count" id="cchBatchCountLabel">Đã chọn: 0 nhân viên</div>
                </div>

                <!-- Date & Shift Row -->
                <div class="cch-form-row">
                    <div class="cch-form-group cch-col">
                        <label class="cch-label">Ngày chấm công <span class="cch-req">*</span></label>
                        <div class="cch-date-input-wrap">
                            <i class="fa-regular fa-calendar cch-input-icon"></i>
                            <input type="date" id="cchDateInput" class="cch-input" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                            <button type="button" class="cch-btn-today" id="btnSetToday">Hôm nay</button>
                        </div>
                    </div>
                    <div class="cch-form-group cch-col">
                        <label class="cch-label">Ca làm việc áp dụng <span class="cch-req">*</span></label>
                        <div class="cch-select-wrap">
                            <select id="cchShiftSelect" class="cch-select">
                                <?php if (empty($shifts)): ?>
                                    <option value="1" data-start="08:30" data-end="17:30" selected>Ca Hành chính (08:30 - 17:30)</option>
                                <?php else: ?>
                                    <?php foreach ($shifts as $idx => $s): 
                                        $sIn = substr($s['gioBatDau'] ?? '08:30', 0, 5);
                                        $sOut = substr($s['gioKetThuc'] ?? '17:30', 0, 5);
                                    ?>
                                        <option value="<?= (int)$s['id'] ?>" 
                                                data-start="<?= $sIn ?>" 
                                                data-end="<?= $sOut ?>"
                                                <?= $idx === 0 ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['tenCa']) ?> (<?= $sIn ?> - <?= $sOut ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <i class="fa-regular fa-clock cch-select-search-icon"></i>
                            <i class="fa-solid fa-chevron-down cch-select-arrow"></i>
                        </div>
                    </div>
                </div>

                <!-- Time Check-in / Check-out Box -->
                <div class="cch-time-card">
                    <div class="cch-time-card-head">
                        <div class="cch-time-head-title">
                            <i class="fa-regular fa-clock"></i>
                            <span>Mốc giờ Check-in / Check-out</span>
                        </div>
                        <div class="cch-time-badge" id="cchWorkCreditBadge">
                            <span class="dot"></span> <span id="cchCreditText">1.0 công chuẩn • 8.0 giờ làm</span>
                        </div>
                    </div>

                    <div class="cch-time-boxes-row">
                        <!-- Box Check-in -->
                        <div class="cch-time-box">
                            <div class="cch-time-box-top">
                                <span class="cch-time-box-label">GIỜ VÀO (CHECK-IN)</span>
                                <span class="cch-time-tag" id="cchTimeInPeriod">08:30 AM</span>
                            </div>
                            <div class="cch-time-input-wrap">
                                <input type="time" step="60" id="cchTimeIn" class="cch-time-field" value="08:30">
                            </div>
                        </div>

                        <!-- Box Check-out -->
                        <div class="cch-time-box">
                            <div class="cch-time-box-top">
                                <span class="cch-time-box-label">GIỜ RA (CHECK-OUT)</span>
                                <span class="cch-time-tag" id="cchTimeOutPeriod">05:35 PM</span>
                            </div>
                            <div class="cch-time-input-wrap">
                                <input type="time" step="60" id="cchTimeOut" class="cch-time-field" value="17:35">
                            </div>
                        </div>
                    </div>

                    <!-- Quick Shift Select Buttons -->
                    <div class="cch-quick-select-row">
                        <span class="cch-quick-label">Chọn nhanh:</span>
                        <button type="button" class="cch-quick-btn active" id="btnQuickFull">
                            <i class="fa-solid fa-bolt"></i> Đúng ca chuẩn (08:30 - 17:30)
                        </button>
                        <button type="button" class="cch-quick-btn" id="btnQuickMorning">
                            Nửa ca sáng (0.5 công)
                        </button>
                        <button type="button" class="cch-quick-btn" id="btnQuickAfternoon">
                            Nửa ca chiều (0.5 công)
                        </button>
                    </div>

                    <!-- Late Exemption Checkbox -->
                    <label class="cch-exempt-row">
                        <input type="checkbox" id="cchMienTruCheck" checked class="cch-checkbox">
                        <div class="cch-exempt-text">
                            <div class="cch-exempt-title">Miễn trừ tính phạt đi trễ do sự cố thiết bị FaceID</div>
                            <div class="cch-exempt-desc">Nhân viên vẫn được tính chuyên cần và không bị trừ điểm KPI đi trễ của tháng này.</div>
                        </div>
                    </label>
                </div>

                <!-- Technical & Personal Reason Cards (4 selectable cards) -->
                <div class="cch-form-group">
                    <div class="cch-reason-header">
                        <label class="cch-label" style="margin-bottom:0;">Lý do sự cố / Nguyên nhân chấm hộ <span class="cch-req">*</span></label>
                        <span class="cch-reason-hint">Nhấn để chọn nguyên nhân</span>
                    </div>

                    <div class="cch-reasons-grid">
                        <!-- Card 1: FaceID -->
                        <div class="cch-reason-card selected" data-reason="Lỗi nhận diện FaceID">
                            <div class="cch-reason-icon icon-face">
                                <i class="fa-solid fa-user-slash"></i>
                            </div>
                            <div class="cch-reason-info">
                                <div class="cch-reason-title">Lỗi nhận diện FaceID</div>
                                <div class="cch-reason-sub">Camera mờ / Sai góc độ quét</div>
                            </div>
                            <div class="cch-reason-check"><i class="fa-solid fa-check"></i></div>
                        </div>

                        <!-- Card 2: Accident / Face Issue -->
                        <div class="cch-reason-card" data-reason="Sự cố khuôn mặt / Tai nạn (Không thể nhận diện)">
                            <div class="cch-reason-icon icon-accident">
                                <i class="fa-solid fa-user-injured"></i>
                            </div>
                            <div class="cch-reason-info">
                                <div class="cch-reason-title">Tai nạn / Sự cố khuôn mặt</div>
                                <div class="cch-reason-sub">Chấn thương, băng gạc, dị ứng...</div>
                            </div>
                            <div class="cch-reason-check"><i class="fa-solid fa-check"></i></div>
                        </div>

                        <!-- Card 3: Tablet power / crash -->
                        <div class="cch-reason-card" data-reason="Tablet treo / Mất nguồn">
                            <div class="cch-reason-icon icon-tablet">
                                <i class="fa-solid fa-tablet-screen-button"></i>
                            </div>
                            <div class="cch-reason-info">
                                <div class="cch-reason-title">Tablet treo / Mất nguồn</div>
                                <div class="cch-reason-sub">App thoát đột ngột, hết pin</div>
                            </div>
                            <div class="cch-reason-check"><i class="fa-solid fa-check"></i></div>
                        </div>

                        <!-- Card 4: Wi-Fi Disconnect -->
                        <div class="cch-reason-card" data-reason="Mất kết nối Wi-Fi">
                            <div class="cch-reason-icon icon-wifi">
                                <i class="fa-solid fa-wifi"></i>
                            </div>
                            <div class="cch-reason-info">
                                <div class="cch-reason-title">Mất kết nối Wi-Fi</div>
                                <div class="cch-reason-sub">Đứt cáp quang / Ngoại lệ mạng</div>
                            </div>
                            <div class="cch-reason-check"><i class="fa-solid fa-check"></i></div>
                        </div>
                    </div>
                    <input type="hidden" id="cchSelectedReason" value="Lỗi nhận diện FaceID">
                </div>

                <!-- Manager / HR Verification Notes -->
                <div class="cch-form-group" style="margin-bottom: 8px;">
                    <label class="cch-label">Ghi chú xác minh của Quản lý / HR <span class="cch-opt">(Tùy chọn)</span></label>
                    <textarea id="cchNoteInput" class="cch-textarea" rows="2" 
                              placeholder="Ví dụ: Trưởng nhóm Backend (Anh Tuấn) đã xác nhận nhân viên có mặt tại bàn làm việc từ 08:25 sáng..."></textarea>
                </div>
            </form>
        </div>

        <!-- Modal Foot -->
        <div class="cch-modal-foot">
            <div class="cch-foot-audit">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Lưu vết Audit Log bởi HR: <strong><?= $hrName ?></strong></span>
            </div>
            <div class="cch-foot-actions">
                <button type="button" class="cch-btn-cancel" id="btnCancelModal">Hủy bỏ</button>
                <button type="button" class="cch-btn-submit" id="btnSubmitOverride">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Lưu & Xác nhận chấm hộ</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- CSS STYLES FOR CHẤM CÔNG HỘ                                -->
<!-- ========================================================= -->
<style>
/* Page Layout */
.cham-cong-ho-page {
    padding: 24px 28px 40px 28px;
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    box-sizing: border-box;
}

.cch-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.cch-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}

.cch-breadcrumb i { font-size: 0.65rem; color: #94a3b8; }

.cch-page-title {
    font-size: 1.65rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 4px 0;
    letter-spacing: -0.02em;
}

.cch-page-subtitle {
    color: #64748b;
    font-size: 0.9rem;
    margin: 0;
}

.cch-btn-create {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #2563eb;
    color: #ffffff;
    font-size: 0.9rem;
    font-weight: 700;
    padding: 10px 18px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    transition: all 0.2s ease;
}

.cch-btn-create:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
}

/* Stats Cards */
.cch-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.cch-stat-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
}

.cch-stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.card-total .cch-stat-icon { background: #eff6ff; color: #2563eb; }
.card-face .cch-stat-icon { background: #fef3c7; color: #d97706; }
.card-accident .cch-stat-icon { background: #fee2e2; color: #e11d48; }
.card-device .cch-stat-icon { background: #f3e8ff; color: #9333ea; }

.cch-stat-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #64748b;
    display: block;
    margin-bottom: 2px;
}

.cch-stat-value {
    font-size: 1.6rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
}

/* Table Card */
.cch-table-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    overflow: hidden;
}

.cch-filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 12px;
    background: #fafbfc;
}

.cch-filter-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    flex: 1;
}

.cch-month-box {
    display: inline-flex;
    align-items: center;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 6px 12px;
    gap: 8px;
    color: #475569;
}

.cch-month-box input {
    border: none;
    outline: none;
    font-size: 0.88rem;
    font-weight: 600;
    color: #1e293b;
    background: transparent;
    cursor: pointer;
}

.cch-search-box {
    display: inline-flex;
    align-items: center;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 6px 14px;
    gap: 10px;
    flex: 1;
    max-width: 440px;
}

.cch-search-box input {
    border: none;
    outline: none;
    font-size: 0.88rem;
    color: #1e293b;
    width: 100%;
}

.cch-search-box i { color: #94a3b8; font-size: 0.85rem; }

.cch-btn-refresh {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #475569;
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.cch-btn-refresh:hover { background: #f8fafc; color: #1e293b; border-color: #94a3b8; }

/* Data Table */
.cch-table-responsive { overflow-x: auto; }

.cch-data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
    text-align: left;
}

.cch-data-table thead th {
    background: #f8fafc;
    color: #64748b;
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 12px 18px;
    border-bottom: 1px solid #e2e8f0;
}

.cch-data-table tbody td {
    padding: 14px 18px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.cch-data-table tbody tr:hover { background: #f8fafc; }

.cch-emp-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.cch-avatar-box {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: #2563eb;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.cch-emp-name {
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
}

.cch-emp-meta {
    font-size: 0.78rem;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 2px;
}

.cch-code-pill {
    background: #f1f5f9;
    color: #475569;
    padding: 1px 6px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 0.72rem;
}

.cch-date-val { font-weight: 600; color: #1e293b; }
.cch-shift-val { font-size: 0.78rem; color: #64748b; }

.cch-time-val {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    color: #0f172a;
    font-size: 0.9rem;
}

.time-in { color: #16a34a; }
.time-out { color: #2563eb; }
.time-arrow { font-size: 0.75rem; color: #94a3b8; }

.cch-cong-badge {
    display: inline-block;
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 700;
}

.cch-reason-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
}

.badge-face { background: #fef3c7; color: #b45309; }
.badge-accident { background: #fee2e2; color: #be123c; border: 1px solid #fecdd3; }
.badge-tablet { background: #f3e8ff; color: #7e22ce; }
.badge-wifi { background: #ffedd5; color: #c2410c; }
.badge-other { background: #f1f5f9; color: #475569; }

.cch-exempt-badge {
    font-size: 0.72rem;
    color: #16a34a;
    margin-top: 4px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.cch-note-text {
    max-width: 220px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 0.82rem;
    color: #475569;
}

.cch-muted { color: #94a3b8; font-style: italic; }

.cch-hr-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 6px;
}

.cch-hr-name i { color: #2563eb; font-size: 0.8rem; }
.cch-created-time { font-size: 0.78rem; color: #64748b; }

.cch-empty-cell {
    padding: 60px 20px !important;
    text-align: center;
}

.cch-empty-state i { font-size: 2.5rem; color: #cbd5e1; margin-bottom: 12px; }
.cch-empty-state p { color: #64748b; font-size: 0.95rem; margin: 0; }

/* ========================================================= */
/* MODAL STYLES (MATCHING THE SCREENSHOT EXACTLY)            */
/* ========================================================= */
.cch-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.cch-modal-overlay.show {
    display: flex;
    opacity: 1;
}

.cch-modal-card {
    background: #ffffff;
    border-radius: 20px;
    width: 100%;
    max-width: 720px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    animation: cchModalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes cchModalPop {
    0% { transform: scale(0.95) translateY(10px); opacity: 0; }
    100% { transform: scale(1) translateY(0); opacity: 1; }
}

/* Modal Head */
.cch-modal-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 22px 28px 18px 28px;
    border-bottom: 1px solid #f1f5f9;
    flex-shrink: 0;
}

.cch-head-left {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.cch-head-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: #eff6ff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
}

.cch-title-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 3px;
}

.cch-title-wrap h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.01em;
}

.cch-override-tag {
    background: #dbeafe;
    color: #1d4ed8;
    font-size: 0.68rem;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 6px;
    letter-spacing: 0.04em;
}

.cch-head-subtitle {
    margin: 0;
    font-size: 0.84rem;
    color: #64748b;
    line-height: 1.35;
}

.cch-btn-close {
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 6px;
    border-radius: 8px;
    transition: all 0.2s;
}

.cch-btn-close:hover { background: #f1f5f9; color: #0f172a; }

/* Modal Body */
.cch-modal-body {
    padding: 20px 28px;
    overflow-y: auto;
    flex: 1 1 auto;
}

.cch-modal-body::-webkit-scrollbar { width: 6px; }
.cch-modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

/* Mode Switcher */
.cch-mode-switch {
    display: flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 12px;
    margin-bottom: 18px;
}

.cch-mode-btn {
    flex: 1;
    border: none;
    background: transparent;
    padding: 8px 14px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    border-radius: 9px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.cch-mode-btn .dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #cbd5e1;
}

.cch-mode-btn.active {
    background: #ffffff;
    color: #2563eb;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.cch-mode-btn.active .dot { background: #2563eb; }

/* Form Elements */
.cch-form-group { margin-bottom: 18px; }

.cch-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
}

.cch-req { color: #ef4444; }
.cch-opt { font-weight: 400; color: #94a3b8; font-size: 0.8rem; }

.cch-select-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.cch-select {
    width: 100%;
    padding: 10px 38px 10px 36px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #0f172a;
    background: #ffffff;
    appearance: none;
    cursor: pointer;
    outline: none;
    transition: all 0.2s;
}

.cch-select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.cch-select-search-icon {
    position: absolute;
    left: 12px;
    color: #64748b;
    font-size: 0.85rem;
    pointer-events: none;
}

.cch-select-arrow {
    position: absolute;
    right: 12px;
    color: #64748b;
    font-size: 0.78rem;
    pointer-events: none;
}

/* Visual Selected Emp Card */
.cch-selected-emp-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 14px;
    margin-top: 10px;
}

.cch-card-avatar {
    width: 36px;
    height: 36px;
    background: #2563eb;
    color: #ffffff;
    font-weight: 700;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
}

.cch-card-info { flex: 1; }
.cch-card-name { font-weight: 700; color: #0f172a; font-size: 0.9rem; margin-right: 6px; }
.cch-card-sub { font-size: 0.82rem; color: #64748b; margin-right: 8px; }
.cch-card-code {
    background: #e2e8f0;
    color: #334155;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
}
.cch-card-check { color: #16a34a; font-size: 1.1rem; }

/* Batch Multi-select */
.cch-batch-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.cch-batch-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
}

.cch-btn-link {
    background: none;
    border: none;
    color: #2563eb;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
    font-size: 0.8rem;
}

.cch-btn-link:hover { text-decoration: underline; }
.cch-sep { color: #cbd5e1; }

.cch-batch-list-box {
    max-height: 180px;
    overflow-y: auto;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    background: #ffffff;
    padding: 6px;
}

.cch-batch-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
    transition: background 0.15s;
}

.cch-batch-item:hover { background: #f1f5f9; }
.cch-batch-check { width: 16px; height: 16px; cursor: pointer; }
.cch-batch-name { font-weight: 600; color: #0f172a; flex: 1; }
.cch-batch-dept { color: #64748b; font-size: 0.8rem; margin-right: 8px; }
.cch-batch-count { font-size: 0.8rem; color: #64748b; margin-top: 6px; font-weight: 600; }

/* Form Row (2 Columns) */
.cch-form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 18px;
}

.cch-col { flex: 1; margin-bottom: 0; }

.cch-date-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.cch-input-icon {
    position: absolute;
    left: 12px;
    color: #64748b;
    pointer-events: none;
    font-size: 0.85rem;
}

.cch-input {
    width: 100%;
    padding: 10px 80px 10px 36px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #0f172a;
    outline: none;
}

.cch-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.cch-btn-today {
    position: absolute;
    right: 8px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
    padding: 4px 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.cch-btn-today:hover { background: #e2e8f0; color: #1e293b; }

/* Time Card Box */
.cch-time-card {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 18px;
    margin-bottom: 18px;
}

.cch-time-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.cch-time-head-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.88rem;
    font-weight: 700;
    color: #0f172a;
}

.cch-time-badge {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    font-size: 0.76rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 9999px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.cch-time-badge .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #2563eb;
}

.cch-time-boxes-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 12px;
}

.cch-time-box {
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    padding: 10px 14px;
    transition: border-color 0.2s;
}

.cch-time-box:focus-within { border-color: #2563eb; }

.cch-time-box-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.cch-time-box-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.03em;
}

.cch-time-tag {
    background: #eff6ff;
    color: #2563eb;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 4px;
}

.cch-time-input-wrap {
    display: flex;
    align-items: center;
    width: 100%;
}

.cch-time-field {
    width: 100%;
    border: none;
    outline: none;
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
    font-family: inherit;
    background: transparent;
    padding: 2px 0;
    box-sizing: border-box;
}

/* Quick Shift Buttons */
.cch-quick-select-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.cch-quick-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748b;
}

.cch-quick-btn {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
    color: #334155;
    padding: 5px 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: all 0.15s;
}

.cch-quick-btn:hover { background: #f1f5f9; border-color: #94a3b8; }

.cch-quick-btn.active {
    background: #eff6ff;
    border-color: #2563eb;
    color: #2563eb;
    font-weight: 700;
}

/* Exemption Row */
.cch-exempt-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    cursor: pointer;
}

.cch-checkbox {
    width: 18px;
    height: 18px;
    margin-top: 2px;
    accent-color: #2563eb;
    cursor: pointer;
}

.cch-exempt-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
}

.cch-exempt-desc {
    font-size: 0.78rem;
    color: #64748b;
    margin-top: 2px;
}

/* Reason Cards (4 Columns) */
.cch-reason-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.cch-reason-hint {
    font-size: 0.78rem;
    color: #64748b;
}

.cch-reasons-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.cch-reason-card {
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 14px;
    cursor: pointer;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    position: relative;
    transition: all 0.2s ease;
}

.cch-reason-card:hover {
    border-color: #94a3b8;
    transform: translateY(-1px);
}

.cch-reason-card.selected {
    border-color: #2563eb;
    background: #eff6ff;
    box-shadow: 0 0 0 1px #2563eb;
}

.cch-reason-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.icon-face { background: #fef3c7; color: #b45309; }
.icon-accident { background: #fee2e2; color: #e11d48; }
.icon-tablet { background: #f3e8ff; color: #7e22ce; }
.icon-wifi { background: #ffedd5; color: #c2410c; }

.cch-reason-info { flex: 1; padding-right: 18px; }

.cch-reason-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
    margin-bottom: 2px;
}

.cch-reason-sub {
    font-size: 0.72rem;
    color: #64748b;
    line-height: 1.3;
}

.cch-reason-check {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #2563eb;
    color: #ffffff;
    font-size: 0.6rem;
    display: none;
    align-items: center;
    justify-content: center;
}

.cch-reason-card.selected .cch-reason-check { display: flex; }

/* Textarea */
.cch-textarea {
    width: 100%;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.88rem;
    font-family: inherit;
    color: #0f172a;
    outline: none;
    resize: vertical;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.cch-textarea:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

/* Modal Foot */
.cch-modal-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 28px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    flex-shrink: 0;
    flex-wrap: wrap;
    gap: 12px;
}

.cch-foot-audit {
    font-size: 0.82rem;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 8px;
}

.cch-foot-audit i { color: #2563eb; }

.cch-foot-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.cch-btn-cancel {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #475569;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 9px 18px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.cch-btn-cancel:hover { background: #f1f5f9; color: #1e293b; }

.cch-btn-submit {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #2563eb;
    border: none;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.88rem;
    padding: 9px 20px;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    transition: all 0.2s;
}

.cch-btn-submit:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
}

.cch-btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Responsive */
@media (max-width: 992px) {
    .cch-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .cch-reasons-grid { grid-template-columns: 1fr; }
}

@media (max-width: 640px) {
    .cham-cong-ho-page { padding: 16px; }
    .cch-stats-grid { grid-template-columns: 1fr; }
    .cch-form-row { flex-direction: column; gap: 12px; }
    .cch-time-boxes-row { grid-template-columns: 1fr; }
    .cch-modal-card { border-radius: 16px; }
    .cch-modal-head, .cch-modal-body, .cch-modal-foot { padding: 16px; }
}
</style>

<!-- ========================================================= -->
<!-- JAVASCRIPT LOGIC                                          -->
<!-- ========================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Elements
    const modal = document.getElementById('cchOverrideModal');
    const btnOpen = document.getElementById('btnOpenOverrideModal');
    const btnClose = document.getElementById('btnCloseModal');
    const btnCancel = document.getElementById('btnCancelModal');
    const btnSubmit = document.getElementById('btnSubmitOverride');
    
    const btnModeSingle = document.getElementById('btnModeSingle');
    const btnModeBatch = document.getElementById('btnModeBatch');
    const groupSingle = document.getElementById('groupSingleEmployee');
    const groupBatch = document.getElementById('groupBatchEmployee');
    
    const singleEmpSelect = document.getElementById('cchSingleEmpSelect');
    const empCard = document.getElementById('cchSelectedEmpCard');
    const empCardAvatar = document.getElementById('cchEmpCardAvatar');
    const empCardName = document.getElementById('cchEmpCardName');
    const empCardDept = document.getElementById('cchEmpCardDept');
    const empCardCode = document.getElementById('cchEmpCardCode');
    
    const btnSelectAllBatch = document.getElementById('btnSelectAllBatch');
    const btnUnselectAllBatch = document.getElementById('btnUnselectAllBatch');
    const batchChecks = document.querySelectorAll('.cch-batch-check');
    const batchCountLabel = document.getElementById('cchBatchCountLabel');

    const dateInput = document.getElementById('cchDateInput');
    const btnSetToday = document.getElementById('btnSetToday');
    const shiftSelect = document.getElementById('cchShiftSelect');
    
    const timeIn = document.getElementById('cchTimeIn');
    const timeOut = document.getElementById('cchTimeOut');
    const timeInPeriod = document.getElementById('cchTimeInPeriod');
    const timeOutPeriod = document.getElementById('cchTimeOutPeriod');
    const creditText = document.getElementById('cchCreditText');
    
    const btnQuickFull = document.getElementById('btnQuickFull');
    const btnQuickMorning = document.getElementById('btnQuickMorning');
    const btnQuickAfternoon = document.getElementById('btnQuickAfternoon');
    
    const mienTruCheck = document.getElementById('cchMienTruCheck');
    const reasonCards = document.querySelectorAll('.cch-reason-card');
    const selectedReasonInput = document.getElementById('cchSelectedReason');
    const noteInput = document.getElementById('cchNoteInput');

    const filterMonth = document.getElementById('cchFilterMonth');
    const searchInput = document.getElementById('cchSearchInput');
    const btnRefresh = document.getElementById('btnRefreshHistory');
    const tbody = document.getElementById('cchHistoryTbody');

    let currentMode = 'single';
    let currentCredit = 1.0;

    // Helper format 12h label
    function formatTimeLabel(timeStr) {
        if (!timeStr) return '--:--';
        const [hStr, mStr] = timeStr.split(':');
        let h = parseInt(hStr, 10);
        const m = mStr || '00';
        const period = h >= 12 ? 'PM' : 'AM';
        let h12 = h % 12;
        if (h12 === 0) h12 = 12;
        return (h12 < 10 ? '0' + h12 : h12) + ':' + m + ' ' + period;
    }

    // Open/Close Modal
    function openModal() {
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (btnOpen) btnOpen.addEventListener('click', openModal);
    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
        }
    });

    // Switch Single / Batch Mode
    btnModeSingle.addEventListener('click', function () {
        currentMode = 'single';
        btnModeSingle.classList.add('active');
        btnModeBatch.classList.remove('active');
        groupSingle.style.display = 'block';
        groupBatch.style.display = 'none';
    });

    btnModeBatch.addEventListener('click', function () {
        currentMode = 'batch';
        btnModeBatch.classList.add('active');
        btnModeSingle.classList.remove('active');
        groupSingle.style.display = 'none';
        groupBatch.style.display = 'block';
    });

    // Single Employee Selection Change -> Update Visual Card
    singleEmpSelect.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (opt && opt.value) {
            const name = opt.getAttribute('data-name') || '';
            const dept = opt.getAttribute('data-dept') || '';
            const code = opt.getAttribute('data-code') || '';
            
            let initials = name ? name.substring(0, 1).toUpperCase() : 'NV';
            const parts = name.split(' ');
            if (parts.length > 1) {
                initials = (parts[0].substring(0, 1) + parts[parts.length - 1].substring(0, 1)).toUpperCase();
            }

            empCardAvatar.textContent = initials;
            empCardName.textContent = name;
            empCardDept.textContent = '• ' + dept;
            empCardCode.textContent = code;
            empCard.style.display = 'flex';
        } else {
            empCard.style.display = 'none';
        }
    });

    // Batch Selection Logic
    function updateBatchCount() {
        const checked = document.querySelectorAll('.cch-batch-check:checked').length;
        batchCountLabel.textContent = 'Đã chọn: ' + checked + ' nhân viên';
    }

    if (btnSelectAllBatch) {
        btnSelectAllBatch.addEventListener('click', function () {
            batchChecks.forEach(cb => cb.checked = true);
            updateBatchCount();
        });
    }

    if (btnUnselectAllBatch) {
        btnUnselectAllBatch.addEventListener('click', function () {
            batchChecks.forEach(cb => cb.checked = false);
            updateBatchCount();
        });
    }

    batchChecks.forEach(cb => cb.addEventListener('change', updateBatchCount));

    // Date today shortcut
    if (btnSetToday) {
        btnSetToday.addEventListener('click', function () {
            const today = new Date().toISOString().split('T')[0];
            dateInput.value = today;
        });
    }

    // Shift selection changes default times
    shiftSelect.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const start = opt.getAttribute('data-start') || '08:30';
        const end = opt.getAttribute('data-end') || '17:30';
        timeIn.value = start;
        timeOut.value = end;
        btnQuickFull.click();
    });

    // Period Indicator (AM/PM) & Work Credit Calculation
    function updatePeriodAndCredit() {
        const vIn = timeIn.value;
        const vOut = timeOut.value;
        
        if (vIn) {
            timeInPeriod.textContent = formatTimeLabel(vIn);
        }
        if (vOut) {
            timeOutPeriod.textContent = formatTimeLabel(vOut);
        }

        if (vIn && vOut) {
            const [h1, m1] = vIn.split(':').map(Number);
            const [h2, m2] = vOut.split(':').map(Number);
            let diffMins = (h2 * 60 + m2) - (h1 * 60 + m1);
            if (diffMins < 0) diffMins = 0;
            // Subtract 60m lunch break if >= 5 hours
            if (diffMins >= 300) diffMins -= 60;
            const hours = (diffMins / 60).toFixed(1);
            
            creditText.textContent = currentCredit.toFixed(1) + ' công chuẩn • ' + hours + ' giờ làm';
        }
    }

    timeIn.addEventListener('input', updatePeriodAndCredit);
    timeOut.addEventListener('input', updatePeriodAndCredit);

    // Quick selection buttons
    btnQuickFull.addEventListener('click', function () {
        btnQuickFull.classList.add('active');
        btnQuickMorning.classList.remove('active');
        btnQuickAfternoon.classList.remove('active');
        currentCredit = 1.0;
        
        const opt = shiftSelect.options[shiftSelect.selectedIndex];
        timeIn.value = opt ? (opt.getAttribute('data-start') || '08:30') : '08:30';
        timeOut.value = opt ? (opt.getAttribute('data-end') || '17:35') : '17:35';
        updatePeriodAndCredit();
    });

    btnQuickMorning.addEventListener('click', function () {
        btnQuickMorning.classList.add('active');
        btnQuickFull.classList.remove('active');
        btnQuickAfternoon.classList.remove('active');
        currentCredit = 0.5;
        timeIn.value = '08:30';
        timeOut.value = '12:00';
        updatePeriodAndCredit();
    });

    btnQuickAfternoon.addEventListener('click', function () {
        btnQuickAfternoon.classList.add('active');
        btnQuickFull.classList.remove('active');
        btnQuickMorning.classList.remove('active');
        currentCredit = 0.5;
        timeIn.value = '13:30';
        timeOut.value = '17:35';
        updatePeriodAndCredit();
    });

    // Reason card selection
    reasonCards.forEach(card => {
        card.addEventListener('click', function () {
            reasonCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            const r = this.getAttribute('data-reason') || '';
            selectedReasonInput.value = r;

            if (r.includes('Tai nạn') || r.includes('khuôn mặt')) {
                noteInput.placeholder = 'Vui lòng ghi rõ chi tiết sự cố (ví dụ: Nhân viên bị dị ứng/chấn thương mắt, đã xác nhận có mặt trực tiếp...)';
                noteInput.focus();
            } else {
                noteInput.placeholder = 'Ví dụ: Trưởng nhóm Backend (Anh Tuấn) đã xác nhận nhân viên có mặt tại bàn làm việc từ 08:25 sáng...';
            }
        });
    });

    // Submit Override Form
    btnSubmit.addEventListener('click', function () {
        const ngay = dateInput.value;
        const lyDo = selectedReasonInput.value;
        const maCa = shiftSelect.value || 1;
        const vIn = timeIn.value || '08:30';
        const vOut = timeOut.value || '17:35';
        const mienTru = mienTruCheck.checked ? 1 : 0;
        const ghiChu = noteInput.value.trim();

        if (!ngay) {
            alert('Vui lòng chọn ngày chấm công.');
            return;
        }

        if (!lyDo) {
            alert('Vui lòng chọn lý do sự cố kỹ thuật.');
            return;
        }

        const formData = new FormData();
        formData.append('mode', currentMode);
        formData.append('ngay', ngay);
        formData.append('maCa', maCa);
        formData.append('gioVao', vIn);
        formData.append('gioRa', vOut);
        formData.append('congChuan', currentCredit);
        formData.append('mienTruDiTre', mienTru);
        formData.append('lyDo', lyDo);
        formData.append('ghiChu', ghiChu);

        if (currentMode === 'single') {
            const maNV = singleEmpSelect.value;
            if (!maNV) {
                alert('Vui lòng chọn nhân viên cần chấm công hộ.');
                return;
            }
            formData.append('maNV', maNV);
        } else {
            const selectedChecks = Array.from(document.querySelectorAll('.cch-batch-check:checked')).map(cb => cb.value);
            if (selectedChecks.length === 0) {
                alert('Vui lòng chọn ít nhất 1 nhân viên để chấm hàng loạt.');
                return;
            }
            selectedChecks.forEach(id => formData.append('maNVs[]', id));
        }

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang ghi nhận...';

        fetch('index.php?page=hr-api-override-attendance', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Lưu & Xác nhận chấm hộ';

            if (data.success) {
                alert(data.message || 'Chấm công hộ thành công!');
                closeModal();
                noteInput.value = '';
                loadHistory();
            } else {
                alert(data.message || 'Có lỗi xảy ra khi chấm công hộ.');
            }
        })
        .catch(err => {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Lưu & Xác nhận chấm hộ';
            alert('Lỗi kết nối máy chủ: ' + err.message);
        });
    });

    // Reload History Table via API
    function loadHistory() {
        const month = filterMonth.value;
        const q = searchInput.value.trim();

        fetch(`index.php?page=hr-api-override-history&month=${encodeURIComponent(month)}&q=${encodeURIComponent(q)}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            renderHistoryTable(data.data || []);
            updateStats(data.data || []);
        })
        .catch(console.error);
    }

    function renderHistoryTable(rows) {
        if (!rows || rows.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="cch-empty-cell">
                        <div class="cch-empty-state">
                            <i class="fa-regular fa-folder-open"></i>
                            <p>Chưa có lượt chấm công hộ nào trong tháng ${filterMonth.value}</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        rows.forEach(row => {
            const empName = row.tenNhanVien || 'Nhân viên';
            const empDept = row.phongBan || 'N/A';
            const empCode = row.maNhanVienCode || ('NV' + String(row.maNguoiDuocChamHo).padStart(4, '0'));
            
            let initials = empName ? empName.substring(0, 1).toUpperCase() : 'NV';
            const parts = empName.split(' ');
            if (parts.length > 1) {
                initials = (parts[0].substring(0, 1) + parts[parts.length - 1].substring(0, 1)).toUpperCase();
            }

            const r = row.lyDo || '';
            let badgeClass = 'badge-other';
            if (r.toLowerCase().includes('tai nạn') || r.toLowerCase().includes('tổn thương') || r.toLowerCase().includes('chấn thương') || r.toLowerCase().includes('dị ứng') || r.toLowerCase().includes('sự cố khuôn mặt')) {
                badgeClass = 'badge-accident';
            } else if (r.toLowerCase().includes('faceid') || r.toLowerCase().includes('nhận diện')) {
                badgeClass = 'badge-face';
            } else if (r.toLowerCase().includes('tablet') || r.toLowerCase().includes('nguồn') || r.toLowerCase().includes('treo')) {
                badgeClass = 'badge-tablet';
            } else if (r.toLowerCase().includes('wi-fi') || r.toLowerCase().includes('wifi') || r.toLowerCase().includes('mạng')) {
                badgeClass = 'badge-wifi';
            }

            const timeIn = row.gioVao ? row.gioVao.substring(11, 16) : '--:--';
            const timeOut = row.gioRa ? row.gioRa.substring(11, 16) : '--:--';
            const ngayFormatted = row.ngayChamHo ? row.ngayChamHo.split('-').reverse().join('/') : '';
            const createdFormatted = row.ngayTao ? (row.ngayTao.substring(8, 10) + '/' + row.ngayTao.substring(5, 7) + '/' + row.ngayTao.substring(0, 4) + ' ' + row.ngayTao.substring(11, 16)) : '';

            html += `
                <tr>
                    <td>
                        <div class="cch-emp-cell">
                            <div class="cch-avatar-box">${initials}</div>
                            <div>
                                <div class="cch-emp-name">${escapeHtml(empName)}</div>
                                <div class="cch-emp-meta">
                                    <span>${escapeHtml(empDept)}</span>
                                    <span class="cch-code-pill">${escapeHtml(empCode)}</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="cch-date-val">${ngayFormatted}</div>
                        <div class="cch-shift-val">${escapeHtml(row.tenCa || 'Ca Hành chính')}</div>
                    </td>
                    <td>
                        <div class="cch-time-val">
                            <span class="time-in">${timeIn}</span>
                            <i class="fa-solid fa-arrow-right-long time-arrow"></i>
                            <span class="time-out">${timeOut}</span>
                        </div>
                    </td>
                    <td>
                        <span class="cch-cong-badge">${parseFloat(row.congChuan || 1.0).toFixed(1)} công</span>
                    </td>
                    <td>
                        <div class="cch-reason-badge ${badgeClass}">${escapeHtml(row.lyDo)}</div>
                        ${parseInt(row.mienTruDiTre, 10) ? '<div class="cch-exempt-badge"><i class="fa-solid fa-check"></i> Miễn trừ phạt trễ</div>' : ''}
                    </td>
                    <td>
                        <div class="cch-note-text" title="${escapeHtml(row.ghiChuHR || '')}">
                            ${row.ghiChuHR ? escapeHtml(row.ghiChuHR) : '<span class="cch-muted">Không có ghi chú</span>'}
                        </div>
                    </td>
                    <td>
                        <div class="cch-hr-name"><i class="fa-solid fa-shield-halved"></i> ${escapeHtml(row.tenHR || 'HR')}</div>
                    </td>
                    <td>
                        <div class="cch-created-time">${createdFormatted}</div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    function updateStats(rows) {
        let total = rows.length;
        let face = 0, accident = 0, device = 0;
        rows.forEach(r => {
            const reason = (r.lyDo || '').toLowerCase();
            if (reason.includes('tai nạn') || reason.includes('tổn thương') || reason.includes('chấn thương') || reason.includes('dị ứng') || reason.includes('sự cố khuôn mặt') || reason.includes('khác')) {
                accident++;
            } else if (reason.includes('faceid') || reason.includes('nhận diện')) {
                face++;
            } else if (reason.includes('tablet') || reason.includes('nguồn') || reason.includes('treo') || reason.includes('wi-fi') || reason.includes('wifi') || reason.includes('mạng')) {
                device++;
            } else {
                accident++;
            }
        });

        document.getElementById('statTotal').textContent = total;
        document.getElementById('statFace').textContent = face;
        document.getElementById('statAccident').textContent = accident;
        document.getElementById('statDevice').textContent = device;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    // Filter Listeners
    filterMonth.addEventListener('change', loadHistory);
    
    let searchTimeout;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadHistory, 300);
    });

    if (btnRefresh) btnRefresh.addEventListener('click', loadHistory);

    // Initial check on page load
    updatePeriodAndCredit();
});
</script>

<?php include 'app/views/layouts/footer.php'; ?>
