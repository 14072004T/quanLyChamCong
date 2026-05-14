<?php
require_once 'app/models/KetNoi.php';
$db = new KetNoi();
$conn = $db->connect();
$res = $conn->query("DESCRIBE employee_timesheet_approval");
if (!$res) {
    echo "Table does not exist or error: " . $conn->error;
} else {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
