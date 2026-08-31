<?php
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}
$user = $_SESSION['user'] ?? [];
$role = $_SESSION['role'] ?? 'nhanvien';

// Check if face is registered to hide option
require_once 'app/models/FaceModel.php';
require_once 'app/models/ChamCongModel.php';
$faceModel = new FaceModel();
$chamCongModel = new ChamCongModel();
$maND = $user['maND'] ?? null;
$hasFace = $faceModel->getFaceProfile($maND) !== null;

$isHR = ($role === 'hr');
$showFaceRegisterOption = false;

if ($isHR) {
    $showFaceRegisterOption = true;
    if ($hasFace) {
        $rawList = $chamCongModel->getEmployees('', true) ?? [];
        $hasUnregisteredEmp = false;
        foreach ($rawList as $emp) {
            if ($emp['maND'] != $maND && !$faceModel->getFaceProfile($emp['maND'])) {
                $hasUnregisteredEmp = true;
                break;
            }
        }
        if (!$hasUnregisteredEmp) {
            $showFaceRegisterOption = false;
        }
    }
}

$initials = '';
$parts = explode(' ', trim($user['hoTen'] ?? 'ND'));
if (count($parts) >= 2) {
    $initials = mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1);
} else {
    $initials = mb_substr($parts[0], 0, 2);
}
$initials = mb_strtoupper($initials);
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>

<div class="main-container">
    <div class="dashboard-container" style="padding: 16px;">
        
        <h2 class="mb-request-title" style="margin-bottom: 20px;"><i class="fa-solid fa-user-gear"></i> Tài khoản</h2>

        <!-- Profile Detail Card -->
        <div class="mb-profile-card" style="flex-direction: column; align-items: center; text-align: center; gap: 15px; padding: 30px 20px;">
            <div class="user-avatar" style="width: 72px; height: 72px; font-size: 26px; border-radius: 50%;">
                <?= htmlspecialchars($initials) ?>
            </div>
            
            <div class="mb-profile-meta">
                <h3 style="font-size: 20px;"><?= htmlspecialchars($user['hoTen'] ?? 'Hội viên') ?></h3>
                <p style="font-size: 13px; margin-top: 4px;"><?= htmlspecialchars($user['phongBan'] ?? 'Sản xuất') ?> • ID: #<?= htmlspecialchars($user['maND'] ?? '1') ?></p>
            </div>

            <span class="mb-profile-role-badge">
                Vai trò: <?= strtoupper($role) ?>
            </span>
        </div>

        <!-- Settings Options list -->
        <div class="mb-request-card" style="padding: 8px 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid #f1f3f5;">
                <span style="font-size: 14px; font-weight: 600; color: #334155;"><i class="fa-solid fa-building-shield" style="width:20px; color:#4a5568"></i> Phòng ban</span>
                <span style="font-size: 14px; font-weight: 500; color: #64748b;"><?= htmlspecialchars($user['phongBan'] ?? 'Sản xuất') ?></span>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid #f1f3f5;">
                <span style="font-size: 14px; font-weight: 600; color: #334155;"><i class="fa-solid fa-signature" style="width:20px; color:#4a5568"></i> Chức vụ</span>
                <span style="font-size: 14px; font-weight: 500; color: #64748b;"><?= htmlspecialchars($user['chucVu'] ?? 'Nhân viên') ?></span>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 0;">
                <span style="font-size: 14px; font-weight: 600; color: #334155;"><i class="fa-solid fa-mobile-screen-button" style="width:20px; color:#4a5568"></i> Phiên bản ứng dụng</span>
                <span style="font-size: 14px; font-weight: 500; color: #64748b;"><?= htmlspecialchars(defined('APP_VERSION') ? APP_VERSION : 'v2.4.26') ?></span>
            </div>
        </div>

        <!-- Face Register Option -->
        <?php if ($showFaceRegisterOption): ?>
        <a href="index.php?page=face-register" class="mb-full-card" style="margin-bottom: 16px; text-decoration: none;">
            <div class="mb-full-card-left">
                <h4 style="margin:0; font-size:13px; color:#334155; font-weight:700;"><i class="fa-solid fa-portrait" style="color:#1e62ec; margin-right:6px"></i> Nhận diện khuôn mặt</h4>
                <p style="margin:4px 0 0 0; font-size:11px; color:#94a3b8">Đăng ký hoặc cập nhật dữ liệu FaceID</p>
            </div>
            <div class="mb-icon-circle-btn" style="width:32px; height:32px; font-size:14px;">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </a>
        <?php endif; ?>

        <!-- Logout Button -->
        <div style="padding: 10px 0;">
            <a href="index.php?page=logout" class="mb-request-submit-btn" style="background-color: #ef4444; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.2); text-decoration: none;">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất tài khoản
            </a>
        </div>

    </div>
</div>

<div class="mb-login-footer" style="background: none; border-top: none;">
    <div>© 2026 RFT HỆ THỐNG QUẢN LÝ CHẤM CÔNG – <?= htmlspecialchars(defined('APP_VERSION') ? APP_VERSION : 'v2.4.26') ?></div>
    <div style="margin-top: 4px;">Cần hỗ trợ? <a href="#">Trò chuyện ngay</a></div>
</div>
<?php include 'app/views/layouts/footer.php'; ?>
