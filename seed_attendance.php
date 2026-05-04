<?php
require_once 'app/models/ketNoi.php';

echo "Bắt đầu tạo dữ liệu test chấm công cho các tháng 1, 2, 3, 4 năm 2026...\n";

$db = new KetNoi();
$conn = $db->connect();
if (!$conn) {
    die("Connection failed\n");
}

// Lấy danh sách nhân viên đang hoạt động
$result = $conn->query("SELECT maND FROM nguoidung WHERE trangThai = 1");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = (int)$row['maND'];
}

echo "Tìm thấy " . count($users) . " nhân viên đang hoạt động trong database dl_final.\n";

$months = [1, 2, 3, 4];
$year = 2026;
$totalInserted = 0;

foreach ($months as $month) {
    echo "Đang xử lý tháng $month/2026...\n";
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $day);
        
        // Bỏ qua Chủ nhật (Ngày nghỉ cuối tuần)
        $dayOfWeek = date('N', strtotime($dateStr));
        if ($dayOfWeek == 7) continue;
        
        // Bỏ qua một số ngày Lễ
        if ($month == 1 && $day == 1) continue;
        if ($month == 4 && $day == 30) continue;

        foreach ($users as $userId) {
            // Xác suất 5% nhân viên nghỉ phép/ốm/vắng mặt
            if (rand(1, 100) <= 5) continue; 
            
            // Giờ Check-in (Từ 07:45 đến 08:15)
            $checkInHour = 7;
            $checkInMinute = rand(45, 59);
            // Một số đi vào đúng 8h hoặc đi trễ
            if (rand(1, 10) > 6) {
                $checkInHour = 8;
                $checkInMinute = rand(0, 15);
            }
            $checkInTime = sprintf("%s %02d:%02d:%02d", $dateStr, $checkInHour, $checkInMinute, rand(0, 59));
            
            // Giờ Check-out (Từ 17:00 đến 17:30)
            $checkOutHour = 17;
            $checkOutMinute = rand(0, 30);
            
            // 30% làm thêm giờ (OT) ca tối (18:00 - 22:00)
            if (rand(1, 10) > 7) {
                $checkOutHour = rand(21, 22); // Check out lúc 21h-22h
                if ($checkOutHour == 22) {
                    $checkOutMinute = rand(0, 15);
                }
            }
            $checkOutTime = sprintf("%s %02d:%02d:%02d", $dateStr, $checkOutHour, $checkOutMinute, rand(0, 59));
            
            // 2% xác suất quên check-out
            $missingCheckout = (rand(1, 100) <= 2);
            
            // Thêm Check-in
            $stmt = $conn->prepare("SELECT id FROM attendance_logs WHERE maND = ? AND DATE(created_at) = ? AND action = 'IN'");
            $stmt->bind_param("is", $userId, $dateStr);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                $stmtIn = $conn->prepare("INSERT INTO attendance_logs (maND, action, created_at) VALUES (?, 'IN', ?)");
                $stmtIn->bind_param("is", $userId, $checkInTime);
                $stmtIn->execute();
                $totalInserted++;
            }
            
            // Thêm Check-out nếu không bị "quên"
            if (!$missingCheckout) {
                $stmtOutCheck = $conn->prepare("SELECT id FROM attendance_logs WHERE maND = ? AND DATE(created_at) = ? AND action = 'OUT'");
                $stmtOutCheck->bind_param("is", $userId, $dateStr);
                $stmtOutCheck->execute();
                if (!$stmtOutCheck->get_result()->fetch_assoc()) {
                    $stmtOut = $conn->prepare("INSERT INTO attendance_logs (maND, action, created_at) VALUES (?, 'OUT', ?)");
                    $stmtOut->bind_param("is", $userId, $checkOutTime);
                    $stmtOut->execute();
                    $totalInserted++;
                }
            }
        }
    }
}

echo "Hoàn tất! Đã thêm $totalInserted bản ghi chấm công (IN/OUT) vào database dl_final.\n";
?>
