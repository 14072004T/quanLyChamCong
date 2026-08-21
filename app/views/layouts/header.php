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
    <link rel="stylesheet" href="public/css/mobile.css?v=<?= time() ?>">
</head>

<body class="<?= AuthMiddleware::isMobile() ? 'mobile-view' : '' ?>">
    <script>
        (function () {
            try {
                const params = new URLSearchParams(window.location.search);
                const force = params.get('device');
                if (force === 'mobile') {
                    document.body.classList.add('mobile-view');
                    return;
                }
                if (force === 'desktop') {
                    return;
                }
                const isTabletOrMobile = window.innerWidth <= 1024 && (
                    navigator.maxTouchPoints > 0 || /iPad|iPhone|iPod|Android|Mobile|Tablet/i.test(navigator.userAgent)
                );
                if (isTabletOrMobile) {
                    document.body.classList.add('mobile-view');
                }
            } catch (e) {}
        })();
    </script>
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
                $soGio = floor($minutes / 60);
                $remMinutes = $minutes % 60;
                
                $result = '';
                if ($soGio > 0) {
                    $result .= $soGio . ' giờ ';
                }
                if ($remMinutes > 0 || $soGio == 0) {
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

                $trangThai = $req['trangThai'] ?? 'pending';
                $updatedTimeStr = $req['ngayCapNhat'] ?? $req['ngayTao'] ?? date('Y-m-d H:i:s');
                $updatedTime = strtotime($updatedTimeStr);
                $daysAgo = (time() - $updatedTime) / 86400;

                // Stop populating older handled requests (e.g., > 3 days old)
                if ($trangThai !== 'pending' && $daysAgo > 3) {
                    continue;
                }

                $notificationCount++;
                
                $titleMsg = 'Yêu cầu đang chờ duyệt';
                if ($trangThai === 'approved') {
                    $titleMsg = 'Yêu cầu đã ĐƯỢC DUYỆT';
                } elseif ($trangThai === 'rejected') {
                    $titleMsg = 'Yêu cầu BỊ TỪ CHỐI';
                }

                $meta = 'Ngày: ' . ($req['ngayChamCong'] ?? '');
                if ($trangThai !== 'pending') {
                    $meta .= ' - ' . htmlspecialchars($req['ghiChuNS'] ?: 'Không có ghi chú');
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
                $parts = explode('-', $ts['thangNam'] ?? '');
                $monthText = count($parts) === 2 ? "Tháng {$parts[1]}/{$parts[0]}" : ($ts['thangNam'] ?? '');
                $notificationItems[] = [
                    'title' => 'Bảng công ' . $monthText . ' chờ xác nhận',
                    'meta' => 'HR gửi: ' . ($ts['hr_name'] ?? 'HR'),
                    'time' => $ts['ngayGui'] ?? '',
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
                    'meta' => ($row['hoTen'] ?? 'Nhân viên') . ' - ' . ($row['ngayChamCong'] ?? ''),
                    'time' => $row['ngayTao'] ?? '',
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
                        'title' => 'Bảng công kỳ ' . ($row['thangNam'] ?? '') . ': ' . $approved . '/' . $total . ' NV đã duyệt',
                        'meta' => $pending > 0 ? 'Còn ' . $pending . ' nhân viên chưa duyệt' : 'Tất cả nhân viên đã duyệt ✓',
                        'time' => $row['last_submitted'] ?? '',
                        'link' => 'index.php?page=tinh-cong&month=' . urlencode((string)($row['thangNam'] ?? '')),
                    ];
                }
            }
        } elseif ($role === 'manager') {
            // Manager chỉ còn yêu cầu chỉnh sửa/nghỉ phép
            $notificationCount = 0;
        }
    }
    ?>
    <?php if (AuthMiddleware::isMobile()): ?>
        <header class="mobile-header">
            <button type="button" class="menu-trigger" title="Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="header-brand-group">
                <div class="header-logo">RFT</div>
                <button type="button" class="sidebar-reopen-btn" title="Mở menu" aria-label="Mở menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <button type="button" class="header-notif" id="mobileNotifBtn" onclick="toggleMobileNotif()" style="cursor:pointer;position:relative;" title="Thông báo">
                    <i class="fa-regular fa-bell"></i>
                    <?php if ($notificationCount > 0): ?>
                        <span class="notif-count"><?= (int)$notificationCount ?></span>
                    <?php endif; ?>
                </button>
                <a href="index.php?page=logout" style="color: #ff4d4f; font-size: 19px; display: flex; align-items: center; text-decoration: none;" title="Đăng xuất" onclick="return confirm('Bạn có chắc chắn muốn đăng xuất tài khoản?')">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>

            <!-- Mobile Notifications Panel -->
            <div id="mobileNotifPanel" class="notif-panel" aria-hidden="true" style="display:none; position:absolute; top:56px; right:16px; width:300px;" hidden>
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
        </header>

        <!-- Bottom Navigation Bar for Mobile -->
        <nav class="bottom-nav">
            <?php $page = $_GET['page'] ?? 'home'; ?>
            <a href="index.php?page=home" class="bottom-nav-item <?= in_array($page, ['home', 'cham-cong-dashboard']) ? 'active' : '' ?>">
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            <a href="index.php?page=lich-su-cham-cong" class="bottom-nav-item <?= ($page === 'lich-su-cham-cong') ? 'active' : '' ?>">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>History</span>
            </a>
            <a href="index.php?page=bang-cong-thang" class="bottom-nav-item <?= ($page === 'bang-cong-thang') ? 'active' : '' ?>">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Calendar</span>
            </a>
            <a href="index.php?page=profile" class="bottom-nav-item <?= ($page === 'profile') ? 'active' : '' ?>">
                <i class="fa-solid fa-user"></i>
                <span>Profile</span>
            </a>
        </nav>

        <script>
            function toggleMobileNotif() {
                const panel = document.getElementById('mobileNotifPanel');
                if (panel.style.display === 'none' || !panel.style.display) {
                    panel.style.display = 'block';
                    panel.removeAttribute('hidden');
                    panel.setAttribute('aria-hidden', 'false');
                } else {
                    panel.style.display = 'none';
                    panel.setAttribute('hidden', 'true');
                    panel.setAttribute('aria-hidden', 'true');
                }
            }
        </script>
    <?php else: ?>
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
    <?php endif; ?>
