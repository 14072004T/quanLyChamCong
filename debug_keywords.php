<?php
require_once 'app/models/ketNoi.php';

$db = new KetNoi();
$conn = $db->connect();

$testUsers = [
    'nhanvien01' => 'nhanvien',
    'hr01' => 'hr',
    'manager01' => 'manager',
    'tech01' => 'tech'
];

foreach ($testUsers as $username => $expectedRole) {
    $sql = "SELECT tk.*, nd.chucVu FROM taikhoan tk LEFT JOIN nguoidung nd ON tk.maTK = nd.maTK WHERE tk.tenDangNhap = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) continue;
    
    $user = $result->fetch_assoc();
    $chucVu = trim($user['chucVu'] ?? 'Nhan vien');
    $chucVuLower = mb_strtolower($chucVu, 'UTF-8');
    
    echo "User: $username\n";
    echo "Raw: $chucVu\n";
    echo "Lower: $chucVuLower\n";
    
    // Check keywords
    $has_nhan = stripos($chucVuLower, 'nhan') !== false;
    $has_vien = stripos($chucVuLower, 'vien') !== false;
    $has_phan = stripos($chucVuLower, 'phan') !== false;
    $has_ky = stripos($chucVuLower, 'ky') !== false;
    $has_quan = stripos($chucVuLower, 'quan') !== false;
    $has_ly = stripos($chucVuLower, 'ly') !== false;
    
    echo "  nhan=$has_nhan, vien=$has_vien, phan=$has_phan, ky=$has_ky, quan=$has_quan, ly=$has_ly\n";
    
    // Test keyword matching logic
    if ($has_nhan && $has_vien) {
        echo "  => Matched: nhanvien\n";
    } elseif ($has_phan && $has_nhan) {
        echo "  => Matched: hr\n";
    } elseif ($has_quan && $has_ly) {
        echo "  => Matched: manager\n";
    } elseif ($has_phan && $has_ky) {
        echo "  => Matched: tech\n";
    } else {
        echo "  => Default: nhanvien\n";
    }
    echo "\n";
}
?>
