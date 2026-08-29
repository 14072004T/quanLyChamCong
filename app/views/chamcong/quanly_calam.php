<?php
if (!isset($_SESSION['user'])) { header('Location: index.php?page=login'); exit(); }
if (($_SESSION['role'] ?? '') !== 'hr') { header('Location: index.php?page=home'); exit(); }

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

function shiftDisplayText($value) {
    $text = trim((string)($value ?? ''));
    if ($text !== '' && preg_match('/(?:Ã.|Â.|á»|áº|Ä.|Ä‘)/u', $text)) {
        $fixed = @utf8_decode($text);
        if ($fixed !== false && $fixed !== '') $text = $fixed;
    }
    return $text;
}
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<style>
/* Modal Styles */
.lr-modal { display:none; position:fixed; z-index:9999; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; padding:20px; backdrop-filter:blur(2px); }
.lr-modal-content { background:#fff; border-radius:12px; width:100%; max-width:500px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1); overflow:hidden; animation:modalSlideUp 0.3s ease; }
@keyframes modalSlideUp { from { transform:translateY(20px); opacity:0; } to { transform:translateY(0); opacity:1; } }
.lr-modal-header { padding:16px 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
.lr-modal-body { padding:20px; }
.lr-modal-footer { padding:12px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; }
.lr-detail-row { display:flex; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:8px; }
.lr-detail-label { width:120px; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; }
.lr-detail-val { flex:1; font-size:13px; color:#1e293b; font-weight:500; }
.lr-badge-approved { background: #10b981; color: #fff; }
.lr-badge-pending  { background: #f59e0b; color: #fff; }
.lr-badge-rejected { background: #ef4444; color: #fff; }
.lr-badge { padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 3px; }
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">

        <div class="panel">
            <h2 style="border:none;padding:0;margin:0 0 6px;">QUẢN LÝ CA LÀM VIỆC</h2>
            <p style="color:#64748b;margin:0;">Ca làm việc được gán tự động theo tháng (Hành chính). Nhân viên đăng ký OT riêng, hệ thống tự tính thêm giờ.</p>
            <a href="index.php?page=tablet-cham-cong" class="btn btn-primary" style="margin-top:14px;display:inline-flex;align-items:center;gap:8px;text-decoration:none;"><i class="fas fa-tablet-screen-button"></i> Mở tablet chấm công</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Lịch phân ca theo tháng -->
        <div class="panel">
            <div class="panel-header" style="margin-bottom:14px;">
                <h3 style="margin:0;"><i class="fas fa-calendar-alt" style="color:#3b82f6;"></i> LỊCH PHÂN CA THÁNG</h3>
                <div class="panel-header-actions">
                    <div class="form-group" style="margin:0;">
                        <input type="month" id="shift-month-picker" value="<?= date('Y-m') ?>" style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:0.85em;">
                    </div>
                </div>
            </div>
            <div class="alert alert-info" style="margin-bottom:14px;">
                <i class="fas fa-info-circle"></i>
                <div>Tất cả nhân viên được gán <strong>ca Hành chính (HC: 08:00 - 17:00)</strong> tự động. Ngày T7, CN mặc định <strong>OFF</strong> nhưng có thể đổi sang ca làm việc khác trong lịch phân ca. Ngày thường cũng có thể chọn ca <strong>OFF</strong> khi cần cho nhân viên nghỉ. Nhân viên đăng ký OT sẽ hiển thị thêm badge <span class="shift-cell shift-ot" style="padding:2px 8px;font-size:0.75em;">OT</span></div>
            </div>
            <div class="attendance-grid-wrapper">
                <table class="attendance-grid" id="monthly-shift-grid">
                    <thead id="shift-grid-head"></thead>
                    <tbody id="shift-grid-body">
                        <tr><td colspan="32" class="empty-state">Đang tải lịch phân ca...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Danh sách Ca Làm Việc + Tạo ca mới -->
        <div class="panel">
            <div class="shift-layout">
                <div>
                    <h3><i class="fas fa-list" style="color:#3b82f6;"></i> Danh sách Ca Làm Việc</h3>
                    <table class="table" id="shift-list-table">
                        <thead>
                            <tr>
                                <th>TÊN CA</th>
                                <th>KÝ HIỆU</th>
                                <th>MÀU</th>
                                <th>GIỜ BẮT ĐẦU</th>
                                <th>GIỜ KẾT THÚC</th>
                                <th>TRẠNG THÁI</th>
                                <th>THAO TÁC</th>
                            </tr>
                        </thead>
                        <tbody id="shift-list-body">
                            <?php if (!empty($shifts)): ?>
                                <?php foreach ($shifts as $shift): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars(shiftDisplayText($shift['tenCa'])) ?></strong></td>
                                        <td><strong><?= htmlspecialchars($shift['kyHieu'] ?? '') ?></strong></td>
                                        <td><span style="display:inline-block;width:18px;height:18px;border-radius:4px;background:<?= htmlspecialchars($shift['mauSac'] ?? '#3b82f6') ?>;vertical-align:middle;"></span> <?= htmlspecialchars($shift['mauSac'] ?? '#3b82f6') ?></td>
                                        <td><?= htmlspecialchars(substr($shift['gioBatDau'], 0, 5)) ?></td>
                                        <td><?= htmlspecialchars(substr($shift['gioKetThuc'], 0, 5)) ?></td>
                                        <td><span class="trangThai-badge <?= (int)$shift['hoatDong'] ? 'trangThai-approved' : 'trangThai-rejected' ?>"><?= (int)$shift['hoatDong'] ? 'Đang dùng' : 'Tắt' ?></span></td>
                                        <td><button type="button" class="btn btn-secondary btn-sm edit-shift" data-id="<?= (int)$shift['id'] ?>" data-name="<?= htmlspecialchars(shiftDisplayText($shift['tenCa']), ENT_QUOTES) ?>" data-code="<?= htmlspecialchars($shift['kyHieu'] ?? '', ENT_QUOTES) ?>" data-color="<?= htmlspecialchars($shift['mauSac'] ?? '#3b82f6', ENT_QUOTES) ?>" data-start="<?= htmlspecialchars(substr($shift['gioBatDau'], 0, 5), ENT_QUOTES) ?>" data-end="<?= htmlspecialchars(substr($shift['gioKetThuc'], 0, 5), ENT_QUOTES) ?>"><i class="fas fa-pen"></i> Sửa</button> <form method="post" onsubmit="return confirm('Xóa ca này?');" style="display:inline;"><input type="hidden" name="form_action" value="delete_shift"><input type="hidden" name="id" value="<?= (int)$shift['id'] ?>"><button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Xóa</button></form></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="empty-state">Chưa có ca làm việc.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-primary btn-sm" id="toggle-add-shift" style="margin-top:12px;"><i class="fas fa-plus"></i> Thêm ca mới</button>
                </div>
                <div class="shift-list-side" id="add-shift-form-panel" style="display:none;">
                    <h4 id="shift-form-title">Tạo ca mới</h4>
                    <form id="shift-form">
                        <input type="hidden" name="id" value="0">
                        <div class="form-group">
                            <label>Tên ca *</label>
                            <input type="text" name="tenCa" placeholder="VD: Hành chính" required>
                        </div>
                        <div class="form-group">
                            <label>Ký hiệu *</label>
                            <input type="text" name="kyHieu" placeholder="VD: HC" maxlength="20" required>
                        </div>
                        <div class="form-group">
                            <label>Màu hiển thị *</label>
                            <input type="color" name="mauSac" value="#3b82f6" style="height:38px;width:100%;padding:3px;">
                        </div>
                        <div class="form-group">
                            <label>Giờ bắt đầu *</label>
                            <input type="time" name="gioBatDau" required>
                        </div>
                        <div class="form-group">
                            <label>Giờ kết thúc *</label>
                            <input type="time" name="gioKetThuc" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm" style="width:100%;">Lưu ca</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- DETAIL MODAL -->
        <div id="detailModal" class="lr-modal">
            <div class="lr-modal-content">
                <div class="lr-modal-header">
                    <h4 style="margin:0; font-size:15px; font-weight:700; color:#1e293b;"><i class="fas fa-circle-info" style="color:#4f6ef7"></i> Chi tiết đơn nghỉ</h4>
                    <button onclick="closeModal()" style="border:none; background:none; cursor:pointer; color:#94a3b8; font-size:18px;"><i class="fas fa-times"></i></button>
                </div>
                <div class="lr-modal-body" id="modalBody">
                    <p style="text-align:center; color:#64748b;">Đang tải...</p>
                </div>
                <div class="lr-modal-footer">
                    <button class="btn btn-secondary btn-sm" onclick="closeModal()">Đóng</button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var monthPicker = document.getElementById('shift-month-picker');
    var gridHead = document.getElementById('shift-grid-head');
    var gridBody = document.getElementById('shift-grid-body');
    var shiftForm = document.getElementById('shift-form');
    var toggleBtn = document.getElementById('toggle-add-shift');
    var formPanel = document.getElementById('add-shift-form-panel');
    var shiftFormTitle = document.getElementById('shift-form-title');

    function escapeHtml(val) {
        return String(val ?? '').replace(/[&<>"]/g, function(c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
        });
    }

    // Modal Logic
    window.openModal = function(id) {
        if (!id || id == 0) return;
        var modal = document.getElementById('detailModal');
        var body = document.getElementById('modalBody');
        modal.style.display = 'flex';
        body.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin" style="font-size:24px; color:#4f6ef7"></i><p style="margin-top:10px; color:#64748b">Đang lấy dữ liệu...</p></div>';

        fetch('index.php?page=get-leave-detail&id=' + id)
            .then(function(res) { return res.json(); })
            .then(function(res) {
                if (res.success) {
                    var d = res.data;
                    var statusClass = 'lr-badge-' + d.trangThai;
                    var statusText = d.trangThai === 'approved' ? 'Đã duyệt' : (d.trangThai === 'rejected' ? 'Từ chối' : 'Chờ duyệt');
                    
                    body.innerHTML = 
                        '<div class="lr-detail-row"><div class="lr-detail-label">Loại nghỉ</div><div class="lr-detail-val">' + d.leave_type_text + '</div></div>' +
                        '<div class="lr-detail-row"><div class="lr-detail-label">Thời gian</div><div class="lr-detail-val">' + d.from_date_fmt + ' đến ' + d.to_date_fmt + '</div></div>' +
                        '<div class="lr-detail-row"><div class="lr-detail-label">Lý do</div><div class="lr-detail-val">' + d.lyDo + '</div></div>' +
                        '<div class="lr-detail-row"><div class="lr-detail-label">Trạng thái</div><div class="lr-detail-val"><span class="lr-badge ' + statusClass + '">' + statusText + '</span></div></div>' +
                        (d.approver_name ? '<div class="lr-detail-row"><div class="lr-detail-label">Người duyệt</div><div class="lr-detail-val">' + d.approver_name + '</div></div>' : '') +
                        (d.approved_at_fmt ? '<div class="lr-detail-row"><div class="lr-detail-label">Ngày duyệt</div><div class="lr-detail-val">' + d.approved_at_fmt + '</div></div>' : '') +
                        (d.ghiChuNS ? '<div class="lr-detail-row"><div class="lr-detail-label">Phản hồi</div><div class="lr-detail-val" style="color:#ef4444">' + d.ghiChuNS + '</div></div>' : '') +
                        '<div class="lr-detail-row" style="border:none"><div class="lr-detail-label">Ngày gửi</div><div class="lr-detail-val">' + d.created_at_fmt + '</div></div>';
                } else {
                    body.innerHTML = '<p style="color:#ef4444; text-align:center;">Lỗi: ' + res.message + '</p>';
                }
            })
            .catch(function(err) {
                body.innerHTML = '<p style="color:#ef4444; text-align:center;">Không thể kết nối máy chủ.</p>';
            });
    };

    window.closeModal = function() {
        document.getElementById('detailModal').style.display = 'none';
    };

    window.addEventListener('click', function(event) {
        var modal = document.getElementById('detailModal');
        if (event.target == modal) {
            closeModal();
        }
    });

    toggleBtn.addEventListener('click', function () {
        formPanel.style.display = formPanel.style.display === 'none' ? 'block' : 'none';
        if (formPanel.style.display === 'block') {
            shiftForm.reset();
            shiftForm.elements.id.value = '0';
            shiftForm.elements.mauSac.value = '#3b82f6';
            if (shiftFormTitle) shiftFormTitle.textContent = 'Tạo ca mới';
        }
    });

    document.querySelectorAll('.edit-shift').forEach(function(button) {
        button.addEventListener('click', function() {
            shiftForm.elements.id.value = this.dataset.id;
            shiftForm.elements.tenCa.value = this.dataset.name;
            shiftForm.elements.kyHieu.value = this.dataset.code;
            shiftForm.elements.mauSac.value = this.dataset.color;
            shiftForm.elements.gioBatDau.value = this.dataset.start;
            shiftForm.elements.gioKetThuc.value = this.dataset.end;
            if (shiftFormTitle) shiftFormTitle.textContent = 'Sửa ca làm việc';
            formPanel.style.display = 'block';
            formPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    function getDaysInMonth(monthKey) {
        var parts = monthKey.split('-');
        return new Date(parseInt(parts[0]), parseInt(parts[1]), 0).getDate();
    }

    function getDayOfWeek(monthKey, day) {
        var parts = monthKey.split('-');
        return new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, day).getDay();
    }

    function getDayLabel(dow) {
        return ['CN','T2','T3','T4','T5','T6','T7'][dow];
    }

    function loadMonthlyShifts() {
        var month = monthPicker.value;
        var days = getDaysInMonth(month);

        // Build header
        var headHtml = '<tr><th>Nhân viên</th>';
        for (var d = 1; d <= days; d++) {
            var dow = getDayOfWeek(month, d);
            var isWeekend = (dow === 0 || dow === 6);
            headHtml += '<th style="' + (isWeekend ? 'color:#f59e0b;' : '') + '">' + d + '<br><small>' + getDayLabel(dow) + '</small></th>';
        }
        headHtml += '</tr>';
        gridHead.innerHTML = headHtml;

        // Fetch employees + attendance data
        Promise.all([
            fetch('index.php?page=hr-api-employees&limit=0', { headers: { 'Accept': 'application/json' } }).then(function(r) { return r.json(); }),
            fetch('index.php?page=hr-api-payroll&month=' + encodeURIComponent(month), { headers: { 'Accept': 'application/json' } }).then(function(r) { return r.json(); }),
            fetch('index.php?page=hr-api-shifts', { headers: { 'Accept': 'application/json' } }).then(function(r) { return r.json(); })
        ]).then(function(results) {
            var employees = (results[0].data || []).filter(function(e) { return e.trangThai == 1; });
            var payrollData = results[1].data || [];
            var otSchedule = results[1].otSchedule || {};
            var shifts = (results[2].data || []).filter(function(s) { return Number(s.hoatDong) === 1; });

            // Map payroll by maND
            var payrollMap = {};
            payrollData.forEach(function(p) { payrollMap[p.maND] = p; });

            if (!employees.length) {
                gridBody.innerHTML = '<tr><td colspan="' + (days + 1) + '" class="empty-state">Không có nhân viên.</td></tr>';
                return;
            }

            gridBody.innerHTML = employees.map(function(emp) {
                var payroll = payrollMap[emp.maND] || {};
                var employeeOtSchedule = otSchedule[String(emp.maND)] || otSchedule[emp.maND] || {};
                var totalDays = 0;
                var cells = '<td>' + escapeHtml(emp.hoTen) + '<br><small style="color:#64748b;">' + escapeHtml(emp.phongBan || '') + '</small></td>';

                var empCreatedDate = emp.ngayTao ? emp.ngayTao.substring(0, 10) : '';

                for (var d = 1; d <= days; d++) {
                    var dow = getDayOfWeek(month, d);
                    var isWeekend = (dow === 0 || dow === 6);
                    var currentDate = month + '-' + String(d).padStart(2, '0');
                    var otInfo = employeeOtSchedule[currentDate] || null;
                    var dayBreakdown = (payroll.daily_breakdown && payroll.daily_breakdown[currentDate]) ? payroll.daily_breakdown[currentDate] : null;
                    var isLeave = dayBreakdown && dayBreakdown.day_type === 'leave';
                    var isHoliday = dayBreakdown && dayBreakdown.day_type === 'holiday';

                    cells += '<td>';
                    
                    // Chỉ hiển thị ca làm kể từ ngày nhân viên được tạo
                    if (empCreatedDate && currentDate < empCreatedDate) {
                        cells += '<span style="color:#e2e8f0;">-</span>';
                    } else {
                        if (isLeave) {
                            var tooltip = dayBreakdown.leave_reason ? escapeHtml(dayBreakdown.day_type_label + ': ' + dayBreakdown.leave_reason) : escapeHtml(dayBreakdown.day_type_label || 'Nghỉ phép');
                            var leaveId = dayBreakdown.leave_id || 0;
                            cells += '<span onclick="openModal(' + leaveId + ')" class="shift-cell shift-off" style="background-color:#ef4444;color:white;border-color:#ef4444;display:inline-block;cursor:pointer;" title="' + tooltip + '">OFF</span>';
                        } else if (isHoliday) {
                            cells += '<span class="shift-cell shift-off" style="background-color:#f59e0b;color:white;border-color:#f59e0b;" title="' + escapeHtml(dayBreakdown.day_type_label || 'Ngày lễ') + '">LỄ</span>';
                        } else if (isWeekend) {
                            totalDays++;
                            if (shifts.length) {
                                var weekendDefault = shifts.find(function(shift) { return shift.kyHieu === 'OFF'; }) || shifts[0];
                                var activeShiftId = (dayBreakdown && dayBreakdown.maCa) ? parseInt(dayBreakdown.maCa, 10) : parseInt(weekendDefault.id, 10);
                                var activeShift = shifts.find(function(shift) { return parseInt(shift.id, 10) === activeShiftId; }) || weekendDefault;
                                
                                var weekendOptions = shifts.map(function(shift) {
                                    var selected = parseInt(shift.id, 10) === activeShiftId ? ' selected' : '';
                                    return '<option value="' + shift.id + '" data-color="' + escapeHtml(shift.mauSac || '#3b82f6') + '"' + selected + '>' + escapeHtml(shift.kyHieu || shift.tenCa) + '</option>';
                                }).join('');
                                cells += '<select class="shift-cell shift-picker" data-ma-nd="' + emp.maND + '" data-date="' + currentDate + '" title="Đổi ca" style="background:' + escapeHtml(activeShift.mauSac || '#94a3b8') + ';color:#fff;border-color:' + escapeHtml(activeShift.mauSac || '#94a3b8') + '" onchange="changeMonthlyShift(this)">' + weekendOptions + '</select>';
                            } else {
                                cells += '<span class="shift-cell shift-off">OFF</span>';
                            }
                        } else {
                            totalDays++;
                            if (shifts.length) {
                                var weekdayDefault = shifts.find(function(shift) { return shift.kyHieu === 'HC'; }) || shifts[0];
                                var activeShiftId = (dayBreakdown && dayBreakdown.maCa) ? parseInt(dayBreakdown.maCa, 10) : parseInt(weekdayDefault.id, 10);
                                var activeShift = shifts.find(function(shift) { return parseInt(shift.id, 10) === activeShiftId; }) || weekdayDefault;

                                var shiftOptions = shifts.map(function(shift) {
                                    var selected = parseInt(shift.id, 10) === activeShiftId ? ' selected' : '';
                                    return '<option value="' + shift.id + '" data-color="' + escapeHtml(shift.mauSac || '#3b82f6') + '"' + selected + '>' + escapeHtml(shift.kyHieu || shift.tenCa) + '</option>';
                                }).join('');
                                cells += '<select class="shift-cell shift-picker" data-ma-nd="' + emp.maND + '" data-date="' + currentDate + '" title="Đổi ca" style="background:' + escapeHtml(activeShift.mauSac || '#3b82f6') + ';color:#fff;border-color:' + escapeHtml(activeShift.mauSac || '#3b82f6') + '" onchange="changeMonthlyShift(this)">' + shiftOptions + '</select>';
                            } else {
                                cells += '<span class="shift-cell shift-hc">-</span>';
                            }
                        }

                        if (otInfo) {
                            cells += '<span class="shift-cell shift-ot" title="' + escapeHtml(otInfo.lyDo || 'OT đã duyệt') + '">OT</span>';
                        }
                    }
                    cells += '</td>';
                }
                return '<tr>' + cells + '</tr>';
            }).join('');
        }).catch(function() {
            gridBody.innerHTML = '<tr><td colspan="' + (days + 1) + '" class="empty-state">Lỗi tải dữ liệu.</td></tr>';
        });
    }

    window.changeMonthlyShift = function(select) {
        var selectedOption = select.options[select.selectedIndex];
        var color = selectedOption ? selectedOption.dataset.color : '#3b82f6';
        select.style.backgroundColor = color;
        select.style.borderColor = color;
        var formData = new FormData();
        formData.append('maND', select.dataset.maNd);
        formData.append('maCa', select.value);
        formData.append('hieuLucTu', select.dataset.date);
        fetch('index.php?page=hr-api-shift-assignments', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(result) {
                if (!result.success) alert(result.message || 'Không thể đổi ca');
            })
            .catch(function() { alert('Không thể kết nối máy chủ khi đổi ca.'); });
    };

    monthPicker.addEventListener('change', loadMonthlyShifts);

    // Save new shift via API
    shiftForm.addEventListener('submit', function(e) {
        e.preventDefault();
        fetch('index.php?page=hr-api-shifts', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(shiftForm)
        })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            alert(json.message || 'OK');
            if (json.success) { shiftForm.reset(); location.reload(); }
        });
    });

    loadMonthlyShifts();
});
</script>
<?php include 'app/views/layouts/footer.php'; ?>
