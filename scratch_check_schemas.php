<?php
$conn = new mysqli('localhost', 'root', '', 'dl_final');
echo "TABLE: don_nghi_phep\n";
$res = $conn->query("DESCRIBE don_nghi_phep");
while($row = $res->fetch_assoc()) echo $row['Field'] . " - " . $row['Type'] . "\n";

echo "\nTABLE: attendance_corrections\n";
$res = $conn->query("DESCRIBE attendance_corrections");
while($row = $res->fetch_assoc()) echo $row['Field'] . " - " . $row['Type'] . "\n";
