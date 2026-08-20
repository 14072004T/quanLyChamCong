<?php 
// Kiểm tra xác thực
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}



// Khởi tạo biến mặc định (tránh undefined variable)
$role = $_SESSION['role'] ?? 'nhanvien';
$trangThaiHomNay = $trangThaiHomNay ?? null;
$hasWifi = $hasWifi ?? false;
$stats = $stats ?? [];
$history = $history ?? [];
$success = $success ?? null;
$error = $error ?? null;
$view = $view ?? null;

// Kiểm tra quyền hạn
$allowedRoles = ['nhanvien', 'hr', 'manager', 'tech'];
if (!in_array($role, $allowedRoles)) {
    header('Location: index.php?page=home');
    exit();
}

// Determine which view to include (default: nhanvien dashboard)
if (!isset($view) || is_null($view)) {
    if ($role === 'hr') {
        $view = 'app/views/chamcong/hr_panel.php';
    } elseif ($role === 'manager') {
        $view = 'app/views/chamcong/manager_panel.php';
    } elseif ($role === 'tech') {
        // Tech views should be set by TechController
        $view = 'app/views/chamcong/tech_panel.php';
    } else {
        // Default nhanvien view (inline below)
        $view = null;
    }
}
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php if (AuthMiddleware::isMobile()): ?>
    <!-- ========================================== -->
    <!-- MOBILE FULL SCREEN CAMERA VIEW            -->
    <!-- ========================================== -->
    <?php if (!$hasFaceRegistered): ?>
        <div class="mb-face-body" style="background-color: #0a0a0c; justify-content: center; align-items: center; padding: 20px; text-align: center;">
            <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 30px 20px; max-width: 320px; width: 100%;">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(217, 119, 6, 0.1); color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 36px; margin: 0 auto 20px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 style="color: white; margin: 0 0 10px 0; font-size: 18px; font-weight: 750;">Chưa đăng ký khuôn mặt</h3>
                <?php if ($role === 'hr'): ?>
                    <p style="color: #a0aec0; font-size: 13px; line-height: 1.5; margin: 0 0 24px 0;">Bạn cần thực hiện đăng ký khuôn mặt trên hệ thống trước khi thực hiện chấm công.</p>
                    <a href="index.php?page=face-register" class="mb-attendance-btn" style="text-decoration: none; margin: 0 auto; background-color: #d97706; box-shadow: 0 4px 14px rgba(217, 119, 6, 0.25);">
                        <i class="fa-solid fa-portrait"></i> Đăng ký ngay
                    </a>
                <?php else: ?>
                    <p style="color: #a0aec0; font-size: 13px; line-height: 1.5; margin: 0 0 24px 0;">Bạn chưa có dữ liệu khuôn mặt trên hệ thống. Vui lòng liên hệ bộ phận Nhân sự (HR) để được đăng ký khuôn mặt trước khi chấm công.</p>
                <?php endif; ?>
                <a href="index.php?page=home" style="display: block; margin-top: 16px; color: #a0aec0; font-size: 13px; text-decoration: none; font-weight: 600;">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại Trang chủ
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="mb-face-body">
            <header class="mb-face-header">
                <button type="button" class="back-btn" onclick="window.location.href='index.php?page=home'" title="Back">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <h2 id="mobile-title-text">Chấm công khuôn mặt</h2>
                <button type="button" class="flash-btn" title="Flash">
                    <i class="fa-solid fa-lightbulb"></i>
                </button>
            </header>

            <div class="mb-face-camera-container">
                <video id="modal-video" class="mb-face-camera-preview" autoplay muted playsinline style="transform: scaleX(-1);"></video>
                <canvas id="modal-canvas" class="mb-face-camera-preview" style="z-index: 5; transform: scaleX(-1);"></canvas>
                
                <div class="mb-face-overlay-guide"></div>
                <div class="mb-face-corners">
                    <div class="mb-face-corners-inner"></div>
                </div>

                <!-- Challenge instruction overlay for Mobile -->
                <div id="liveness-challenge-overlay" style="position: absolute; bottom: 180px; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.85)); padding: 20px 16px; display: none; z-index: 20; text-align: center; border-radius: 12px; margin: 0 10px;">
                    <div id="liveness-challenge-text" style="color: white; font-size: 16px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fas fa-eye"></i> <span>Vui lòng chớp mắt</span>
                    </div>
                </div>

                <!-- Light badge -->
                <div class="mb-face-light-badge">
                    <i class="fa-solid fa-sun"></i>
                    <span>ÁNH SÁNG TỐT</span>
                </div>
                
                <!-- Guidance Box -->
                <div class="mb-face-status-panel">
                    <h4 id="face-modal-title"><i class="fa-solid fa-circle" style="color: #1e62ec; font-size: 8px;"></i> Đang nhận diện...</h4>
                    <p id="face-modal-status">Vui lòng đưa mặt vào khung hình và giữ yên trong vài giây.</p>
                    
                    <!-- Progress Bar & Stepper -->
                    <div class="liveness-progress-label" id="liveness-progress-label" style="font-size: 11px; color: #64748b; text-align: right; margin-bottom: 4px; font-weight: 600; display: none;">0%</div>
                    <div class="liveness-progress-wrap" id="liveness-progress-wrap" style="background: #e2e8f0; border-radius: 8px; height: 6px; margin-bottom: 14px; overflow: hidden; width: 100%; display: none;">
                        <div id="liveness-progress-bar" class="liveness-progress-bar" style="height: 100%; border-radius: 8px; background: linear-gradient(90deg, #3b82f6, #10b981); width: 0%;"></div>
                    </div>
                    
                    <button id="modal-btn-verify" class="mb-request-submit-btn" style="margin-top: 15px; background-color: #1e62ec;" disabled>
                        <i class="fa-solid fa-fingerprint"></i> XÁC THỰC VÀ CHẤM CÔNG
                    </button>
                </div>
            </div>

            <canvas id="snapshot-canvas" style="display: none;"></canvas>
        </div>
    <?php endif; ?>
<?php else: ?>
    <!-- ========================================== -->
    <!-- DESKTOP VIEW                               -->
    <!-- ========================================== -->
    <div class="main-container">
        <?php include 'app/views/layouts/sidebar.php'; ?>
        
        <div class="dashboard-container">
            <?php if ($view && file_exists($view)): ?>
                <!-- Include role-specific view -->
                <?php include $view; ?>
            <?php else: ?>
                <!-- ========== NHÂN VIÊN DASHBOARD (REFINED COMPACT UI) ========== -->
                <style>
                    .emp-dashboard { max-width: 1000px; margin: 0 auto; padding: 10px; font-family: 'Inter', sans-serif; }
                    .emp-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
                    .emp-stat-card { background: white; border-radius: 10px; padding: 16px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; align-items: center; text-align: center; transition: all 0.2s; }
                    .emp-stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
                    .emp-stat-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 8px; }
                    .emp-stat-label { font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
                    .emp-stat-val { font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 2px; }
                    
                    .bg-blue { background: #eff6ff; color: #3b82f6; }
                    .bg-red { background: #fef2f2; color: #ef4444; }
                    .bg-green { background: #f0fdf4; color: #10b981; }
                    .bg-purple { background: #faf5ff; color: #a855f7; }

                    .emp-main-grid { display: grid; grid-template-columns: 1fr 320px; gap: 20px; }
                    .emp-card { background: white; border-radius: 10px; padding: 20px; border: 1px solid #e2e8f0; }
                    
                    .trangThai-container { text-align: center; padding: 10px 0; }
                    .trangThai-badge-big { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 13px; margin-bottom: 16px; }
                    .trangThai-in { background: #dcfce7; color: #15803d; }
                    .trangThai-out { background: #fee2e2; color: #b91c1c; }
                    .trangThai-none { background: #f1f5f9; color: #475569; }

                    .clock-big { font-size: 42px; font-weight: 700; color: #1e293b; letter-spacing: -1px; margin: 0; }
                    .date-sub { font-size: 14px; color: #64748b; margin-bottom: 24px; }
                    
                    .btn-hanhDong-mid { display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 12px 32px; border-radius: 8px; font-size: 15px; font-weight: 600; text-decoration: none; transition: all 0.2s; border: none; cursor: pointer; }
                    .btn-in { background: #10b981; color: white; box-shadow: 0 4px 6px -1px rgba(16,185,129,0.2); }
                    .btn-in:hover { background: #059669; transform: scale(1.02); }
                    .btn-out { background: #ef4444; color: white; box-shadow: 0 4px 6px -1px rgba(239,68,68,0.2); }
                    .btn-out:hover { background: #dc2626; transform: scale(1.02); }

                    .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
                    .detail-row:last-child { border-bottom: none; }
                    .detail-label { color: #64748b; font-weight: 500; }
                    .detail-val { color: #1e293b; font-weight: 600; }

                    @media (max-width: 768px) {
                        .emp-stats-grid { grid-template-columns: repeat(2, 1fr); }
                        .emp-main-grid { grid-template-columns: 1fr; }
                    }
                </style>

                <div class="emp-dashboard">
                    <!-- Thông báo -->
                    <?php if ($success): ?><div class="alert alert-success" style="margin-bottom: 16px; border-radius: 8px;"><?= htmlspecialchars($success) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-error" style="margin-bottom: 16px; border-radius: 8px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                    <!-- 4 Thống kê nhỏ -->
                    <div class="emp-stats-grid">
                        <?php $eStats = $stats['employee'] ?? []; ?>
                        <div class="emp-stat-card">
                            <div class="emp-stat-icon bg-blue"><i class="fas fa-calendar-check"></i></div>
                            <span class="emp-stat-label">Ngày công</span>
                            <span class="emp-stat-val"><?= number_format($eStats['work_days'] ?? 0, 1) ?></span>
                        </div>
                        <div class="emp-stat-card">
                            <div class="emp-stat-icon bg-red"><i class="fas fa-clock"></i></div>
                            <span class="emp-stat-label">Đi trễ</span>
                            <span class="emp-stat-val"><?= $eStats['late_times'] ?? 0 ?></span>
                        </div>
                        <div class="emp-stat-card">
                            <div class="emp-stat-icon bg-green"><i class="fas fa-bolt"></i></div>
                            <span class="emp-stat-label">Tăng ca</span>
                            <span class="emp-stat-val"><?= number_format($eStats['ot_hours'] ?? 0, 1) ?>h</span>
                        </div>
                        <div class="emp-stat-card">
                            <div class="emp-stat-icon bg-purple"><i class="fas fa-umbrella-beach"></i></div>
                            <span class="emp-stat-label">Nghỉ phép</span>
                            <span class="emp-stat-val"><?= number_format($eStats['leave_days'] ?? 0, 1) ?></span>
                        </div>
                    </div>

                    <div class="emp-main-grid">
                        <!-- Khu vực chấm công -->
                        <div class="emp-card">
                            <div class="trangThai-container">
                                <?php if ($trangThaiHomNay === 'IN'): ?>
                                    <span class="trangThai-badge-big trangThai-in"><i class="fas fa-check-circle"></i> Đang trong ca làm việc</span>
                                <?php elseif ($trangThaiHomNay === 'OUT'): ?>
                                    <span class="trangThai-badge-big trangThai-none"><i class="fas fa-flag-checkered"></i> Đã hoàn thành công việc</span>
                                <?php else: ?>
                                    <span class="trangThai-badge-big trangThai-out"><i class="fas fa-exclamation-circle"></i> Chưa chấm công</span>
                                <?php endif; ?>

                                <h1 class="clock-big" id="realtime-clock">--:--:--</h1>
                                <p class="date-sub"><?= date('l, d F Y') ?></p>

                                <div style="margin-top: 10px;">
                                    <?php if ($trangThaiHomNay === null || $trangThaiHomNay === 'OUT'): ?>
                                        <button onclick="triggerFaceAttendance('IN')" class="btn-hanhDong-mid btn-in" <?= ($trangThaiHomNay === 'OUT') ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                                            <i class="fas fa-fingerprint"></i> CHẤM CÔNG VÀO
                                        </button>
                                    <?php else: ?>
                                        <button onclick="triggerFaceAttendance('OUT')" class="btn-hanhDong-mid btn-out">
                                            <i class="fas fa-sign-out-alt"></i> CHẤM CÔNG RA
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Chi tiết trong ngày -->
                        <div class="emp-card">
                            <h3 style="margin-top: 0; font-size: 15px; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">Chi tiết hôm nay</h3>
                            <?php 
                                $inTime = '--:--'; $outTime = '--:--';
                                foreach ($history ?? [] as $h) {
                                    if (substr($h['ngayTao'], 0, 10) === date('Y-m-d')) {
                                        if ($h['hanhDong'] === 'IN') $inTime = substr($h['ngayTao'], 11, 5);
                                        if ($h['hanhDong'] === 'OUT') $outTime = substr($h['ngayTao'], 11, 5);
                                    }
                                }
                            ?>
                            <div class="detail-row">
                                <span class="detail-label">Giờ bắt đầu</span>
                                <span class="detail-val"><?= $inTime ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Giờ kết thúc</span>
                                <span class="detail-val"><?= $outTime ?></span>
                            </div>
                            <div class="detail-row" style="margin-top: 8px;">
                                <span class="detail-label">Ca làm việc</span>
                                <span class="detail-val" style="color: #3b82f6;"><?= htmlspecialchars($todayShiftStatus['shift']['tenCa'] ?? 'Chưa gán') ?></span>
                            </div>
                            
                            <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 8px;">
                                <a href="index.php?page=yeu-cau-chinh-sua-cham-cong" style="font-size: 13px; color: #64748b; text-decoration: none; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-edit"></i> Quên chấm công? Gửi yêu cầu
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Nhận diện khuôn mặt -->
                <div id="face-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                    <div style="background: white; border-radius: 16px; width: 90%; max-width: 500px; padding: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: 1px solid #e2e8f0; position: relative;">
                        <button onclick="closeFaceModal()" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 18px; cursor: pointer; color: #64748b;"><i class="fas fa-times"></i></button>
                        
                        <h3 id="face-modal-title" style="margin: 0 0 15px; font-size: 16px; font-weight: 700; color: #0f172a; text-align: center;">Xác thực khuôn mặt Chấm công</h3>
                        
                        <div id="face-modal-status" style="padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;">
                            Đang tải mô hình nhận diện...
                        </div>

                        <!-- Progress Bar & Stepper -->
                        <div class="liveness-progress-label" id="liveness-progress-label" style="font-size: 11px; color: #64748b; text-align: right; margin-bottom: 4px; font-weight: 600; display: none;">0%</div>
                        <div class="liveness-progress-wrap" id="liveness-progress-wrap" style="background: #e2e8f0; border-radius: 8px; height: 6px; margin-bottom: 14px; overflow: hidden; width: 100%; display: none;">
                            <div id="liveness-progress-bar" class="liveness-progress-bar" style="height: 100%; border-radius: 8px; background: linear-gradient(90deg, #3b82f6, #10b981); width: 0%;"></div>
                        </div>

                        <div style="position: relative; width: 100%; aspect-ratio: 4/3; background: #0f172a; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1;">
                            <video id="modal-video" autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
                            <canvas id="modal-canvas" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; transform: scaleX(-1);"></canvas>

                            <!-- Challenge instruction overlay for Desktop -->
                            <div id="liveness-challenge-overlay" style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); padding: 20px 16px 14px; display: none; z-index: 20;">
                                <div id="liveness-challenge-text" style="color: white; font-size: 15px; font-weight: 700; text-align: center; text-shadow: 0 2px 4px rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <i class="fas fa-eye"></i> <span>Vui lòng chớp mắt</span>
                                </div>
                            </div>
                        </div>
                        
                        <canvas id="snapshot-canvas" style="display: none;"></canvas>

                        <div style="margin-top: 15px; text-align: center;">
                            <span style="font-size: 12px; color: #64748b; display: block; margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Vui lòng căn chỉnh khuôn mặt thẳng góc với camera</span>
                            <button id="modal-btn-verify" class="btn-hanhDong-mid btn-in" style="width: 100%; padding: 12px; display: inline-flex; align-items: center; justify-content: center;" disabled>
                                <i class="fas fa-fingerprint"></i> XÁC THỰC VÀ CHẤM CÔNG
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; // End of view check ?>
        </div>
    </div>
<?php endif; ?>

            <script src="public/js/face-api.js"></script>
            <script src="public/js/face-liveness.js"></script>
            <script>
                let currentAction = '<?= ($trangThaiHomNay === 'IN') ? 'OUT' : 'IN' ?>';
                let faceApiLoaded = false;
                let modalStream = null;
                let detectionInterval = null;
                let livenessDetector = null;
                let isVerificationComplete = false;

                function updateClock() {
                    const now = new Date();
                    const clockEl = document.getElementById('realtime-clock');
                    if (clockEl) {
                        clockEl.textContent = now.toLocaleTimeString('en-GB');
                    }
                }
                setInterval(updateClock, 1000);
                updateClock();

                async function triggerFaceAttendance(action) {
                    currentAction = action;
                    isVerificationComplete = false;

                    const modal = document.getElementById('face-modal');
                    if (modal) {
                        modal.style.display = 'flex';
                    }
                    
                    const title = document.getElementById('face-modal-title');
                    if (title) {
                        title.innerHTML = '<i class="fas fa-portrait"></i> Xác thực khuôn mặt - Chấm công ' + (action === 'IN' ? 'Vào' : 'Ra');
                    }

                    const mobileTitle = document.getElementById('mobile-title-text');
                    if (mobileTitle) {
                        mobileTitle.textContent = 'Chấm công ' + (action === 'IN' ? 'Vào' : 'Ra');
                    }
                    
                    resetLivenessUI();

                    const status = document.getElementById('face-modal-status');
                    if (status) {
                        status.style.cssText = 'padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;';
                        status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải mô hình nhận diện khuôn mặt...';
                    }
                    
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
                                if (status) {
                                    status.style.background = '#fef2f2';
                                    status.style.color = '#dc2626';
                                    status.style.borderColor = '#fecaca';
                                    status.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Lỗi khởi tạo thư viện nhận dạng khuôn mặt: ' + (errLocal.message || errLocal);
                                }
                                return;
                            }
                        }
                    }
                    
                    if (status) {
                        status.innerHTML = '<i class="fas fa-video"></i> Đang khởi động camera...';
                    }
                    
                    const video = document.getElementById('modal-video');
                    try {
                        modalStream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } }
                        });
                        video.srcObject = modalStream;
                        video.onplay = () => {
                            initLivenessDetector();
                            if (detectionInterval) clearTimeout(detectionInterval);
                            detectFaceModal();
                        };
                        video.onplaying = () => {
                            initLivenessDetector();
                            if (detectionInterval) clearTimeout(detectionInterval);
                            detectFaceModal();
                        };
                        // Force play for iOS Safari
                        await video.play();
                        // Call immediately after playing starts to bypass delayed/blocked play events
                        setTimeout(detectFaceModal, 300);
                    } catch (err) {
                        console.error('Không thể mở camera:', err);
                        if (status) {
                            status.style.background = '#fef2f2';
                            status.style.color = '#dc2626';
                            status.style.borderColor = '#fecaca';
                            status.innerHTML = '<i class="fas fa-camera-slash"></i> Không thể truy cập camera. Vui lòng cho phép quyền camera trên trình duyệt.';
                        }
                    }
                }

                function initLivenessDetector() {
                    const video = document.getElementById('modal-video');
                    const canvas = document.getElementById('modal-canvas');

                    if (livenessDetector) {
                        livenessDetector.destroy();
                    }

                    livenessDetector = new LivenessDetector(video, canvas, {
                        onStatusChange: (message, type) => {
                            const status = document.getElementById('face-modal-status');
                            if (status) {
                                if (type === 'success') {
                                    status.style.cssText = 'padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;';
                                    status.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
                                } else if (type === 'error') {
                                    status.style.cssText = 'padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;';
                                    status.innerHTML = `<i class="fas fa-times-circle"></i> ${message}`;
                                } else if (type === 'warning') {
                                    status.style.cssText = 'padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #fffbeb; color: #d97706; border: 1px solid #fef3c7;';
                                    status.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                                } else {
                                    status.style.cssText = 'padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;';
                                    status.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${message}`;
                                }
                            }

                            // Show challenge overlay based on state
                            const overlay = document.getElementById('liveness-challenge-overlay');
                            const challengeText = document.getElementById('liveness-challenge-text');

                            if (overlay && challengeText && livenessDetector) {
                                const state = livenessDetector.getState();
                                if (state === 'challenging') {
                                    overlay.style.display = 'block';
                                    const ch = livenessDetector.challenges[livenessDetector.currentChallengeIndex];
                                    if (ch) {
                                        const info = LivenessDetector.CHALLENGE_INSTRUCTIONS[ch];
                                        challengeText.innerHTML = `<i class="fas ${info.icon}"></i> <span>${info.vi}</span>`;
                                    }
                                } else if (state === 'blink_check') {
                                    overlay.style.display = 'block';
                                    challengeText.innerHTML = '<i class="fas fa-eye"></i> <span>Vui lòng CHỚP MẮT tự nhiên</span>';
                                } else if (state === 'pose_check') {
                                    overlay.style.display = 'block';
                                    challengeText.innerHTML = '<i class="fas fa-user"></i> <span>Nhìn THẲNG vào camera</span>';
                                } else {
                                    overlay.style.display = 'none';
                                }
                            }
                        },

                        onProgress: (percent, label) => {
                            const labelEl = document.getElementById('liveness-progress-label');
                            const barEl = document.getElementById('liveness-progress-bar');
                            const wrapEl = document.getElementById('liveness-progress-wrap');

                            if (labelEl) {
                                labelEl.style.display = 'block';
                                labelEl.textContent = `${percent}% — ${label}`;
                            }
                            if (wrapEl) {
                                wrapEl.style.display = 'block';
                            }
                            if (barEl) {
                                barEl.style.width = `${percent}%`;
                            }
                        },

                        onComplete: async (livenessToken, descriptor) => {
                            isVerificationComplete = true;
                            const overlay = document.getElementById('liveness-challenge-overlay');
                            if (overlay) overlay.style.display = 'none';

                            await submitAttendance(livenessToken, descriptor);
                        },

                        onFail: (reason) => {
                            isVerificationComplete = true;
                            const overlay = document.getElementById('liveness-challenge-overlay');
                            if (overlay) overlay.style.display = 'none';

                            const status = document.getElementById('face-modal-status');
                            if (status) {
                                status.style.cssText = 'padding: 16px; border-radius: 12px; font-size: 14px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; line-height: 1.5;';
                                status.innerHTML = `<i class="fas fa-user-times" style="font-size: 28px; color: #ef4444; margin-bottom: 10px; display: block;"></i> <strong style="font-size: 16px; display:block; margin-bottom:6px;">Xác thực thất bại</strong> ${reason}`;
                            }

                            const btnVerify = document.getElementById('modal-btn-verify');
                            if (btnVerify) {
                                btnVerify.disabled = false;
                                btnVerify.innerHTML = '<i class="fas fa-sync-alt"></i> THỬ LẠI';
                                btnVerify.style.backgroundColor = '#f59e0b';
                                btnVerify.onclick = () => {
                                    btnVerify.style.backgroundColor = '#1e62ec';
                                    btnVerify.innerHTML = '<i class="fa-solid fa-fingerprint"></i> XÁC THỰC VÀ CHẤM CÔNG';
                                    btnVerify.disabled = true;
                                    triggerFaceAttendance(currentAction);
                                };
                            }
                        }
                    });

                    livenessDetector.start();
                }

                let isDetectingModal = false;

                async function detectFaceModal() {
                    const video = document.getElementById('modal-video');
                    if (isVerificationComplete || !video || !faceApiLoaded || video.paused || video.ended || video.readyState < 2) {
                        detectionInterval = setTimeout(detectFaceModal, 200);
                        return;
                    }

                    if (isDetectingModal) return;
                    isDetectingModal = true;

                    try {
                        const canvas = document.getElementById('modal-canvas');
                        const displaySize = { width: video.videoWidth || 640, height: video.videoHeight || 480 };
                        faceapi.matchDimensions(canvas, displaySize);

                        const detection = await faceapi.detectSingleFace(
                            video, 
                            new faceapi.TinyFaceDetectorOptions({ inputSize: 128, scoreThreshold: 0.4 })
                        ).withFaceLandmarks().withFaceDescriptor();

                        const ctx = canvas.getContext('2d');
                        ctx.clearRect(0, 0, canvas.width, canvas.height);

                        if (detection) {
                            const resized = faceapi.resizeResults(detection, displaySize);
                            const box = resized.detection.box;
                            
                            const state = livenessDetector ? livenessDetector.getState() : 'idle';
                            if (state === 'completed') {
                                ctx.strokeStyle = '#10b981';
                            } else if (state === 'failed') {
                                ctx.strokeStyle = '#ef4444';
                            } else {
                                ctx.strokeStyle = '#3b82f6';
                            }
                            ctx.lineWidth = 3;
                            ctx.strokeRect(box.x, box.y, box.width, box.height);

                            // Draw landmarks dots
                            if (detection.landmarks) {
                                const positions = detection.landmarks.positions;
                                ctx.fillStyle = '#10b981';
                                for (let i = 36; i <= 47; i++) {
                                    const sx = positions[i].x * (displaySize.width / (video.videoWidth || 640));
                                    const sy = positions[i].y * (displaySize.height / (video.videoHeight || 480));
                                    ctx.beginPath();
                                    ctx.arc(sx, sy, 2, 0, 2 * Math.PI);
                                    ctx.fill();
                                }
                            }

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

                    isDetectingModal = false;
                    if (!isVerificationComplete) {
                        detectionInterval = setTimeout(detectFaceModal, 16);
                    }
                }

                function closeFaceModal() {
                    if (detectionInterval) {
                        clearTimeout(detectionInterval);
                        detectionInterval = null;
                    }
                    if (livenessDetector) {
                        livenessDetector.destroy();
                        livenessDetector = null;
                    }
                    if (modalStream) {
                        modalStream.getTracks().forEach(track => track.stop());
                        modalStream = null;
                    }
                    const video = document.getElementById('modal-video');
                    if (video) video.srcObject = null;

                    const modal = document.getElementById('face-modal');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                    
                    const canvas = document.getElementById('modal-canvas');
                    if (canvas) {
                        const ctx = canvas.getContext('2d');
                        ctx.clearRect(0, 0, canvas.width, canvas.height);
                    }

                    resetLivenessUI();
                    isVerificationComplete = false;
                }

                function resetLivenessUI() {
                    const labelEl = document.getElementById('liveness-progress-label');
                    const wrapEl = document.getElementById('liveness-progress-wrap');
                    const barEl = document.getElementById('liveness-progress-bar');
                    const overlay = document.getElementById('liveness-challenge-overlay');

                    if (labelEl) labelEl.style.display = 'none';
                    if (wrapEl) wrapEl.style.display = 'none';
                    if (barEl) barEl.style.width = '0%';
                    if (overlay) overlay.style.display = 'none';

                    const btnVerify = document.getElementById('modal-btn-verify');
                    if (btnVerify) {
                        btnVerify.disabled = true;
                        btnVerify.innerHTML = '<i class="fa-solid fa-fingerprint"></i> XÁC THỰC VÀ CHẤM CÔNG';
                        btnVerify.style.backgroundColor = '#1e62ec';
                    }
                }

                async function submitAttendance(livenessToken, descriptor) {
                    const status = document.getElementById('face-modal-status');
                    const normalizedDescriptor = Array.isArray(descriptor)
                        ? descriptor
                        : (ArrayBuffer.isView(descriptor) ? Array.from(descriptor) : null);

                    if (!normalizedDescriptor || normalizedDescriptor.length === 0) {
                        if (status) {
                            status.style.cssText = 'padding: 16px; border-radius: 12px; font-size: 14px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; line-height: 1.5;';
                            status.innerHTML = '<i class="fas fa-user-times" style="font-size: 28px; color: #ef4444; margin-bottom: 10px; display: block;"></i> <strong style="font-size: 16px; display:block; margin-bottom:6px;">Không thể đọc dữ liệu khuôn mặt</strong> Vui lòng nhìn thẳng vào camera và thử lại.';
                        }

                        const btnVerify = document.getElementById('modal-btn-verify');
                        if (btnVerify) {
                            btnVerify.disabled = false;
                            btnVerify.innerHTML = '<i class="fas fa-sync-alt"></i> THỬ LẠI';
                            btnVerify.style.backgroundColor = '#f59e0b';
                        }
                        return;
                    }

                    if (status) {
                        status.style.cssText = 'padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;';
                        status.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi ảnh và dữ liệu chấm công...';
                    }

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
                    const embeddingStr = JSON.stringify(normalizedDescriptor);
                    
                    const formData = new FormData();
                    formData.append('hanhDong', currentAction);
                    formData.append('tenWifi', 'INTERNAL_NETWORK');
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
                            if (status) {
                                status.style.cssText = 'padding: 12px; border-radius: 12px; font-size: 14px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;';
                                status.innerHTML = '<i class="fas fa-check-circle" style="font-size: 18px; margin-bottom: 4px; display: block;"></i> ' + result.message;
                            }
                            setTimeout(() => {
                                closeFaceModal();
                                window.location.reload();
                            }, 1500);
                        } else {
                            const msg = result.message || '';
                            const isPolicyError = msg.includes('Ca làm việc') || msg.includes('chấm công') || msg.includes('mạng nội bộ');
                            
                            if (status) {
                                status.style.cssText = 'padding: 16px; border-radius: 12px; font-size: 14px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; line-height: 1.5;';
                            }
                            
                            if (isPolicyError) {
                                if (status) {
                                    status.innerHTML = '<i class="fas fa-calendar-times" style="font-size: 28px; color: #ef4444; margin-bottom: 10px; display: block;"></i> <strong style="font-size: 16px; display:block; margin-bottom:6px;">Chấm công không thành công</strong>' + msg;
                                }
                                
                                const btnVerify = document.getElementById('modal-btn-verify');
                                if (btnVerify) {
                                    btnVerify.outerHTML = '<a href="index.php?page=home" class="mb-request-submit-btn" style="margin-top: 15px; background-color: #475569; color: white; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;"><i class="fas fa-arrow-left"></i> Quay lại Trang chủ</a>';
                                }
                                
                                if (modalStream) {
                                    modalStream.getTracks().forEach(track => track.stop());
                                }
                            } else {
                                if (status) {
                                    status.innerHTML = '<i class="fas fa-user-times" style="font-size: 28px; color: #ef4444; margin-bottom: 10px; display: block;"></i> <strong style="font-size: 16px; display:block; margin-bottom:6px;">Xác thực không khớp</strong>' + msg;
                                }
                                const btnVerify = document.getElementById('modal-btn-verify');
                                if (btnVerify) {
                                    btnVerify.innerHTML = '<i class="fas fa-sync-alt"></i> THỬ LẠI';
                                    btnVerify.disabled = false;
                                    btnVerify.style.backgroundColor = '#f59e0b';
                                    btnVerify.onclick = () => {
                                        btnVerify.style.backgroundColor = '#1e62ec';
                                        btnVerify.innerHTML = '<i class="fa-solid fa-fingerprint"></i> XÁC THỰC VÀ CHẤM CÔNG';
                                        btnVerify.disabled = true;
                                        triggerFaceAttendance(currentAction);
                                    };
                                }
                            }
                        }
                    } catch (err) {
                        console.error('Lỗi API xác thực:', err);
                        if (status) {
                            status.style.cssText = 'padding: 12px; border-radius: 12px; font-size: 13px; font-weight: 600; text-align: center; margin-bottom: 12px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;';
                            status.innerHTML = '<i class="fas fa-wifi"></i> Lỗi kết nối mạng hoặc máy chủ. Vui lòng thử lại.';
                        }
                        const btnVerify = document.getElementById('modal-btn-verify');
                        if (btnVerify) {
                            btnVerify.disabled = false;
                        }
                        isVerificationComplete = false;
                    }
                }

                // Tự động khởi chạy camera trên thiết bị di động
                if (<?= (AuthMiddleware::isMobile() && $hasFaceRegistered) ? 'true' : 'false' ?>) {
                    triggerFaceAttendance(currentAction);
                }
            </script>
<?php include 'app/views/layouts/footer.php'; ?>

