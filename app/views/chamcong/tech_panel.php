<div class="tech-container">
<style>
.tech-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 20px;
}
.tech-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    color: white;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 24px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.tech-header h2 { margin: 0 0 8px 0; font-size: 22px; font-weight: 600; }
.tech-header p { margin: 0; color: #cbd5e1; font-size: 14px; }
.tech-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
    cursor: pointer;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
.stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.icon-blue { background: #eff6ff; color: #3b82f6; }
.icon-green { background: #f0fdf4; color: #10b981; }
.icon-purple { background: #faf5ff; color: #a855f7; }
.stat-info h3 { margin: 0; font-size: 13px; color: #64748b; font-weight: 500; text-transform: uppercase; }
.stat-info p { margin: 2px 0 0 0; font-size: 20px; font-weight: 700; color: #0f172a; }

.tech-modules {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
}
.module-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    display: flex;
    flex-direction: column;
}
.module-header { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
.module-header i { font-size: 20px; }
.module-header h3 { margin: 0; font-size: 16px; font-weight: 600; color: #1e293b; }
.module-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}
.btn-wifi { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
.btn-wifi:hover { background: #dbeafe; }
.btn-settings { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.btn-settings:hover { background: #dcfce7; }
</style>

<div class="tech-header">
    <div>
        <h2><i class="fas fa-laptop-code" style="margin-right: 12px; opacity: 0.8;"></i>Bảng điều khiển Kỹ thuật</h2>
        <p>Quản lý mạng nội bộ và cấu hình hệ thống chấm công</p>
    </div>
    <i class="fas fa-shield-alt" style="font-size: 40px; opacity: 0.2;"></i>
</div>

<div class="tech-stats">
    <div class="stat-card" onclick="window.location.href='index.php?page=tech-wifi'">
        <div class="stat-icon icon-green"><i class="fas fa-wifi"></i></div>
        <div class="stat-info">
            <h3>WiFi Hoạt động</h3>
            <p><?php echo count(array_filter($wifiList ?? [], fn($w) => $w['is_active'])); ?></p>
        </div>
    </div>
    <div class="stat-card" onclick="window.location.href='index.php?page=tech-wifi'">
        <div class="stat-icon icon-blue"><i class="fas fa-network-wired"></i></div>
        <div class="stat-info">
            <h3>Tổng mạng</h3>
            <p><?php echo count($wifiList ?? []); ?></p>
        </div>
    </div>
    <div class="stat-card" onclick="window.location.href='index.php?page=tech-settings'">
        <div class="stat-icon icon-purple"><i class="fas fa-cogs"></i></div>
        <div class="stat-info">
            <h3>Cài đặt</h3>
            <p>4</p>
        </div>
    </div>
</div>

<div class="tech-modules">
    <div class="module-card">
        <div class="module-header">
            <i class="fas fa-wifi" style="color: #3b82f6;"></i>
            <h3>Quản lý WiFi</h3>
        </div>
        <a href="index.php?page=tech-wifi" class="module-btn btn-wifi">
            <i class="fas fa-external-link-alt"></i> Truy cập Quản lý
        </a>
    </div>
    <div class="module-card">
        <div class="module-header">
            <i class="fas fa-sliders-h" style="color: #10b981;"></i>
            <h3>Cài đặt hệ thống</h3>
        </div>
        <a href="index.php?page=tech-settings" class="module-btn btn-settings">
            <i class="fas fa-external-link-alt"></i> Truy cập Cài đặt
        </a>
    </div>
</div>
</div>
