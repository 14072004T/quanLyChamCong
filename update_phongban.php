<?php
/**
 * Script cập nhật phòng ban ngẫu nhiên cho tất cả nhân viên trong bảng nguoidung.
 * Truy cập URL này trên trình duyệt để chạy. Xóa file sau khi chạy xong.
 */

require_once __DIR__ . '/app/models/ketNoi.php';

$db = new KetNoi();
$conn = $db->connect();

// Danh sách phòng ban từ dropdown
$departments = [
    'Ban Điều hành',
    'Phòng Nhân sự',
    'Phòng Kế toán',
    'Phòng Kinh doanh & Marketing',
    'Phòng Công nghệ thông tin (IT)',
    'Phòng Sản xuất',
    'Phòng Kiểm soát chất lượng (QC)',
    'Phòng Hành chính',
];

// Lấy tất cả nhân viên
$result = $conn->query("SELECT maND, hoTen, phongBan FROM nguoidung ORDER BY maND");

if (!$result) {
    die("Lỗi truy vấn: " . $conn->error);
}

$stmt = $conn->prepare("UPDATE nguoidung SET phongBan = ? WHERE maND = ?");
if (!$stmt) {
    die("Lỗi prepare: " . $conn->error);
}

$updated = 0;
$errors = 0;
$rows = [];

while ($row = $result->fetch_assoc()) {
    $maND = (int) $row['maND'];
    $hoTen = $row['hoTen'];
    $oldDept = $row['phongBan'] ?? '';

    // Chọn phòng ban ngẫu nhiên
    $newDept = $departments[array_rand($departments)];

    $stmt->bind_param("si", $newDept, $maND);

    if ($stmt->execute()) {
        $updated++;
        $status = "OK";
        $statusClass = "ok";
    } else {
        $errors++;
        $status = "Lỗi: " . $stmt->error;
        $statusClass = "err";
    }

    $rows[] = [
        'maND' => $maND,
        'hoTen' => $hoTen,
        'oldDept' => $oldDept,
        'newDept' => $newDept,
        'status' => $status,
        'statusClass' => $statusClass,
    ];
}

$stmt->close();
$db->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cập nhật phòng ban</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        h2 { color: #2c3e50; }
        table { border-collapse: collapse; width: 100%; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        th { background: #34495e; color: white; padding: 10px; text-align: left; }
        td { padding: 8px 10px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f0f8ff; }
        .ok { color: green; font-weight: bold; }
        .err { color: red; font-weight: bold; }
        .summary { margin-top: 20px; padding: 15px; background: #d4edda; border-radius: 8px; font-size: 16px; }
        .warning { margin-top: 10px; padding: 15px; background: #f8d7da; border-radius: 8px; color: #721c24; font-size: 14px; }
    </style>
</head>
<body>
<h2>🔄 Cập nhật phòng ban ngẫu nhiên cho tất cả nhân viên</h2>
<table>
    <tr>
        <th>Mã NV</th>
        <th>Họ tên</th>
        <th>Phòng ban cũ</th>
        <th>➡️ Phòng ban mới</th>
        <th>Trạng thái</th>
    </tr>
<?php foreach ($rows as $r): ?>
    <tr>
        <td><?= $r['maND'] ?></td>
        <td><?= htmlspecialchars($r['hoTen']) ?></td>
        <td><?= htmlspecialchars($r['oldDept']) ?></td>
        <td><strong><?= htmlspecialchars($r['newDept']) ?></strong></td>
        <td class="<?= $r['statusClass'] ?>"><?= $r['status'] ?></td>
    </tr>
<?php endforeach; ?>
</table>

<div class="summary">
    ✅ <strong>Tổng kết:</strong> Cập nhật thành công <strong><?= $updated ?></strong> nhân viên, lỗi <strong><?= $errors ?></strong>.
</div>

<div class="warning">
    ⚠️ <strong>Hãy xóa file <code>update_phongban.php</code> khỏi repo sau khi xác nhận dữ liệu đã đổi!</strong>
</div>

</body>
</html>
