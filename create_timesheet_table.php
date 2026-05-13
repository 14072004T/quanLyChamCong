<?php
$conn = new mysqli("127.0.0.1", "root", "", "dl_final");
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$sql = "CREATE TABLE IF NOT EXISTS `employee_timesheet_approval` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `month_key` VARCHAR(7) NOT NULL,
  `maND` INT NOT NULL,
  `hr_sender_id` INT NOT NULL,
  `status` ENUM('submitted','approved') DEFAULT 'submitted',
  `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `approved_at` DATETIME DEFAULT NULL,
  `employee_note` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_month_employee` (`month_key`, `maND`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql)) {
    echo "Table employee_timesheet_approval created successfully\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
