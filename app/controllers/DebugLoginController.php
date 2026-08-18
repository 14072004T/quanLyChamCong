<?php
/**
 * Debug login endpoint to verify role assignment
 * Access via: index.php?page=debug-login&test_user=hr01
 */

if (!isset($_GET['test_user'])) {
    die('Usage: ?page=debug-login&test_user=hr01');
}

require_once 'app/models/ketNoi.php';

$testUser = $_GET['test_user'];
$db = new KetNoi();
$conn = $db->connect();

$sql = "SELECT tk.*, nd.hoTen, nd.chucVu, nd.phongBan, nd.maND, nd.trangThai as trangThaiND, tk.trangThai as trangThaiTK
FROM taikhoan tk 
LEFT JOIN nguoidung nd ON tk.maTK = nd.maTK 
WHERE tk.tenDangNhap = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $testUser);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found: $testUser");
}

$user = $result->fetch_assoc();

// Diacritics remover (same as LoginController)
function removeDiacritics($text) {
    $map = [
        'á' => 'a', 'à' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
        'ă' => 'a', 'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
        'â' => 'a', 'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
        'é' => 'e', 'è' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
        'ê' => 'e', 'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
        'í' => 'i', 'ì' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
        'ô' => 'o', 'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
        'ơ' => 'o', 'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
        'ư' => 'u', 'ứ' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
        'ý' => 'y', 'ỳ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
        'đ' => 'd'
    ];
    return strtr($text, $map);
}

$roleMapping = [
    'nhan vien' => 'nhanvien',
    'bo phan nhan su' => 'hr',
    'quan ly / ban lanh dao' => 'manager',
    'bo phan ky thuat' => 'tech'
];

$chucVu = trim($user['chucVu'] ?? 'Nhan vien');
$chucVuLower = mb_strtolower($chucVu, 'UTF-8');
$normalizedChucVu = removeDiacritics($chucVuLower);
$role = isset($roleMapping[$normalizedChucVu]) ? $roleMapping[$normalizedChucVu] : 'nhanvien';

echo "<pre style='background:#f0f0f0; padding:20px; font-family:monospace;'>";
echo "=== DEBUG LOGIN ROLE ASSIGNMENT ===\n\n";
echo "Test User: $testUser\n";
echo "Database chucVu: " . json_encode($chucVu) . "\n";
echo "After mb_strtolower: " . json_encode($chucVuLower) . "\n";
echo "After removeDiacritics: " . json_encode($normalizedChucVu) . "\n";
echo "Matched role: " . json_encode($role) . "\n";
echo "\nRole Mapping:\n";
foreach ($roleMapping as $key => $val) {
    $match = ($key === $normalizedChucVu) ? " ← MATCH!" : "";
    echo "  '$key' => '$val'$match\n";
}
echo "\n";
echo "Full user record:\n";
print_r($user);
echo "</pre>";
?>
