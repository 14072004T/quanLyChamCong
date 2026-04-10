<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập — RFT Hệ thống Chấm Công</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            height: 100%;
            width: 100%;
            font-family: 'Poppins', 'Segoe UI', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: #0f172a;
        }

        /* ========== SPLIT LAYOUT ========== */
        .login-page {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left branding panel */
        .login-brand {
            flex: 1;
            background: linear-gradient(145deg, #0f172a 0%, #1e3a8a 50%, #1d4ed8 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px;
            position: relative;
            overflow: hidden;
        }

        .login-brand::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: rgba(59,130,246,.08);
            top: -120px; right: -120px;
        }

        .login-brand::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(6,182,212,.06);
            bottom: -60px; left: -60px;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            height: 56px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 32px;
            font-weight: 800;
            font-size: 2em;
            letter-spacing: -2px;
        }

        .brand-logo .l-r { color: #e2e8f0; padding: 0 10px; height: 56px; display: flex; align-items: center; background: rgba(255,255,255,.06); }
        .brand-logo .l-f { color: white; padding: 0 16px; height: 56px; display: flex; align-items: center; background: linear-gradient(135deg, #3b82f6, #1d4ed8); transform: skewX(-10deg); margin: 0 -3px; box-shadow: 0 0 20px rgba(59,130,246,.5); }
        .brand-logo .l-t { color: white; padding: 0 14px; height: 56px; display: flex; align-items: center; background: linear-gradient(135deg, #06b6d4, #0891b2); transform: skewX(-10deg); border-radius: 0 8px 8px 0; box-shadow: 0 0 20px rgba(6,182,212,.4); }

        .brand-tagline {
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .brand-tagline h2 {
            font-size: 1.6em;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .brand-tagline p {
            font-size: 0.92em;
            color: #64748b;
            line-height: 1.7;
            max-width: 320px;
        }

        .brand-features {
            margin-top: 40px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            position: relative;
            z-index: 1;
        }

        .brand-feature {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #94a3b8;
            font-size: 0.88em;
        }

        .brand-feature i {
            width: 32px; height: 32px;
            background: rgba(59,130,246,.12);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #3b82f6;
            font-size: 0.9em;
            flex-shrink: 0;
        }

        /* Right form panel */
        .login-form-panel {
            width: 480px;
            min-width: 380px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
        }

        .login-form-inner {
            width: 100%;
            max-width: 380px;
            animation: slideInRight 0.45s ease;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        /* ========== FORM HEADER ========== */
        .form-header { margin-bottom: 32px; }

        .form-header h1 {
            font-size: 1.6em;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .form-header p {
            font-size: 0.88em;
            color: #64748b;
        }

        /* ========== ALERTS ========== */
        .canh-bao {
            padding: 12px 16px;
            border-radius: 9px;
            border: 1px solid;
            font-size: 0.87em;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: fadeIn .3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .canh-bao i { margin-top: 1px; flex-shrink: 0; }

        .canh-bao-loi  { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        .canh-bao-info { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }

        /* ========== FORM FIELDS ========== */
        .dang-nhap-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .dang-nhap-form-nhom { display: flex; flex-direction: column; gap: 5px; }

        .dang-nhap-form-nhom label {
            font-size: 0.78em;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.9em;
        }

        .dang-nhap-form-nhom input {
            width: 100%;
            padding: 11px 14px 11px 40px;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            font-size: 0.92em;
            font-family: inherit;
            background: #f8fafc;
            color: #0f172a;
            transition: border-color .25s ease, box-shadow .25s ease, background .25s ease;
        }

        .dang-nhap-form-nhom input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59,130,246,.12);
        }

        .dang-nhap-form-nhom input::placeholder { color: #cbd5e1; }

        /* ========== SUBMIT BUTTON ========== */
        .dang-nhap-nut {
            padding: 12px 20px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 9px;
            font-size: 0.92em;
            font-weight: 700;
            cursor: pointer;
            transition: all .25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
            margin-top: 4px;
            box-shadow: 0 4px 14px rgba(37,99,235,.3);
            letter-spacing: 0.3px;
        }

        .dang-nhap-nut:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 6px 20px rgba(37,99,235,.4);
            transform: translateY(-1px);
        }

        .dang-nhap-nut:active { transform: translateY(0); }

        /* ========== FOOTER TEXT ========== */
        .dang-nhap-footer {
            text-align: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
            font-size: 0.78em;
            color: #94a3b8;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 820px) {
            .login-brand { display: none; }
            .login-form-panel { width: 100%; min-width: unset; }
        }

        @media (max-width: 480px) {
            .login-form-panel { padding: 32px 24px; }
        }
    </style>
</head>
<body>

    <div class="login-page">

        <!-- LEFT BRANDING PANEL -->
        <div class="login-brand">
            <div class="brand-logo">
                <span class="l-r">R</span>
                <span class="l-f">F</span>
                <span class="l-t">T</span>
            </div>
            <div class="brand-tagline">
                <h2>Hệ thống<br>Quản lý Chấm Công</h2>
                <p>Giải pháp số hoá chấm công thông minh<br>dành cho doanh nghiệp hiện đại.</p>
            </div>
            <div class="brand-features">
                <div class="brand-feature">
                    <i class="fas fa-wifi"></i>
                    <span>Chấm công qua WiFi nội bộ</span>
                </div>
                <div class="brand-feature">
                    <i class="fas fa-qrcode"></i>
                    <span>Hỗ trợ mã QR dự phòng</span>
                </div>
                <div class="brand-feature">
                    <i class="fas fa-chart-bar"></i>
                    <span>Báo cáo & thống kê tự động</span>
                </div>
                <div class="brand-feature">
                    <i class="fas fa-shield-alt"></i>
                    <span>Bảo mật & phân quyền linh hoạt</span>
                </div>
            </div>
        </div>

        <!-- RIGHT FORM PANEL -->
        <div class="login-form-panel">
            <div class="login-form-inner">

                <div class="form-header">
                    <h1>Đăng nhập</h1>
                    <p>Vui lòng nhập thông tin tài khoản để tiếp tục</p>
                </div>

                <!-- Thông báo lỗi -->
                <?php
                    $loi = $_GET['error'] ?? '';
                    if ($loi === '1'):
                ?>
                    <div class="canh-bao canh-bao-loi">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Sai tên đăng nhập hoặc mật khẩu. Vui lòng thử lại.</span>
                    </div>
                <?php endif; ?>

                <?php if ($loi === 'inactive'): ?>
                    <div class="canh-bao canh-bao-info">
                        <i class="fas fa-lock"></i>
                        <div>
                            <strong>Tài khoản đã bị khóa</strong><br>
                            <span style="font-size:.9em">Vui lòng liên hệ quản trị viên để được hỗ trợ.</span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- FORM -->
                <form class="dang-nhap-form" method="POST" action="index.php?page=login-process">

                    <div class="dang-nhap-form-nhom">
                        <label for="ten-dang-nhap">Tên đăng nhập</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input
                                type="text"
                                id="ten-dang-nhap"
                                name="username"
                                placeholder="Nhập tên đăng nhập"
                                autofocus
                                required>
                        </div>
                    </div>

                    <div class="dang-nhap-form-nhom">
                        <label for="mat-khau">Mật khẩu</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input
                                type="password"
                                id="mat-khau"
                                name="password"
                                placeholder="Nhập mật khẩu"
                                required>
                        </div>
                    </div>

                    <button type="submit" class="dang-nhap-nut">
                        <i class="fas fa-arrow-right-to-bracket"></i>
                        Đăng Nhập
                    </button>
                </form>

                <div class="dang-nhap-footer">
                    <p>© 2026 RFT Hệ Thống Quản Lý Chấm Công &mdash; v1.0.2</p>
                </div>

            </div>
        </div>

    </div>
</body>
</html>
