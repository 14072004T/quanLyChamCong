<!DOCTYPE html>
<html lang="vi">

<head>
    <?php
    $styleVersion = @filemtime('public/css/style.css') ?: '1.0.3';
    $dashboardStyleVersion = @filemtime('public/css/dashboard.css') ?: '1.0.3';
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFT — Hệ thống Quản lý Chấm Công</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css?v=<?= (int)$styleVersion ?>">
    <link rel="stylesheet" href="public/css/dashboard.css?v=<?= (int)$dashboardStyleVersion ?>">
</head>

<body>
    <?php
    $notificationItems = [];
    $notificationCount = 0;

    if (isset($_SESSION['user'])) {
        require_once 'app/models/ChamCongModel.php';
        $notificationModel = new ChamCongModel();
        $role = $_SESSION['role'] ?? 'nhanvien';
        $maND = (int)($_SESSION['user']['maND'] ?? 0);

        if ($role === 'nhanvien' && $maND > 0) {
            $requests = $notificationModel->getYeuCauTheoNhanVien($maND);
            foreach ($requests as $req) {
                if (($req['status'] ?? '') !== 'pending') {
                    continue;
                }
                $notificationCount++;
                if (count($notificationItems) < 6) {
                    $notificationItems[] = [
                        'title' => 'Yêu cầu chỉnh sửa đang chờ duyệt',
                        'meta' => 'Ngày: ' . ($req['attendance_date'] ?? ''),
                        'time' => $req['created_at'] ?? '',
                        'link' => 'index.php?page=yeu-cau-chinh-sua-cham-cong&request_id=' . (int)($req['id'] ?? 0) . '#request-' . (int)($req['id'] ?? 0),
                    ];
                }
            }
        } elseif ($role === 'hr') {
            $pendingCorrections = $notificationModel->getCorrectionRequests('pending');
            $pendingMonthly = $notificationModel->getMonthlyApprovals('submitted');
            $processedMonthly = $notificationModel->getMonthlyApprovalsBySender($maND, ['approved', 'rejected'], 6);
            $notificationCount = count($pendingCorrections) + count($pendingMonthly) + count($processedMonthly);

            foreach (array_slice($pendingCorrections, 0, 3) as $row) {
                $notificationItems[] = [
                    'title' => 'Có yêu cầu chỉnh sửa chờ xử lý',
                    'meta' => ($row['hoTen'] ?? 'Nhân viên') . ' - ' . ($row['attendance_date'] ?? ''),
                    'time' => $row['created_at'] ?? '',
                    'link' => 'index.php?page=xuly-yeucau&request_id=' . (int)($row['id'] ?? 0) . '#request-' . (int)($row['id'] ?? 0),
                ];
            }
            foreach (array_slice($pendingMonthly, 0, 3) as $row) {
                $notificationItems[] = [
                    'title' => 'Bảng công chưa được quản lý phê duyệt',
                    'meta' => 'Kỳ: ' . ($row['month_key'] ?? ''),
                    'time' => $row['submitted_at'] ?? '',
                    'link' => 'index.php?page=tinh-cong&month=' . urlencode((string)($row['month_key'] ?? '')),
                ];
            }
            foreach (array_slice($processedMonthly, 0, 3) as $row) {
                $notificationItems[] = [
                    'title' => ($row['status'] ?? '') === 'approved' ? 'Bảng công đã được duyệt' : 'Bảng công bị trả về',
                    'meta' => 'Kỳ: ' . ($row['month_key'] ?? '') . ' - QL: ' . ($row['approver_name'] ?? 'Chưa rõ'),
                    'time' => $row['approved_at'] ?? $row['submitted_at'] ?? '',
                    'link' => 'index.php?page=tinh-cong&month=' . urlencode((string)($row['month_key'] ?? '')),
                ];
            }
        } elseif ($role === 'manager') {
            $pendingCorrections = $notificationModel->getCorrectionRequests('pending');
            $pendingMonthly = $notificationModel->getMonthlyApprovals('submitted');
            $notificationCount = count($pendingCorrections) + count($pendingMonthly);

            foreach (array_slice($pendingMonthly, 0, 4) as $row) {
                $notificationItems[] = [
                    'title' => 'Có bảng công chờ phê duyệt',
                    'meta' => 'Kỳ: ' . ($row['month_key'] ?? ''),
                    'time' => $row['submitted_at'] ?? '',
                    'link' => 'index.php?page=pheduyet-bang-cong&approval_id=' . (int)($row['id'] ?? 0),
                ];
            }
            foreach (array_slice($pendingCorrections, 0, 2) as $row) {
                $notificationItems[] = [
                    'title' => 'Có yêu cầu chỉnh sửa chưa approve',
                    'meta' => ($row['hoTen'] ?? 'Nhân viên') . ' - ' . ($row['attendance_date'] ?? ''),
                    'time' => $row['created_at'] ?? '',
                    'link' => 'index.php?page=pheduyet-bang-cong',
                ];
            }
        }
    }
    ?>
    <header class="header">
        <div class="logo" id="logo-interlock">
            <span>R</span><span class="middle-letter">F</span><span>T</span>
        </div>
        <h1>HỆ THỐNG QUẢN LÝ CHẤM CÔNG</h1>
        <div class="user-controls">
            <span class="icon-btn" title="Tìm kiếm" style="cursor:pointer">
                <i class="fa-solid fa-magnifying-glass"></i>
            </span>
            <div class="notif-wrapper" id="notifWrapper">
                <button type="button" id="notifBellBtn" class="icon-btn" title="Thông báo" style="cursor:pointer;position:relative">
                    <i class="fa-regular fa-bell"></i>
                    <?php if ($notificationCount > 0): ?>
                        <span class="notif-count"><?= (int)$notificationCount ?></span>
                    <?php endif; ?>
                </button>

                <div id="notifPanel" class="notif-panel" aria-hidden="true" hidden>
                    <div class="notif-panel-head">
                        <strong>Thông báo</strong>
                        <span><?= (int)$notificationCount ?> thông báo</span>
                    </div>
                    <div class="notif-panel-list">
                        <?php if (!empty($notificationItems)): ?>
                            <?php foreach ($notificationItems as $item): ?>
                                <a class="notif-item" href="<?= htmlspecialchars($item['link']) ?>">
                                    <div class="notif-item-title"><?= htmlspecialchars($item['title']) ?></div>
                                    <div class="notif-item-meta"><?= htmlspecialchars($item['meta']) ?></div>
                                    <div class="notif-item-time"><?= htmlspecialchars($item['time']) ?></div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="notif-empty">Không có thông báo mới.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <a href="<?php echo isset($_SESSION['user']) ? 'index.php?page=cham-cong-dashboard' : 'index.php?page=login'; ?>"
                class="user-link">
                <?php
                if (isset($_SESSION['user'])) {
                    $tenHienThi = htmlspecialchars($_SESSION['user']['hoTen'] ?? 'Người dùng');
                    $initials = '';
                    $parts = explode(' ', trim($_SESSION['user']['hoTen'] ?? 'ND'));
                    if (count($parts) >= 2) {
                        $initials = mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1);
                    } else {
                        $initials = mb_substr($parts[0], 0, 2);
                    }
                    echo '<div class="user-avatar">' . htmlspecialchars(mb_strtoupper($initials)) . '</div>';
                    echo '<span class="welcome-text">' . $tenHienThi . '</span>';
                } else {
                    echo '<div class="user-avatar"><i class="fa-regular fa-user" style="font-size:13px"></i></div>';
                    echo '<span class="welcome-text">Đăng nhập</span>';
                }
                ?>
            </a>
        </div>
    </header>
