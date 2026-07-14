<?php

require_once "app/models/ketNoi.php";

$db = new KetNoi();
$conn = $db->connect();

echo "Database hiện tại: ";

$result = $conn->query("SELECT DATABASE()");

$row = $result->fetch_row();

echo $row[0];

echo "<br>";

$result = $conn->query("SELECT COUNT(*) FROM tonghopngaycong");

$data = $result->fetch_row();

echo "Số dòng tonghopngaycong: ".$data[0];

?>