<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập — RFT Hệ thống Chấm Công</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Quicksand:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-hover: #1d4ed8;
            --cyan-accent: #38bdf8;
            --cyan-glow: rgba(56, 189, 248, 0.25);
            --bg-canvas: #edf2f9;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --card-dark-bg: #111827;
            --input-dark-bg: #1e293b;
            --input-dark-border: #334155;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-canvas);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
            color: var(--text-dark);
        }

        /* Ambient Glowing Background Orbs */
        .bg-glow {
            position: absolute;
            border-radius: 50%;
            z-index: 0;
            filter: blur(100px);
            pointer-events: none;
        }
        .bg-glow-1 {
            width: 600px; height: 600px;
            background: rgba(191, 219, 254, 0.65);
            top: -100px; left: 50%;
            transform: translateX(-50%);
        }
        .bg-glow-2 {
            width: 500px; height: 500px;
            background: rgba(224, 242, 254, 0.7);
            bottom: -100px; left: -100px;
        }
        .bg-glow-3 {
            width: 550px; height: 550px;
            background: rgba(219, 234, 254, 0.6);
            bottom: -150px; right: -100px;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px 40px;
            z-index: 1;
        }

        /* HEADER SECTION */
        .header-section {
            text-align: center;
            margin-bottom: 36px;
            max-width: 720px;
        }

        .brand-logo-pills {
            display: inline-flex;
            gap: 6px;
            margin-bottom: 20px;
        }
        .logo-pill {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Quicksand', 'Inter', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: white;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
        }
        .logo-pill.r { background: #0f172a; }
        .logo-pill.f { background: #0ea5e9; }
        .logo-pill.t { background: #2563eb; }

        .header-title {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
        }
        .header-subtitle {
            font-size: 15px;
            color: #64748b;
            font-weight: 400;
            line-height: 1.5;
        }

        /* CONTENT CONTAINER */
        .content-container {
            display: flex;
            gap: 36px;
            max-width: 1040px;
            width: 100%;
            align-items: flex-start;
            justify-content: center;
        }

        /* LEFT PANEL - AI ATTENDANCE CONSOLE MOCKUP */
        .console-panel {
            flex: 1.1;
            min-width: 0;
        }

        .mockup-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.08), 0 2px 6px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(226, 232, 240, 0.9);
            padding: 20px;
            margin-bottom: 24px;
        }

        .mockup-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            padding-bottom: 4px;
        }
        .mockup-nav-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mockup-brand-badge {
            background: #0f172a;
            color: white;
            font-weight: 800;
            font-size: 10px;
            padding: 4px 6px;
            border-radius: 5px;
            letter-spacing: 0.5px;
        }
        .mockup-nav-title {
            font-weight: 700;
            font-size: 14px;
            color: #1e293b;
        }

        .mockup-nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .live-status-pill {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #dcfce7;
            color: #16a34a;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 100px;
        }
        .pulse-dot {
            width: 7px;
            height: 7px;
            background-color: #16a34a;
            border-radius: 50%;
            animation: pulse 1.8s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
        }
        .bell-icon {
            color: #64748b;
            font-size: 14px;
        }

        /* MOCKUP INNER SUB-PANELS */
        .mockup-body {
            display: flex;
            gap: 14px;
        }

        /* SUB-PANEL 1: AI CAMERA SCREEN */
        .ai-camera-card {
            flex: 1.2;
            background: #0b1329;
            border-radius: 14px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 220px;
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.5);
        }

        .camera-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 2;
        }
        .cam-status {
            font-size: 11px;
            font-weight: 600;
            color: #e0f2fe;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .cam-status-dot {
            width: 6px; height: 6px;
            background: #38bdf8;
            border-radius: 50%;
        }
        .faceid-badge {
            border: 1px solid rgba(56, 189, 248, 0.4);
            background: rgba(56, 189, 248, 0.1);
            color: #38bdf8;
            font-size: 10px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 100px;
        }

        /* Face Scanner Frame Target */
        .scanner-frame-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 12px 0;
            z-index: 2;
        }
        .scanner-box {
            position: relative;
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .scanner-corner {
            position: absolute;
            width: 16px;
            height: 16px;
            border-color: #38bdf8;
            border-style: solid;
        }
        .corner-tl { top: 0; left: 0; border-width: 2px 0 0 2px; border-top-left-radius: 4px; }
        .corner-tr { top: 0; right: 0; border-width: 2px 2px 0 0; border-top-right-radius: 4px; }
        .corner-bl { bottom: 0; left: 0; border-width: 0 0 2px 2px; border-bottom-left-radius: 4px; }
        .corner-br { bottom: 0; right: 0; border-width: 0 2px 2px 0; border-bottom-right-radius: 4px; }
        
        .face-avatar-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 2px solid #38bdf8;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #38bdf8;
            font-size: 22px;
            background: rgba(56, 189, 248, 0.08);
        }
        
        .auto-recognize-pill {
            background: #00d285;
            color: #042f1a;
            font-size: 10px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 100px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: -10px;
            box-shadow: 0 4px 12px rgba(0, 210, 133, 0.4);
            z-index: 3;
        }

        .camera-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            z-index: 2;
        }
        .cam-foot-left {
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .cam-foot-left .dot {
            width: 5px; height: 5px; background: #10b981; border-radius: 50%;
        }
        .cam-foot-right {
            color: #38bdf8;
            font-weight: 600;
        }

        /* SUB-PANEL 2: SYSTEM STATUS */
        .system-status-card {
            flex: 0.85;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .status-icon-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
        }
        .status-content {
            margin-top: 10px;
        }
        .status-sub {
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }
        .status-main {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin: 4px 0;
            line-height: 1.3;
        }
        .status-desc {
            font-size: 11px;
            color: #64748b;
            line-height: 1.4;
        }
        .status-active-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #059669;
            margin-top: 10px;
        }
        .active-dot {
            width: 6px; height: 6px; background: #059669; border-radius: 50%;
        }

        /* FEATURES GRID */
        .features-grid-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 20px;
            padding: 0 4px;
        }
        .feature-box {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .feature-bullet-dot {
            width: 6px;
            height: 6px;
            background: #2563eb;
            border-radius: 50%;
            margin-top: 6px;
            flex-shrink: 0;
        }
        .feature-desc-text {
            font-size: 12.5px;
            color: #475569;
            line-height: 1.45;
        }
        .feature-desc-text strong {
            color: #0f172a;
            font-weight: 700;
        }

        /* RIGHT PANEL - DARK LOGIN CARD */
        .login-card-panel {
            width: 420px;
            background: var(--card-dark-bg);
            border-radius: 24px;
            padding: 36px 32px;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.05);
            color: white;
            animation: slideUp 0.4s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card-header {
            margin-bottom: 26px;
        }
        .login-card-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: white;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }
        .login-card-header p {
            font-size: 13.5px;
            color: #94a3b8;
        }

        /* ALERTS inside dark card */
        .alert-box {
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.4;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .alert-warning {
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fcd34d;
        }

        /* FORM CONTROLS */
        .form-group-item {
            margin-bottom: 20px;
        }
        .form-group-item label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 8px;
        }
        .input-relative-wrapper {
            position: relative;
        }
        .input-relative-wrapper i.left-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 14px;
            transition: color 0.2s;
        }
        .input-control-field {
            width: 100%;
            padding: 13px 16px 13px 44px;
            background: var(--input-dark-bg);
            border: 1px solid var(--input-dark-border);
            border-radius: 10px;
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        .input-control-field.has-right-icon {
            padding-right: 44px;
        }
        .input-control-field:focus {
            outline: none;
            border-color: #3b82f6;
            background: #1e293b;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }
        .input-control-field:focus + i.left-icon,
        .input-relative-wrapper:focus-within i.left-icon {
            color: #38bdf8;
        }
        .input-control-field::placeholder {
            color: #64748b;
        }
        .eye-toggle-btn {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 14px;
            cursor: pointer;
            transition: color 0.2s;
        }
        .eye-toggle-btn:hover {
            color: #cbd5e1;
        }

        /* CHECKBOX & FORGOT LINK ROW */
        .form-options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 14px;
            margin-bottom: 22px;
        }
        .remember-checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #94a3b8;
            cursor: pointer;
            user-select: none;
        }
        .remember-checkbox-label input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #2563eb;
            cursor: pointer;
            border-radius: 4px;
        }
        .forgot-password-link {
            font-size: 13px;
            color: #38bdf8;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .forgot-password-link:hover {
            color: #7dd3fc;
            text-decoration: underline;
        }

        /* PRIMARY SUBMIT BUTTON */
        .btn-primary-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.4);
        }
        .btn-primary-submit:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
        }
        .btn-primary-submit:active {
            transform: translateY(1px);
        }

        /* DIVIDER */
        .divider-container {
            display: flex;
            align-items: center;
            margin: 24px 0 20px;
        }
        .divider-line {
            flex: 1;
            height: 1px;
            background: #334155;
        }
        .divider-text {
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.8px;
            color: #64748b;
            padding: 0 12px;
            text-transform: uppercase;
        }

        /* BIOMETRIC BUTTON */
        .btn-biometric-auth {
            width: 100%;
            padding: 13px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s ease;
        }
        .btn-biometric-auth i {
            color: #38bdf8;
            font-size: 14px;
        }
        .btn-biometric-auth:hover {
            background: #27354a;
            border-color: #38bdf8;
            color: white;
        }

        /* FOOTER */
        .footer-bar {
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #64748b;
            width: 100%;
            z-index: 10;
        }
        .footer-bar a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        .footer-bar a:hover {
            text-decoration: underline;
        }

        /* RESPONSIVE LAYOUT */
        @media (max-width: 960px) {
            .content-container {
                flex-direction: column;
                align-items: center;
                gap: 32px;
            }
            .console-panel {
                width: 100%;
                max-width: 480px;
            }
            .login-card-panel {
                width: 100%;
                max-width: 440px;
            }
            .bg-glow { display: none; }
        }
        @media (max-width: 520px) {
            .header-title { font-size: 24px; }
            .header-subtitle { font-size: 14px; }
            .mockup-body { flex-direction: column; }
            .features-grid-wrapper { grid-template-columns: 1fr; }
            .footer-bar { flex-direction: column; gap: 8px; text-align: center; }
        }
    </style>
    <?php
    require_once 'app/middleware/AuthMiddleware.php';
    ?>
    <link rel="stylesheet" href="public/css/mobile.css?v=<?= time() ?>">
</head>
<body class="<?= AuthMiddleware::isMobile() ? 'mobile-view mb-login-body' : '' ?>">
    <script>
        (function () {
            try {
                const params = new URLSearchParams(window.location.search);
                const force = params.get('device');
                if (force === 'mobile') {
                    document.body.classList.add('mobile-view');
                    document.body.classList.add('mb-login-body');
                    return;
                }
                if (force === 'desktop') {
                    return;
                }
                const isTabletOrMobile = window.innerWidth <= 1024 &&
                    /iPad|iPhone|iPod|Android|Mobile|Tablet/i.test(navigator.userAgent);
                if (isTabletOrMobile) {
                    document.body.classList.add('mobile-view');
                    document.body.classList.add('mb-login-body');
                    document.cookie = 'device_hint=mobile; path=/; max-age=2592000; SameSite=Lax';
                } else {
                    document.cookie = 'device_hint=; path=/; max-age=0; SameSite=Lax';
                }
            } catch (e) {}
        })();
    </script>

    <?php if (AuthMiddleware::isMobile()): ?>
        <!-- ========================================== -->
        <!-- MOBILE LOGIN VIEW                          -->
        <!-- ========================================== -->
        <div class="main-content" style="padding: 40px 16px 20px;">
            
            <!-- Logo boxes: R, F, T -->
            <div class="mb-login-logo-container">
                <div class="mb-login-logo-box r">R</div>
                <div class="mb-login-logo-box f">F</div>
                <div class="mb-login-logo-box t">T</div>
            </div>

            <h1 class="mb-login-title">Chào mừng trở lại!</h1>
            <p class="mb-login-subtitle">Hệ thống quản lý chấm công kỹ thuật số dành cho doanh nghiệp hiện đại.</p>

            <!-- Login Card -->
            <div class="mb-login-card">
                <h2>Đăng nhập</h2>
                <p>Vui lòng nhập thông tin tài khoản để tiếp tục</p>

                <?php
                    $loi = $_GET['error'] ?? '';
                    if ($loi === '1'):
                ?>
                    <div class="alert alert-error" style="margin-bottom: 16px; border-radius: 8px; font-size: 13px;">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Sai tên đăng nhập hoặc mật khẩu. Vui lòng thử lại.</span>
                    </div>
                <?php endif; ?>

                <?php if ($loi === 'inactive'): ?>
                    <div class="alert alert-warning" style="margin-bottom: 16px; border-radius: 8px; font-size: 13px;">
                        <i class="fas fa-lock"></i>
                        <div>
                            <strong>Tài khoản đã bị khóa</strong><br>
                            <span>Vui lòng liên hệ quản trị viên để được hỗ trợ.</span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($loi === 'ib_only'): ?>
                    <div class="alert alert-warning" style="margin-bottom: 16px; border-radius: 8px; font-size: 13px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #fca5a5;">
                        <i class="fas fa-desktop"></i>
                        <div>
                            <strong>Quyền truy cập hạn chế</strong><br>
                            <span>Tài khoản này chỉ được phép hoạt động trên Internet Banking (IB).</span>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php?page=login-process">
                    <div class="mb-form-group">
                        <label for="username">TÊN ĐĂNG NHẬP / EMAIL</label>
                        <div class="mb-input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="username" name="username" class="mb-input-field" placeholder="Nhập tên đăng nhập hoặc email" autocomplete="username" autofocus required>
                        </div>
                    </div>

                    <div class="mb-form-group">
                        <label for="matKhau">MẬT KHẨU</label>
                        <div class="mb-input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="matKhau" name="matKhau" class="mb-input-field" placeholder="Nhập mật khẩu" autocomplete="current-password" required>
                            <i class="fas fa-eye toggle-password" onclick="togglePasswordVisibility('matKhau', this)"></i>
                        </div>
                    </div>

                    <div class="mb-forgot-pw">
                        <a href="#">QUÊN MẬT KHẨU?</a>
                    </div>

                    <button type="submit" class="mb-login-btn">
                        <i class="fas fa-arrow-right-to-bracket"></i> Đăng nhập
                    </button>
                </form>
            </div>

            <!-- Quick option cards -->
            <div class="mb-quick-options">
                <a href="#" class="mb-quick-option-card">
                    <i class="fas fa-wifi"></i>
                    <span>WIFI CHẤM CÔNG</span>
                </a>
                <a href="index.php?page=tablet-cham-cong" class="mb-quick-option-card">
                    <i class="far fa-face-smile"></i>
                    <span>FACEID</span>
                </a>
            </div>

            <!-- Mobile Footer -->
            <div class="mb-login-footer" style="background: none; border-top: none;">
                <div>© 2026 RFT HỆ THỐNG QUẢN LÝ CHẤM CÔNG – <?= htmlspecialchars(defined('APP_VERSION') ? APP_VERSION : 'v2.4.26') ?></div>
                <div style="margin-top: 4px;">CẦN HỖ TRỢ? <a href="#">Trò chuyện ngay</a> <i class="far fa-comment-dots" style="color: #1b5ed8;"></i></div>
            </div>

        </div>

        <script>
            function togglePasswordVisibility(fieldId, iconEl) {
                const field = document.getElementById(fieldId);
                if (field.type === 'password') {
                    field.type = 'text';
                    iconEl.classList.remove('fa-eye');
                    iconEl.classList.add('fa-eye-slash');
                } else {
                    field.type = 'password';
                    iconEl.classList.remove('fa-eye-slash');
                    iconEl.classList.add('fa-eye');
                }
            }
        </script>

    <?php else: ?>
        <!-- ========================================== -->
        <!-- DESKTOP LOGIN VIEW (REDESIGNED TO IMAGE)   -->
        <!-- ========================================== -->
        <!-- Ambient Background Glow Orbs -->
        <div class="bg-glow bg-glow-1"></div>
        <div class="bg-glow bg-glow-2"></div>
        <div class="bg-glow bg-glow-3"></div>

        <div class="main-content">
            <!-- TOP HEADER -->
            <div class="header-section">
                <div class="brand-logo-pills">
                    <div class="logo-pill r">R</div>
                    <div class="logo-pill f">F</div>
                    <div class="logo-pill t">T</div>
                </div>
                <h1 class="header-title">Hệ thống Quản lý Chấm công</h1>
                <p class="header-subtitle">Giải pháp số hoá chấm công thông minh ứng dụng nhận diện khuôn mặt AI cho doanh nghiệp hiện đại.</p>
            </div>

            <!-- MAIN CONTENT CONTAINER -->
            <div class="content-container">
                <!-- LEFT COLUMN: AI ATTENDANCE CONSOLE MOCKUP & FEATURES -->
                <div class="console-panel">
                    <div class="mockup-card">
                        <!-- Navigation bar of mockup -->
                        <div class="mockup-nav">
                            <div class="mockup-nav-left">
                                <span class="mockup-brand-badge">RFT</span>
                                <span class="mockup-nav-title">AI Attendance Console</span>
                            </div>
                            <div class="mockup-nav-right">
                                <div class="live-status-pill">
                                    <span class="pulse-dot"></span>
                                    <span>Camera Live</span>
                                </div>
                                <i class="fas fa-bell bell-icon"></i>
                            </div>
                        </div>

                        <!-- Inner Cards -->
                        <div class="mockup-body">
                            <!-- Card 1: Dark AI Camera Screen -->
                            <div class="ai-camera-card">
                                <div class="camera-header">
                                    <span class="cam-status">
                                        <span class="cam-status-dot"></span> Sẵn sàng nhận diện
                                    </span>
                                    <span class="faceid-badge">AI FaceID 3D</span>
                                </div>

                                <div class="scanner-frame-wrapper">
                                    <div class="scanner-box">
                                        <div class="scanner-corner corner-tl"></div>
                                        <div class="scanner-corner corner-tr"></div>
                                        <div class="scanner-corner corner-bl"></div>
                                        <div class="scanner-corner corner-br"></div>
                                        <div class="face-avatar-icon">
                                            <i class="far fa-user"></i>
                                        </div>
                                    </div>
                                    <div class="auto-recognize-pill">
                                        <i class="fas fa-check"></i> Tự động nhận diện
                                    </div>
                                </div>

                                <div class="camera-footer">
                                    <span class="cam-foot-left">
                                        <span class="dot"></span> Hệ thống hoạt động ổn định
                                    </span>
                                    <span class="cam-foot-right">Chính xác & Bảo mật</span>
                                </div>
                            </div>

                            <!-- Card 2: Light System Status -->
                            <div class="system-status-card">
                                <div>
                                    <div class="status-icon-circle">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="status-content">
                                        <div class="status-sub">Trạng thái hệ thống</div>
                                        <div class="status-main">Chấm công tự động</div>
                                        <div class="status-desc">Ghi nhận dữ liệu ra vào tức thì không cần chạm.</div>
                                    </div>
                                </div>
                                <div class="status-active-badge">
                                    <span class="active-dot"></span> Đang hoạt động 24/7
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Features Grid -->
                    <div class="features-grid-wrapper">
                        <div class="feature-box">
                            <span class="feature-bullet-dot"></span>
                            <div class="feature-desc-text">
                                <strong>Nhận diện khuôn mặt</strong> chính xác, hỗ trợ nhận diện khi đeo khẩu trang.
                            </div>
                        </div>
                        <div class="feature-box">
                            <span class="feature-bullet-dot"></span>
                            <div class="feature-desc-text">
                                <strong>Liveness Detection:</strong> Chống gian lận hình ảnh, video & mô phỏng 3D tân tiến.
                            </div>
                        </div>
                        <div class="feature-box">
                            <span class="feature-bullet-dot"></span>
                            <div class="feature-desc-text">
                                <strong>Báo cáo & Thống kê:</strong> Đồng bộ dữ liệu ca kíp tức thì, tự động tính công chuẩn xác.
                            </div>
                        </div>
                        <div class="feature-box">
                            <span class="feature-bullet-dot"></span>
                            <div class="feature-desc-text">
                                <strong>Bảo mật Doanh nghiệp:</strong> Mã hóa sinh trắc chuẩn SHA-256 và phân quyền đa tầng.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: DARK LOGIN CARD -->
                <div class="login-card-panel">
                    <div class="login-card-header">
                        <h2>Đăng nhập</h2>
                        <p>Vui lòng nhập thông tin tài khoản để tiếp tục</p>
                    </div>

                    <?php
                        $loi = $_GET['error'] ?? '';
                        if ($loi === '1'):
                    ?>
                        <div class="alert-box alert-error">
                            <i class="fas fa-exclamation-circle" style="margin-top: 2px;"></i>
                            <span>Sai tên đăng nhập hoặc mật khẩu. Vui lòng thử lại.</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($loi === 'inactive'): ?>
                        <div class="alert-box alert-warning">
                            <i class="fas fa-lock" style="margin-top: 2px;"></i>
                            <div>
                                <strong>Tài khoản đã bị khóa</strong><br>
                                <span>Vui lòng liên hệ quản trị viên để được hỗ trợ.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($loi === 'ib_only'): ?>
                        <div class="alert-box alert-error">
                            <i class="fas fa-desktop" style="margin-top: 2px;"></i>
                            <div>
                                <strong>Quyền truy cập hạn chế</strong><br>
                                <span>Tài khoản này chỉ được phép hoạt động trên Internet Banking (IB).</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?page=login-process">
                        <div class="form-group-item">
                            <label for="username">TÊN ĐĂNG NHẬP / EMAIL</label>
                            <div class="input-relative-wrapper">
                                <i class="fas fa-user left-icon"></i>
                                <input type="text" id="username" name="username" class="input-control-field" placeholder="Nhập tên đăng nhập hoặc email" autocomplete="username" autofocus required>
                            </div>
                        </div>

                        <div class="form-group-item">
                            <label for="matKhau">MẬT KHẨU</label>
                            <div class="input-relative-wrapper">
                                <i class="fas fa-lock left-icon"></i>
                                <input type="password" id="matKhau" name="matKhau" class="input-control-field has-right-icon" placeholder="Nhập mật khẩu" autocomplete="current-password" required>
                                <i class="fas fa-eye eye-toggle-btn" onclick="togglePasswordVisibility('matKhau', this)"></i>
                            </div>
                        </div>

                        <div class="form-options-row">
                            <label class="remember-checkbox-label">
                                <input type="checkbox" id="rememberMe" name="remember">
                                <span>Ghi nhớ đăng nhập</span>
                            </label>
                            <a href="#" class="forgot-password-link">Quên mật khẩu?</a>
                        </div>

                        <button type="submit" class="btn-primary-submit">
                            <i class="fas fa-arrow-right-to-bracket"></i> Đăng Nhập
                        </button>
                    </form>

                    <div class="divider-container">
                        <div class="divider-line"></div>
                        <span class="divider-text">HOẶC PHƯƠNG THỨC SINH TRẮC</span>
                        <div class="divider-line"></div>
                    </div>

                    <button type="button" class="btn-biometric-auth" onclick="window.location.href='index.php?page=tablet-cham-cong'">
                        <i class="fas fa-expand"></i>
                        <span>Đăng nhập nhanh bằng FaceID thiết bị</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <footer class="footer-bar">
            <div>© 2026 RFT Hệ Thống Quản Lý Chấm Công — <?= htmlspecialchars(defined('APP_VERSION') ? APP_VERSION : 'v2.4.26') ?></div>
            <div>Cần hỗ trợ? <a href="#">Trò chuyện ngay</a></div>
        </footer>
    <?php endif; ?>

    <script>
        function togglePasswordVisibility(fieldId, iconEl) {
            const field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                iconEl.classList.remove('fa-eye');
                iconEl.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                iconEl.classList.remove('fa-eye-slash');
                iconEl.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
