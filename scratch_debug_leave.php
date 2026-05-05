<?php
define('CORPUS_NAME', '14072004T/quanLyChamCong');
$_SESSION['user'] = ['maND' => 1]; // Mock session

require_once 'app/models/ChamCongModel.php';

$model = new ChamCongModel();
$id = 1;
$data = $model->getLeaveById($id);

echo "DEBUG INFO for Leave ID: $id\n";
if ($data) {
    print_r($data);
} else {
    echo "No data found for ID: $id\n";
    // Check if table exists and has data
    echo "\nChecking table don_nghi_phep:\n";
    $conn = new mysqli('localhost', 'root', '', 'dl_final');
    $res = $conn->query("SELECT COUNT(*) as count FROM don_nghi_phep");
    if ($res) {
        $row = $res->fetch_assoc();
        echo "Total records in don_nghi_phep: " . $row['count'] . "\n";
        
        if ($row['count'] > 0) {
            $res2 = $conn->query("SELECT id FROM don_nghi_phep LIMIT 5");
            echo "First 5 IDs: ";
            while($r = $res2->fetch_assoc()) echo $r['id'] . " ";
            echo "\n";
        }
    } else {
        echo "Error querying table: " . $conn->error . "\n";
    }
}
