<?php
/**
 * Tạo dữ liệu chấm công FULL THÁNG 10/2026 cho nhanvien02 (maND = 2)
 *
 * Chèn đồng bộ vào 3 bảng:
 *   1. tablet_face_scans: Nguồn dữ liệu tính công cho HR (tinhcong.php)
 *   2. lichsuchamcong: Lịch sử chấm công cho Nhân viên (lichsu.php)
 *   3. tonghopngaycong: Tổng hợp ngày công
 */

require_once __DIR__ . '/../app/models/ketNoi.php';

header('Content-Type: text/plain; charset=utf-8');

$ketNoi = new KetNoi();
$conn   = $ketNoi->connect();
if (!$conn) {
    die("Không thể kết nối CSDL.\n");
}

$maND = 2; // nhanvien02 - Phan Thị Kim Truyền

// Đảm bảo có bảng tablet_face_scans
$conn->query("
CREATE TABLE IF NOT EXISTS `tablet_face_scans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `maND` int(11) NOT NULL,
  `thoiGianQuet` datetime NOT NULL DEFAULT current_timestamp(),
  `anhChup` varchar(255) DEFAULT NULL,
  `score` float DEFAULT 0,
  `deviceInfo` varchar(120) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mand_thoigian` (`maND`, `thoiGianQuet`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Xoá dữ liệu tháng 10 cũ
$conn->query("DELETE FROM tablet_face_scans WHERE maND = $maND AND thoiGianQuet BETWEEN '2026-10-01 00:00:00' AND '2026-10-31 23:59:59'");
$conn->query("DELETE FROM lichsuchamcong   WHERE maND = $maND AND ngayTao BETWEEN '2026-10-01 00:00:00' AND '2026-10-31 23:59:59'");
$conn->query("DELETE FROM tonghopngaycong  WHERE maND = $maND AND ngayLamViec BETWEEN '2026-10-01' AND '2026-10-31'");

$workDays = [
    '2026-10-01' => ['07:52:14', '17:03:28', null],
    '2026-10-02' => ['07:58:41', '17:08:15', null],
    '2026-10-05' => ['07:55:33', '17:02:47', null],
    '2026-10-06' => ['08:12:05', '17:05:30', 'Đi muộn 12p'],
    '2026-10-07' => ['07:48:22', '17:01:10', null],
    '2026-10-08' => ['07:56:50', '17:35:42', null],
    '2026-10-09' => ['07:59:18', '17:04:55', null],
    '2026-10-12' => ['07:53:40', '17:02:33', null],
    '2026-10-13' => ['08:25:11', '17:06:20', 'Đi muộn 25p'],
    '2026-10-14' => ['07:50:05', '17:00:45', null],
    '2026-10-15' => ['07:57:32', '18:02:18', null],
    '2026-10-16' => ['07:54:28', '17:03:50', null],
    '2026-10-19' => ['07:51:15', '17:01:22', null],
    '2026-10-20' => ['07:58:07', '17:07:40', null],
    '2026-10-21' => ['08:05:44', '17:04:12', 'Đi muộn 5p'],
    '2026-10-22' => ['07:49:30', '17:45:55', null],
    '2026-10-23' => ['07:55:20', '17:02:08', null],
    '2026-10-26' => ['07:53:48', '17:03:15', null],
    '2026-10-27' => ['07:57:12', '17:05:42', null],
    '2026-10-28' => ['08:33:27', '17:08:50', 'Đi muộn 33p'],
    '2026-10-29' => ['07:50:55', '17:01:30', null],
    '2026-10-30' => ['07:56:38', '17:04:22', null],
];

$stmtTablet = $conn->prepare("INSERT INTO tablet_face_scans (maND, thoiGianQuet, deviceInfo) VALUES (?, ?, 'Kiosk Tablet')");
$stmtIN     = $conn->prepare("INSERT INTO lichsuchamcong (maND, hanhDong, phuongThuc, ghiChu, ngayTao) VALUES (?, 'IN', 'faceid', ?, ?)");
$stmtOUT    = $conn->prepare("INSERT INTO lichsuchamcong (maND, hanhDong, phuongThuc, ghiChu, ngayTao) VALUES (?, 'OUT', 'faceid', NULL, ?)");
$stmtTH     = $conn->prepare("INSERT INTO tonghopngaycong (maND, ngayLamViec, gioVaoDau, gioRaCuoi, phutLamViec, phutTangCa, phutDiTre, trangThai, ngayTao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

foreach ($workDays as $date => $info) {
    list($inTime, $outTime, $note) = $info;
    $dtIn  = "$date $inTime";
    $dtOut = "$date $outTime";

    // 1. Tablet scans (HR timesheet)
    $stmtTablet->bind_param("is", $maND, $dtIn);
    $stmtTablet->execute();
    $stmtTablet->bind_param("is", $maND, $dtOut);
    $stmtTablet->execute();

    // 2. Lich su cham cong (Employee history)
    $stmtIN->bind_param("iss", $maND, $note, $dtIn);
    $stmtIN->execute();
    $stmtOUT->bind_param("is", $maND, $dtOut);
    $stmtOUT->execute();

    // 3. Tong hop ngay cong
    $lateMin = (strtotime($dtIn) > strtotime("$date 08:00:00")) ? (int)((strtotime($dtIn) - strtotime("$date 08:00:00")) / 60) : 0;
    $status  = $lateMin > 0 ? 'late' : 'normal';
    $stmtTH->bind_param("isssiiis", $maND, $date, $dtIn, $dtOut, $workMin, $otMin, $lateMin, $status);
    $workMin = 480; $otMin = 0;
    $stmtTH->execute();
}

echo "Thành công! Đã thêm full 22 ngày công tháng 10 cho nhanvien02.\n";
