<?php 
// Kiểm tra xác thực
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}

// Chỉ TECH có thể quản lý WiFi
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
            <h2>Quản lý WiFi</h2>
            <p>Quản lý danh sách WiFi, cấu hình mac address cho chấm công.</p>
        </div>

        <div class="panel">
            <h3>Danh sách WiFi</h3>
            <button class="btn btn-success" onclick="alert('Thêm WiFi')">
                <i class="fas fa-plus"></i> Thêm WiFi
            </button>
            <table class="table" style="margin-top: 15px;">
                <thead>
                    <tr>
                        <th>Tên WiFi</th>
                        <th>SSID</th>
                        <th>Địa điểm</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="empty-state">Đang tải dữ liệu...</td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</div>
<?php include 'app/views/layouts/footer.php'; ?>
