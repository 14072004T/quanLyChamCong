<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'dl_final';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo 'CONNECT ERROR: ' . $conn->connect_error . "\n";
    exit(1);
}
$conn->set_charset('utf8mb4');
$result = $conn->query("SELECT maND, hoTen, chucVu, phongBan, ngayTao FROM nguoidung LIMIT 20");
if (!$result) {
    echo 'QUERY ERROR: ' . $conn->error . "\n";
    exit(1);
}
while ($row = $result->fetch_assoc()) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}
$conn->close();
