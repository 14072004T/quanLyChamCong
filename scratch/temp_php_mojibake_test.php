<?php
$tests = [
    'NhÃ¢n viÃªn',
    'Sáº£n xuáº¥t',
    'Bá»™ pháº­n NhÃ¢n sá»±',
    'Quáº£n lÃ½ / Ban lÃ£nh Ä‘áº¡o',
    'Bá»™ pháº­n Ká»¹ thuáº­t',
    'Nhân viên',
    'Sản xuất',
];

function repair($value) {
    $value = (string)$value;
    $candidates = [];
    $candidates[] = $value;
    $candidates[] = @mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    $candidates[] = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    $candidates[] = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $value);
    $candidates[] = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
    $candidates[] = @utf8_decode($value);
    return array_values(array_unique($candidates));
}

foreach ($tests as $s) {
    echo "ORIG: $s\n";
    foreach (repair($s) as $r) {
        echo "  -> [" . bin2hex($r) . "] " . $r . "\n";
    }
    echo "---\n";
}
