<?php
if (!isset($_SESSION['user'])) { header('Location: index.php?page=login'); exit(); }
if (($_SESSION['role'] ?? '') !== 'hr') { header('Location: index.php?page=home'); exit(); }

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$editing = null;
if (!empty($_GET['edit']) && !empty($employees)) {
    foreach ($employees as $emp) {
        if ((int)$emp['maND'] === (int)$_GET['edit']) {
            $editing = $emp;
            break;
        }
    }
}
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<style>
/* Department layout */
.dept-layout { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
.dept-side-panel { position: sticky; top: 16px; }
@media (max-width: 768px) { .dept-layout { grid-template-columns: 1fr; } .dept-side-panel { position: static; } }
</style>
<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">

        <div class="panel">
            <h2 style="border:none;padding:0;margin:0 0 6px;">QUẢN LÝ NHÂN VIÊN</h2>
            <p style="color:#64748b;margin:0;">Thêm mới, chỉnh sửa và tìm kiếm thông tin nhân viên.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="panel">
            <h3><?= $editing ? 'Cập nhật nhân viên' : 'Thêm nhân viên mới' ?></h3>
            <div id="validationMessage" style="display:none;padding:12px;margin-bottom:12px;border-radius:6px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;">
                <i class="fas fa-exclamation-circle"></i> Vui lòng nhập đầy đủ thông tin
            </div>
            <form id="employeeForm" method="POST" action="index.php?page=quan-ly-nhanvien" class="filter-row" style="flex-wrap:wrap;">
                <input type="hidden" name="maND" value="<?= (int)($editing['maND'] ?? 0) ?>">
                <div class="form-group" style="min-width:200px;">
                    <label>Họ tên *</label>
                    <input type="text" name="hoTen" required value="<?= htmlspecialchars($editing['hoTen'] ?? '') ?>">
                </div>
                <div class="form-group" style="min-width:200px;">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($editing['email'] ?? '') ?>">
                </div>
                <div class="form-group" style="min-width:160px;">
                    <label>Số điện thoại</label>
                    <input type="text" name="soDienThoai" value="<?= htmlspecialchars($editing['soDienThoai'] ?? '') ?>">
                </div>
                <div class="form-group" style="min-width:160px;">
                    <label>Phòng ban</label>
                    <select name="phongBan" id="phongBanSelect">
                        <option value="">-- Chọn phòng ban --</option>
                        <?php
                        $selectedDept = $editing['phongBan'] ?? '';
                        $deptNames = array_column($departments ?? [], 'tenPhongBan');
                        if (!empty($selectedDept) && !in_array($selectedDept, $deptNames, true)):
                        ?>
                            <option value="<?= htmlspecialchars($selectedDept) ?>" selected><?= htmlspecialchars($selectedDept) ?> (Hiện tại)</option>
                        <?php endif; ?>
                        <?php foreach ($departments ?? [] as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['tenPhongBan']) ?>"
                                <?= $selectedDept === $dept['tenPhongBan'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['tenPhongBan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="display:none;">
                    <label>Chức vụ *</label>
                    <select name="chucVu" required>
                        <?php
                        $roles = ['Nhân viên', 'Bộ phận Nhân sự', 'Bộ phận Kỹ thuật', 'Quản lý'];
                        $selectedRole = $editing['chucVu'] ?? 'Nhân viên';
                        if (!empty($selectedRole) && !in_array($selectedRole, $roles, true)):
                        ?>
                            <option value="<?= htmlspecialchars($selectedRole) ?>" selected><?= htmlspecialchars($selectedRole) ?> (Hiện tại)</option>
                        <?php endif; ?>
                        <?php foreach ($roles as $roleLabel): ?>
                            <option value="<?= htmlspecialchars($roleLabel) ?>" <?= $selectedRole === $roleLabel ? 'selected' : '' ?>><?= htmlspecialchars($roleLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="min-width:130px;">
                    <label>Trạng thái</label>
                    <select name="trangThaiTK">
                        <option value="1" <?= (int)($editing['trangThaiTK'] ?? 1) === 1 ? 'selected' : '' ?>>Hoạt động</option>
                        <option value="0" <?= (int)($editing['trangThaiTK'] ?? 1) === 0 ? 'selected' : '' ?>>Ngừng hoạt động</option>
                    </select>
                </div>
                <div class="form-group" style="min-width:130px;">
                     <button type="submit" class="btn btn-success btn-sm">Lưu thông tin</button>
                    <a class="btn btn-secondary btn-sm" href="index.php?page=quan-ly-nhanvien">Làm mới</a>
                </div>
            </form>
        </div>

        <div class="panel">
            <div class="panel-header" style="margin-bottom:12px;">
                <h3 style="margin:0;">Danh sách nhân viên</h3>
                <form method="GET" action="index.php" style="display:flex;gap:8px;">
                    <input type="hidden" name="page" value="quan-ly-nhanvien">
                    <input type="text" name="q" value="<?= htmlspecialchars($keyword ?? '') ?>" placeholder="Tìm theo tên, email..." style="padding:6px 12px;border:1px solid #e2e8f0;border-radius:6px;font-size:0.85em;min-width:200px;">
                    <button class="btn btn-primary btn-sm" type="submit">Tìm</button>
                </form>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>MÃ NV</th>
                        <th>HỌ TÊN</th>
                        <th>TÀI KHOẢN</th>
                        <th>EMAIL</th>
                        <th>PHÒNG BAN</th>
                        <th>TRẠNG THÁI</th>
                        <th>HÀNH ĐỘNG</th>
                    </tr>
                </thead>
               <tbody>
<?php if (!empty($employees)): ?>
    <?php foreach ($employees as $emp): ?>

        <tr>
            <td><?= (int)$emp['maND'] ?></td>
            <td><?= htmlspecialchars($emp['hoTen']) ?></td>
            <td>
                <code style="background:#e0f2fe;color:#0369a1;padding:3px 8px;border-radius:4px;font-weight:600;font-size:0.9em;">
                    <?= htmlspecialchars($emp['tenDangNhap'] ?? ('user' . $emp['maND'])) ?>
                </code>
            </td>
            <td>
                <a href="mailto:<?= htmlspecialchars($emp['email'] ?? '') ?>" style="color:#3b82f6;">
                    <?= htmlspecialchars($emp['email'] ?? '') ?>
                </a>
            </td>
            <td><?= htmlspecialchars($emp['phongBan'] ?? '') ?></td>
            <td>
                <span class="trangThai-badge <?= (int)($emp['trangThaiTK'] ?? 0) === 1 ? 'trangThai-approved' : 'trangThai-rejected' ?>">
                    <?= (int)($emp['trangThaiTK'] ?? 0) === 1 ? 'Hoạt động' : 'Ngừng hoạt động' ?>
                </span>
            </td>
            <td>
                <div style="display:flex;gap:6px;align-items:center;">
                    <a class="btn btn-sm btn-primary"
                       href="index.php?page=quan-ly-nhanvien&edit=<?= (int)$emp['maND'] ?>">
                        Sửa
                    </a>
                    <form method="POST" action="index.php?page=quan-ly-nhanvien" style="display:inline;margin:0;" onsubmit="return confirm('Bạn có chắc chắn muốn đặt lại mật khẩu cho nhân viên <?= htmlspecialchars(addslashes($emp['hoTen'])) ?> về mặc định (123456)?');">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="maND" value="<?= (int)$emp['maND'] ?>">
                        <button type="submit" class="btn btn-sm" style="background:#f59e0b;color:#fff;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;font-size:0.85em;display:inline-flex;align-items:center;gap:4px;">
                            <i class="fas fa-key"></i> Reset pass
                        </button>
                    </form>
                </div>
            </td>
        </tr>

    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="7" class="empty-state">
            Không có nhân viên phù hợp.
        </td>
    </tr>
<?php endif; ?>
</tbody>
            </table>
        </div>

        <div class="panel">
            <div class="dept-layout">
                <!-- Danh sách phòng ban -->
                <div>
                    <h3 style="margin:0 0 12px;"><i class="fas fa-building" style="color:#3b82f6;"></i> Danh sách Phòng Ban</h3>
                    <table class="table" id="dept-list-table">
                        <thead>
                            <tr>
                                <th>TÊN PHÒNG BAN</th>
                                <th>MÔ TẢ</th>
                                <th>TRẠNG THÁI</th>
                                <th>THAO TÁC</th>
                            </tr>
                        </thead>
                        <tbody id="dept-list-body">
                            <?php if (!empty($departments)): ?>
                                <?php foreach ($departments as $dept): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($dept['tenPhongBan']) ?></strong></td>
                                        <td><?= htmlspecialchars($dept['moTa'] ?? '') ?></td>
                                        <td><span class="trangThai-badge <?= (int)$dept['hoatDong'] ? 'trangThai-approved' : 'trangThai-rejected' ?>"><?= (int)$dept['hoatDong'] ? 'Đang dùng' : 'Tắt' ?></span></td>
                                        <td>
                                            <button type="button" class="btn btn-secondary btn-sm edit-dept"
                                                data-id="<?= (int)$dept['id'] ?>"
                                                data-name="<?= htmlspecialchars($dept['tenPhongBan'], ENT_QUOTES) ?>"
                                                data-mota="<?= htmlspecialchars($dept['moTa'] ?? '', ENT_QUOTES) ?>"
                                                data-active="<?= (int)$dept['hoatDong'] ?>">
                                                <i class="fas fa-pen"></i> Sửa
                                            </button>
                                            <form method="post" action="index.php?page=quan-ly-nhanvien" onsubmit="return confirm('Xóa phòng ban này?');" style="display:inline;">
                                                <input type="hidden" name="action" value="delete_department">
                                                <input type="hidden" name="id" value="<?= (int)$dept['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Xóa</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="empty-state">Chưa có phòng ban. Hãy tạo phòng ban đầu tiên!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-primary btn-sm" id="toggle-add-dept" style="margin-top:12px;">
                        <i class="fas fa-plus"></i> Thêm phòng ban mới
                    </button>
                </div>

                <!-- Form thêm/sửa phòng ban -->
                <div class="dept-side-panel" id="add-dept-form-panel" style="display:none;">
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:20px;">
                        <h4 id="dept-form-title" style="margin:0 0 16px;font-size:15px;color:#1e293b;">Tạo phòng ban mới</h4>
                        <form id="dept-form">
                            <input type="hidden" name="id" value="0">
                            <div class="form-group">
                                <label>Tên phòng ban *</label>
                                <input type="text" name="tenPhongBan" placeholder="VD: Phòng Kế toán" required>
                            </div>
                            <div class="form-group">
                                <label>Mô tả</label>
                                <input type="text" name="moTa" placeholder="Mô tả ngắn về phòng ban">
                            </div>
                            <div class="form-group">
                                <label>Trạng thái</label>
                                <select name="hoatDong">
                                    <option value="1">Đang dùng</option>
                                    <option value="0">Tắt</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm" style="width:100%;">Lưu phòng ban</button>
                            <button type="button" id="cancel-dept-form" class="btn btn-secondary btn-sm" style="width:100%;margin-top:8px;">Hủy</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== FORM NHÂN VIÊN =====
    var form = document.getElementById('employeeForm');
    var validationMessage = document.getElementById('validationMessage');
    var hoTenInput = form.querySelector('input[name="hoTen"]');
    var chucVuSelect = form.querySelector('select[name="chucVu"]');

    hoTenInput.addEventListener('input', function() {
        if (validationMessage.style.display !== 'none') {
            validationMessage.style.display = 'none';
        }
    });

    chucVuSelect.addEventListener('change', function() {
        if (validationMessage.style.display !== 'none') {
            validationMessage.style.display = 'none';
        }
    });

    form.addEventListener('submit', function(e) {
        var hoTen = hoTenInput.value.trim();
        var chucVu = chucVuSelect.value;
        if (!hoTen || !chucVu) {
            e.preventDefault();
            validationMessage.style.display = 'block';
            hoTenInput.focus();
            return false;
        }
    });

    // ===== FORM PHÒNG BAN =====
    var deptToggleBtn = document.getElementById('toggle-add-dept');
    var deptFormPanel = document.getElementById('add-dept-form-panel');
    var deptForm = document.getElementById('dept-form');
    var deptFormTitle = document.getElementById('dept-form-title');
    var cancelDeptBtn = document.getElementById('cancel-dept-form');

    function resetDeptForm() {
        deptForm.reset();
        deptForm.elements.id.value = '0';
        deptFormTitle.textContent = 'Tạo phòng ban mới';
    }

    deptToggleBtn.addEventListener('click', function() {
        var isHidden = deptFormPanel.style.display === 'none';
        deptFormPanel.style.display = isHidden ? 'block' : 'none';
        if (isHidden) {
            resetDeptForm();
            deptFormPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    cancelDeptBtn.addEventListener('click', function() {
        deptFormPanel.style.display = 'none';
        resetDeptForm();
    });

    // Nút sửa phòng ban
    document.querySelectorAll('.edit-dept').forEach(function(button) {
        button.addEventListener('click', function() {
            deptForm.elements.id.value = this.dataset.id;
            deptForm.elements.tenPhongBan.value = this.dataset.name;
            deptForm.elements.moTa.value = this.dataset.mota;
            deptForm.elements.hoatDong.value = this.dataset.active;
            deptFormTitle.textContent = 'Sửa phòng ban';
            deptFormPanel.style.display = 'block';
            deptFormPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    // Gửi form phòng ban qua AJAX (giống quanly_calam.php)
    deptForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var submitBtn = deptForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

        fetch('index.php?page=hr-api-departments', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(deptForm)
        })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            alert(json.message || 'OK');
            if (json.success) {
                location.reload();
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Lưu phòng ban';
            }
        })
        .catch(function() {
            alert('Không thể kết nối máy chủ.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Lưu phòng ban';
        });
    });

    // Cập nhật select phòng ban nếu dữ liệu từ DB rỗng, load qua API
    // (đã load server-side nên không cần fetch thêm)
});
</script>
<?php include 'app/views/layouts/footer.php'; ?>
