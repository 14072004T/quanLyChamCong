<?php 
// Security check - must be logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}

// Safety check - initialize variables if not provided by controller
$message = $message ?? '';
$requests = $requests ?? [];
$activeRequestId = (int)($_GET['request_id'] ?? 0);
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>
<style>
.request-row-highlight {
    animation: requestRowPulse 2.4s ease;
    background: #fff7d6 !important;
}
@keyframes requestRowPulse {
    0% { background: #ffe58f; }
    100% { background: #fff7d6; }
}
</style>
<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container">

        <!-- Tiêu đề chính -->
        <div class="panel">
            <h2>Yêu cầu Chỉnh sửa Chấm công</h2>
            <p>Gửi yêu cầu chỉnh sửa lại dữ liệu chấm công cho phòng HR xem xét và phê duyệt.</p>
        </div>

        <!-- FORM GỬI YÊU CẦU -->
        <div class="panel">
            <h3>Gửi Yêu cầu Chỉnh sửa</h3>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <strong><?= htmlspecialchars($message) ?></strong>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=yeu-cau-chinh-sua-cham-cong">
                <div class="form-group">
                    <label for="attendance-date">Ngày Chấm công</label>
                    <input type="date" id="attendance-date" name="attendance_date" required>
                    <small style="color: #999;">Chọn ngày bạn muốn chỉnh sửa dữ liệu chấm công</small>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="old-time">Thời gian Cũ (nếu có)</label>
                        <input type="datetime-local" id="old-time" name="old_time">
                        <small style="color: #999;">Thời gian yêu cầu chỉnh sửa hiện tại</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="new-time">Thời gian Mới <span style="color: #ef4444;">*</span></label>
                        <input type="datetime-local" id="new-time" name="new_time" required>
                        <small style="color: #999;">Thời gian chỉnh sửa đề nghị</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reason">Lý do <span style="color: #ef4444;">*</span></label>
                    <textarea id="reason" name="reason" rows="4" placeholder="Ví dụ: Quên chấm công chiều do công việc lâu..." required></textarea>
                    <small style="color: #999;">Vui lòng giải thích rõ lý do yêu cầu chỉnh sửa</small>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">
                        <i class="fas fa-paper-plane"></i> Gửi Yêu cầu
                    </button>
                    <a href="index.php?page=cham-cong-dashboard" class="btn btn-secondary" style="flex: 1; text-align: center;">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                </div>
            </form>
        </div>

        <!-- BẢNG TRẠNG THÁI YÊU CẦU -->
        <div class="panel">
            <h3>Trạng thái Xử lý Yêu cầu</h3>
            
            <?php if (!empty($requests) && is_array($requests)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ngày Công</th>
                            <th>Thời gian Chỉnh sửa</th>
                            <th>Lý do</th>
                            <th>Trạng thái</th>
                            <th>Ghi chú (HR)</th>
                            <th>Ngày Gửi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $row): ?>
                            <tr id="request-<?= (int)($row['id'] ?? 0) ?>" class="<?= $activeRequestId === (int)($row['id'] ?? 0) ? 'request-row-highlight' : '' ?>">
                                <td>
                                    <strong><?= htmlspecialchars($row['attendance_date'] ?? '') ?></strong>
                                </td>
                                <td style="font-family: monospace; font-size: 0.9em;">
                                    <?= htmlspecialchars($row['new_time'] ?? '') ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($row['reason'] ?? '') ?>
                                </td>
                                <td>
                                    <?php 
                                        $status = htmlspecialchars($row['status'] ?? 'Chờ duyệt');
                                        $status_class = '';
                                        
                                        if (strpos($status, 'Đã') !== false || strpos($status, 'duyệt') !== false) {
                                            $status_class = 'status-approved';
                                        } elseif (strpos($status, 'Từ chối') !== false) {
                                            $status_class = 'status-rejected';
                                        } else {
                                            $status_class = 'status-pending';
                                        }
                                        
                                        echo '<span class="status-badge ' . $status_class . '">' . $status . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                        $note = htmlspecialchars($row['hr_note'] ?? '');
                                        echo (!empty($note)) ? $note : '<span style="color: #999;">-</span>';
                                    ?>
                                </td>
                                <td style="font-size: 0.9em; color: #666;">
                                    <?= htmlspecialchars(substr($row['created_at'] ?? '', 0, 10)) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>
                        <i class="fas fa-inbox" style="font-size: 2em; color: #ccc;"></i>
                    </p>
                    <p>Chưa có yêu cầu chỉnh sửa nào. <a href="#" style="color: #0099ff;">Gửi yêu cầu ngay</a></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- HƯỚNG DẪN -->
        <div class="panel" style="background-color: #fef3c7; border-left: 4px solid #f59e0b;">
            <h3 style="color: #92400e;">💡 Hướng dẫn Gửi Yêu cầu</h3>
            <ul style="color: #92400e; margin-left: 20px;">
                <li>Kiểm tra ngày và thời gian chấm công sai</li>
                <li>Nhập đầy đủ thông tin ngày công và giờ cần chỉnh sửa</li>
                <li>Giải thích rõ ràng lý do yêu cầu chỉnh sửa</li>
                <li>Yêu cầu sẽ được phòng HR xem xét trong vòng 1-2 ngày làm việc</li>
                <li>Bạn sẽ nhận được thông báo khi yêu cầu được xử lý</li>
            </ul>
        </div>

        <!-- NÚT QUAY LẠI -->
        <div class="panel text-center">
            <a href="index.php?page=cham-cong-dashboard" class="btn btn-secondary" style="padding: 12px 24px;">
                <i class="fas fa-arrow-left"></i> Quay lại Dashboard
            </a>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var activeRequestId = <?= (int)$activeRequestId ?>;
    if (!activeRequestId) return;

    var targetRow = document.getElementById('request-' + activeRequestId);
    if (!targetRow) return;

    targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
});
</script>
<?php include 'app/views/layouts/footer.php'; ?>
