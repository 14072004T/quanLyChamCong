<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập — RFT Hệ thống Chấm Công</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1b5ed8;
            --light-blue: #eef4ff;
            --text-dark: #1e293b;
            --form-bg: #1e293b; /* Right panel background */
            --input-bg: #334155;
            --input-border: #475569;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
            color: var(--text-dark);
        }

        /* Background Bubbles */
        .bg-shape {
            position: absolute;
            border-radius: 50%;
            z-index: -1;
            filter: blur(90px);
        }
        .bg-shape-1 {
            width: 700px; height: 700px;
            background: rgba(186, 218, 255, 0.45);
            top: -150px; left: -150px;
        }
        .bg-shape-2 {
            width: 800px; height: 800px;
            background: rgba(191, 230, 255, 0.5);
            bottom: -200px; right: -200px;
        }
        .bg-shape-3 {
            width: 500px; height: 500px;
            background: rgba(219, 234, 254, 0.6);
            bottom: -50px; left: -100px;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 50px 20px 40px;
            z-index: 1;
        }

        /* HEADER SECTION */
        .header-section {
            text-align: center;
            margin-bottom: 48px;
        }
        .brand-logo {
            display: inline-flex;
            gap: 2px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.25);
            background: linear-gradient(135deg, #475569, #0ea5e9, #3b82f6);
            padding: 4px;
        }
        .brand-logo span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            font-size: 26px;
            font-weight: 800;
            font-family: 'Inter', sans-serif;
            color: white;
            border-radius: 8px;
        }
        .logo-r { background: #334155; }
        .logo-f { background: #0ea5e9; }
        .logo-t { background: #3b82f6; }

        .header-title {
            font-size: 32px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .header-subtitle {
            font-size: 16px;
            color: #475569;
            font-weight: 400;
        }

        /* CONTENT WRAPPER */
        .content-wrapper {
            display: flex;
            gap: 60px;
            max-width: 1000px;
            width: 100%;
            align-items: flex-start;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* LEFT ILLUSTRATION PANEL */
        .illustration-panel {
            flex: 1;
            min-width: 380px;
            max-width: 500px;
            padding-top: 10px;
        }
        .mockup-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08), 0 1px 3px rgba(0,0,0,0.05);
            padding: 24px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            transform: perspective(1000px) rotateY(2deg);
        }
        .mockup-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
        }
        .mockup-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .mockup-logo {
            width: 32px; height: 32px;
            background: #0f172a;
            border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 12px; font-weight: bold;
        }
        .mockup-title {
            font-weight: 700;
            font-size: 16px;
            color: #1e293b;
        }
        .mockup-header-right {
            display: flex;
            gap: 12px;
            color: #64748b;
        }
        
        .mockup-cards {
            display: flex;
            gap: 14px;
        }
        .m-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 12px;
            flex: 1;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            position: relative;
        }
        .m-card-chart {
            display: flex; align-items: flex-end; justify-content: space-between;
            height: 60px; margin-bottom: 16px; padding: 0 8px;
        }
        .bar { width: 14%; background: #0ea5e9; border-radius: 2px 2px 0 0; }
        .bar:nth-child(1) { height: 40%; background: #0ea5e9; }
        .bar:nth-child(2) { height: 70%; background: #0284c7; }
        .bar:nth-child(3) { height: 50%; background: #3b82f6; }
        .bar:nth-child(4) { height: 90%; background: #0f172a; }
        .bar:nth-child(5) { height: 35%; background: #38bdf8; }

        .m-card-icon {
            font-size: 40px;
            margin-bottom: 12px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
            position: relative;
        }
        
        /* Custom styled calendar with location pin for Check-in pin */
        .icon-checkin-wrapper {
            position: relative;
            display: inline-block;
            font-size: 40px;
            color: #93c5fd;
        }
        .icon-map-pin {
            position: absolute;
            bottom: -5px;
            right: -5px;
            font-size: 24px;
            color: #3b82f6;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        /* Face ID style */
        .icon-faceid {
            color: #0f172a;
            font-size: 44px;
        }

        .m-card p {
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
        }

        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 24px;
            padding: 0 10px;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .feature-bullet {
            margin-top: 6px;
            font-size: 6px;
            color: #0f172a;
        }
        .feature-text {
            font-size: 14px;
            color: #475569;
            line-height: 1.5;
        }
        .feature-text strong {
            color: #0f172a;
            font-weight: 700;
        }

        /* RIGHT FORM PANEL */
        .login-panel {
            width: 400px;
            background: var(--form-bg);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
            color: white;
            position: relative;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .form-header {
            margin-bottom: 32px;
        }
        .form-header h2 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 8px;
            color: white;
        }
        .form-header p {
            font-size: 14px;
            color: #94a3b8;
        }

        .form-group {
            margin-bottom: 22px;
            text-align: left;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            color: #cbd5e1;
        }
        .input-group {
            position: relative;
        }
        .input-group i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
        }
        .input-group input {
            width: 100%;
            padding: 13px 16px 13px 44px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 8px;
            color: white;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: all 0.25s;
        }
        .input-group input:focus {
            outline: none;
            border-color: #3b82f6;
            background: #1e293b;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }
        .input-group input::placeholder {
            color: #94a3b8;
        }
        
        .input-group input::-ms-reveal,
        .input-group input::-ms-clear {
            filter: invert(1);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s;
            margin-top: 24px;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
        }
        .btn-submit:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }
        .btn-submit:active {
            transform: translateY(1px);
        }

        /* ALERTS */
        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.4;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }
        .alert i {
            margin-top: 2px;
        }

        /* DIVIDER - No longer needed as per request, but keeping struct clean */

        /* FOOTER */
        .footer {
            background: #1e293b; /* Dark bottom bar */
            color: #94a3b8;
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            width: 100%;
            z-index: 10;
        }
        .footer a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
        }
        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .content-wrapper {
                flex-direction: column;
                align-items: center;
                gap: 40px;
            }
            .illustration-panel {
                display: none; /* Hide on smaller screens to prioritize login */
            }
            .footer {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            .bg-shape { display: none; }
            body { background: #e2e8f0; }
        }
    </style>
</head>
<body>

    <!-- Background bubbles -->
    <div class="bg-shape bg-shape-1"></div>
    <div class="bg-shape bg-shape-2"></div>
    <div class="bg-shape bg-shape-3"></div>

    <div class="main-content">
        <!-- HEADER -->
        <div class="header-section">
            <div class="brand-logo">
                <span class="logo-r">R</span>
                <span class="logo-f">F</span>
                <span class="logo-t">T</span>
            </div>
            <h1 class="header-title">Hệ thống Quản lý Chấm công</h1>
            <p class="header-subtitle">Giải pháp số hoá chấm công thông minh dành cho doanh nghiệp hiện đại.</p>
        </div>

        <!-- CONTENT -->
        <div class="content-wrapper">
            <!-- Left Illustration -->
            <div class="illustration-panel">
                <div class="mockup-container">
                    <div class="mockup-header">
                        <div class="mockup-header-left">
                            <div class="mockup-logo">RFT</div>
                            <span class="mockup-title">Dashboard</span>
                        </div>
                        <div class="mockup-header-right">
                            <i class="fas fa-bell"></i>
                            <i class="fas fa-user-circle"></i>
                        </div>
                    </div>
                    
                    <div class="mockup-cards">
                        <div class="m-card">
                            <div class="m-card-chart">
                                <div class="bar"></div>
                                <div class="bar"></div>
                                <div class="bar"></div>
                                <div class="bar"></div>
                                <div class="bar"></div>
                            </div>
                            <p>Báo cáo & thống kê</p>
                        </div>
                        <div class="m-card">
                            <div class="m-card-icon">
                                <div class="icon-checkin-wrapper">
                                    <i class="far fa-calendar"></i>
                                    <i class="fas fa-map-marker-alt icon-map-pin"></i>
                                </div>
                            </div>
                            <p>Check-in pin</p>
                        </div>
                        <div class="m-card">
                            <div class="m-card-icon">
                                <i class="fas fa-expand icon-faceid"></i>
                            </div>
                            <p>FaceID</p>
                        </div>
                    </div>
                </div>

                <div class="features-grid">
                    <div class="feature-item">
                        <i class="fas fa-circle feature-bullet"></i>
                        <div class="feature-text">
                            <strong>WiFi</strong> chấm công qua WiFi nội bộ địa chấm công
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-circle feature-bullet"></i>
                        <div class="feature-text">
                            <strong>Reports</strong> Báo cáo & thống kê tự động
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-circle feature-bullet"></i>
                        <div class="feature-text">
                            <strong>QR</strong> giải pháp số hoá chấm công bằng QR dự phòng
                        </div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-circle feature-bullet"></i>
                        <div class="feature-text">
                            <strong>Security</strong> Bảo mật & phân quyền linh hoạt
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Login Form -->
            <div class="login-panel">
                <div class="form-header">
                    <h2>Đăng nhập</h2>
                    <p>Vui lòng nhập thông tin tài khoản để tiếp tục</p>
                </div>

                <?php
                    $loi = $_GET['error'] ?? '';
                    if ($loi === '1'):
                ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Sai tên đăng nhập hoặc mật khẩu. Vui lòng thử lại.</span>
                    </div>
                <?php endif; ?>

                <?php if ($loi === 'inactive'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-lock"></i>
                        <div>
                            <strong>Tài khoản đã bị khóa</strong><br>
                            <span>Vui lòng liên hệ quản trị viên để được hỗ trợ.</span>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php?page=login-process">
                    <div class="form-group">
                        <label for="username">TÊN ĐĂNG NHẬP</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" autocomplete="username" autofocus required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">MẬT KHẨU</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" autocomplete="current-password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-arrow-right-to-bracket"></i> Đăng Nhập
                    </button>
                    <!-- Chú ý: Phần "Hoặc tiếp tục với Google vs Microsoft" đã được loại bỏ theo yêu cầu -->
                </form>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <div>© 2026 RFT Hệ Thống Quản Lý Chấm Công — v1.0.2</div>
        <div>Cần hỗ trợ? <a href="#">Trò chuyện ngay</a></div>
    </footer>

</body>
</html>
