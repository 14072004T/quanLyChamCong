<div class="panel">
    <h2>Bảng điều khiển - Bộ phận Kỹ thuật</h2>
    <p>Quản lý WiFi và cấu hình hệ thống chấm công.</p>
</div>

<div class="dashboard-stats">
    <div class="card">
        <h3>WiFi hoạt động</h3>
        <p><?php 
            $activeWifi = count(array_filter($wifiList ?? [], fn($w) => $w['is_active']));
            echo $activeWifi;
        ?></p>
    </div>
    <div class="card">
        <h3>Tổng WiFi</h3>
        <p><?php echo count($wifiList ?? []); ?></p>
    </div>
    <div class="card">
        <h3>Cài đặt hệ thống</h3>
        <p><?php echo count($settings ?? []); ?></p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
    <!-- WiFi Link -->
    <div class="panel">
        <h3 style="margin-top: 0; display:flex; align-items:center; gap:8px;">
            <i class="fas fa-wifi" style="color: var(--brand-primary);"></i> Quản lý WiFi
        </h3>
        <p>Thêm, bật/tắt hoặc xóa các mạng WiFi được phép chấm công.</p>
        <a href="index.php?page=tech-wifi" class="btn btn-primary" style="width: 100%; text-align: center;">
            <i class="fas fa-wifi"></i> Quản lý WiFi
        </a>
    </div>

    <!-- Settings Link -->
    <div class="panel">
        <h3 style="margin-top: 0; display:flex; align-items:center; gap:8px;">
            <i class="fas fa-cog" style="color: var(--brand-primary);"></i> Cài đặt hệ thống
        </h3>
        <p>Quản lý các thông số cấu hình của hệ thống chấm công.</p>
        <a href="index.php?page=tech-settings" class="btn btn-success" style="width: 100%; text-align: center;">
            <i class="fas fa-cog"></i> Cài đặt
        </a>
    </div>
</div>
