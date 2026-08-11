<?php
// Attendance Panel with Face Liveness Detection
// ==============================================
// Multi-layer anti-spoofing pipeline integrated into face attendance.
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
        
        .att-trangThai-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .att-trangThai-label { font-size: 13px; color: #64748b; }
        .att-trangThai-val { font-size: 14px; font-weight: 600; color: #1e293b; }
        
        .badge-trangThai { padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
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

        /* ─── Liveness Modal Styles ─── */
        .liveness-modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;
            backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
        }
        .liveness-modal-content {
            background: white; border-radius: 16px; width: 92%; max-width: 540px;
            padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); border: 1px solid #e2e8f0;
            position: relative; max-height: 95vh; overflow-y: auto;
        }
        .liveness-modal-close {
            position: absolute; top: 14px; right: 14px; background: #f1f5f9; border: none;
            font-size: 16px; cursor: pointer; color: #64748b; width: 32px; height: 32px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
        }
        .liveness-modal-close:hover { background: #fee2e2; color: #dc2626; }
        .liveness-modal-title {
            margin: 0 0 16px; font-size: 17px; font-weight: 700; color: #0f172a;
            text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px;
        }

        /* Status banner */
        .liveness-status {
            padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 600;
            text-align: center; margin-bottom: 14px; transition: all 0.3s ease;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .liveness-status-info { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .liveness-status-success { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .liveness-status-warning { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
        .liveness-status-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* Progress bar */
        .liveness-progress-wrap {
            background: #f1f5f9; border-radius: 8px; height: 8px; margin-bottom: 14px; overflow: hidden;
        }
        .liveness-progress-bar {
            height: 100%; border-radius: 8px; transition: width 0.4s ease, background 0.3s;
            background: linear-gradient(90deg, #3b82f6, #10b981);
            width: 0%;
        }
        .liveness-progress-label {
            font-size: 11px; color: #64748b; text-align: right; margin-bottom: 4px; font-weight: 600;
        }

        /* Blink detection indicator */
        .liveness-blink-indicator {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600;
            margin-bottom: 12px; transition: all 0.3s ease;
        }
        .blink-waiting {
            background: #fef3c7; color: #92400e; border: 1px solid #fde68a;
        }
        .blink-detected {
            background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;
            animation: blinkPulse 0.5s ease;
        }
        @keyframes blinkPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }

        /* Step indicators */
        .liveness-steps {
            display: flex; justify-content: space-between; margin-bottom: 16px; padding: 10px 12px;
            background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; gap: 4px;
        }
        .liveness-step {
            display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600;
            color: #94a3b8; transition: all 0.3s;
        }
        .liveness-step-num {
            width: 22px; height: 22px; border-radius: 50%; background: #f1f5f9; color: #94a3b8;
            border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700; transition: all 0.3s; flex-shrink: 0;
        }
        .liveness-step.active { color: #3b82f6; }
        .liveness-step.active .liveness-step-num { background: #3b82f6; color: white; border-color: #3b82f6; }
        .liveness-step.done { color: #10b981; }
        .liveness-step.done .liveness-step-num { background: #ecfdf5; color: #10b981; border-color: #10b981; }

        /* Camera area */
        .liveness-camera-area {
            position: relative; width: 100%; aspect-ratio: 4/3; background: #0f172a;
            border-radius: 10px; overflow: hidden; border: 2px solid #e2e8f0;
            transition: border-color 0.3s;
        }
        .liveness-camera-area.detecting { border-color: #3b82f6; }
        .liveness-camera-area.success { border-color: #10b981; }
        .liveness-camera-area.failed { border-color: #ef4444; }

        /* Challenge instruction overlay */
        .liveness-challenge-overlay {
            position: absolute; bottom: 0; left: 0; right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 20px 16px 14px; display: none;
        }
        .liveness-challenge-text {
            color: white; font-size: 15px; font-weight: 700; text-align: center;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5); display: flex; align-items: center;
            justify-content: center; gap: 8px;
        }

        /* Spoof alert */
        .liveness-spoof-alert {
            display: none; padding: 14px 16px; border-radius: 10px; margin-top: 14px;
            background: linear-gradient(135deg, #fef2f2, #fee2e2); border: 2px solid #fca5a5;
            text-align: center; animation: shakeAlert 0.5s ease;
        }
        .liveness-spoof-alert i { font-size: 24px; color: #dc2626; display: block; margin-bottom: 6px; }
        .liveness-spoof-alert strong { color: #991b1b; font-size: 15px; display: block; margin-bottom: 4px; }
        .liveness-spoof-alert p { color: #b91c1c; font-size: 12px; margin: 0; }

        @keyframes shakeAlert {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }

        /* Retry button */
        .liveness-retry-btn {
            display: none; width: 100%; padding: 12px; border-radius: 8px; font-size: 14px;
            font-weight: 600; border: 2px solid #3b82f6; background: white; color: #3b82f6;
            cursor: pointer; margin-top: 12px; transition: all 0.2s;
        }
        .liveness-retry-btn:hover { background: #3b82f6; color: white; }
    </style>

    <div class="att-header-mini">
        <div class="att-header-info">
            <h2><i class="fas fa-shield-alt" style="color: #3b82f6;"></i> Chấm công xác minh khuôn mặt thật</h2>
            <p>Xác thực mạng nội bộ + Nhận diện liveness + Ghi nhận thời gian</p>
        </div>
        <div id="clock-mini" style="font-size: 20px; font-weight: 700; color: #1e293b;">--:--:--</div>
    </div>

    <div id="message-container"></div>

    <div class="att-grid">
        <!-- Network Info -->
        <div class="att-card-mini">
            <h3><i class="fas fa-network-wired"></i> Kết nối mạng</h3>
            <div class="att-trangThai-row">
                <span class="att-trangThai-label">Địa chỉ IP:</span>
                <span id="ip-display" class="att-trangThai-val">...</span>
            </div>
            <div class="att-trangThai-row">
                <span class="att-trangThai-label">Chọn WiFi:</span>
                <select id="wifi-select" class="yc-input" style="height: 30px; font-size: 12px; width: 140px; padding: 2px 8px;">
                    <option value="">Đang tải...</option>
                </select>
            </div>
            <div class="att-trangThai-row">
                <span class="att-trangThai-label">Trạng thái:</span>
                <span id="network-validation">
                    <span class="badge-trangThai bg-warning">Đang kiểm tra...</span>
                </span>
            </div>
        </div>

        <!-- Today Info -->
        <div class="att-card-mini">
            <h3><i class="fas fa-calendar-day"></i> Trạng thái hôm nay</h3>
            <div class="att-trangThai-row">
                <span class="att-trangThai-label">Giờ vào:</span>
                <span id="checkin-time" class="att-trangThai-val">—</span>
            </div>
            <div class="att-trangThai-row">
                <span class="att-trangThai-label">Giờ ra:</span>
                <span id="checkout-time" class="att-trangThai-val">—</span>
            </div>
            <div class="att-trangThai-row">
                <span class="att-trangThai-label">Tổng giờ:</span>
                <span id="total-soGio" class="att-trangThai-val">—</span>
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

    <!-- ═══ LIVENESS VERIFICATION MODAL ═══ -->
    <div id="liveness-modal" class="liveness-modal-overlay">
        <div class="liveness-modal-content">
            <button id="liveness-close-btn" class="liveness-modal-close"><i class="fas fa-times"></i></button>
            
            <h3 id="liveness-modal-title" class="liveness-modal-title">
                <i class="fas fa-shield-alt"></i> Xác minh khuôn mặt thật
            </h3>

            <!-- Status banner -->
            <div id="liveness-status" class="liveness-status liveness-status-info">
                <i class="fas fa-spinner fa-spin"></i> Đang tải mô hình nhận diện...
            </div>



            <!-- Progress bar -->
            <div class="liveness-progress-label" id="liveness-progress-label">0%</div>
            <div class="liveness-progress-wrap">
                <div id="liveness-progress-bar" class="liveness-progress-bar" style="width: 0%;"></div>
            </div>

            <!-- ★ Blink Detection Indicator -->
            <div id="liveness-blink-indicator" class="liveness-blink-indicator blink-waiting">
                <i class="fas fa-eye" id="blink-icon"></i>
                <span id="blink-text">Chờ phát hiện chớp mắt tự nhiên...</span>
            </div>

            <!-- Camera feed -->
            <div id="liveness-camera" class="liveness-camera-area">
                <video id="liveness-video" autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
                <canvas id="liveness-canvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; transform: scaleX(-1);"></canvas>
                
                <!-- Challenge instruction overlay -->
                <div id="liveness-challenge-overlay" class="liveness-challenge-overlay">
                    <div id="liveness-challenge-text" class="liveness-challenge-text">
                        <i class="fas fa-eye"></i> <span>Vui lòng chớp mắt</span>
                    </div>
                </div>
            </div>

            <!-- Snapshot canvas (hidden) -->
            <canvas id="liveness-snapshot" style="display: none;"></canvas>

            <!-- Spoof alert -->
            <div id="liveness-spoof-alert" class="liveness-spoof-alert">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>PHÁT HIỆN KHUÔN MẶT GIẢ!</strong>
                <p id="liveness-spoof-reason">Hệ thống phát hiện bạn không phải người thật.</p>
            </div>

            <!-- Retry button -->
            <button id="liveness-retry-btn" class="liveness-retry-btn">
                <i class="fas fa-redo"></i> Thử lại xác minh
            </button>

            <!-- Info footer -->
            <div style="margin-top: 12px; text-align: center;">
                <span style="font-size: 11px; color: #94a3b8;">
                    <i class="fas fa-lock"></i> Xác minh liveness 8 lớp bảo mật + Phát hiện nháy mắt — Chỉ khuôn mặt thật mới được chấm công
                </span>
            </div>
        </div>
    </div>
</div>

<script src="public/js/face-api.js"></script>
<script src="public/js/face-liveness.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiBase = 'index.php?page=';

    // ─── Data Loading (Unchanged from original) ───────────────────
    function formatTimeFromHours(soGio) {
        if (!soGio || soGio <= 0) return '—';
        const h = Math.floor(soGio);
        const m = Math.round((soGio - h) * 60);
        return (h > 0 ? h + 'h ' : '') + m + 'm';
    }

    function loadData() {
        // IP check + Wifi list
        fetch(apiBase + 'attendance-validate-network')
            .then(res => res.json())
            .then(data => {
                document.getElementById('ip-display').textContent = data.ip || 'Unknown';
                document.getElementById('network-validation').innerHTML = data.is_allowed 
                    ? '<span class="badge-trangThai bg-success">Hợp lệ</span>'
                    : '<span class="badge-trangThai bg-danger">Mạng ngoài</span>';
                
                const wifiSelect = document.getElementById('wifi-select');
                if (data.allowed_networks && data.allowed_networks.length > 0) {
                    let options = '';
                    let matched = false;
                    data.allowed_networks.forEach(w => {
                        const isMatch = data.ip && data.ip.startsWith(w.daiIP);
                        options += `<option value="${w.tenWifi}" ${isMatch ? 'selected' : ''}>${w.tenWifi}</option>`;
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
                    document.getElementById('total-soGio').textContent = formatTimeFromHours(data.total_hours);
                }
            });

        // History
        fetch(apiBase + 'attendance-history&limit=5')
            .then(res => res.json())
            .then(data => {
                const historyList = document.getElementById('history-list');
                if (data.success && data.data.length > 0) {
                    let html = '<table class="history-table-compact"><thead><tr><th>Ngày</th><th>WiFi</th><th>Vào</th><th>Ra</th></tr></thead><tbody>';
                    data.data.forEach(r => {
                        const wifiDisplay = r.tenWifi || 'Wifi Công ty';
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

    // ─── Liveness Variables ───────────────────────────────────────
    let currentAction = 'IN';
    let faceApiLoaded = false;
    let modalStream = null;
    let detectionInterval = null;
    let livenessDetector = null;
    let isVerificationComplete = false;

    // ─── Button Handlers ──────────────────────────────────────────
    document.getElementById('checkin-btn').onclick = () => {
        triggerLivenessAttendance('IN');
    };

    document.getElementById('checkout-btn').onclick = () => {
        triggerLivenessAttendance('OUT');
    };

    // ─── Main Liveness Flow ───────────────────────────────────────
    async function triggerLivenessAttendance(action) {
        currentAction = action;
        isVerificationComplete = false;

        const modal = document.getElementById('liveness-modal');
        modal.style.display = 'flex';

        const title = document.getElementById('liveness-modal-title');
        title.innerHTML = '<i class="fas fa-shield-alt"></i> Xác minh khuôn mặt — Chấm công ' + (action === 'IN' ? 'Vào' : 'Ra');

        // Reset UI
        resetLivenessUI();

        const statusEl = document.getElementById('liveness-status');
        statusEl.className = 'liveness-status liveness-status-info';
        statusEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải mô hình nhận diện khuôn mặt...';

        // Load face-api models (Tính toán đường dẫn tuyệt đối local public/models/)
        if (!faceApiLoaded) {
            // Khởi tạo TensorFlow.js backend (WebGL / CPU) trước khi load models
            if (window.faceapi && faceapi.tf) {
                try {
                    await faceapi.tf.setBackend('webgl');
                } catch (e1) {
                    try {
                        await faceapi.tf.setBackend('cpu');
                    } catch (e2) {}
                }
                if (typeof faceapi.tf.ready === 'function') {
                    await faceapi.tf.ready();
                }
            }

            const getLocalPath = () => {
                const p = window.location.pathname;
                const dir = p.substring(0, p.lastIndexOf('/') + 1);
                return dir + 'public/models';
            };
            const localUrl = getLocalPath();
            const cdnUrl = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
            
            try {
                await faceapi.nets.tinyFaceDetector.loadFromUri(localUrl);
                await faceapi.nets.faceLandmark68Net.loadFromUri(localUrl);
                await faceapi.nets.faceRecognitionNet.loadFromUri(localUrl);
                faceApiLoaded = true;
                console.log('✓ Loaded face-api models from:', localUrl);
            } catch (errLocal) {
                console.warn('Local model load failed (' + localUrl + '), trying CDN...', errLocal);
                try {
                    await faceapi.nets.tinyFaceDetector.loadFromUri(cdnUrl);
                    await faceapi.nets.faceLandmark68Net.loadFromUri(cdnUrl);
                    await faceapi.nets.faceRecognitionNet.loadFromUri(cdnUrl);
                    faceApiLoaded = true;
                    console.log('✓ Loaded face-api models from CDN');
                } catch (errCdn) {
                    console.error('Lỗi tải mô hình face-api:', errLocal, errCdn);
                    updateLivenessStatus('Lỗi khởi tạo thư viện nhận dạng khuôn mặt: ' + (errLocal.message || errLocal), 'error');
                    return;
                }
            }
        }

        updateLivenessStatus('Đang khởi động camera...', 'info');

        // Start camera
        const video = document.getElementById('liveness-video');
        try {
            modalStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }
            });
            video.srcObject = modalStream;

            video.onplay = () => {
                initLivenessDetector();
                if (detectionInterval) clearTimeout(detectionInterval);
                detectFaceLiveness();
            };
        } catch (err) {
            console.error('Không thể mở camera:', err);
            updateLivenessStatus('Không thể truy cập camera. Vui lòng cho phép quyền camera.', 'error');
        }
    }

    // ─── Initialize LivenessDetector ──────────────────────────────
    function initLivenessDetector() {
        const video = document.getElementById('liveness-video');
        const canvas = document.getElementById('liveness-canvas');

        // Destroy previous instance if exists
        if (livenessDetector) {
            livenessDetector.destroy();
        }

        livenessDetector = new LivenessDetector(video, canvas, {
            onStatusChange: (message, type) => {
                updateLivenessStatus(message, type);
            },

            onProgress: (percent, label) => {
                const bar = document.getElementById('liveness-progress-bar');
                const labelEl = document.getElementById('liveness-progress-label');
                bar.style.width = percent + '%';
                labelEl.textContent = percent + '% — ' + label;
            },

            onBlinkDetected: (count) => {
                const indicator = document.getElementById('liveness-blink-indicator');
                const icon = document.getElementById('blink-icon');
                const text = document.getElementById('blink-text');
                indicator.className = 'liveness-blink-indicator blink-detected';
                icon.className = 'fas fa-check-circle';
                text.textContent = `✅ Đã phát hiện ${count} lần chớp mắt!`;
            },

            onComplete: async (livenessToken, descriptor) => {
                isVerificationComplete = true;
                document.getElementById('liveness-challenge-overlay').style.display = 'none';

                // Update camera border
                document.getElementById('liveness-camera').classList.remove('detecting');
                document.getElementById('liveness-camera').classList.add('success');

                // Now submit to server
                await submitAttendance(livenessToken, descriptor);
            },

            onFail: (reason) => {
                // Show spoof alert
                document.getElementById('liveness-challenge-overlay').style.display = 'none';
                document.getElementById('liveness-spoof-alert').style.display = 'block';
                document.getElementById('liveness-spoof-reason').textContent = reason;
                document.getElementById('liveness-retry-btn').style.display = 'block';

                // Update camera border
                document.getElementById('liveness-camera').classList.remove('detecting');
                document.getElementById('liveness-camera').classList.add('failed');

                // Stop detection
                if (detectionInterval) {
                    clearTimeout(detectionInterval);
                    detectionInterval = null;
                }
            }
        });

        // Start the liveness session
        livenessDetector.start();
    }

    // ─── Face Detection Loop with Liveness Processing ─────────────
    let isDetecting = false;

    async function detectFaceLiveness() {
        const video = document.getElementById('liveness-video');
        if (!faceApiLoaded || video.paused || video.ended) {
            detectionInterval = setTimeout(detectFaceLiveness, 500);
            return;
        }

        if (isDetecting || isVerificationComplete) return;
        isDetecting = true;

        try {
            const canvas = document.getElementById('liveness-canvas');
            const displaySize = { width: video.videoWidth || 640, height: video.videoHeight || 480 };
            faceapi.matchDimensions(canvas, displaySize);

            const detection = await faceapi.detectSingleFace(
                video,
                new faceapi.TinyFaceDetectorOptions({ inputSize: 128, scoreThreshold: 0.4 })
            ).withFaceLandmarks().withFaceDescriptor();

            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (detection) {
                // Draw face bounding box
                const resized = faceapi.resizeResults(detection, displaySize);
                const box = resized.detection.box;

                const state = livenessDetector ? livenessDetector.getState() : 'idle';
                if (state === 'completed') {
                    ctx.strokeStyle = '#10b981';
                } else if (state === 'failed') {
                    ctx.strokeStyle = '#ef4444';
                } else {
                    ctx.strokeStyle = '#3b82f6';
                    document.getElementById('liveness-camera').classList.add('detecting');
                }
                ctx.lineWidth = 3;
                ctx.strokeRect(box.x, box.y, box.width, box.height);

                // Draw EAR indicators (small dots on eye landmarks)
                if (detection.landmarks) {
                    const positions = detection.landmarks.positions;
                    // Left eye: 36-41, Right eye: 42-47
                    ctx.fillStyle = '#10b981';
                    for (let i = 36; i <= 47; i++) {
                        const p = faceapi.resizeResults({ x: positions[i].x, y: positions[i].y }, displaySize);
                        // Scale manually since resizeResults expects detection format
                        const sx = positions[i].x * (displaySize.width / (video.videoWidth || 640));
                        const sy = positions[i].y * (displaySize.height / (video.videoHeight || 480));
                        ctx.beginPath();
                        ctx.arc(sx, sy, 2, 0, 2 * Math.PI);
                        ctx.fill();
                    }
                }

                // Feed detection to liveness engine
                if (livenessDetector && !isVerificationComplete) {
                    livenessDetector.processFrame(detection);
                }
            } else {
                if (livenessDetector && !isVerificationComplete) {
                    livenessDetector.processFrame(null);
                }
            }
        } catch (err) {
            console.error('Lỗi nhận dạng:', err);
        }

        isDetecting = false;
        if (!isVerificationComplete) {
            detectionInterval = setTimeout(detectFaceLiveness, 16);
        }
    }

    // ─── Submit Attendance to Server ──────────────────────────────
    async function submitAttendance(livenessToken, descriptor) {
        updateLivenessStatus('Đang gửi kết quả xác thực lên máy chủ...', 'info');

        const video = document.getElementById('liveness-video');
        const snapCanvas = document.getElementById('liveness-snapshot');
        snapCanvas.width = video.videoWidth || 640;
        snapCanvas.height = video.videoHeight || 480;
        const sCtx = snapCanvas.getContext('2d');

        // Mirror snapshot to match what user sees
        sCtx.translate(snapCanvas.width, 0);
        sCtx.scale(-1, 1);
        sCtx.drawImage(video, 0, 0, snapCanvas.width, snapCanvas.height);
        sCtx.setTransform(1, 0, 0, 1, 0, 0);

        const photoBase64 = snapCanvas.toDataURL('image/jpeg', 0.85);
        const embeddingStr = JSON.stringify(Array.from(descriptor));
        const wifi = document.getElementById('wifi-select').value;

        const formData = new FormData();
        formData.append('hanhDong', currentAction);
        formData.append('tenWifi', wifi || 'INTERNAL_NETWORK');
        formData.append('phuongThuc', 'LAN');
        formData.append('embedding', embeddingStr);
        formData.append('photo', photoBase64);
        formData.append('livenessToken', JSON.stringify(livenessToken));

        try {
            const response = await fetch('index.php?page=face-api-verify', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                updateLivenessStatus(result.message, 'success');
                showMsg('Chấm công ' + (currentAction === 'IN' ? 'vào' : 'ra') + ' thành công! (Liveness Score: ' + ((result.livenessScore || 0) * 100).toFixed(0) + '%)', 'success');

                setTimeout(() => {
                    closeLivenessModal();
                    loadData();
                }, 2000);
            } else {
                updateLivenessStatus(result.message || 'Xác thực thất bại', 'error');
                document.getElementById('liveness-retry-btn').style.display = 'block';
            }
        } catch (err) {
            console.error('Lỗi API xác thực:', err);
            updateLivenessStatus('Lỗi kết nối mạng hoặc máy chủ.', 'error');
            document.getElementById('liveness-retry-btn').style.display = 'block';
        }
    }

    // ─── UI Helper Functions ──────────────────────────────────────
    function updateLivenessStatus(message, type) {
        const el = document.getElementById('liveness-status');
        const iconMap = {
            'info': 'fa-spinner fa-spin',
            'success': 'fa-check-circle',
            'warning': 'fa-exclamation-circle',
            'error': 'fa-times-circle'
        };
        el.className = 'liveness-status liveness-status-' + type;
        el.innerHTML = `<i class="fas ${iconMap[type] || 'fa-info-circle'}"></i> ${message}`;
    }

    function resetLivenessUI() {
        // Reset progress

        // Reset progress
        document.getElementById('liveness-progress-bar').style.width = '0%';
        document.getElementById('liveness-progress-label').textContent = '0%';

        // Hide alerts
        document.getElementById('liveness-spoof-alert').style.display = 'none';
        document.getElementById('liveness-retry-btn').style.display = 'none';
        document.getElementById('liveness-challenge-overlay').style.display = 'none';

        // Reset blink indicator
        const blinkIndicator = document.getElementById('liveness-blink-indicator');
        const blinkIcon = document.getElementById('blink-icon');
        const blinkText = document.getElementById('blink-text');
        blinkIndicator.className = 'liveness-blink-indicator blink-waiting';
        blinkIcon.className = 'fas fa-eye';
        blinkText.textContent = 'Chờ phát hiện chớp mắt tự nhiên...';

        // Reset camera border
        const camera = document.getElementById('liveness-camera');
        camera.classList.remove('detecting', 'success', 'failed');
    }

    function closeLivenessModal() {
        // Stop detection loop
        if (detectionInterval) {
            clearTimeout(detectionInterval);
            detectionInterval = null;
        }

        // Destroy liveness detector
        if (livenessDetector) {
            livenessDetector.destroy();
            livenessDetector = null;
        }

        // Stop camera
        if (modalStream) {
            modalStream.getTracks().forEach(track => track.stop());
            modalStream = null;
        }

        const video = document.getElementById('liveness-video');
        video.srcObject = null;

        document.getElementById('liveness-modal').style.display = 'none';
        isVerificationComplete = false;
        isDetecting = false;

        // Clear canvas
        const canvas = document.getElementById('liveness-canvas');
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        resetLivenessUI();
    }

    // ─── Event Handlers ───────────────────────────────────────────
    document.getElementById('liveness-close-btn').onclick = closeLivenessModal;

    document.getElementById('liveness-retry-btn').onclick = () => {
        resetLivenessUI();
        isVerificationComplete = false;
        isDetecting = false;
        initLivenessDetector();
        detectFaceLiveness();
    };

    // Close modal on background click
    document.getElementById('liveness-modal').onclick = (e) => {
        if (e.target === document.getElementById('liveness-modal')) {
            closeLivenessModal();
        }
    };

    // ─── Clock & Initial Load ─────────────────────────────────────
    function updateClock() {
        document.getElementById('clock-mini').textContent = new Date().toLocaleTimeString('en-GB');
    }
    setInterval(updateClock, 1000);
    updateClock();
    loadData();
});
</script>
