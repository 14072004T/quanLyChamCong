<?php
require_once __DIR__ . '/../app/models/ketNoi.php';

try {
    $db = new KetNoi();
    $conn = $db->connect();
    
    $sql = "
    CREATE TABLE IF NOT EXISTS `don_ot` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `maND` int(11) NOT NULL,
      `ngayOT` date NOT NULL,
      `soGioOT` float NOT NULL,
      `ghiChu` text COLLATE utf8mb4_unicode_ci NOT NULL,
      `nguoiDuyet` int(11) NOT NULL,
      `trangThai` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
      `ngayDuyet` datetime DEFAULT NULL,
      `ngayTao` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table don_ot created successfully\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>
