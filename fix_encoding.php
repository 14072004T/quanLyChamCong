<?php
/**
 * Fix database encoding issues
 * Convert mojibake (corrupted UTF-8) back to proper UTF-8
 */

require_once 'app/models/ketNoi.php';

$db = new KetNoi();
$conn = $db->connect();

// Function to fix mojibake: UTF-8 bytes interpreted as Latin1
function fixMojibake($text) {
    if (!is_string($text)) return $text;
    
    // Detect if it's mojibake by checking for common patterns
    if (preg_match('/[áàảãạăắằẳẵặâấầẩẫậéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵđ]/u', $text)) {
        // Already properly encoded
        return $text;
    }
    
    // Try to fix mojibake by treating it as UTF-8 encoded as Latin1
    if (preg_match('/[\xC0-\xFF]/', $text)) {
        $fixed = iconv('ISO-8859-1', 'UTF-8', $text);
        return $fixed !== false ? $fixed : $text;
    }
    
    return $text;
}

echo "=== Database Encoding Fix ===\n\n";

// Get all tables that might have Vietnamese text
$tables = ['nguoidung', 'taikhoan'];

foreach ($tables as $table) {
    echo "Checking table: $table\n";
    
    $sql = "SHOW COLUMNS FROM $table";
    $result = $conn->query($sql);
    
    $stringColumns = [];
    while ($row = $result->fetch_assoc()) {
        // Check if column type contains 'char', 'text', 'varchar'
        if (stripos($row['Type'], 'char') !== false || stripos($row['Type'], 'text') !== false) {
            $stringColumns[] = $row['Field'];
        }
    }
    
    echo "  String columns: " . implode(', ', $stringColumns) . "\n";
    
    // Check sample data
    $sql = "SELECT * FROM $table LIMIT 3";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        foreach ($stringColumns as $col) {
            $original = $row[$col];
            $fixed = fixMojibake($original);
            
            if ($original !== $fixed) {
                echo "  [$col] NEED FIX: '$original' → '$fixed'\n";
            }
        }
    }
}

// Now let's specifically check nguoidung.chucVu values
echo "\n\n=== Checking chucVu values in nguoidung ===\n";
$sql = "SELECT maNV, chucVu FROM nguoidung LIMIT 10";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $original = $row['chucVu'];
    $fixed = fixMojibake($original);
    echo "ID: {$row['maNV']} | Original: '$original' | Fixed: '$fixed'\n";
}

echo "\n";
?>
