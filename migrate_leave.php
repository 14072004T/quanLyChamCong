<?php
require_once 'app/models/ketNoi.php';
$db = new ketNoi();
$conn = $db->ketNoi();

// Add ghiChuNS column if not exists
$res = $conn->query("SHOW COLUMNS FROM donnghiphep LIKE 'ghiChuNS'");
if ($res->num_rows === 0) {
    $conn->query("ALTER TABLE donnghiphep ADD COLUMN ghiChuNS TEXT DEFAULT NULL AFTER nguoiDuyet");
    echo 'Added ghiChuNS column<br>';
} else {
    echo 'ghiChuNS already exists<br>';
}

// Show current columns
$res2 = $conn->query('SHOW COLUMNS FROM donnghiphep');
echo '<pre>';
while ($row = $res2->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
echo '</pre>';
echo 'Done!';
