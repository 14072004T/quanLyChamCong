<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../app/models/ChamCongModel.php';

$m = new ChamCongModel();
$emps = $m->getEmployees('', true, 0);
echo "Total active employees: " . count($emps) . PHP_EOL;

$validDepts = ['Sản xuất', 'Kho', 'QC', 'Bảo trì'];
$filteredCount = 0;

foreach ($emps as $e) {
    $chucVu = mb_strtolower(trim($e['chucVu'] ?? ''), 'UTF-8');
    $phongBan = (string)($e['phongBan'] ?? '');
    $isNhanVien = ($chucVu === 'nhân viên');
    $isValidDept = in_array($phongBan, $validDepts, true);
    
    echo "maND={$e['maND']} | hoTen={$e['hoTen']} | chucVu=[{$e['chucVu']}] | phongBan=[{$e['phongBan']}] | isNV=" . ($isNhanVien ? 'YES' : 'NO') . " | validDept=" . ($isValidDept ? 'YES' : 'NO') . PHP_EOL;
    
    if ($isNhanVien && $isValidDept) {
        $filteredCount++;
    }
}

echo PHP_EOL . "Employees passing filter (chucVu=nhân viên AND phongBan in validDepts): {$filteredCount}" . PHP_EOL;

// Also check attendance data for recent months  
echo PHP_EOL . "=== Checking attendance data ===" . PHP_EOL;
$conn = new mysqli('localhost', 'root', '', 'chamcong_db');
if ($conn->connect_error) {
    // try another DB name
    $conn = new mysqli('localhost', 'root', '', 'quanlychamcong');
}
if (!$conn->connect_error) {
    $result = $conn->query("SELECT COUNT(*) as cnt, DATE(ngayTao) as dt FROM lichsuchamcong GROUP BY DATE(ngayTao) ORDER BY dt DESC LIMIT 10");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "Date: {$row['dt']} | Records: {$row['cnt']}" . PHP_EOL;
        }
    }
    $conn->close();
} else {
    echo "Cannot connect to database" . PHP_EOL;
}
