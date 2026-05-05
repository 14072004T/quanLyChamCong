<?php
$conn = new mysqli('localhost', 'root', '', 'dl_final');
$res = $conn->query("SELECT * FROM attendance_shifts LIMIT 1");
print_r($res->fetch_assoc());
