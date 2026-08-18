<?php
/**
 * Test role mapping with actual database mojibake values
 */

function removeDiacritics($text) {
    // First, try to fix mojibake encoding (UTF-8 interpreted as Latin1)
    // Check if text contains common mojibake patterns
    if (preg_match('/[\xC0-\xFF]/', $text)) {
        $fixed = @iconv('ISO-8859-1', 'UTF-8', $text);
        if ($fixed !== false && $fixed !== '') {
            $text = $fixed;
        }
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
    return strtr($text, $map);
}

$roleMapping = [
    'nhan vien' => 'nhanvien',
    'bo phan nhan su' => 'hr',
    'quan ly / ban lanh dao' => 'manager',
    'bo phan ky thuat' => 'tech'
];

// Test cases with ACTUAL database mojibake values
// Using iconv to create the mojibake (convert proper UTF-8 to Latin1 encoded as UTF-8)
$testCases = [
    iconv('UTF-8', 'ISO-8859-1', 'Nhân viên') => 'nhanvien',
    iconv('UTF-8', 'ISO-8859-1', 'Bộ phận Nhân sự') => 'hr',
    iconv('UTF-8', 'ISO-8859-1', 'Quản lý / Ban lãnh đạo') => 'manager',
    iconv('UTF-8', 'ISO-8859-1', 'Bộ phật Kỹ thuật') => 'tech',
];

echo "=== ROLE MAPPING TEST WITH MOJIBAKE FIX ===\n\n";

$passCount = 0;
$failCount = 0;

foreach ($testCases as $chucVu => $expectedRole) {
    $normalizedChucVu = removeDiacritics(mb_strtolower($chucVu, 'UTF-8'));
    $role = isset($roleMapping[$normalizedChucVu]) ? $roleMapping[$normalizedChucVu] : 'nhanvien';
    
    $status = ($role === $expectedRole) ? 'PASS' : 'FAIL';
    if ($role === $expectedRole) {
        $passCount++;
    } else {
        $failCount++;
    }
    
    echo "[$status] Input (mojibake): " . bin2hex(substr($chucVu, 0, 10)) . "...\n";
    echo "        Normalized: '$normalizedChucVu'\n";
    echo "        Expected: '$expectedRole' | Got: '$role'\n\n";
}

echo "=== RESULTS ===\n";
echo "PASSED: $passCount / " . ($passCount + $failCount) . "\n";
echo "FAILED: $failCount / " . ($passCount + $failCount) . "\n";
?>
