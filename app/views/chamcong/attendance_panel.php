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

    <!-- Modal Nhận diện khuôn mặt -->
    <div id="face-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: white; border-radius: 16px; width: 90%; max-width: 500px; padding: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: 1px solid #e2e8f0; position: relative;">
            <button id="close-modal-btn" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 18px; cursor: pointer; color: #64748b;"><i class="fas fa-times"></i></button>
            
            <h3 id="face-modal-title" style="margin: 0 0 15px; font-size: 16px; font-weight: 700; color: #0f172a; text-align: center;">Xác thực khuôn mặt Chấm công</h3>
            
            <div id="face-modal-status" style="padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;">
                Đang tải mô hình nhận diện...
            </div>

            <div style="position: relative; width: 100%; aspect-ratio: 4/3; background: #0f172a; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1;">
                <video id="modal-video" autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
                <canvas id="modal-canvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; transform: scaleX(-1);"></canvas>
            </div>
            
            <canvas id="snapshot-canvas" style="display: none;"></canvas>

            <div style="margin-top: 15px; text-align: center;">
                <span style="font-size: 12px; color: #64748b; display: block; margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Vui lòng căn chỉnh khuôn mặt thẳng góc với camera</span>
                <button id="modal-btn-verify" class="btn-att-mid btn-att-in" style="width: 100%; padding: 12px; display: inline-flex; align-items: center; justify-content: center;" disabled>
                    <i class="fas fa-fingerprint"></i> XÁC THỰC VÀ CHẤM CÔNG
                </button>
            </div>
        </div>
    </div>
</div>

<script src="public/js/face-api.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiBase = 'index.php?page=';

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
        fetch(apiBase + 'attendance-history?limit=5')
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

    let currentAction = 'IN';
    let faceApiLoaded = false;
    let modalStream = null;
    let detectionInterval = null;
    let latestDescriptor = null;

    document.getElementById('checkin-btn').onclick = () => {
        triggerFaceAttendance('IN');
    };

    document.getElementById('checkout-btn').onclick = () => {
        triggerFaceAttendance('OUT');
    };

    async function triggerFaceAttendance(action) {
        currentAction = action;
        const modal = document.getElementById('face-modal');
        modal.style.display = 'flex';
        
        const title = document.getElementById('face-modal-title');
        title.innerHTML = '<i class="fas fa-portrait"></i> Xác thực khuôn mặt - Chấm công ' + (action === 'IN' ? 'Vào' : 'Ra');
        
        const status = document.getElementById('face-modal-status');
        status.style.cssText = 'padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;';
        status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải mô hình nhận diện khuôn mặt...';
        
        if (!faceApiLoaded) {
            const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
            try {
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
                faceApiLoaded = true;
            } catch (err) {
                console.error('Lỗi tải mô hình face-api:', err);
                status.style.background = '#fef2f2';
                status.style.color = '#dc2626';
                status.style.borderColor = '#fecaca';
                status.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Lỗi khởi tạo thư viện nhận dạng khuôn mặt. Vui lòng kiểm tra internet.';
                return;
            }
        }
        
        status.innerHTML = '<i class="fas fa-video"></i> Đang khởi động camera...';
        
        const video = document.getElementById('modal-video');
        try {
            modalStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }
            });
            video.srcObject = modalStream;
            video.onplay = () => {
                if (detectionInterval) clearTimeout(detectionInterval);
                detectFaceModal();
            };
        } catch (err) {
            console.error('Không thể mở camera:', err);
            status.style.background = '#fef2f2';
            status.style.color = '#dc2626';
            status.style.borderColor = '#fecaca';
            status.innerHTML = '<i class="fas fa-camera-slash"></i> Không thể truy cập camera. Vui lòng cho phép quyền camera trên trình duyệt.';
        }
    }

    let isDetectingModal = false;

    async function detectFaceModal() {
        const video = document.getElementById('modal-video');
        if (!faceApiLoaded || video.paused || video.ended) {
            detectionInterval = setTimeout(detectFaceModal, 500);
            return;
        }

        if (isDetectingModal) return;
        isDetectingModal = true;

        try {
            const canvas = document.getElementById('modal-canvas');
            const status = document.getElementById('face-modal-status');
            const btnVerify = document.getElementById('modal-btn-verify');
            
            const displaySize = { width: video.videoWidth || 640, height: video.videoHeight || 480 };
            faceapi.matchDimensions(canvas, displaySize);

            // Nhận diện khuôn mặt với inputSize 160 cho nhẹ
            const detection = await faceapi.detectSingleFace(
                video, 
                new faceapi.TinyFaceDetectorOptions({ inputSize: 160, scoreThreshold: 0.5 })
            ).withFaceLandmarks().withFaceDescriptor();

            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (detection) {
                const resized = faceapi.resizeResults(detection, displaySize);
                const box = resized.detection.box;
                ctx.strokeStyle = '#10b981';
                ctx.lineWidth = 3;
                ctx.strokeRect(box.x, box.y, box.width, box.height);
                
                latestDescriptor = detection.descriptor;
                
                status.style.cssText = 'padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;';
                status.innerHTML = '<i class="fas fa-smile"></i> Khuôn mặt hợp lệ. Sẵn sàng chấm công!';
                btnVerify.disabled = false;
            } else {
                latestDescriptor = null;
                status.style.cssText = 'padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #fffbeb; color: #d97706; border: 1px solid #fef3c7;';
                status.innerHTML = '<i class="fas fa-user-slash"></i> Vui lòng căn chỉnh khuôn mặt thẳng góc với camera.';
                btnVerify.disabled = true;
            }
        } catch (err) {
            console.error('Lỗi nhận dạng:', err);
        }

        isDetectingModal = false;
        detectionInterval = setTimeout(detectFaceModal, 150);
    }

    function closeFaceModal() {
        if (detectionInterval) {
            clearTimeout(detectionInterval);
            detectionInterval = null;
        }
        if (modalStream) {
            modalStream.getTracks().forEach(track => track.stop());
            modalStream = null;
        }
        const video = document.getElementById('modal-video');
        video.srcObject = null;
        document.getElementById('face-modal').style.display = 'none';
        document.getElementById('modal-btn-verify').disabled = true;
        latestDescriptor = null;
        
        const canvas = document.getElementById('modal-canvas');
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    document.getElementById('close-modal-btn').onclick = closeFaceModal;

    document.getElementById('modal-btn-verify').onclick = async () => {
        if (!latestDescriptor) return;
        
        const btnVerify = document.getElementById('modal-btn-verify');
        const status = document.getElementById('face-modal-status');
        btnVerify.disabled = true;
        
        status.style.cssText = 'padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;';
        status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang chụp ảnh và xác thực khuôn mặt...';
        
        const video = document.getElementById('modal-video');
        const snapCanvas = document.getElementById('snapshot-canvas');
        snapCanvas.width = video.videoWidth || 640;
        snapCanvas.height = video.videoHeight || 480;
        const sCtx = snapCanvas.getContext('2d');
        
        sCtx.translate(snapCanvas.width, 0);
        sCtx.scale(-1, 1);
        sCtx.drawImage(video, 0, 0, snapCanvas.width, snapCanvas.height);
        sCtx.setTransform(1, 0, 0, 1, 0, 0);
        
        const photoBase64 = snapCanvas.toDataURL('image/jpeg', 0.85);
        const embeddingStr = JSON.stringify(Array.from(latestDescriptor));
        
        const wifi = document.getElementById('wifi-select').value;
        
        const formData = new FormData();
        formData.append('hanhDong', currentAction);
        formData.append('tenWifi', wifi || 'INTERNAL_NETWORK');
        formData.append('phuongThuc', 'LAN');
        formData.append('embedding', embeddingStr);
        formData.append('photo', photoBase64);
        
        try {
            const response = await fetch('index.php?page=face-api-verify', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                status.style.cssText = 'padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;';
                status.innerHTML = '<i class="fas fa-check-circle"></i> ' + result.message;
                
                showMsg('Chấm công ' + (currentAction === 'IN' ? 'vào' : 'ra') + ' thành công!', 'success');
                
                setTimeout(() => {
                    closeFaceModal();
                    loadData();
                }, 1500);
            } else {
                status.style.cssText = 'padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;';
                status.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (result.message || 'Xác thực thất bại');
                btnVerify.disabled = false;
            }
        } catch (err) {
            console.error('Lỗi API xác thực:', err);
            status.style.cssText = 'padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;';
            status.innerHTML = '<i class="fas fa-wifi"></i> Lỗi kết nối mạng hoặc máy chủ.';
            btnVerify.disabled = false;
        }
    };

    function updateClock() {
        document.getElementById('clock-mini').textContent = new Date().toLocaleTimeString('en-GB');
    }
    setInterval(updateClock, 1000);
    updateClock();
    loadData();
});
</script>
