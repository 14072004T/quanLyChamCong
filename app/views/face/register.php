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
            .face-reg-wrap {
                max-width: 1100px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 20px;
                border: 1px solid #e2e8f0;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
                padding: 30px;
                transition: all 0.3s ease;
            }
            .face-reg-header {
                display: flex;
                align-items: center;
                gap: 20px;
                margin-bottom: 28px;
                border-bottom: 1px solid #f1f5f9;
                padding-bottom: 20px;
            }
            .face-reg-title h2 {
                margin: 0;
                font-size: 24px;
                color: #0f172a;
                font-weight: 800;
                letter-spacing: -0.5px;
            }
            .face-reg-title p {
                margin: 6px 0 0;
                font-size: 14px;
                color: #64748b;
            }
            
            /* Bố cục Grid 2 cột */
            .reg-container-grid {
                display: grid;
                grid-template-columns: 1.2fr 1fr;
                gap: 32px;
                align-items: start;
            }
            
            /* Cột Camera trái */
            .reg-camera-col {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }
            
            .camera-outer-frame {
                position: relative;
                width: 100%;
                aspect-ratio: 4/3;
                background: #090d16;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: inset 0 0 40px rgba(0, 0, 0, 0.8), 0 8px 20px rgba(15, 23, 42, 0.15);
                border: 3px solid #f1f5f9;
                transition: border-color 0.3s ease;
            }
            
            .camera-outer-frame.detecting {
                border-color: #3b82f6;
            }
            
            .camera-outer-frame.completed {
                border-color: #10b981;
            }
            
            #video-element {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transform: scaleX(-1);
            }
            
            #overlay-canvas {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                transform: scaleX(-1);
                z-index: 2;
            }
            
            /* Hiệu ứng quét Laser */
            .laser-scanner-line {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 3px;
                background: linear-gradient(90deg, transparent, #3b82f6, #60a5fa, #3b82f6, transparent);
                box-shadow: 0 0 12px 2px rgba(59, 130, 246, 0.8);
                z-index: 3;
                animation: scan-motion 3.5s infinite linear;
                display: none;
                opacity: 0.7;
            }
            
            .camera-outer-frame.active .laser-scanner-line {
                display: block;
            }
            
            @keyframes scan-motion {
                0% { top: 0%; }
                50% { top: 100%; }
                100% { top: 0%; }
            }
            
            /* Khung ảo định vị khuôn mặt */
            .face-guidance-overlay {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 55%;
                height: 70%;
                border: 2px dashed rgba(255, 255, 255, 0.25);
                border-radius: 50%/45%;
                z-index: 1;
                pointer-events: none;
                transition: all 0.3s ease;
                box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.4);
            }
            
            /* Cột thông tin phải */
            .reg-info-col {
                display: flex;
                flex-direction: column;
                gap: 24px;
                background: #f8fafc;
                padding: 24px;
                border-radius: 16px;
                border: 1px solid #f1f5f9;
            }
            
            .status-banner {
                padding: 14px 18px;
                border-radius: 10px;
                font-size: 14.5px;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 10px;
                transition: all 0.3s ease;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            }
            .status-loading { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
            .status-ready { background: #ecfdf5; color: #059669; border: 1px solid #d1fae5; }
            .status-noface { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
            .status-error { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
            
            /* Vertical Stepper */
            .vertical-stepper {
                display: flex;
                flex-direction: column;
                gap: 16px;
                padding: 6px 4px;
            }
            
            .step-item {
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 12px 16px;
                background: #ffffff;
                border-radius: 10px;
                border: 1px solid #e2e8f0;
                transition: all 0.3s ease;
            }
            
            .step-item.active {
                border-color: #3b82f6;
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.08);
                background: #f0f7ff;
            }
            
            .step-item.completed {
                border-color: #10b981;
                background: #f0fdf4;
            }
            
            .step-circle {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: #f1f5f9;
                color: #64748b;
                border: 2px solid #cbd5e1;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 13px;
                transition: all 0.3s ease;
                flex-shrink: 0;
            }
            
            .step-item.active .step-circle {
                background: #3b82f6;
                color: #ffffff;
                border-color: #3b82f6;
                box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            }
            
            .step-item.completed .step-circle {
                background: #10b981;
                color: #ffffff;
                border-color: #10b981;
            }
            
            .step-details {
                display: flex;
                flex-direction: column;
            }
            
            .step-title {
                font-size: 14px;
                font-weight: 700;
                color: #334155;
                transition: color 0.3s ease;
            }
            
            .step-item.active .step-title {
                color: #1e40af;
            }
            
            .step-desc {
                font-size: 12px;
                color: #64748b;
                margin-top: 2px;
            }
            
            .reg-controls {
                display: flex;
                flex-direction: column;
                gap: 18px;
                margin-top: 8px;
            }
            
            .select-target {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            
            .select-target label {
                font-size: 13.5px;
                font-weight: 700;
                color: #334155;
            }
            
            .yc-input-select {
                height: 44px;
                width: 100%;
                border-radius: 10px;
                border: 1px solid #cbd5e1;
                padding: 8px 14px;
                font-size: 14.5px;
                color: #1e293b;
                font-weight: 500;
                background-color: #ffffff;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
                transition: border-color 0.2s, box-shadow 0.2s;
                outline: none;
                cursor: pointer;
            }
            
            .yc-input-select:focus {
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            }
            
            .history-indicator {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 14px 18px;
                background: #ffffff;
                border-radius: 10px;
                border: 1px solid #e2e8f0;
                font-size: 13.5px;
                color: #475569;
                font-weight: 500;
            }
            
            .btn-action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                width: 100%;
                padding: 15px;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 700;
                border: none;
                cursor: pointer;
                transition: all 0.2s ease;
                color: white;
                background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
            }
            
            .btn-action:hover:not(:disabled) {
                background: linear-gradient(135deg, #2563eb, #1e40af);
                transform: translateY(-1px);
                box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
            }
            
            .btn-action:active:not(:disabled) {
                transform: translateY(1px);
            }
            
            .btn-action:disabled {
                opacity: 0.55;
                cursor: not-allowed;
                box-shadow: none;
                background: #94a3b8;
            }
            
            /* ===== WIZARD STEPS BAR ===== */
            .setup-wizard {
                display: flex;
                flex-direction: column;
                gap: 24px;
            }
            
            .wizard-steps-bar {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0;
                padding: 20px 16px;
                background: linear-gradient(135deg, #f8fafc, #eef2ff);
                border-radius: 14px;
                border: 1px solid #e2e8f0;
            }
            
            .wizard-step-indicator {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 8px 16px;
                border-radius: 8px;
                transition: all 0.3s ease;
                opacity: 0.5;
            }
            
            .wizard-step-indicator.active {
                opacity: 1;
                background: #ffffff;
                box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
            }
            
            .wizard-step-indicator.completed {
                opacity: 1;
            }
            
            .wiz-num {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                background: #e2e8f0;
                color: #64748b;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 13px;
                flex-shrink: 0;
                transition: all 0.3s ease;
            }
            
            .wizard-step-indicator.active .wiz-num {
                background: #3b82f6;
                color: #ffffff;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            }
            
            .wizard-step-indicator.completed .wiz-num {
                background: #10b981;
                color: #ffffff;
            }
            
            .wiz-label {
                font-size: 13.5px;
                font-weight: 600;
                color: #475569;
            }
            
            .wizard-step-indicator.active .wiz-label {
                color: #1e40af;
                font-weight: 700;
            }
            
            .wizard-step-connector {
                width: 40px;
                height: 2px;
                background: #cbd5e1;
                flex-shrink: 0;
            }
            
            /* ===== WIZARD CARDS ===== */
            .wizard-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 28px;
                transition: all 0.3s ease;
                animation: fadeSlideIn 0.3s ease;
            }
            
            @keyframes fadeSlideIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            .wizard-card-header {
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 16px;
                font-weight: 700;
                color: #0f172a;
                margin-bottom: 10px;
            }
            
            .wizard-card-desc {
                font-size: 14px;
                color: #64748b;
                margin: 0 0 18px 0;
            }
            
            /* ===== DEPT STATS ===== */
            .dept-stats {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-top: 14px;
                padding: 12px 16px;
                background: #eff6ff;
                border-radius: 8px;
                border: 1px solid #dbeafe;
                font-size: 13.5px;
                font-weight: 600;
                color: #1e40af;
            }
            
            /* ===== SELECTED EMPLOYEE INFO ===== */
            .selected-employee-info {
                margin-top: 16px;
                padding: 16px 20px;
                background: #f0fdf4;
                border-radius: 10px;
                border: 1px solid #d1fae5;
                display: flex;
                flex-direction: column;
                gap: 10px;
                animation: fadeSlideIn 0.3s ease;
            }
            
            .emp-info-row {
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 14px;
            }
            
            .emp-info-label {
                color: #64748b;
                font-weight: 600;
                min-width: 100px;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            
            .emp-info-value {
                color: #0f172a;
                font-weight: 700;
            }
            
            /* ===== SCANNING TARGET BAR ===== */
            .scanning-target-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px 20px;
                background: linear-gradient(135deg, #eff6ff, #eef2ff);
                border-radius: 12px;
                border: 1px solid #dbeafe;
                margin-bottom: 24px;
                animation: fadeSlideIn 0.3s ease;
            }
            
            .scanning-target-info {
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 14.5px;
                color: #1e293b;
            }
            
            .btn-back-setup {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 16px;
                border-radius: 8px;
                font-size: 13px;
                font-weight: 600;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #475569;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .btn-back-setup:hover {
                background: #f1f5f9;
                border-color: #94a3b8;
            }
            
            /* Responsive Grid */
            @media (max-width: 900px) {
                .reg-container-grid {
                    grid-template-columns: 1fr;
                    gap: 24px;
                }
                .face-reg-wrap {
                    padding: 20px;
                }
                .wizard-steps-bar {
                    flex-wrap: wrap;
                    gap: 8px;
                }
                .wizard-step-connector {
                    display: none;
                }
                .scanning-target-bar {
                    flex-direction: column;
                    gap: 12px;
                    text-align: center;
                }
            }
            
            /* ===== SETUP GRID LAYOUT ===== */
            .setup-grid {
                display: grid;
                grid-template-columns: 1fr 1.2fr;
                gap: 28px;
                align-items: stretch;
                margin-top: 10px;
            }
            
            .setup-form-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 30px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
                display: flex;
                flex-direction: column;
                gap: 20px;
            }
            
            .card-title-badge {
                align-self: flex-start;
                padding: 6px 12px;
                background: #f0fdf4;
                color: #10b981;
                font-size: 11.5px;
                font-weight: 800;
                border-radius: 6px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border: 1px solid #d1fae5;
                margin-bottom: 8px;
            }
            
            .setup-form-group {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            
            .setup-form-group label {
                font-size: 13.5px;
                font-weight: 700;
                color: #475569;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .text-indigo { color: #6366f1; }
            .text-cyan { color: #0891b2; }
            
            .setup-profile-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 30px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
                display: flex;
                flex-direction: column;
                justify-content: center;
                min-height: 280px;
                position: relative;
            }
            
            /* Placeholder style when empty */
            .profile-placeholder {
                text-align: center;
                padding: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 16px;
            }
            
            .placeholder-icon {
                width: 68px;
                height: 68px;
                border-radius: 50%;
                background: #f8fafc;
                color: #cbd5e1;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 28px;
                border: 2px dashed #e2e8f0;
                animation: pulse-border 2s infinite ease-in-out;
            }
            
            @keyframes pulse-border {
                0% { box-shadow: 0 0 0 0 rgba(203, 213, 225, 0.4); }
                70% { box-shadow: 0 0 0 10px rgba(203, 213, 225, 0); }
                100% { box-shadow: 0 0 0 0 rgba(203, 213, 225, 0); }
            }
            
            .profile-placeholder h4 {
                font-size: 16px;
                font-weight: 700;
                color: #64748b;
                margin: 0;
            }
            
            .profile-placeholder p {
                font-size: 13.5px;
                color: #94a3b8;
                margin: 0;
                max-width: 320px;
                line-height: 1.5;
            }
            
            /* Details style when loaded */
            .profile-details-panel {
                display: flex;
                flex-direction: column;
                gap: 24px;
                height: 100%;
                justify-content: space-between;
                animation: fadeSlideIn 0.4s ease;
            }
            
            .profile-header-badge {
                display: flex;
                align-items: center;
                gap: 16px;
                border-bottom: 1px solid #f1f5f9;
                padding-bottom: 16px;
            }
            
            .avatar-circle {
                width: 54px;
                height: 54px;
                border-radius: 50%;
                background: linear-gradient(135deg, #eff6ff, #dbeafe);
                color: #2563eb;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 22px;
                border: 1px solid #bfdbfe;
                box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.05);
            }
            
            .header-name-box h4 {
                font-size: 18px;
                font-weight: 800;
                color: #0f172a;
                margin: 0;
                letter-spacing: -0.3px;
            }
            
            .header-name-box span {
                font-size: 12.5px;
                font-weight: 600;
                color: #2563eb;
                background: #eff6ff;
                padding: 2px 8px;
                border-radius: 4px;
                display: inline-block;
                margin-top: 4px;
            }
            
            .profile-body-rows {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            
            .profile-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 14px;
                border-bottom: 1px dashed #f1f5f9;
                padding-bottom: 8px;
            }
            
            .profile-row:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }
            
            .p-label {
                color: #64748b;
                font-weight: 600;
            }
            
            .p-val {
                color: #1e293b;
                font-weight: 700;
            }
            
            .btn-start-scan {
                background: linear-gradient(135deg, #10b981, #059669);
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
            }
            
            .btn-start-scan:hover:not(:disabled) {
                background: linear-gradient(135deg, #059669, #047857);
                box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
            }
            
            @media (max-width: 900px) {
                .setup-grid {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
            }
            
            /* ===== REGISTERED EMPLOYEES TABLE ===== */
            .registered-section {
                margin-top: 28px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                overflow: hidden;
            }
            
            .registered-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 18px 24px;
                background: linear-gradient(135deg, #fef2f2, #fff1f2);
                border-bottom: 1px solid #fecdd3;
            }
            
            .registered-header-left {
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 15px;
                font-weight: 700;
                color: #be123c;
            }
            
            .registered-count {
                background: #fff1f2;
                color: #e11d48;
                font-size: 12px;
                font-weight: 800;
                padding: 3px 10px;
                border-radius: 20px;
                border: 1px solid #fecdd3;
            }
            
            .reg-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .reg-table th {
                text-align: left;
                padding: 12px 20px;
                font-size: 12px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #64748b;
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
            }
            
            .reg-table td {
                padding: 12px 20px;
                font-size: 14px;
                color: #1e293b;
                border-bottom: 1px solid #f1f5f9;
                vertical-align: middle;
            }
            
            .reg-table tr:last-child td {
                border-bottom: none;
            }
            
            .reg-table tr:hover td {
                background: #f8fafc;
            }
            
            .badge-registered {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: #f0fdf4;
                color: #16a34a;
                font-size: 12px;
                font-weight: 700;
                padding: 3px 10px;
                border-radius: 6px;
                border: 1px solid #bbf7d0;
            }
            
            .btn-delete-face {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 6px 14px;
                font-size: 12.5px;
                font-weight: 700;
                border: 1px solid #fecdd3;
                background: #fff1f2;
                color: #e11d48;
                border-radius: 7px;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .btn-delete-face:hover {
                background: #ffe4e6;
                border-color: #fda4af;
                box-shadow: 0 2px 6px rgba(225, 29, 72, 0.12);
            }
            
            @media (max-width: 900px) {
                .reg-table th:nth-child(3),
                .reg-table td:nth-child(3) {
                    display: none;
                }
            }
        </style>
        
        <div class="face-reg-wrap">
            <div class="face-reg-header">
                <div style="width: 52px; height: 52px; border-radius: 14px; background: #eff6ff; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #3b82f6; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.1);">
                    <i class="fas fa-portrait"></i>
                </div>
                <div class="face-reg-title">
                    <h2>Đăng ký khuôn mặt Nhân viên</h2>
                    <p>Chọn phòng ban và nhân viên trước khi tiến hành quét khuôn mặt</p>
                </div>
            </div>
            
            <!-- ========== BƯỚC 1 & 2: THIẾT LẬP PHÒNG BAN & NHÂN VIÊN SONG SONG ========== -->
            <div id="setup-panel">
                <?php if (empty($employeesList)): ?>
                    <div class="status-banner status-ready" style="flex-direction: column; gap: 8px; padding: 32px;">
                        <i class="fas fa-check-circle" style="font-size: 36px;"></i>
                        <strong style="font-size: 18px;">Tất cả nhân viên đã được đăng ký khuôn mặt!</strong>
                        <p style="margin: 0; font-weight: 400;">Không còn nhân viên nào cần đăng ký trong hệ thống.</p>
                        <a href="index.php?page=home" class="btn-action" style="max-width: 300px; margin-top: 12px; text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Quay về trang chủ
                        </a>
                    </div>
                <?php else: ?>
                    <div class="setup-wizard">
                        <!-- Wizard Step Indicators (Dạng mỏng, hiển thị ngang ở trên) -->
                        <div class="wizard-steps-bar">
                            <div class="wizard-step-indicator active" id="wiz-step-1">
                                <span class="wiz-num">1</span>
                                <span class="wiz-label">Chọn Phòng ban & Nhân viên</span>
                            </div>
                            <div class="wizard-step-connector"></div>
                            <div class="wizard-step-indicator" id="wiz-step-2">
                                <span class="wiz-num">2</span>
                                <span class="wiz-label">Xem Hồ sơ</span>
                            </div>
                            <div class="wizard-step-connector"></div>
                            <div class="wizard-step-indicator" id="wiz-step-3">
                                <span class="wiz-num">3</span>
                                <span class="wiz-label">Quét khuôn mặt</span>
                            </div>
                        </div>
                        
                        <!-- Layout Grid 2 cột song song -->
                        <div class="setup-grid">
                            
                            <!-- CỘT TRÁI: Form Lựa chọn -->
                            <div class="setup-form-card">
                                <div class="card-title-badge">
                                    <i class="fas fa-sliders-h"></i> Bộ lọc thiết lập
                                </div>
                                
                                <div class="setup-form-group">
                                    <label for="dept-select">
                                        <i class="fas fa-building text-indigo"></i> Chọn Phòng ban:
                                    </label>
                                    <select id="dept-select" class="yc-input-select">
                                        <option value="">-- Chọn phòng ban --</option>
                                        <?php foreach ($departmentsList as $dept): ?>
                                            <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="setup-form-group" id="employee-select-group" style="display: none; margin-top: 8px;">
                                    <label for="employee-select">
                                        <i class="fas fa-user-tag text-cyan"></i> Chọn Nhân viên:
                                    </label>
                                    <select id="employee-select" class="yc-input-select">
                                        <option value="">-- Chọn nhân viên --</option>
                                    </select>
                                </div>
                                
                                <!-- Thống kê số nhân viên chưa đăng ký -->
                                <div class="dept-stats" id="dept-stats" style="display: none;">
                                    <i class="fas fa-info-circle"></i>
                                    <span id="dept-stats-text"></span>
                                </div>
                            </div>
                            
                            <!-- CỘT PHẢI: Hồ sơ nhân viên được chọn -->
                            <div class="setup-profile-card">
                                
                                <!-- Trạng thái Chờ khi chưa chọn nhân viên -->
                                <div class="profile-placeholder" id="profile-placeholder">
                                    <div class="placeholder-icon">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <h4>Thông tin sinh trắc</h4>
                                    <p>Vui lòng lựa chọn Phòng ban và Nhân viên ở cột bên trái để tải thông tin hồ sơ chi tiết.</p>
                                </div>
                                
                                <!-- Trạng thái Sẵn sàng khi đã chọn nhân viên -->
                                <div class="profile-details-panel" id="selected-emp-info" style="display: none;">
                                    <div>
                                        <div class="profile-header-badge">
                                            <div class="avatar-circle">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <div class="header-name-box">
                                                <h4 id="emp-info-name"></h4>
                                                <span id="emp-info-dept"></span>
                                            </div>
                                        </div>
                                        
                                        <div class="profile-body-rows">
                                            <div class="profile-row">
                                                <span class="p-label"><i class="fas fa-fingerprint" style="margin-right: 4px;"></i> Mã Nhân viên:</span>
                                                <span class="p-val" id="emp-info-id"></span>
                                            </div>
                                            <div class="profile-row">
                                                <span class="p-label"><i class="fas fa-briefcase" style="margin-right: 4px;"></i> Chức vụ:</span>
                                                <span class="p-val" id="emp-info-role">Nhân viên</span>
                                            </div>
                                            <div class="profile-row">
                                                <span class="p-label"><i class="fas fa-shield-alt" style="margin-right: 4px;"></i> Trạng thái bảo mật:</span>
                                                <span class="p-val" style="color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Chưa có Face ID</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button id="btn-start-camera" class="btn-action btn-start-scan" disabled>
                                        <i class="fas fa-camera"></i> KÍCH HOẠT CAMERA QUÉT MẶT
                                    </button>
                                </div>
                                
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- Danh sách nhân viên ĐÃ đăng ký thuộc phòng ban (Hiển thị động bằng JS) -->
                    <div class="registered-section" id="registered-section" style="display: none;">
                        <div class="registered-header">
                            <div class="registered-header-left">
                                <i class="fas fa-user-check"></i>
                                <span>Nhân viên ĐÃ ĐĂNG KÝ khuôn mặt trong phòng ban</span>
                            </div>
                            <span class="registered-count" id="registered-count">0</span>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="reg-table">
                                <thead>
                                    <tr>
                                        <th>Mã NV</th>
                                        <th>Họ tên</th>
                                        <th>Chức vụ</th>
                                        <th>Trạng thái</th>
                                        <th style="text-align: right;">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="registered-list-body">
                                    <!-- JS sẽ render dòng dữ liệu tại đây -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
            
            <!-- ========== BƯỚC 3: CAMERA QUÉT KHUÔN MẶT (ẨN MẶC ĐỊNH) ========== -->
            <div id="camera-panel" style="display: none;">
                <!-- Thanh thông tin nhân viên đang quét -->
                <div class="scanning-target-bar" id="scanning-target-bar">
                    <div class="scanning-target-info">
                        <i class="fas fa-user-circle" style="font-size: 20px; color: #3b82f6;"></i>
                        <span>Đang quét cho: <strong id="scanning-emp-name"></strong> (ID: <span id="scanning-emp-id"></span>)</span>
                    </div>
                    <button class="btn-back-setup" id="btn-back-setup">
                        <i class="fas fa-arrow-left"></i> Chọn nhân viên khác
                    </button>
                </div>
                
                <div class="reg-container-grid">
                    <!-- Cột Camera trái -->
                    <div class="reg-camera-col">
                        <div class="camera-outer-frame" id="camera-frame-container">
                            <video id="video-element" autoplay muted playsinline></video>
                            <canvas id="overlay-canvas"></canvas>
                            <div class="laser-scanner-line"></div>
                            <div class="face-guidance-overlay"></div>
                        </div>
                    </div>
                    
                    <!-- Cột thông tin phải -->
                    <div class="reg-info-col">
                        <!-- Trạng thái -->
                        <div id="status-display" class="status-banner status-loading">
                            <i class="fas fa-spinner fa-spin"></i> Đang tải mô hình nhận diện khuôn mặt...
                        </div>
                        
                        <!-- Stepper chỉ hướng quét khuôn mặt dạng dọc -->
                        <div class="vertical-stepper" id="face-stepper" style="display: none;">
                            <div class="step-item active" id="step-front">
                                <span class="step-circle">1</span>
                                <div class="step-details">
                                    <span class="step-title">Góc chính diện</span>
                                    <span class="step-desc">Nhìn thẳng vào camera để ghi nhận hình dáng tổng quan</span>
                                </div>
                            </div>
                            <div class="step-item" id="step-turn1">
                                <span class="step-circle">2</span>
                                <div class="step-details">
                                    <span class="step-title">Góc nghiêng thứ nhất</span>
                                    <span class="step-desc">Quay nhẹ đầu sang bên trái hoặc bên phải</span>
                                </div>
                            </div>
                            <div class="step-item" id="step-turn2">
                                <span class="step-circle">3</span>
                                <div class="step-details">
                                    <span class="step-title">Góc nghiêng thứ hai</span>
                                    <span class="step-desc">Quay nhẹ đầu theo hướng ngược lại để hoàn tất</span>
                                </div>
                            </div>
                        </div>
                        
                        <button id="btn-register" class="btn-action" disabled>
                            <i class="fas fa-save"></i> LƯU DỮ LIỆU KHUÔN MẶT
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="public/js/face-api.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    // Nhúng danh sách nhân viên từ PHP
    const unregisteredEmployees = <?= json_encode($employeesList ?? []) ?>;
    const registeredEmployees = <?= json_encode($registeredList ?? []) ?>;

    // Các phần tử giao diện
    const deptSelect = document.getElementById('dept-select');
    const employeeSelect = document.getElementById('employee-select');
    const btnStartCamera = document.getElementById('btn-start-camera');
    const setupPanel = document.getElementById('setup-panel');
    const cameraPanel = document.getElementById('camera-panel');
    const btnBackSetup = document.getElementById('btn-back-setup');

    // Thẻ thông tin nhân viên
    const deptStats = document.getElementById('dept-stats');
    const deptStatsText = document.getElementById('dept-stats-text');
    const selectedEmpInfo = document.getElementById('selected-emp-info');
    const empInfoId = document.getElementById('emp-info-id');
    const empInfoName = document.getElementById('emp-info-name');
    const empInfoDept = document.getElementById('emp-info-dept');

    // Các phần tử danh sách đã đăng ký
    const registeredSection = document.getElementById('registered-section');
    const registeredCount = document.getElementById('registered-count');
    const registeredListBody = document.getElementById('registered-list-body');

    // Thanh chỉ báo bước wizard
    const wizStep1 = document.getElementById('wiz-step-1');
    const wizStep2 = document.getElementById('wiz-step-2');
    const wizStep3 = document.getElementById('wiz-step-3');

    // Các phần tử Camera quét mặt
    const video = document.getElementById('video-element');
    const canvas = document.getElementById('overlay-canvas');
    const statusDisplay = document.getElementById('status-display');
    const btnRegister = document.getElementById('btn-register');
    const scanningEmpName = document.getElementById('scanning-emp-name');
    const scanningEmpId = document.getElementById('scanning-emp-id');

    let lastDescriptor = null;
    let isModelLoaded = false;
    let cameraStream = null;
    let isDetecting = false;
    let detectionTimeout = null;

    // Các biến trạng thái của Stepper quét 3 bước
    let currentStep = 'front'; // 'front', 'turn1', 'turn2', 'completed'
    let successFrames = 0;
    const requiredSuccessFrames = 8;
    let savedFrontDescriptor = null;
    let firstTurnSide = null;

    const stepFront = document.getElementById('step-front');
    const stepTurn1 = document.getElementById('step-turn1');
    const stepTurn2 = document.getElementById('step-turn2');
    const faceStepper = document.getElementById('face-stepper');

    // ==========================================
    // 1. LOGIC WIZARD BƯỚC 1 & 2
    // ==========================================
    
    // Thay đổi phòng ban (Bước 1)
    if (deptSelect) {
        deptSelect.onchange = function() {
            const selectedDept = this.value;
            const empSelectGroup = document.getElementById('employee-select-group');
            const profilePlaceholder = document.getElementById('profile-placeholder');
            
            // Xóa danh sách nhân viên hiện tại
            employeeSelect.innerHTML = '<option value="">-- Chọn nhân viên --</option>';
            selectedEmpInfo.style.display = 'none';
            if (profilePlaceholder) profilePlaceholder.style.display = 'flex';
            btnStartCamera.disabled = true;

            // Xóa danh sách nhân viên đã đăng ký trong bảng cũ
            registeredListBody.innerHTML = '';
            registeredSection.style.display = 'none';

            if (selectedDept) {
                const targetDeptLower = selectedDept.trim().toLowerCase();

                // 1. Lọc nhân viên CHƯA đăng ký thuộc phòng ban
                const filteredUnregistered = unregisteredEmployees.filter(emp => {
                    const empDept = (emp.phongBan || '').trim().toLowerCase();
                    return empDept === targetDeptLower;
                });
                
                filteredUnregistered.forEach(emp => {
                    const opt = document.createElement('option');
                    opt.value = emp.maND;
                    opt.textContent = `${emp.hoTen} (ID: ${emp.maND})`;
                    employeeSelect.appendChild(opt);
                });

                // Cập nhật thống kê phòng ban
                deptStatsText.textContent = `Có ${filteredUnregistered.length} nhân viên chưa đăng ký.`;
                deptStats.style.display = 'flex';

                // Hiển thị phần chọn nhân viên (Bước 2)
                if (empSelectGroup) empSelectGroup.style.display = 'block';
                wizStep1.classList.add('completed');
                wizStep1.classList.remove('active');
                wizStep2.classList.add('active');

                // 2. Lọc nhân viên ĐÃ đăng ký thuộc phòng ban
                const filteredRegistered = registeredEmployees.filter(emp => {
                    const empDept = (emp.phongBan || '').trim().toLowerCase();
                    return empDept === targetDeptLower;
                });

                if (filteredRegistered.length > 0) {
                    registeredCount.textContent = filteredRegistered.length;
                    
                    filteredRegistered.forEach(emp => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><strong>#${emp.maND}</strong></td>
                            <td><strong>${emp.hoTen}</strong></td>
                            <td>${emp.chucVu || 'Nhân viên'}</td>
                            <td><span class="badge-registered"><i class="fas fa-check-circle"></i> Đã đăng ký</span></td>
                            <td style="text-align: right;">
                                <button class="btn-delete-face" data-id="${emp.maND}" data-name="${emp.hoTen}">
                                    <i class="fas fa-trash-alt"></i> Xóa Face ID
                                </button>
                            </td>
                        `;
                        registeredListBody.appendChild(tr);
                    });

                    // Gắn sự kiện cho các nút Xóa Face ID vừa tạo
                    const deleteButtons = registeredListBody.querySelectorAll('.btn-delete-face');
                    deleteButtons.forEach(btn => {
                        btn.onclick = async function() {
                            const maND = this.getAttribute('data-id');
                            const name = this.getAttribute('data-name');
                            
                            const confirmDelete = confirm(`Bạn có chắc chắn muốn XÓA dữ liệu khuôn mặt của nhân viên "${name}" (ID: ${maND})?\n\nHành động này không thể hoàn tác!`);
                            if (!confirmDelete) return;

                            const formData = new FormData();
                            formData.append('targetMaND', maND);

                            try {
                                const response = await fetch('index.php?page=face-api-delete', {
                                    method: 'POST',
                                    body: formData
                                });
                                
                                const responseText = await response.text();
                                let result;
                                try {
                                    result = JSON.parse(responseText);
                                } catch(e) {
                                    throw new Error("Không thể xử lý phản hồi từ máy chủ. Chi tiết: " + responseText.substring(0, 200));
                                }

                                if (result.success) {
                                    alert(result.message);
                                    window.location.reload();
                                } else {
                                    alert('Lỗi: ' + result.message);
                                }
                            } catch (err) {
                                console.error('Lỗi khi gửi yêu cầu xóa:', err);
                                alert(err.message);
                            }
                        };
                    });

                    registeredSection.style.display = 'block';
                }
            } else {
                deptStats.style.display = 'none';
                if (empSelectGroup) empSelectGroup.style.display = 'none';
                wizStep1.classList.add('active');
                wizStep1.classList.remove('completed');
                wizStep2.classList.remove('active', 'completed');
            }
        };
    }

    // Thay đổi nhân viên (Bước 2)
    if (employeeSelect) {
        employeeSelect.onchange = function() {
            const selectedMaND = this.value;
            const profilePlaceholder = document.getElementById('profile-placeholder');
            const empInfoRole = document.getElementById('emp-info-role');

            if (selectedMaND) {
                const emp = unregisteredEmployees.find(e => e.maND.toString() === selectedMaND.toString());
                if (emp) {
                    empInfoId.textContent = emp.maND;
                    empInfoName.textContent = emp.hoTen;
                    empInfoDept.textContent = emp.phongBan || 'Không xác định';
                    if (empInfoRole) empInfoRole.textContent = emp.chucVu || 'Nhân viên';
                    
                    if (profilePlaceholder) profilePlaceholder.style.display = 'none';
                    selectedEmpInfo.style.display = 'flex';
                    btnStartCamera.disabled = false;
                }
            } else {
                selectedEmpInfo.style.display = 'none';
                if (profilePlaceholder) profilePlaceholder.style.display = 'flex';
                btnStartCamera.disabled = true;
            }
        };
    }

    // Nhấn Bắt đầu quét khuôn mặt (Chuyển sang Bước 3)
    if (btnStartCamera) {
        btnStartCamera.onclick = async function() {
            const selectedMaND = employeeSelect.value;
            const emp = unregisteredEmployees.find(e => e.maND.toString() === selectedMaND.toString());
            if (!emp) return;

            // Thiết lập thông tin nhân viên đang quét
            scanningEmpName.textContent = emp.hoTen;
            scanningEmpId.textContent = emp.maND;

            // Chuyển đổi giao diện panel
            setupPanel.style.display = 'none';
            cameraPanel.style.display = 'block';

            // Cập nhật chỉ báo bước quét
            wizStep2.classList.add('completed');
            wizStep2.classList.remove('active');
            wizStep3.classList.add('active');

            // Reset camera state & Stepper
            currentStep = 'front';
            successFrames = 0;
            savedFrontDescriptor = null;
            firstTurnSide = null;
            lastDescriptor = null;
            btnRegister.disabled = true;
            window.regWarmupFrames = 0;
            updateStepperUI();

            // Khởi chạy nạp mô hình và bật camera
            await initFaceApiAndCamera();
        };
    }

    // Nhấn Quay lại chọn nhân viên
    if (btnBackSetup) {
        btnBackSetup.onclick = function() {
            // Tắt camera
            stopCamera();

            // Quay về giao diện thiết lập
            cameraPanel.style.display = 'none';
            setupPanel.style.display = 'block';

            // Cập nhật chỉ báo bước quét
            wizStep3.classList.remove('active', 'completed');
            wizStep2.classList.add('active');
            wizStep2.classList.remove('completed');
        };
    }

    function stopCamera() {
        if (detectionTimeout) clearTimeout(detectionTimeout);
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }
        if (video) {
            video.srcObject = null;
        }
    }

    // ==========================================
    // 2. NHẬN DIỆN KHUÔN MẶT & STEPPER JS
    // ==========================================

    // Hàm cập nhật trạng thái trực quan cho Stepper dọc
    function updateStepperUI() {
        if (!faceStepper) return;
        faceStepper.style.display = 'flex';
        
        const resetStep = (el, stepNum) => {
            el.classList.remove('active', 'completed');
            const circle = el.querySelector('.step-circle');
            if (circle) {
                circle.innerHTML = stepNum;
            }
        };
        
        const activeStep = (el, stepNum) => {
            el.classList.add('active');
            el.classList.remove('completed');
            const circle = el.querySelector('.step-circle');
            if (circle) {
                circle.innerHTML = stepNum;
            }
        };
        
        const completeStep = (el) => {
            el.classList.add('completed');
            el.classList.remove('active');
            const circle = el.querySelector('.step-circle');
            if (circle) {
                circle.innerHTML = '<i class="fas fa-check"></i>';
            }
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

        // Thêm trạng thái phát sáng cho camera container
        const cameraFrame = document.getElementById('camera-frame-container');
        if (cameraFrame) {
            cameraFrame.classList.remove('detecting', 'completed');
            if (currentStep === 'completed') {
                cameraFrame.classList.add('completed');
                cameraFrame.classList.remove('active');
            } else {
                cameraFrame.classList.add('detecting', 'active');
            }
        }
    }

    // Tải mô hình & chạy camera
    async function initFaceApiAndCamera() {
        if (isModelLoaded) {
            statusDisplay.className = 'status-banner status-noface';
            statusDisplay.innerHTML = '<i class="fas fa-video"></i> Đang khởi chạy camera...';
            startCamera();
            return;
        }

        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';
        statusDisplay.className = 'status-banner status-loading';
        statusDisplay.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải mô hình nhận diện khuôn mặt...';

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
            statusDisplay.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Lỗi tải thư viện nhận dạng. Hãy thử lại.';
        }
    }

    // Khởi chạy Camera
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
            await video.play();
            setTimeout(detectFace, 300);
        } catch (err) {
            console.error('Không thể truy cập camera:', err);
            statusDisplay.className = 'status-banner status-error';
            statusDisplay.innerHTML = '<i class="fas fa-camera-slash"></i> Không thể truy cập Camera. Vui lòng cấp quyền camera.';
        }
    }

    // Phân tích khuôn mặt tuần tự
    async function detectFace() {
        if (!isModelLoaded || !cameraStream || video.paused || video.ended || video.readyState < 2) {
            detectionTimeout = setTimeout(detectFace, 300);
            return;
        }

        if (isDetecting) return;
        isDetecting = true;

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

            const detection = await faceapi.detectSingleFace(
                video, 
                new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 })
            ).withFaceLandmarks().withFaceDescriptor();

            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (detection) {
                const resizedDetection = faceapi.resizeResults(detection, displaySize);
                const box = resizedDetection.detection.box;
                ctx.strokeStyle = (currentStep === 'completed') ? '#10b981' : '#3b82f6';
                ctx.lineWidth = 3;
                ctx.strokeRect(box.x, box.y, box.width, box.height);

                const landmarks = detection.landmarks;
                const leftJaw = landmarks.positions[0];
                const rightJaw = landmarks.positions[16];
                const noseTip = landmarks.positions[30];

                const dLeft = noseTip.x - leftJaw.x;
                const dRight = rightJaw.x - noseTip.x;

                if (dRight !== 0) {
                    const ratio = dLeft / dRight;
                    
                    if (currentStep === 'front') {
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
                        
                        const landmarks = detection.landmarks;
                        const leftJaw = landmarks.positions[0];
                        const rightJaw = landmarks.positions[16];
                        const noseTip = landmarks.positions[30];
                        const dLeft = noseTip.x - leftJaw.x;
                        const dRight = rightJaw.x - noseTip.x;
                        if (dRight !== 0) {
                            const ratio = dLeft / dRight;
                            if (ratio >= 0.72 && ratio <= 1.38) {
                                lastDescriptor = detection.descriptor;
                            }
                        }
                        if (!lastDescriptor) {
                            lastDescriptor = detection.descriptor;
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

    // Hủy timeout khi trang unload
    window.addEventListener('beforeunload', () => {
        stopCamera();
    });

    // 4. Nhấn nút đăng ký — gửi API lưu
    btnRegister.onclick = async () => {
        if (!lastDescriptor) return;

        const userConfirmed = confirm('Bạn có chắc chắn muốn LƯU dữ liệu khuôn mặt này?\n\n• Nhấn [OK] để LƯU đăng ký.\n• Nhấn [Cancel] để HỦY và quét lại.');
        
        if (!userConfirmed) {
            // Người dùng chọn HỦY — reset quét
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

        btnRegister.disabled = true;
        const targetMaND = employeeSelect.value;
        const embeddingString = JSON.stringify(Array.from(lastDescriptor));

        const formData = new FormData();
        formData.append('embedding', embeddingString);
        formData.append('targetMaND', targetMaND);

        statusDisplay.className = 'status-banner status-loading';
        statusDisplay.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu dữ liệu khuôn mặt...';

        try {
            const response = await fetch('index.php?page=face-api-register', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                statusDisplay.className = 'status-banner status-ready';
                statusDisplay.innerHTML = '<i class="fas fa-check-circle"></i> ' + result.message;
                
                stopCamera();
                
                setTimeout(() => {
                    alert('Đăng ký khuôn mặt thành công!');
                    window.location.reload();
                }, 500);
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
