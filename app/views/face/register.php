<?php
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    
    <div class="dashboard-container" style="padding: 24px; font-family: 'Inter', sans-serif;">
        <style>
            .face-reg-wrap { max-width: 900px; margin: 0 auto; background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 24px; }
            .face-reg-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; }
            .face-reg-title h2 { margin: 0; font-size: 20px; color: #0f172a; font-weight: 700; }
            .face-reg-title p { margin: 4px 0 0; font-size: 13px; color: #64748b; }
            
            .camera-area { position: relative; width: 100%; max-width: 640px; margin: 0 auto; aspect-ratio: 4/3; background: #0f172a; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0; }
            #video-element { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
            #overlay-canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; transform: scaleX(-1); }
            
            .status-banner { padding: 12px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; text-align: center; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; gap: 8px; }
            .status-loading { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
            .status-ready { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
            .status-noface { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
            .status-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

            .reg-controls { margin-top: 24px; display: flex; flex-direction: column; gap: 16px; }
            .select-target { display: flex; flex-direction: column; gap: 6px; }
            .select-target label { font-size: 13px; font-weight: 600; color: #475569; }
            
            .btn-action { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px; border-radius: 8px; font-size: 15px; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; color: white; background: #3b82f6; box-shadow: 0 4px 6px -1px rgba(59,130,246,0.2); }
            .btn-action:hover:not(:disabled) { background: #2563eb; transform: translateY(-1px); }
            .btn-action:disabled { opacity: 0.5; cursor: not-allowed; }
            
            .history-indicator { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; color: #475569; }
            
            /* Responsive layout */
            @media (max-width: 640px) { .face-reg-wrap { padding: 16px; } }
        </style>

        <div class="face-reg-wrap">
            <div class="face-reg-header">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #eff6ff; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #3b82f6;">
                    <i class="fas fa-portrait"></i>
                </div>
                <div class="face-reg-title">
                    <h2>Đăng ký khuôn mặt Nhân viên</h2>
                    <p>Chụp và lưu trữ vector đặc trưng khuôn mặt phục vụ chấm công nhận diện</p>
                </div>
            </div>

            <!-- Trạng thái -->
            <div id="status-display" class="status-banner status-loading">
                <i class="fas fa-spinner fa-spin"></i> Đang tải mô hình nhận diện khuôn mặt...
            </div>

            <!-- Stepper chỉ hướng quét khuôn mặt -->
            <div id="face-stepper" style="display: none; justify-content: space-around; margin-bottom: 20px; background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div id="step-front" style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #3b82f6; transition: all 0.3s;">
                    <span class="step-num" style="width: 24px; height: 24px; border-radius: 50%; background: #3b82f6; color: white; border: 2px solid #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 11px;">1</span>
                    <span class="step-text">Chính diện</span>
                </div>
                <div id="step-turn1" style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #94a3b8; transition: all 0.3s;">
                    <span class="step-num" style="width: 24px; height: 24px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-size: 11px;">2</span>
                    <span class="step-text">Quay nghiêng 1</span>
                </div>
                <div id="step-turn2" style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #94a3b8; transition: all 0.3s;">
                    <span class="step-num" style="width: 24px; height: 24px; border-radius: 50%; background: #f1f5f9; color: #94a3b8; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-size: 11px;">3</span>
                    <span class="step-text">Quay nghiêng 2</span>
                </div>
            </div>

            <!-- Khu vực Camera Feed -->
            <div class="camera-area">
                <video id="video-element" autoplay muted playsinline></video>
                <canvas id="overlay-canvas"></canvas>
            </div>

            <div class="reg-controls">
                <!-- Dropdown chọn nhân viên (chỉ hiển thị cho HR/Manager) -->
                <?php if ($isHR && !empty($employeesList)): ?>
                    <div class="select-target">
                        <label for="employee-select"><i class="fas fa-user-tag"></i> Đăng ký cho nhân viên:</label>
                        <select id="employee-select" class="yc-input" style="height: 42px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 8px 12px;">
                            <option value="<?= $currentMaND ?>">-- Bản thân (<?= htmlspecialchars($_SESSION['user']['hoTen']) ?>) --</option>
                            <?php foreach ($employeesList as $emp): ?>
                                <?php if ($emp['maND'] != $currentMaND): ?>
                                    <option value="<?= $emp['maND'] ?>"><?= htmlspecialchars($emp['hoTen']) ?> (<?= htmlspecialchars($emp['phongBan'] ?? '') ?> - ID: <?= $emp['maND'] ?>)</option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" id="employee-select" value="<?= $currentMaND ?>">
                <?php endif; ?>

                <div class="history-indicator">
                    <span><i class="fas fa-key"></i> Trạng thái khuôn mặt của bạn:</span>
                    <span id="reg-status-badge">
                        <?php if ($existingProfile): ?>
                            <span style="color: #10b981; font-weight:700;"><i class="fas fa-check-circle"></i> ĐÃ ĐĂNG KÝ (<?= date('d/m/Y', strtotime($existingProfile['ngayCapNhat'] ?? $existingProfile['ngayTao'])) ?>)</span>
                        <?php else: ?>
                            <span style="color: #64748b; font-weight:700;"><i class="fas fa-times-circle"></i> CHƯA ĐĂNG KÝ</span>
                        <?php endif; ?>
                    </span>
                </div>

                <button id="btn-register" class="btn-action" disabled>
                    <i class="fas fa-camera"></i> ĐĂNG KÝ KHUÔN MẶT
                </button>
            </div>
        </div>
    </div>
</div>

<script src="public/js/face-api.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const video = document.getElementById('video-element');
    const canvas = document.getElementById('overlay-canvas');
    const statusDisplay = document.getElementById('status-display');
    const btnRegister = document.getElementById('btn-register');
    const employeeSelect = document.getElementById('employee-select');
    
    let lastDescriptor = null;
    let isModelLoaded = false;
    let cameraStream = null;

    // Các biến trạng thái của Stepper quét 3 bước
    let currentStep = 'front'; // 'front', 'turn1', 'turn2', 'completed'
    let successFrames = 0;
    const requiredSuccessFrames = 8; // ~1-1.2 giây nhận diện ổn định mỗi bước
    let savedFrontDescriptor = null;
    let firstTurnSide = null; // 'left' hoặc 'right'

    const stepFront = document.getElementById('step-front');
    const stepTurn1 = document.getElementById('step-turn1');
    const stepTurn2 = document.getElementById('step-turn2');
    const faceStepper = document.getElementById('face-stepper');

    // Hàm cập nhật trạng thái trực quan cho Stepper
    function updateStepperUI() {
        if (!faceStepper) return;
        faceStepper.style.display = 'flex';
        
        const resetStep = (el, stepNum) => {
            el.style.color = '#94a3b8';
            el.style.fontWeight = '600';
            const num = el.querySelector('.step-num');
            num.style.background = '#f1f5f9';
            num.style.color = '#94a3b8';
            num.style.borderColor = '#cbd5e1';
            num.innerHTML = stepNum;
        };
        
        const activeStep = (el, stepNum) => {
            el.style.color = '#3b82f6';
            el.style.fontWeight = '700';
            const num = el.querySelector('.step-num');
            num.style.background = '#3b82f6';
            num.style.color = 'white';
            num.style.borderColor = '#3b82f6';
            num.innerHTML = stepNum;
        };
        
        const completeStep = (el) => {
            el.style.color = '#10b981';
            el.style.fontWeight = '700';
            const num = el.querySelector('.step-num');
            num.style.background = '#e2fbf0';
            num.style.color = '#10b981';
            num.style.borderColor = '#10b981';
            num.innerHTML = '<i class="fas fa-check"></i>';
        };
        
        if (currentStep === 'front') {
            activeStep(stepFront, '1');
            resetStep(stepTurn1, '2');
            resetStep(stepTurn2, '3');
        } else if (currentStep === 'turn1') {
            completeStep(stepFront);
            activeStep(stepTurn1, '2');
            resetStep(stepTurn2, '3');
        } else if (currentStep === 'turn2') {
            completeStep(stepFront);
            completeStep(stepTurn1);
            activeStep(stepTurn2, '3');
        } else if (currentStep === 'completed') {
            completeStep(stepFront);
            completeStep(stepTurn1);
            completeStep(stepTurn2);
        }
    }

    // 1. Tải các file weights từ CDN (chứa manifests + shards)
    const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
    
    try {
        await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
        
        isModelLoaded = true;
        statusDisplay.className = 'status-banner status-noface';
        statusDisplay.innerHTML = '<i class="fas fa-video"></i> Mô hình đã tải. Đang khởi chạy camera...';
        
        updateStepperUI();
        startCamera();
    } catch (err) {
        console.error('Lỗi tải mô hình face-api:', err);
        statusDisplay.className = 'status-banner status-error';
        statusDisplay.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Lỗi tải thư viện nhận dạng khuôn mặt. Vui lòng kết nối mạng.';
    }

    let isDetecting = false;
    let detectionTimeout = null;

    // 2. Khởi chạy Camera
    async function startCamera() {
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'user',
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            });
            video.srcObject = cameraStream;
            video.onplay = () => {
                if (detectionTimeout) clearTimeout(detectionTimeout);
                detectFace();
            };
            video.onplaying = () => {
                if (detectionTimeout) clearTimeout(detectionTimeout);
                detectFace();
            };
            // Force play for iOS Safari
            await video.play();
            // Gọi dự phòng lập tức sau khi bắt đầu để chống lỗi trễ sự kiện của trình duyệt di động
            setTimeout(detectFace, 300);
        } catch (err) {
            console.error('Không thể truy cập camera:', err);
            statusDisplay.className = 'status-banner status-error';
            statusDisplay.innerHTML = '<i class="fas fa-camera-slash"></i> Không thể truy cập Camera. Vui lòng cấp quyền camera.';
        }
    }

    // 3. Phân tích khuôn mặt từ luồng video tuần tự (tránh đè luồng gây đơ CPU)
    async function detectFace() {
        if (!isModelLoaded || video.paused || video.ended || video.readyState < 2) {
            detectionTimeout = setTimeout(detectFace, 300);
            return;
        }

        if (isDetecting) return;
        isDetecting = true;

        // Wait for camera exposure to stabilize
        if (!window.regWarmupFrames) window.regWarmupFrames = 0;
        if (window.regWarmupFrames < 5) {
            window.regWarmupFrames++;
            statusDisplay.className = 'status-banner status-loading';
            statusDisplay.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tối ưu hóa độ sáng camera...';
            isDetecting = false;
            detectionTimeout = setTimeout(detectFace, 150);
            return;
        }

        try {
            const displaySize = { width: video.videoWidth || 640, height: video.videoHeight || 480 };
            faceapi.matchDimensions(canvas, displaySize);

            // Nhận diện khuôn mặt với SsdMobilenetv1 chất lượng cao
            const detection = await faceapi.detectSingleFace(
                video, 
                new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 })
            ).withFaceLandmarks().withFaceDescriptor();

            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (detection) {
                // Resize kết quả vẽ
                const resizedDetection = faceapi.resizeResults(detection, displaySize);
                
                // Vẽ khung mặt lên canvas
                const box = resizedDetection.detection.box;
                ctx.strokeStyle = (currentStep === 'completed') ? '#10b981' : '#3b82f6';
                ctx.lineWidth = 3;
                ctx.strokeRect(box.x, box.y, box.width, box.height);

                // Ước lượng hướng đầu từ landmarks 68 điểm
                const landmarks = detection.landmarks;
                const leftJaw = landmarks.positions[0];
                const rightJaw = landmarks.positions[16];
                const noseTip = landmarks.positions[30];

                const dLeft = noseTip.x - leftJaw.x;
                const dRight = rightJaw.x - noseTip.x;

                if (dRight !== 0) {
                    const ratio = dLeft / dRight;
                    
                    if (currentStep === 'front') {
                        // Bước 1: Nhìn thẳng, tỷ lệ cân đối giữa 0.72 và 1.38
                        if (ratio >= 0.72 && ratio <= 1.38) {
                            successFrames++;
                            statusDisplay.className = 'status-banner status-ready';
                            statusDisplay.innerHTML = `<i class="fas fa-smile"></i> Đang quét chính diện... (${Math.round((successFrames/requiredSuccessFrames)*100)}%)`;
                            
                            if (successFrames >= requiredSuccessFrames) {
                                savedFrontDescriptor = detection.descriptor;
                                currentStep = 'turn1';
                                successFrames = 0;
                                updateStepperUI();
                            }
                        } else {
                            successFrames = 0;
                            statusDisplay.className = 'status-banner status-noface';
                            statusDisplay.innerHTML = '<i class="fas fa-arrows-alt-h"></i> Bước 1/3: Nhìn thẳng chính diện vào camera.';
                        }
                    } else if (currentStep === 'turn1') {
                        // Bước 2: Quay đầu nhẹ sang trái hoặc phải
                        if (ratio < 0.68 || ratio > 1.47) {
                            successFrames++;
                            firstTurnSide = ratio < 0.68 ? 'left' : 'right';
                            const sideText = firstTurnSide === 'left' ? 'trái' : 'phải';
                            statusDisplay.className = 'status-banner status-ready';
                            statusDisplay.innerHTML = `<i class="fas fa-sync"></i> Đang quét góc thứ nhất (${sideText})... (${Math.round((successFrames/requiredSuccessFrames)*100)}%)`;
                            
                            if (successFrames >= requiredSuccessFrames) {
                                currentStep = 'turn2';
                                successFrames = 0;
                                updateStepperUI();
                            }
                        } else {
                            successFrames = 0;
                            statusDisplay.className = 'status-banner status-noface';
                            statusDisplay.innerHTML = '<i class="fas fa-chevron-left"></i> Bước 2/3: Quay đầu nhẹ sang bên TRÁI hoặc bên PHẢI.';
                        }
                    } else if (currentStep === 'turn2') {
                        // Bước 3: Quay đầu nhẹ sang hướng ngược lại
                        const isOppositeOk = (firstTurnSide === 'left') ? (ratio > 1.47) : (ratio < 0.68);
                        const oppositeText = (firstTurnSide === 'left') ? 'PHẢI' : 'TRÁI';
                        
                        if (isOppositeOk) {
                            successFrames++;
                            statusDisplay.className = 'status-banner status-ready';
                            statusDisplay.innerHTML = `<i class="fas fa-sync"></i> Đang quét góc thứ hai (${oppositeText.toLowerCase()})... (${Math.round((successFrames/requiredSuccessFrames)*100)}%)`;
                            
                            if (successFrames >= requiredSuccessFrames) {
                                currentStep = 'completed';
                                successFrames = 0;
                                updateStepperUI();
                            }
                        } else {
                            successFrames = 0;
                            statusDisplay.className = 'status-banner status-noface';
                            statusDisplay.innerHTML = `<i class="fas fa-chevron-right"></i> Bước 3/3: Quay đầu nhẹ sang hướng ngược lại (${oppositeText}).`;
                        }
                    } else if (currentStep === 'completed') {
                        statusDisplay.className = 'status-banner status-ready';
                        statusDisplay.innerHTML = '<i class="fas fa-check-circle"></i> Đã quét đủ 3 góc khuôn mặt! Nhấn nút bên dưới để lưu.';
                        btnRegister.disabled = false;
                        
                        // Capture fresh straight-facing descriptor at the end when user is steady
                        const landmarks = detection.landmarks;
                        const leftJaw = landmarks.positions[0];
                        const rightJaw = landmarks.positions[16];
                        const noseTip = landmarks.positions[30];
                        const dLeft = noseTip.x - leftJaw.x;
                        const dRight = rightJaw.x - noseTip.x;
                        if (dRight !== 0) {
                            const ratio = dLeft / dRight;
                            // Verify they are looking straight (ratio balanced) to get optimal vector quality
                            if (ratio >= 0.72 && ratio <= 1.38) {
                                lastDescriptor = detection.descriptor;
                            }
                        }
                        if (!lastDescriptor) {
                            lastDescriptor = detection.descriptor; // Fallback to current frame descriptor
                        }
                    }
                }
            } else {
                statusDisplay.className = 'status-banner status-noface';
                if (currentStep === 'front') {
                    statusDisplay.innerHTML = '<i class="fas fa-user-slash"></i> Vui lòng căn chỉnh khuôn mặt thẳng góc với camera.';
                } else if (currentStep === 'turn1') {
                    statusDisplay.innerHTML = '<i class="fas fa-chevron-left"></i> Bước 2/3: Quay đầu nhẹ sang bên TRÁI hoặc bên PHẢI.';
                } else if (currentStep === 'turn2') {
                    const oppositeText = (firstTurnSide === 'left') ? 'PHẢI' : 'TRÁI';
                    statusDisplay.innerHTML = `<i class="fas fa-chevron-right"></i> Bước 3/3: Quay đầu nhẹ sang hướng ngược lại (${oppositeText}).`;
                } else if (currentStep === 'completed') {
                    statusDisplay.innerHTML = '<i class="fas fa-check-circle"></i> Đã quét đủ 3 góc khuôn mặt! Nhấn nút bên dưới để lưu.';
                }
                btnRegister.disabled = (currentStep !== 'completed');
            }
        } catch (err) {
            console.error('Lỗi phân tích khuôn mặt:', err);
        }

        isDetecting = false;
        detectionTimeout = setTimeout(detectFace, 150);
    }

    // Reset scanner if target employee changes
    if (employeeSelect) {
        employeeSelect.onchange = () => {
            currentStep = 'front';
            successFrames = 0;
            savedFrontDescriptor = null;
            firstTurnSide = null;
            btnRegister.disabled = true;
            window.regWarmupFrames = 0;
            updateStepperUI();
        };
    }

    // Hủy timeout khi trang unload
    window.addEventListener('beforeunload', () => {
        if (detectionTimeout) clearTimeout(detectionTimeout);
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
        }
    });

    // 4. Nhấn nút đăng ký — hiện dialog Hủy/Lưu TRƯỚC KHI gửi API
    btnRegister.onclick = async () => {
        if (!lastDescriptor) return;

        // Hiển thị dialog xác nhận với 2 nút Hủy và Lưu
        const userConfirmed = confirm('Bạn có chắc chắn muốn LƯU dữ liệu khuôn mặt này?\n\n• Nhấn [OK] để LƯU đăng ký.\n• Nhấn [Cancel] để HỦY và quét lại.');
        
        if (!userConfirmed) {
            // Người dùng chọn HỦY — reset toàn bộ trạng thái quét, yêu cầu làm lại 3 bước
            currentStep = 'front';
            successFrames = 0;
            savedFrontDescriptor = null;
            firstTurnSide = null;
            lastDescriptor = null;
            btnRegister.disabled = true;
            window.regWarmupFrames = 0;
            updateStepperUI();
            
            statusDisplay.className = 'status-banner status-noface';
            statusDisplay.innerHTML = '<i class="fas fa-redo"></i> Đã hủy. Vui lòng thực hiện lại từ Bước 1: Nhìn thẳng vào camera.';
            return;
        }

        // Người dùng xác nhận LƯU — tiến hành gửi API
        btnRegister.disabled = true;
        const targetMaND = employeeSelect.value;
        const embeddingString = JSON.stringify(Array.from(lastDescriptor));

        const formData = new FormData();
        formData.append('embedding', embeddingString);
        formData.append('targetMaND', targetMaND);

        statusDisplay.className = 'status-banner status-loading';
        statusDisplay.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu khuôn mặt lên máy chủ...';

        try {
            const response = await fetch('index.php?page=face-api-register', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                statusDisplay.className = 'status-banner status-ready';
                statusDisplay.innerHTML = '<i class="fas fa-check-circle"></i> ' + result.message;
                
                // Cập nhật nhãn trạng thái đăng ký nếu đăng ký cho chính mình
                if (targetMaND == '<?= $currentMaND ?>') {
                    document.getElementById('reg-status-badge').innerHTML = 
                        '<span style="color: #10b981; font-weight:700;"><i class="fas fa-check-circle"></i> ĐÃ ĐĂNG KÝ (Vừa cập nhật)</span>';
                }
                
                // Dừng camera detection loop
                if (detectionTimeout) clearTimeout(detectionTimeout);
                
                setTimeout(() => {
                    if (confirm('Đăng ký thành công! Bạn có muốn quay lại Bảng điều khiển để chấm công?')) {
                        window.location.href = 'index.php?page=cham-cong';
                    }
                }, 1000);
            } else {
                statusDisplay.className = 'status-banner status-error';
                statusDisplay.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (result.message || 'Lỗi lưu dữ liệu');
                btnRegister.disabled = false;
            }
        } catch (err) {
            console.error('Lỗi khi gửi API:', err);
            statusDisplay.className = 'status-banner status-error';
            statusDisplay.innerHTML = '<i class="fas fa-wifi"></i> Lỗi kết nối mạng hoặc máy chủ.';
            btnRegister.disabled = false;
        }
    };
});
</script>

<?php include 'app/views/layouts/footer.php'; ?>
