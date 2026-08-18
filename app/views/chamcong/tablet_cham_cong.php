<?php
if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'hr') {
    header('Location: index.php?page=home');
    exit;
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Tablet chấm công</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { color-scheme: dark; font-family: Inter, system-ui, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #07111f; color: #f8fafc; }
        .kiosk { min-height: 100vh; display: grid; grid-template-rows: auto 1fr auto; }
        .topbar { display: flex; justify-content: space-between; align-items: center; padding: 18px 28px; background: #0b1b31; border-bottom: 1px solid #1e3a5f; }
        .topbar h1 { margin: 0; font-size: clamp(20px, 3vw, 32px); }
        .topbar a { color: #bfdbfe; text-decoration: none; }
        .stage { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 28px; padding: 28px; align-items: center; max-width: 1500px; width: 100%; margin: auto; }
        .camera-card { position: relative; aspect-ratio: 4 / 3; overflow: hidden; background: #020617; border: 2px solid #2563eb; border-radius: 18px; box-shadow: 0 20px 60px #0008; }
        video, canvas { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        video, canvas { transform: scaleX(-1); }
        .guide { position: absolute; inset: 18% 28%; border: 3px dashed #60a5fa; border-radius: 50%; box-shadow: 0 0 0 999px #0006; pointer-events: none; }
        .panel { background: #0f2138; border: 1px solid #274568; border-radius: 18px; padding: 24px; }
        .panel h2 { margin-top: 0; }
        .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 18px 0; }
        button { border: 0; border-radius: 10px; padding: 14px 12px; font: inherit; font-weight: 800; cursor: pointer; color: white; background: #2563eb; }
        button.active { outline: 3px solid #93c5fd; }
        #action-in { background: #059669; } #action-out { background: #dc2626; }
        #status { min-height: 92px; display: grid; place-items: center; text-align: center; padding: 16px; background: #172e4c; border-radius: 12px; color: #bfdbfe; }
        .bottom { padding: 12px 28px 20px; text-align: center; color: #94a3b8; }
        @media (max-width: 800px) { .stage { grid-template-columns: 1fr; padding: 14px; } .panel { order: -1; } .topbar { padding: 14px; } }
    </style>
</head>
<body>
<div class="kiosk">
    <header class="topbar"><h1><i class="fa-solid fa-tablet-screen-button"></i> Tablet chấm công khuôn mặt</h1><a href="index.php?page=quan-ly-ca-lam"><i class="fa-solid fa-arrow-left"></i> Quản lý ca</a></header>
    <main class="stage">
        <div class="camera-card"><video id="kiosk-video" autoplay muted playsinline></video><canvas id="kiosk-canvas"></canvas><div class="guide"></div></div>
        <section class="panel">
            <h2>Chấm công</h2>
            <p>Chọn thao tác, sau đó nhân viên nhìn vào camera và hoàn thành hướng dẫn xác thực.</p>
            <div class="actions"><button id="action-in" class="active" type="button">CHẤM VÀO</button><button id="action-out" type="button">CHẤM RA</button></div>
            <div id="status">Đang khởi động camera...</div>
        </section>
    </main>
    <footer class="bottom">Hệ thống sẽ tự nhận diện nhân viên và ghi nhận theo ca làm việc.</footer>
</div>
<script src="public/js/face-api.js"></script>
<script src="public/js/face-liveness.js"></script>
<script>
(function () {
    const video = document.getElementById('kiosk-video');
    const canvas = document.getElementById('kiosk-canvas');
    const status = document.getElementById('status');
    let action = 'IN';
    let stream = null;
    let detector = null;
    let detecting = false;
    let complete = false;
    let loaded = false;

    function message(text, type) { status.textContent = text; status.style.color = type === 'error' ? '#fecaca' : type === 'success' ? '#bbf7d0' : '#bfdbfe'; }
    document.getElementById('action-in').onclick = function () { action = 'IN'; this.classList.add('active'); document.getElementById('action-out').classList.remove('active'); restart(); };
    document.getElementById('action-out').onclick = function () { action = 'OUT'; this.classList.add('active'); document.getElementById('action-in').classList.remove('active'); restart(); };

    async function loadModels() {
        if (loaded) return;
        const local = 'public/models';
        try {
            await faceapi.nets.tinyFaceDetector.loadFromUri(local);
            await faceapi.nets.faceLandmark68Net.loadFromUri(local);
            await faceapi.nets.faceRecognitionNet.loadFromUri(local);
        } catch (e) {
            const cdn = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
            await faceapi.nets.tinyFaceDetector.loadFromUri(cdn);
            await faceapi.nets.faceLandmark68Net.loadFromUri(cdn);
            await faceapi.nets.faceRecognitionNet.loadFromUri(cdn);
        }
        loaded = true;
    }

    function startLiveness() {
        if (detector) detector.destroy();
        complete = false;
        detector = new LivenessDetector(video, canvas, {
            onStatusChange: function (text) { if (!complete) message(text); },
            onProgress: function (percent, label) { if (!complete) message(percent + '% - ' + label); },
            onComplete: function (token, descriptor) { complete = true; submit(token, descriptor); },
            onFail: function (reason) { complete = true; message('Xác thực liveness thất bại: ' + reason, 'error'); setTimeout(restart, 1800); }
        });
        detector.start();
    }

    async function detect() {
        if (!complete && video.readyState >= 2 && !detecting) {
            detecting = true;
            try {
                const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 128, scoreThreshold: 0.4 })).withFaceLandmarks().withFaceDescriptor();
                if (detection && detector && !complete) detector.processFrame(detection);
                else if (detector && !complete) detector.processFrame(null);
            } catch (e) { console.error(e); }
            detecting = false;
        }
        requestAnimationFrame(detect);
    }

    async function submit(token, descriptor) {
        message('Đang nhận diện nhân viên và ghi nhận chấm công...');
        const snapshot = document.createElement('canvas');
        snapshot.width = video.videoWidth || 640; snapshot.height = video.videoHeight || 480;
        const ctx = snapshot.getContext('2d'); ctx.translate(snapshot.width, 0); ctx.scale(-1, 1); ctx.drawImage(video, 0, 0, snapshot.width, snapshot.height);
        const data = new FormData();
        data.append('hanhDong', action); data.append('embedding', JSON.stringify(Array.from(descriptor))); data.append('photo', snapshot.toDataURL('image/jpeg', .85)); data.append('livenessToken', JSON.stringify(token));
        try {
            const response = await fetch('index.php?page=tablet-face-api-verify', { method: 'POST', body: data });
            const result = await response.json();
            if (result.success) { message(result.message, 'success'); setTimeout(restart, 2200); }
            else { message(result.message || 'Không thể ghi nhận chấm công.', 'error'); setTimeout(restart, 2200); }
        } catch (e) { message('Không thể kết nối máy chủ.', 'error'); setTimeout(restart, 2200); }
    }

    async function restart() {
        complete = true;
        if (detector) { detector.destroy(); detector = null; }
        if (!stream) {
            try { await loadModels(); stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } } }); video.srcObject = stream; await video.play(); }
            catch (e) { message('Không thể mở camera. Hãy cấp quyền camera cho trình duyệt.', 'error'); return; }
        }
        startLiveness();
    }
    restart(); requestAnimationFrame(detect);
})();
</script>
</body>
</html>
