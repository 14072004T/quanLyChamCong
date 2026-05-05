<?php
// Attendance Panel - Refined Compact UI
if (!isset($_SESSION['user'])) { header('Location: index.php?page=login'); exit; }
$user = $_SESSION['user'];
$hoTen = $user['hoTen'] ?? 'User';
?>

<div class="att-refined-wrap">
    <style>
        .att-refined-wrap { max-width: 1100px; margin: 0 auto; padding: 20px; font-family: 'Inter', sans-serif; }
        .att-header-mini { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; background: white; padding: 16px 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .att-header-info h2 { margin: 0; font-size: 18px; color: #1e293b; }
        .att-header-info p { margin: 2px 0 0; font-size: 13px; color: #64748b; }
        
        .att-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .att-card-mini { background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .att-card-mini h3 { margin: 0 0 16px; font-size: 14px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }
        
        .att-status-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .att-status-label { font-size: 13px; color: #64748b; }
        .att-status-val { font-size: 14px; font-weight: 600; color: #1e293b; }
        
        .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .bg-success { background: #dcfce7; color: #166534; }
        .bg-warning { background: #fef3c7; color: #92400e; }
        .bg-danger { background: #fee2e2; color: #991b1b; }
        .bg-info { background: #dbeafe; color: #1e40af; }

        .btn-group-att { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        .btn-att-mid { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; color: white; }
        .btn-att-mid:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-att-in { background: #10b981; }
        .btn-att-in:hover:not(:disabled) { background: #059669; transform: translateY(-1px); }
        .btn-att-out { background: #ef4444; }
        .btn-att-out:hover:not(:disabled) { background: #dc2626; transform: translateY(-1px); }

        .history-table-compact { width: 100%; border-collapse: collapse; font-size: 13px; }
        .history-table-compact th { text-align: left; padding: 10px; background: #f8fafc; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
        .history-table-compact td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
        
        @media (max-width: 640px) { .att-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="att-header-mini">
        <div class="att-header-info">
            <h2><i class="fas fa-fingerprint" style="color: #3b82f6;"></i> Chấm công hệ thống</h2>
            <p>Xác thực mạng nội bộ và ghi nhận thời gian làm việc</p>
        </div>
        <div id="clock-mini" style="font-size: 20px; font-weight: 700; color: #1e293b;">--:--:--</div>
    </div>

    <div id="message-container"></div>

    <div class="att-grid">
        <!-- Network Info -->
        <div class="att-card-mini">
            <h3><i class="fas fa-network-wired"></i> Kết nối mạng</h3>
            <div class="att-status-row">
                <span class="att-status-label">Địa chỉ IP:</span>
                <span id="ip-display" class="att-status-val">...</span>
            </div>
            <div class="att-status-row">
                <span class="att-status-label">Chọn WiFi:</span>
                <select id="wifi-select" class="yc-input" style="height: 30px; font-size: 12px; width: 140px; padding: 2px 8px;">
                    <option value="">Đang tải...</option>
                </select>
            </div>
            <div class="att-status-row">
                <span class="att-status-label">Trạng thái:</span>
                <span id="network-validation">
                    <span class="badge-status bg-warning">Đang kiểm tra...</span>
                </span>
            </div>
        </div>

        <!-- Today Info -->
        <div class="att-card-mini">
            <h3><i class="fas fa-calendar-day"></i> Trạng thái hôm nay</h3>
            <div class="att-status-row">
                <span class="att-status-label">Giờ vào:</span>
                <span id="checkin-time" class="att-status-val">—</span>
            </div>
            <div class="att-status-row">
                <span class="att-status-label">Giờ ra:</span>
                <span id="checkout-time" class="att-status-val">—</span>
            </div>
            <div class="att-status-row">
                <span class="att-status-label">Tổng giờ:</span>
                <span id="total-hours" class="att-status-val">—</span>
            </div>
        </div>
    </div>

    <div class="btn-group-att">
        <button id="checkin-btn" class="btn-att-mid btn-att-in">
            <i class="fas fa-sign-in-alt"></i> CHẤM CÔNG VÀO
        </button>
        <button id="checkout-btn" class="btn-att-mid btn-att-out">
            <i class="fas fa-sign-out-alt"></i> CHẤM CÔNG RA
        </button>
    </div>

    <div class="att-card-mini">
        <h3><i class="fas fa-history"></i> Lịch sử gần đây</h3>
        <div id="history-list" style="overflow-x: auto;">
            <p style="text-align: center; color: #94a3b8; padding: 20px;">Đang tải dữ liệu...</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiBase = 'index.php?page=';

    function formatTimeFromHours(hours) {
        if (!hours || hours <= 0) return '—';
        const h = Math.floor(hours);
        const m = Math.round((hours - h) * 60);
        return (h > 0 ? h + 'h ' : '') + m + 'm';
    }

    function loadData() {
        // IP check + Wifi list
        fetch(apiBase + 'attendance-validate-network')
            .then(res => res.json())
            .then(data => {
                document.getElementById('ip-display').textContent = data.ip || 'Unknown';
                document.getElementById('network-validation').innerHTML = data.is_allowed 
                    ? '<span class="badge-status bg-success">Hợp lệ</span>'
                    : '<span class="badge-status bg-danger">Mạng ngoài</span>';
                
                const wifiSelect = document.getElementById('wifi-select');
                if (data.allowed_networks && data.allowed_networks.length > 0) {
                    let options = '';
                    let matched = false;
                    data.allowed_networks.forEach(w => {
                        const isMatch = data.ip && data.ip.startsWith(w.ip_range);
                        options += `<option value="${w.wifi_name}" ${isMatch ? 'selected' : ''}>${w.wifi_name}</option>`;
                        if (isMatch) matched = true;
                    });
                    wifiSelect.innerHTML = options;
                } else {
                    wifiSelect.innerHTML = '<option value="">Không có WiFi</option>';
                }
            });

        // Today info
        fetch(apiBase + 'attendance-today')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('checkin-time').textContent = data.checkIn ? data.checkIn.split(' ')[1] : '—';
                    document.getElementById('checkout-time').textContent = data.checkOut ? data.checkOut.split(' ')[1] : '—';
                    document.getElementById('total-hours').textContent = formatTimeFromHours(data.total_hours);
                }
            });

        // History
        fetch(apiBase + 'attendance-history?limit=5')
            .then(res => res.json())
            .then(data => {
                const historyList = document.getElementById('history-list');
                if (data.success && data.data.length > 0) {
                    let html = '<table class="history-table-compact"><thead><tr><th>Ngày</th><th>WiFi</th><th>Vào</th><th>Ra</th></tr></thead><tbody>';
                    data.data.forEach(r => {
                        const wifiDisplay = r.wifi_name || 'Wifi Công ty';
                        html += `<tr><td>${r.date}</td><td><span style="font-size:11px; color:#64748b">${wifiDisplay}</span></td><td>${r.checkIn || '—'}</td><td>${r.checkOut || '—'}</td></tr>`;
                    });
                    html += '</tbody></table>';
                    historyList.innerHTML = html;
                } else {
                    historyList.innerHTML = '<p style="text-align: center; color: #94a3b8; padding: 10px;">Chưa có dữ liệu</p>';
                }
            });
    }

    function showMsg(text, type) {
        const container = document.getElementById('message-container');
        const div = document.createElement('div');
        div.style.cssText = `padding:10px 16px; border-radius:8px; margin-bottom:16px; font-size:13px; font-weight:600; display:flex; justify-content:space-between; align-items:center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);`;
        if (type === 'success') div.style.background = '#dcfce7', div.style.color = '#166534', div.style.border = '1px solid #bbf7d0';
        else div.style.background = '#fee2e2', div.style.color = '#991b1b', div.style.border = '1px solid #fecaca';
        div.innerHTML = `<span>${text}</span><i class="fas fa-times" style="cursor:pointer; opacity:0.5" onclick="this.parentElement.remove()"></i>`;
        container.appendChild(div);
        setTimeout(() => { if(div.parentElement) div.remove(); }, 5000);
    }

    document.getElementById('checkin-btn').onclick = () => {
        const btn = document.getElementById('checkin-btn');
        const wifi = document.getElementById('wifi-select').value;
        btn.disabled = true;
        
        const formData = new FormData();
        formData.append('wifi_name', wifi);

        fetch(apiBase + 'attendance-check-in', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) { showMsg('Chấm công vào thành công!', 'success'); loadData(); }
                else { showMsg(data.message || 'Thất bại', 'error'); btn.disabled = false; }
            });
    };

    document.getElementById('checkout-btn').onclick = () => {
        const btn = document.getElementById('checkout-btn');
        const wifi = document.getElementById('wifi-select').value;
        btn.disabled = true;

        const formData = new FormData();
        formData.append('wifi_name', wifi);

        fetch(apiBase + 'attendance-check-out', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) { showMsg('Chấm công ra thành công!', 'success'); loadData(); }
                else { showMsg(data.message || 'Thất bại', 'error'); btn.disabled = false; }
            });
    };

    function updateClock() {
        document.getElementById('clock-mini').textContent = new Date().toLocaleTimeString('en-GB');
    }
    setInterval(updateClock, 1000);
    updateClock();
    loadData();
});
</script>
