<?php 
// Kiểm tra xác thực
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}

// Chỉ TECH có thể cấu hình hệ thống
if ($_SESSION['role'] !== 'tech') {
    header('Location: index.php?page=home');
    exit();
}
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">
        
        <div class="panel">
            <h2>Cấu hình Hệ thống</h2>
            <p>Cấu hình các tham số hệ thống chấm công.</p>
        </div>

        <div class="panel">
            <h3>Cấu hình Chung</h3>
            <form method="POST" action="index.php?page=luu-cauhinh">
                <div class="form-group">
                    <label>Tên hệ thống:</label>
                    <input type="text" name="system_name" class="form-control" value="Hệ thống Quản lý Chấm công">
                </div>

                <div class="form-group">
                    <label>Múi giờ:</label>
                    <select name="timezone" class="form-control">
                        <option value="Asia/Ho_Chi_Minh">Asia/Ho_Chi_Minh</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Email hỗ trợ:</label>
                    <input type="email" name="support_email" class="form-control">
                </div>

                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Lưu cấu hình
                </button>
            </form>
        </div>

    </div>
</div>
<?php include 'app/views/layouts/footer.php'; ?>
