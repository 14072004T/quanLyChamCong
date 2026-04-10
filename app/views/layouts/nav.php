<nav class="navigation">
    <?php
    $role = $_SESSION['role'] ?? 'nhanvien';
    $attendanceTabLabel = ($role === 'nhanvien') ? 'Chấm công' : 'Quản lý chấm công';
    $attendanceTabIcon = ($role === 'nhanvien') ? 'fa-fingerprint' : 'fa-users-gear';
    ?>
    <div class="nav-links">
        <a href="index.php?page=cham-cong-dashboard" class="nav-button"><i class="fa-solid <?= htmlspecialchars($attendanceTabIcon) ?>"></i> <?= htmlspecialchars($attendanceTabLabel) ?></a>
    </div>

    <div class="auth-section">
        <?php
        if (isset($_SESSION['user'])) {
            echo '<a href="index.php?page=logout" class="nav-button"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>';
        } else {
            echo '<a href="index.php?page=login" class="nav-button"><i class="fa-solid fa-right-to-bracket"></i> Đăng nhập</a>';
        }
        ?>
    </div>

</nav>
