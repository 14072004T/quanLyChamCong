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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css?v=<?= (int)$styleVersion ?>">
    <link rel="stylesheet" href="public/css/dashboard.css?v=<?= (int)$dashboardStyleVersion ?>">
</head>

<body>
    <?php
    $notificationItems = [];
    $notificationCount = 0;

    if (isset($_SESSION['user'])) {
        /**
         * Helper to format minutes into "X giờ Y phút"
         */
        if (!function_exists('formatMinutes')) {
            function formatMinutes($minutes) {
                $minutes = (int)$minutes;
                if ($minutes <= 0) return '0 phút';
                $hours = floor($minutes / 60);
                $remMinutes = $minutes % 60;
                
                $result = '';
                if ($hours > 0) {
                    $result .= $hours . ' giờ ';
                }
                if ($remMinutes > 0 || $hours == 0) {
                    $result .= $remMinutes . ' phút';
                }
                return trim($result);
            }
        }
        
        require_once 'app/models/ChamCongModel.php';
        $notificationModel = new ChamCongModel();
        $role = $_SESSION['role'] ?? 'nhanvien';
        $maND = (int)($_SESSION['user']['maND'] ?? 0);

        if ($role === 'nhanvien' && $maND > 0) {
            $requests = $notificationModel->getYeuCauTheoNhanVien($maND);
            foreach ($requests as $req) {
                // Limit to 6 latest notifications max
                if (count($notificationItems) >= 6) {
                    break;
                }

                $status = $req['status'] ?? 'pending';
                $updatedTimeStr = $req['updated_at'] ?? $req['created_at'] ?? date('Y-m-d H:i:s');
                $updatedTime = strtotime($updatedTimeStr);
                $daysAgo = (time() - $updatedTime) / 86400;

                // Stop populating older handled requests (e.g., > 3 days old)
                if ($status !== 'pending' && $daysAgo > 3) {
                    continue;
                }

                $notificationCount++;
                
                $titleMsg = 'Yêu cầu đang chờ duyệt';
                if ($status === 'approved') {
                    $titleMsg = 'Yêu cầu đã ĐƯỢC DUYỆT';
                } elseif ($status === 'rejected') {
                    $titleMsg = 'Yêu cầu BỊ TỪ CHỐI';
                }

                $meta = 'Ngày: ' . ($req['attendance_date'] ?? '');
                if ($status !== 'pending') {
                    $meta .= ' - ' . htmlspecialchars($req['hr_note'] ?: 'Không có ghi chú');
                }

                $notificationItems[] = [
                    'title' => $titleMsg,
                    'meta' => $meta,
                    'time' => $updatedTimeStr,
                    'link' => 'index.php?page=yeu-cau-chinh-sua-cham-cong',
                ];
            }

            // Bảng công tháng chờ nhân viên duyệt
            $pendingTimesheets = $notificationModel->getPendingTimesheets($maND, 4);
            $notificationCount += count($pendingTimesheets);
            foreach ($pendingTimesheets as $ts) {
                $parts = explode('-', $ts['month_key'] ?? '');
                $monthText = count($parts) === 2 ? "Tháng {$parts[1]}/{$parts[0]}" : ($ts['month_key'] ?? '');
                $notificationItems[] = [
                    'title' => 'Bảng công ' . $monthText . ' chờ xác nhận',
                    'meta' => 'HR gửi: ' . ($ts['hr_name'] ?? 'HR'),
                    'time' => $ts['submitted_at'] ?? '',
                    'link' => 'index.php?page=bang-cong-thang',
                ];
            }
        } elseif ($role === 'hr') {
            $pendingCorrections = $notificationModel->getCorrectionRequests('pending');
            $timesheetSummary = $notificationModel->getTimesheetApprovalSummary();
            $notificationCount = count($pendingCorrections);

            foreach (array_slice($pendingCorrections, 0, 3) as $row) {
                $notificationItems[] = [
                    'title' => 'Có yêu cầu chỉnh sửa chờ xử lý',
                    'meta' => ($row['hoTen'] ?? 'Nhân viên') . ' - ' . ($row['attendance_date'] ?? ''),
                    'time' => $row['created_at'] ?? '',
                    'link' => 'index.php?page=xuly-yeucau&request_id=' . (int)($row['id'] ?? 0) . '#request-' . (int)($row['id'] ?? 0),
                ];
            }
            foreach (array_slice($timesheetSummary, 0, 3) as $row) {
                $total = (int)($row['total'] ?? 0);
                $pending = (int)($row['pending'] ?? 0);
                $approved = (int)($row['approved'] ?? 0);
                if ($total > 0) {
                    $notificationCount++;
                    $notificationItems[] = [
                        'title' => 'Bảng công kỳ ' . ($row['month_key'] ?? '') . ': ' . $approved . '/' . $total . ' NV đã duyệt',
                        'meta' => $pending > 0 ? 'Còn ' . $pending . ' nhân viên chưa duyệt' : 'Tất cả nhân viên đã duyệt ✓',
                        'time' => $row['last_submitted'] ?? '',
                        'link' => 'index.php?page=tinh-cong&month=' . urlencode((string)($row['month_key'] ?? '')),
                    ];
                }
            }
        } elseif ($role === 'manager') {
            // Manager chỉ còn yêu cầu chỉnh sửa/nghỉ phép
            $notificationCount = 0;
        }
    }
    ?>
    <header class="header">
        <div class="brand-logo" title="RFT Hệ thống Chấm công">
            <span class="logo-r">R</span>
            <span class="logo-f">F</span>
            <span class="logo-t">T</span>
        </div>
        <h1>HỆ THỐNG QUẢN LÝ CHẤM CÔNG</h1>
        <div class="user-controls">
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
            <?php if (isset($_SESSION['user'])): ?>
                <a href="index.php?page=logout" class="icon-btn" title="Đăng xuất" style="margin-left: 6px; color: #ef4444; border-color: rgba(239, 68, 68, 0.2);">
                    <i class="fa-solid fa-right-from-bracket" style="color: #ef4444;"></i>
                </a>
            <?php else: ?>
                <a href="index.php?page=login" class="icon-btn" title="Đăng nhập" style="margin-left: 6px; color: #10b981; border-color: rgba(16, 185, 129, 0.2);">
                    <i class="fa-solid fa-right-to-bracket" style="color: #10b981;"></i>
                </a>
            <?php endif; ?>
        </div>
    </header>
