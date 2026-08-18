<?php
/**
 * Test script to verify role mapping fix
 */

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

// Test data from database
$testCases = [
    ['chucVu' => 'Nhân viên', 'expected_role' => 'nhanvien'],
    ['chucVu' => 'Bộ phận Nhân sự', 'expected_role' => 'hr'],
    ['chucVu' => 'Bộ phận Kỹ thuật', 'expected_role' => 'tech'],
    ['chucVu' => 'Quản lý / Ban lãnh đạo', 'expected_role' => 'manager']
];

$roleMapping = [
    'nhan vien' => 'nhanvien',
    'bo phan nhan su' => 'hr',
    'quan ly / ban lanh dao' => 'manager',
    'bo phan ky thuat' => 'tech'
];

echo "=== Role Mapping Test ===\n\n";

foreach ($testCases as $test) {
    $chucVu = trim($test['chucVu']);
    $normalized = removeDiacritics(mb_strtolower($chucVu, 'UTF-8'));
    $role = isset($roleMapping[$normalized]) ? $roleMapping[$normalized] : 'nhanvien';
    $status = ($role === $test['expected_role']) ? '✓ PASS' : '✗ FAIL';
    
    printf("%s | chucVu: '%s' → normalized: '%s' → role: '%s' (expected: '%s')\n",
        $status, $chucVu, $normalized, $role, $test['expected_role']);
}

echo "\n";
?>
