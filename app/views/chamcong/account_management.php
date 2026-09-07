<div class="accounts-container">
    <style>
        .accounts-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        .accounts-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            padding: 24px 28px;
            border-radius: 16px;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .accounts-header h2 {
            margin: 0 0 6px 0;
            font-size: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .accounts-header p {
            margin: 0;
            color: #94a3b8;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-refresh {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 9px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-refresh:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        /* Filter Section */
        .filter-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }

        .filter-input, .filter-select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 9px;
            font-size: 14px;
            color: #1e293b;
            background-color: #f8fafc;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .filter-input:focus, .filter-select:focus {
            outline: none;
            border-color: #3b82f6;
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .filter-actions-btns {
            display: flex;
            gap: 10px;
        }

        .btn-filter-submit {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 9px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            flex: 1;
            justify-content: center;
        }

        .btn-filter-submit:hover {
            background: #1d4ed8;
        }

        .btn-filter-reset {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            border-radius: 9px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }

        .btn-filter-reset:hover {
            background: #e2e8f0;
            color: #334155;
        }

        /* Matrix Reference Card */
        .matrix-info-card {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #475569;
        }

        .matrix-info-header {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }

        .matrix-table th, .matrix-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 10px;
            text-align: center;
        }

        .matrix-table th {
            background: #edf2f7;
            color: #334155;
            font-weight: 600;
        }

        .matrix-table tr:nth-child(even) {
            background: #ffffff;
        }

        /* Table Area */
        .table-card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .accounts-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .accounts-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            padding: 14px 16px;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        .accounts-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }

        .accounts-table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Role Badges */
        .badge-role {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-role.role-hr {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .badge-role.role-tech {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .badge-role.role-manager {
            background: #faf5ff;
            color: #9333ea;
            border: 1px solid #e9d5ff;
        }

        .badge-role.role-nhanvien {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        /* Account Status Badge */
        .badge-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #dcfce7;
            color: #15803d;
        }

        .status-locked {
            background: #fee2e2;
            color: #b91c1c;
        }

        /* Action buttons */
        .btn-action-role {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 7px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-action-role:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-action-toggle {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 7px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-action-toggle:hover {
            background: #e2e8f0;
        }

        /* Modal styling */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-box {
            background: #ffffff;
            border-radius: 16px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: translateY(20px);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .modal-overlay.active .modal-box {
            transform: translateY(0);
        }

        .modal-header {
            background: #1e293b;
            color: white;
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 20px;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: white;
        }

        .modal-body {
            padding: 24px;
        }

        .user-info-summary {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
        }

        .user-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .user-info-row:last-child {
            margin-bottom: 0;
        }

        .user-info-label {
            color: #64748b;
            font-weight: 500;
        }

        .user-info-val {
            color: #0f172a;
            font-weight: 600;
        }

        .role-option-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 10px;
        }

        .role-option-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.2s ease;
        }

        .role-option-card:hover {
            border-color: #93c5fd;
            background: #f0f9ff;
        }

        .role-option-card.selected {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .role-option-card input[type="radio"] {
            accent-color: #2563eb;
            width: 18px;
            height: 18px;
        }

        .role-option-info h4 {
            margin: 0 0 2px 0;
            font-size: 15px;
            color: #0f172a;
            font-weight: 600;
        }

        .role-option-info p {
            margin: 0;
            font-size: 12px;
            color: #64748b;
        }

        .modal-alert {
            background: #fffbebf8;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 12px 14px;
            margin-top: 18px;
            font-size: 13px;
            color: #92400e;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .modal-footer {
            padding: 16px 24px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-modal-save {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 9px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-modal-save:hover {
            background: #1d4ed8;
        }

        .btn-modal-cancel {
            background: white;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 10px 18px;
            border-radius: 9px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-modal-cancel:hover {
            background: #f1f5f9;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 12px;
        }

        /* Toast notification */
        .toast-notification {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #0f172a;
            color: white;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1100;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            font-size: 14px;
        }

        .toast-notification.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast-notification.success i { color: #4ade80; }
        .toast-notification.error i { color: #f87171; }
    </style>

    <!-- Header Section -->
    <div class="accounts-header">
        <div>
            <h2><i class="fas fa-users-cog"></i> Quản lý Tài khoản & Phân quyền</h2>
            <p>Phân quyền truy cập hệ thống và quản lý tài khoản cho toàn bộ cán bộ nhân viên</p>
        </div>
        <div class="header-actions">
            <button class="btn-refresh" onclick="loadAccounts()">
                <i class="fas fa-sync-alt" id="refreshIcon"></i> Tải lại danh sách
            </button>
        </div>
    </div>

    <!-- Reference Matrix Card -->
    <div class="matrix-info-card">
        <div class="matrix-info-header" onclick="toggleMatrixTable()">
            <i class="fas fa-info-circle" style="color: #3b82f6;"></i>
            <span>Bảng tham chiếu phân quyền theo Phòng ban</span>
            <i class="fas fa-chevron-down" id="matrixToggleIcon" style="margin-left: auto; font-size: 12px;"></i>
        </div>
        <div id="matrixTableWrapper" style="display: none;">
            <table class="matrix-table">
                <thead>
                    <tr>
                        <th style="text-align: left;">Phòng ban</th>
                        <th>HR (Nhân sự)</th>
                        <th>Tech (Kỹ thuật)</th>
                        <th>Quản lý / Ban lãnh đạo</th>
                        <th>Nhân viên</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align: left; font-weight: 600;">Ban Điều hành</td>
                        <td></td>
                        <td></td>
                        <td><i class="fas fa-check-circle" style="color: #16a34a;"></i></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: 600;">Phòng Nhân sự</td>
                        <td><i class="fas fa-check-circle" style="color: #16a34a;"></i></td>
                        <td></td>
                        <td></td>
                        <td><i class="fas fa-check-circle" style="color: #16a34a;"></i></td>
                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: 600;">Phòng CNTT (IT)</td>
                        <td></td>
                        <td><i class="fas fa-check-circle" style="color: #16a34a;"></i></td>
                        <td></td>
                        <td><i class="fas fa-check-circle" style="color: #16a34a;"></i></td>
                    </tr>
                    <tr>
                        <td style="text-align: left; font-weight: 600;">Các phòng ban khác (Kế toán, Kinh doanh, Sản xuất, QC...)</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><i class="fas fa-check-circle" style="color: #16a34a;"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form id="filterForm" onsubmit="event.preventDefault(); loadAccounts();">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="filterSearch"><i class="fas fa-search"></i> Tìm kiếm</label>
                    <input type="text" id="filterSearch" class="filter-input" placeholder="Tên nhân viên, tên đăng nhập...">
                </div>
                <div class="filter-group">
                    <label for="filterDept"><i class="fas fa-building"></i> Phòng ban</label>
                    <select id="filterDept" class="filter-select">
                        <option value="">-- Tất cả phòng ban --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filterFromDate"><i class="fas fa-calendar-alt"></i> Ngày tạo từ</label>
                    <input type="date" id="filterFromDate" class="filter-input">
                </div>
                <div class="filter-group">
                    <label for="filterToDate"><i class="fas fa-calendar-alt"></i> Đến ngày</label>
                    <input type="date" id="filterToDate" class="filter-input">
                </div>
                <div class="filter-actions-btns">
                    <button type="submit" class="btn-filter-submit">
                        <i class="fas fa-filter"></i> Lọc dữ liệu
                    </button>
                    <button type="button" class="btn-filter-reset" onclick="resetFilters()" title="Xóa bộ lọc">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="accounts-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">STT</th>
                        <th>Họ & Tên</th>
                        <th>Tên đăng nhập</th>
                        <th>Phòng ban</th>
                        <th>Role hệ thống</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th style="text-align: right;">Hành động</th>
                    </tr>
                </thead>
                <tbody id="accountsTableBody">
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Đang tải dữ liệu tài khoản...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Phân Quyền -->
<div class="modal-overlay" id="roleModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-user-shield"></i> Phân quyền Tài khoản</h3>
            <button class="modal-close" onclick="closeRoleModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="user-info-summary">
                <div class="user-info-row">
                    <span class="user-info-label">Họ và tên:</span>
                    <span class="user-info-val" id="modalUserName">--</span>
                </div>
                <div class="user-info-row">
                    <span class="user-info-label">Tên đăng nhập:</span>
                    <span class="user-info-val" id="modalUsername">--</span>
                </div>
                <div class="user-info-row">
                    <span class="user-info-label">Phòng ban:</span>
                    <span class="user-info-val" id="modalUserDept">--</span>
                </div>
            </div>

            <input type="hidden" id="modalMaND" value="">
            <input type="hidden" id="modalMaTK" value="">

            <label style="font-weight: 700; font-size: 14px; color: #1e293b; display: block; margin-bottom: 8px;">
                Chọn Vai trò (Role) mới:
            </label>

            <div class="role-option-list">
                <label class="role-option-card" id="card_nhanvien" onclick="selectRoleRadio('nhanvien')">
                    <input type="radio" name="selectedRole" value="nhanvien" id="role_nhanvien">
                    <div class="role-option-info">
                        <h4><span class="badge-role role-nhanvien"><i class="fas fa-user"></i> Nhân viên</span></h4>
                        <p>Quyền xem lịch sử chấm công, gửi yêu cầu sửa công, làm đơn xin nghỉ phép.</p>
                    </div>
                </label>

                <label class="role-option-card" id="card_hr" onclick="selectRoleRadio('hr')">
                    <input type="radio" name="selectedRole" value="hr" id="role_hr">
                    <div class="role-option-info">
                        <h4><span class="badge-role role-hr"><i class="fas fa-user-tie"></i> Bộ phận nhân sự (HR)</span></h4>
                        <p>Quyền quản lý nhân viên, ca làm việc, chấm công kiosk tablet, duyệt bảng công.</p>
                    </div>
                </label>

                <label class="role-option-card" id="card_tech" onclick="selectRoleRadio('tech')">
                    <input type="radio" name="selectedRole" value="tech" id="role_tech">
                    <div class="role-option-info">
                        <h4><span class="badge-role role-tech"><i class="fas fa-laptop-code"></i> Bộ phận kỹ thuật (Tech/IT)</span></h4>
                        <p>Quyền cấu hình WiFi, cài đặt tham số hệ thống, quản lý tài khoản & phân quyền.</p>
                    </div>
                </label>

                <label class="role-option-card" id="card_manager" onclick="selectRoleRadio('manager')">
                    <input type="radio" name="selectedRole" value="manager" id="role_manager">
                    <div class="role-option-info">
                        <h4><span class="badge-role role-manager"><i class="fas fa-briefcase"></i> Quản lý / Ban lãnh đạo</span></h4>
                        <p>Quyền xem báo cáo tổng hợp, thổng kê biểu đồ, phê duyệt đơn phép & sửa công.</p>
                    </div>
                </label>
            </div>

            <div class="modal-alert">
                <i class="fas fa-exclamation-triangle" style="margin-top: 2px;"></i>
                <div>
                    <strong>Lưu ý quan trọng:</strong> Khi thay đổi role, cột <code>chucVu</code> trong hồ sơ người dùng sẽ được cập nhật tương ứng. Người dùng sẽ cần <strong>đăng xuất và đăng nhập lại</strong> để hệ thống áp dụng menu và quyền hạn mới.
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-modal-cancel" onclick="closeRoleModal()">Hủy bỏ</button>
            <button class="btn-modal-save" onclick="submitRoleUpdate()"><i class="fas fa-save"></i> Lưu Phân quyền</button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-notification" id="toastNotification">
    <i class="fas fa-info-circle"></i>
    <span id="toastMessage">Thông báo</span>
</div>

<script>
let currentAccountsData = [];

document.addEventListener('DOMContentLoaded', function() {
    loadAccounts();
});

function toggleMatrixTable() {
    const wrapper = document.getElementById('matrixTableWrapper');
    const icon = document.getElementById('matrixToggleIcon');
    if (wrapper.style.display === 'none') {
        wrapper.style.display = 'block';
        icon.className = 'fas fa-chevron-up';
    } else {
        wrapper.style.display = 'none';
        icon.className = 'fas fa-chevron-down';
    }
}

function loadAccounts() {
    const refreshIcon = document.getElementById('refreshIcon');
    if (refreshIcon) refreshIcon.classList.add('fa-spin');

    const search = document.getElementById('filterSearch').value.trim();
    const phongBan = document.getElementById('filterDept').value;
    const fromDate = document.getElementById('filterFromDate').value;
    const toDate = document.getElementById('filterToDate').value;

    const params = new URLSearchParams({
        page: 'tech-accounts-api',
        search: search,
        phongBan: phongBan,
        fromDate: fromDate,
        toDate: toDate
    });

    fetch('index.php?' + params.toString())
        .then(response => response.json())
        .then(res => {
            if (refreshIcon) refreshIcon.classList.remove('fa-spin');
            if (res.success) {
                currentAccountsData = res.data || [];
                renderAccountsTable(currentAccountsData);
            } else {
                showToast(res.message || 'Lỗi khi tải danh sách tài khoản', 'error');
            }
        })
        .catch(err => {
            if (refreshIcon) refreshIcon.classList.remove('fa-spin');
            console.error(err);
            showToast('Không thể kết nối đến máy chủ', 'error');
        });
}

function renderAccountsTable(data) {
    const tbody = document.getElementById('accountsTableBody');
    if (!data || data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <p>Không tìm thấy tài khoản phù hợp với điều kiện lọc</p>
                </td>
            </tr>
        `;
        return;
    }

    const roleLabels = {
        'hr': { label: 'HR', class: 'role-hr', icon: 'fa-user-tie' },
        'tech': { label: 'Tech', class: 'role-tech', icon: 'fa-laptop-code' },
        'manager': { label: 'Quản lý', class: 'role-manager', icon: 'fa-briefcase' },
        'nhanvien': { label: 'Nhân viên', class: 'role-nhanvien', icon: 'fa-user' }
    };

    let html = '';
    data.forEach((item, index) => {
        const isBlocked = (item.trangThaiTK == 0 || (typeof item.trangThaiTK === 'string' && item.trangThaiTK.toLowerCase().includes('khoa')));
        const statusBadge = isBlocked 
            ? '<span class="badge-status status-locked"><i class="fas fa-lock"></i> Đã khóa</span>' 
            : '<span class="badge-status status-active"><i class="fas fa-check"></i> Hoạt động</span>';
        
        const roleInfo = roleLabels[item.role] || roleLabels['nhanvien'];
        const roleBadge = `<span class="badge-role ${roleInfo.class}"><i class="fas ${roleInfo.icon}"></i> ${roleInfo.label}</span>`;
        
        const createdDate = item.ngayTaoTK ? new Date(item.ngayTaoTK).toLocaleDateString('vi-VN') : '--';
        const hoTen = item.hoTen || '(Chưa cập nhật)';
        const phongBan = item.phongBan || '--';

        html += `
            <tr>
                <td style="font-weight: 600; color: #64748b;">${index + 1}</td>
                <td>
                    <strong style="color: #0f172a;">${escapeHtml(hoTen)}</strong>
                    ${item.maND ? `<br><small style="color: #94a3b8;">Mã ND: ${item.maND}</small>` : ''}
                </td>
                <td><code style="background: #f1f5f9; padding: 3px 6px; border-radius: 6px; color: #0f172a; font-weight: 600;">${escapeHtml(item.tenDangNhap)}</code></td>
                <td>${escapeHtml(phongBan)}</td>
                <td>${roleBadge}</td>
                <td>${statusBadge}</td>
                <td><small style="color: #64748b;">${createdDate}</small></td>
                <td style="text-align: right; white-space: nowrap;">
                    <button class="btn-action-role" onclick="openRoleModal(${item.maTK})">
                        <i class="fas fa-user-shield"></i> Phân quyền
                    </button>
                    <button class="btn-action-toggle" onclick="toggleAccountStatus(${item.maTK}, '${escapeHtml(item.tenDangNhap)}', ${isBlocked ? 'unlock' : 'lock'})">
                        <i class="fas ${isBlocked ? 'fa-unlock' : 'fa-lock'}"></i> ${isBlocked ? 'Mở khóa' : 'Khóa'}
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterDept').value = '';
    document.getElementById('filterFromDate').value = '';
    document.getElementById('filterToDate').value = '';
    loadAccounts();
}

function openRoleModal(maTK) {
    const acc = currentAccountsData.find(a => a.maTK == maTK);
    if (!acc) return;

    if (!acc.maND) {
        showToast('Tài khoản này chưa gắn liền với hồ sơ người dùng (nguoidung)', 'error');
        return;
    }

    document.getElementById('modalUserName').innerText = acc.hoTen || '(Chưa cập nhật)';
    document.getElementById('modalUsername').innerText = acc.tenDangNhap;
    document.getElementById('modalUserDept').innerText = acc.phongBan || '--';
    document.getElementById('modalMaND').value = acc.maND;
    document.getElementById('modalMaTK').value = acc.maTK;

    selectRoleRadio(acc.role || 'nhanvien');

    document.getElementById('roleModal').classList.add('active');
}

function selectRoleRadio(role) {
    const radios = document.getElementsByName('selectedRole');
    radios.forEach(r => {
        r.checked = (r.value === role);
    });

    ['nhanvien', 'hr', 'tech', 'manager'].forEach(r => {
        const card = document.getElementById('card_' + r);
        if (card) {
            if (r === role) card.classList.add('selected');
            else card.classList.remove('selected');
        }
    });
}

function closeRoleModal() {
    document.getElementById('roleModal').classList.remove('active');
}

function submitRoleUpdate() {
    const maND = document.getElementById('modalMaND').value;
    const radios = document.getElementsByName('selectedRole');
    let selectedRole = 'nhanvien';
    for (let r of radios) {
        if (r.checked) {
            selectedRole = r.value;
            break;
        }
    }

    if (!maND) {
        showToast('Không tìm thấy thông tin mã người dùng', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('maND', maND);
    formData.append('role', selectedRole);

    fetch('index.php?page=tech-update-role', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeRoleModal();
            loadAccounts();
        } else {
            showToast(data.message || 'Lỗi khi cập nhật phân quyền', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Lỗi khi gửi yêu cầu cập nhật', 'error');
    });
}

function toggleAccountStatus(maTK, tenDangNhap, action) {
    const confirmMsg = action === 'lock' 
        ? `Bạn có chắc chắn muốn KHÓA tài khoản "${tenDangNhap}" không?`
        : `Bạn có chắc chắn muốn MỞ KHÓA tài khoản "${tenDangNhap}" không?`;

    if (!confirm(confirmMsg)) return;

    const formData = new FormData();
    formData.append('maTK', maTK);

    fetch('index.php?page=tech-toggle-account', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            loadAccounts();
        } else {
            showToast(data.message || 'Lỗi khi thay đổi trạng thái tài khoản', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Lỗi kết nối máy chủ', 'error');
    });
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toastNotification');
    const msgEl = document.getElementById('toastMessage');
    
    toast.className = `toast-notification ${type}`;
    msgEl.innerText = message;
    
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>
