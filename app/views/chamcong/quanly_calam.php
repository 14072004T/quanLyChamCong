<?php
if (!isset($_SESSION['user'])) { header('Location: index.php?page=login'); exit(); }
if (($_SESSION['role'] ?? '') !== 'hr') { header('Location: index.php?page=home'); exit(); }

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">

        <div class="panel">
            <h2 style="border:none;padding:0;margin:0 0 6px;">QUẢN LÝ CA LÀM VIỆC</h2>
            <p style="color:#64748b;margin:0;">Ca làm việc được gán tự động theo tháng (Hành chính). Nhân viên đăng ký OT riêng, hệ thống tự tính thêm giờ.</p>
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
                <div>Tất cả nhân viên được gán <strong>ca Hành chính (HC: 08:00 - 17:00)</strong> tự động. Ngày T7, CN tự động OFF. Nhân viên đăng ký OT sẽ hiển thị thêm badge <span class="shift-cell shift-ot" style="padding:2px 8px;font-size:0.75em;">OT</span></div>
            </div>
            <div class="attendance-grid-wrapper">
                <table class="attendance-grid" id="monthly-shift-grid">
                    <thead id="shift-grid-head"></thead>
                    <tbody id="shift-grid-body">
                        <tr><td colspan="33" class="empty-state">Đang tải lịch phân ca...</td></tr>
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
                                <th>GIỜ BẮT ĐẦU</th>
                                <th>GIỜ KẾT THÚC</th>
                                <th>TRẠNG THÁI</th>
                                <th>SỐ NV GÁN</th>
                            </tr>
                        </thead>
                        <tbody id="shift-list-body">
                            <?php if (!empty($shifts)): ?>
                                <?php foreach ($shifts as $shift): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($shift['shift_name']) ?></strong></td>
                                        <td><?= htmlspecialchars(substr($shift['start_time'], 0, 5)) ?></td>
                                        <td><?= htmlspecialchars(substr($shift['end_time'], 0, 5)) ?></td>
                                        <td><span class="status-badge <?= (int)$shift['is_active'] ? 'status-approved' : 'status-rejected' ?>"><?= (int)$shift['is_active'] ? 'Đang dùng' : 'Tắt' ?></span></td>
                                        <td><?= (int)($shift['assigned_count'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="empty-state">Chưa có ca làm việc.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-primary btn-sm" id="toggle-add-shift" style="margin-top:12px;"><i class="fas fa-plus"></i> Thêm ca mới</button>
                </div>
                <div class="shift-list-side" id="add-shift-form-panel" style="display:none;">
                    <h4>Tạo ca mới</h4>
                    <form id="shift-form">
                        <input type="hidden" name="id" value="0">
                        <div class="form-group">
                            <label>Tên ca *</label>
                            <input type="text" name="shift_name" placeholder="VD: HC, Ca sáng, OT" required>
                        </div>
                        <div class="form-group">
                            <label>Giờ bắt đầu *</label>
                            <input type="time" name="start_time" required>
                        </div>
                        <div class="form-group">
                            <label>Giờ kết thúc *</label>
                            <input type="time" name="end_time" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm" style="width:100%;">Lưu ca</button>
                    </form>
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

    function escapeHtml(val) {
        return String(val ?? '').replace(/[&<>"]/g, function(c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];
        });
    }

    toggleBtn.addEventListener('click', function () {
        formPanel.style.display = formPanel.style.display === 'none' ? 'block' : 'none';
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
        headHtml += '<th>TỔNG CÔNG</th></tr>';
        gridHead.innerHTML = headHtml;

        // Fetch employees + attendance data
        Promise.all([
            fetch('index.php?page=hr-api-employees', { headers: { 'Accept': 'application/json' } }).then(function(r) { return r.json(); }),
            fetch('index.php?page=hr-api-payroll&month=' + encodeURIComponent(month), { headers: { 'Accept': 'application/json' } }).then(function(r) { return r.json(); })
        ]).then(function(results) {
            var employees = (results[0].data || []).filter(function(e) { return e.trangThai == 1; });
            var payrollData = results[1].data || [];
            var otSchedule = results[1].otSchedule || {};

            // Map payroll by maND
            var payrollMap = {};
            payrollData.forEach(function(p) { payrollMap[p.maND] = p; });

            if (!employees.length) {
                gridBody.innerHTML = '<tr><td colspan="' + (days + 2) + '" class="empty-state">Không có nhân viên.</td></tr>';
                return;
            }

            gridBody.innerHTML = employees.map(function(emp) {
                var payroll = payrollMap[emp.maND] || {};
                var employeeOtSchedule = otSchedule[String(emp.maND)] || otSchedule[emp.maND] || {};
                var totalDays = 0;
                var cells = '<td>' + escapeHtml(emp.hoTen) + '<br><small style="color:#64748b;">' + escapeHtml(emp.phongBan || '') + '</small></td>';

                for (var d = 1; d <= days; d++) {
                    var dow = getDayOfWeek(month, d);
                    var isWeekend = (dow === 0 || dow === 6);
                    var currentDate = month + '-' + String(d).padStart(2, '0');
                    var otInfo = employeeOtSchedule[currentDate] || null;
                    if (isWeekend) {
                        cells += '<td>';
                        cells += '<span class="shift-cell shift-off">OFF</span>';
                        if (otInfo) {
                            cells += '<span class="shift-cell shift-ot" title="' + escapeHtml(otInfo.reason || 'OT đã duyệt') + '">OT</span>';
                        }
                        cells += '</td>';
                    } else {
                        totalDays++;
                        cells += '<td>';
                        cells += '<span class="shift-cell shift-hc">HC</span>';
                        if (otInfo) {
                            cells += '<span class="shift-cell shift-ot" title="' + escapeHtml(otInfo.reason || 'OT đã duyệt') + '">OT</span>';
                        }
                        cells += '</td>';
                    }
                }
                var actualDays = Number(payroll.work_days || 0);
                cells += '<td class="col-total">' + (actualDays > 0 ? actualDays : totalDays) + '</td>';
                return '<tr>' + cells + '</tr>';
            }).join('');
        }).catch(function() {
            gridBody.innerHTML = '<tr><td colspan="' + (days + 2) + '" class="empty-state">Lỗi tải dữ liệu.</td></tr>';
        });
    }

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
