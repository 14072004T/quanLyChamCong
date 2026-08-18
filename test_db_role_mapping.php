<?php
/**
 * Test role mapping with ACTUAL values from database
 */

require_once 'app/models/ketNoi.php';

function removeDiacritics($text) {
    // Try multiple encoding fixes for mojibake
    
    // First, try UTF-8 to ISO-8859-1 and back
    $utf8Decoded = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $text);
    if ($utf8Decoded !== false && $utf8Decoded !== '') {
        $text = $utf8Decoded;
    }
    
    // Alternative: try mb_convert_encoding
    $converted = @mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');
    if ($converted !== false && $converted !== '') {
        $text = $converted;
    }
    
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
    
    $result = strtr($text, $map);
    // Remove any remaining non-ASCII characters that couldn't be mapped
    $result = preg_replace('/[^\x00-\x7F]/', '', $result);
    return $result;
}

$roleMapping = [
    'nhan vien' => 'nhanvien',
    'bo phan nhan su' => 'hr',
    'quan ly / ban lanh dao' => 'manager',
    'bo phan ky thuat' => 'tech'
];

// Also map the actual mojibake values found in the database
$mojibakeMappings = [
    'NhÃ¢n viÃªn' => 'nhanvien',
    'Bá»™ pháºn NhÃ¢n sá»±' => 'hr',
    'Quáº£n lÃ½ / Ban lÃ£nh Ä'áº¡o' => 'manager',
    'Bá»™ pháºn Ká»¹ thuáºt' => 'tech'
];

$db = new KetNoi();
$conn = $db->connect();

// Get test users by role
$testUsers = [
    'nhanvien01' => 'nhanvien',
    'hr01' => 'hr',
    'manager01' => 'manager',
    'tech01' => 'tech'
];

echo "=== ROLE MAPPING TEST WITH ACTUAL DATABASE VALUES ===\n\n";

$passCount = 0;
$failCount = 0;

foreach ($testUsers as $username => $expectedRole) {
    $sql = "SELECT tk.*, nd.chucVu FROM taikhoan tk LEFT JOIN nguoidung nd ON tk.maTK = nd.maTK WHERE tk.tenDangNhap = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "[SKIP] User not found: $username\n\n";
        continue;
    }
    
    $user = $result->fetch_assoc();
    $chucVu = trim($user['chucVu'] ?? 'Nhan vien');
    
    // First, check if exact mojibake match exists
    if (isset($mojibakeMappings[$chucVu])) {
        $role = $mojibakeMappings[$chucVu];
    } else {
        // Try direct mapping with diacritic removal
        $normalizedChucVu = removeDiacritics(mb_strtolower($chucVu, 'UTF-8'));
        $role = isset($roleMapping[$normalizedChucVu]) ? $roleMapping[$normalizedChucVu] : 'nhanvien';
    }
    
    $status = ($role === $expectedRole) ? 'PASS' : 'FAIL';
    if ($role === $expectedRole) {
        $passCount++;
    } else {
        $failCount++;
    }
    
    echo "[$status] User: $username\n";
    echo "        Raw chucVu: '$chucVu'\n";
    echo "        Expected: '$expectedRole' | Got: '$role'\n\n";
}

echo "=== RESULTS ===\n";
echo "PASSED: $passCount / " . ($passCount + $failCount) . "\n";
echo "FAILED: $failCount / " . ($passCount + $failCount) . "\n";
?>
