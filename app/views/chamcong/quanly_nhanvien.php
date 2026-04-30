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
                    <input type="text" name="phongBan" value="<?= htmlspecialchars($editing['phongBan'] ?? '') ?>">
                </div>
                <div class="form-group" style="min-width:180px;">
                    <label>Chức vụ *</label>
                    <select name="chucVu" required>
                        <?php
                        $roles = ['Nhân viên', 'Bộ phận Nhân sự', 'Quản lý / Ban lãnh đạo', 'Bộ phận Kỹ thuật'];
                        $selectedRole = $editing['chucVu'] ?? 'Nhân viên';
                        foreach ($roles as $roleLabel):
                        ?>
                            <option value="<?= htmlspecialchars($roleLabel) ?>" <?= $selectedRole === $roleLabel ? 'selected' : '' ?>><?= htmlspecialchars($roleLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="min-width:130px;">
                    <label>Trạng thái</label>
                    <select name="trangThai">
                        <option value="1" <?= (int)($editing['trangThai'] ?? 1) === 1 ? 'selected' : '' ?>>Hoạt động</option>
                        <option value="0" <?= (int)($editing['trangThai'] ?? 1) === 0 ? 'selected' : '' ?>>Ngừng</option>
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
                        <th>EMAIL</th>
                        <th>PHÒNG BAN</th>
                        <th>CHỨC VỤ</th>
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
                            <td><a href="mailto:<?= htmlspecialchars($emp['email'] ?? '') ?>" style="color:#3b82f6;"><?= htmlspecialchars($emp['email'] ?? '') ?></a></td>
                            <td><?= htmlspecialchars($emp['phongBan'] ?? '') ?></td>
                            <td><?= htmlspecialchars($emp['chucVu'] ?? '') ?></td>
                            <td>
                                <span class="status-badge <?= (int)$emp['trangThai'] === 1 ? 'status-approved' : 'status-rejected' ?>">
                                    <?= (int)$emp['trangThai'] === 1 ? '• Hoạt động' : '• Ngừng' ?>
                                </span>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-primary" href="index.php?page=quan-ly-nhanvien&edit=<?= (int)$emp['maND'] ?>">Sửa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="empty-state">Không có nhân viên phù hợp.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('employeeForm');
    var validationMessage = document.getElementById('validationMessage');
    var hoTenInput = form.querySelector('input[name="hoTen"]');
    var chucVuSelect = form.querySelector('select[name="chucVu"]');

    // Reset validation message when user types
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

    // Validate on form submit
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
});
</script>
<?php include 'app/views/layouts/footer.php'; ?>
