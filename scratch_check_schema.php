<?php
$conn = new mysqli('localhost', 'root', '', 'dl_final');
$res = $conn->query("DESCRIBE attendance_corrections");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
