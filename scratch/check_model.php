<?php
require 'app/models/ketNoi.php';
require 'app/models/ChamCongModel.php';
try {
    $m = new ChamCongModel();
    $rows = $m->getEmployees('', true);
    echo 'employees=' . count($rows) . "\n";
    foreach (array_slice($rows, 0, 10) as $r) {
        echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
    }
    $data = $m->getMonthlyAttendanceDetailNew('2026-01');
    echo 'detail=' . count($data) . "\n";
    foreach (array_slice($data, 0, 10) as $r) {
        echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
?>
