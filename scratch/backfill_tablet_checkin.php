<?php
/**
 * Vá dữ liệu chấm công cho các ngày đã có lượt quét trong tablet_face_scans
 * nhưng thiếu bản ghi IN/OUT tương ứng trong lichsuchamcong (do bug chặn ghi
 * "giờ vào" khi chưa có ca được gán, trước khi getShiftForUser() có fallback).
 *
 * Chỉ CHÈN THÊM bản ghi còn thiếu (IN dùng thời điểm quét đầu tiên trong ngày,
 * OUT dùng thời điểm quét cuối cùng nếu có từ 2 lượt quét trở lên và OUT đó
 * chưa tồn tại). Không xóa/sửa dữ liệu đã có sẵn. Có thể chạy lại nhiều lần
 * an toàn (bỏ qua ngày đã đủ dữ liệu).
 *
 * CÁCH DÙNG: deploy file này lên server, mở 1 lần bằng trình duyệt để chạy,
 * sau đó XÓA file này khỏi server vì nó thao tác trực tiếp lên CSDL.
 */

require_once __DIR__ . '/../app/models/ketNoi.php';

header('Content-Type: text/plain; charset=utf-8');

$ketNoi = new KetNoi();
$conn = $ketNoi->connect();
if (!$conn) {
    die("Không thể kết nối CSDL.\n");
}

echo "=== BACKFILL GIỜ VÀO/RA TỪ TABLET_FACE_SCANS ===\n\n";

$sql = "SELECT maND, DATE(thoiGianQuet) AS ngay,
               MIN(thoiGianQuet) AS lanDau, MAX(thoiGianQuet) AS lanCuoi, COUNT(*) AS soLan
        FROM tablet_face_scans
        GROUP BY maND, DATE(thoiGianQuet)
        ORDER BY maND, ngay";
$result = $conn->query($sql);
if (!$result) {
    die("Lỗi truy vấn tablet_face_scans: " . $conn->error . "\n");
}

$fixedIn = 0;
$fixedOut = 0;
$checked = 0;

while ($row = $result->fetch_assoc()) {
    $checked++;
    $maND = (int)$row['maND'];
    $ngay = $row['ngay'];
    $lanDau = $row['lanDau'];
    $lanCuoi = $row['lanCuoi'];
    $soLan = (int)$row['soLan'];

    // Kiểm tra đã có IN trong ngày đó chưa
    $checkIn = $conn->prepare("SELECT id FROM lichsuchamcong WHERE maND = ? AND hanhDong = 'IN' AND DATE(ngayTao) = ? LIMIT 1");
    $checkIn->bind_param("is", $maND, $ngay);
    $checkIn->execute();
    $hasIn = $checkIn->get_result()->num_rows > 0;
    $checkIn->close();

    if (!$hasIn) {
        $insertIn = $conn->prepare(
            "INSERT INTO lichsuchamcong (maND, hanhDong, phuongThuc, tenWifi, ghiChu, ngayTao)
             VALUES (?, 'IN', 'LAN', 'TABLET', 'Vá lại giờ vào từ tablet_face_scans (backfill)', ?)"
        );
        $insertIn->bind_param("is", $maND, $lanDau);
        if ($insertIn->execute()) {
            $fixedIn++;
            echo "[maND=$maND][$ngay] Đã thêm IN lúc $lanDau\n";
        } else {
            echo "[maND=$maND][$ngay] LỖI thêm IN: " . $insertIn->error . "\n";
        }
        $insertIn->close();
    }

    // Nếu có từ 2 lượt quét trở lên, đảm bảo có OUT trong ngày đó
    if ($soLan > 1) {
        $checkOut = $conn->prepare("SELECT id FROM lichsuchamcong WHERE maND = ? AND hanhDong = 'OUT' AND DATE(ngayTao) = ? LIMIT 1");
        $checkOut->bind_param("is", $maND, $ngay);
        $checkOut->execute();
        $hasOut = $checkOut->get_result()->num_rows > 0;
        $checkOut->close();

        if (!$hasOut) {
            $insertOut = $conn->prepare(
                "INSERT INTO lichsuchamcong (maND, hanhDong, phuongThuc, tenWifi, ghiChu, ngayTao)
                 VALUES (?, 'OUT', 'LAN', 'TABLET', 'Vá lại giờ ra từ tablet_face_scans (backfill)', ?)"
            );
            $insertOut->bind_param("is", $maND, $lanCuoi);
            if ($insertOut->execute()) {
                $fixedOut++;
                echo "[maND=$maND][$ngay] Đã thêm OUT lúc $lanCuoi\n";
            } else {
                echo "[maND=$maND][$ngay] LỖI thêm OUT: " . $insertOut->error . "\n";
            }
            $insertOut->close();
        }
    }
}

echo "\n=== KẾT QUẢ ===\n";
echo "Số ngày đã kiểm tra: $checked\n";
echo "Số bản ghi IN đã vá: $fixedIn\n";
echo "Số bản ghi OUT đã vá: $fixedOut\n";
echo "\nXong. Vui lòng XÓA file này khỏi server sau khi kiểm tra kết quả.\n";
