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
        .kiosk { min-height: 100vh; display: grid; grid-template-rows: auto 1fr; }
        .topbar { display: flex; justify-content: space-between; align-items: center; padding: 18px 28px; background: #0b1b31; border-bottom: 1px solid #1e3a5f; z-index: 2; }
        .topbar h1 { margin: 0; font-size: clamp(20px, 3vw, 32px); }
        .topbar a { color: #bfdbfe; text-decoration: none; }
        .stage { position: relative; width: 100%; height: 100%; }
        .camera-card { position: absolute; inset: 0; overflow: hidden; background: #020617; }
        video, canvas { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        video, canvas { transform: scaleX(-1); }
        /* Khung canh khuôn mặt dùng tỷ lệ cố định (không dùng % theo chiều màn hình)
           để không bị dẹp/méo khi xoay tablet sang chế độ đứng (portrait). */
        .guide {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            height: min(78vh, 640px);
            aspect-ratio: 3 / 4;
            max-width: 82vw;
            border: 3px dashed #60a5fa;
            border-radius: 50%;
            box-shadow: 0 0 0 999px #0006;
            pointer-events: none;
        }
        #status { position: absolute; left: 50%; bottom: 32px; transform: translateX(-50%); min-width: min(560px, 90vw); text-align: center; padding: 18px 28px; background: rgba(15, 33, 56, 0.92); border: 1px solid #274568; border-radius: 16px; color: #bfdbfe; font-size: clamp(16px, 2.4vw, 22px); font-weight: 700; box-shadow: 0 10px 40px #0008; }
        .bottom { display: none; }
        @media (max-width: 800px) { .topbar { padding: 14px; } }
    </style>
</head>
<body>
<div class="kiosk">
    <header class="topbar"><h1><i class="fa-solid fa-tablet-screen-button"></i> Tablet chấm công khuôn mặt</h1><a href="index.php?page=quan-ly-ca-lam"><i class="fa-solid fa-arrow-left"></i> Quản lý ca</a></header>
    <main class="stage">
        <div class="camera-card"><video id="kiosk-video" autoplay muted playsinline></video><canvas id="kiosk-canvas"></canvas><div class="guide"></div></div>
        <div id="status">Đang khởi động camera...</div>
    </main>
</div>
    <?php
    $faceApiVersion = @filemtime('public/js/face-api.js') ?: '1';
    $livenessVersion = @filemtime('public/js/face-liveness.js') ?: '1';
    ?>
    <script src="public/js/face-api.js?v=<?= (int)$faceApiVersion ?>"></script>
    <script src="public/js/face-liveness.js?v=<?= (int)$livenessVersion ?>"></script>
<script>
(function () {
    const video = document.getElementById('kiosk-video');
    const canvas = document.getElementById('kiosk-canvas');
    const status = document.getElementById('status');
    let stream = null;
    let detector = null;
    let detecting = false;
    let complete = false;
    let loaded = false;
    let sessionPromise = null;

    function message(text, type) { status.textContent = text; status.style.color = type === 'error' ? '#fecaca' : type === 'success' ? '#bbf7d0' : '#bfdbfe'; }

    function fetchSessionSecret() {
        return fetch('index.php?page=face-liveness-session', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(function (res) { return res.json(); })
        .then(function (data) { return (data.success && data.sessionSecret) ? data.sessionSecret : null; })
        .catch(function () { return null; });
    }

    // Chỉ giữ TỐI ĐA 1 request đang chờ tại một thời điểm — server chỉ lưu được
    // 1 secret/session, nếu bắn 2 fetch song song thì fetch xong sau sẽ ghi đè secret
    // của fetch kém may mắn hơn, khiến client ký token bằng secret đã bị thay → “chữ
    // ký không hợp lệ”. Dùng chung 1 promise và tiêu thụ đúng 1 lần để tránh race.
    function prefetchSessionSecret() {
        if (!sessionPromise) sessionPromise = fetchSessionSecret();
    }

    async function consumeSessionSecret() {
        if (!sessionPromise) sessionPromise = fetchSessionSecret();
        var promise = sessionPromise;
        sessionPromise = null;
        return await promise;
    }

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

    async function startLiveness() {
        if (detector) detector.destroy();
        complete = false;
        detector = new LivenessDetector(video, canvas, {
            onStatusChange: function (text) { if (!complete) message(text); },
            onProgress: function (percent, label) { if (!complete) message(percent + '% - ' + label); },
            onComplete: function (token, descriptor) { complete = true; submit(token, descriptor); },
            onFail: function (reason) { complete = true; message('Xác thực liveness thất bại: ' + reason, 'error'); setTimeout(restart, 900); }
        });
        const secret = await consumeSessionSecret();
        if (secret) detector.preloadSecret(secret);
        detector.start();
    }

    async function detect() {
        if (!complete && video.readyState >= 2 && !detecting) {
            detecting = true;
            try {
                // inputSize khớp với lúc đăng ký khuôn mặt (224) — trước đây quét tablet dùng 128,
                // descriptor kém chi tiết hơn đăng ký khiến các nhân viên bị co cụm quá gần nhau,
                // gây nhận nhầm/khó nhận diện dù embedding gốc không hề giống nhau.
                const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 })).withFaceLandmarks().withFaceDescriptor();
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
        data.append('embedding', JSON.stringify(Array.from(descriptor))); data.append('photo', snapshot.toDataURL('image/jpeg', .85)); data.append('livenessToken', JSON.stringify(token));
        // KHÔNG prefetch secret mới trước khi verify xong — server xoá secret hiện tại
        // ngay khi xác thực chữ ký thành công, nếu prefetch chạy song song và xong trước
        // thì secret bị thay giữa chừng, khiến request verify so chữ ký sai ("giả mạo token").
        try {
            const response = await fetch('index.php?page=tablet-face-api-verify', { method: 'POST', body: data });
            const result = await response.json();
            prefetchSessionSecret(); // giờ mới an toàn để lấy trước secret cho vòng quét kế tiếp
            if (result.success) { message(result.message, 'success'); setTimeout(restart, 1200); }
            else { message(result.message || 'Không thể ghi nhận chấm công.', 'error'); setTimeout(restart, 1200); }
        } catch (e) { message('Không thể kết nối máy chủ.', 'error'); setTimeout(restart, 1200); }
    }

    async function restart() {
        complete = true;
        if (detector) { detector.destroy(); detector = null; }
        if (!stream) {
            try { await loadModels(); stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } } }); video.srcObject = stream; await video.play(); }
            catch (e) { message('Không thể mở camera. Hãy cấp quyền camera cho trình duyệt.', 'error'); return; }
        }
        await startLiveness();
    }
    restart(); requestAnimationFrame(detect);
})();
</script>
</body>
</html>
