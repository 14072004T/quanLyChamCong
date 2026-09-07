<?php
$conn = @new mysqli('127.0.0.1', 'root', '', 'dl_final');
if ($conn->connect_error) {
    echo "Local DB Connection Failed: " . $conn->connect_error . "\n";
} else {
    echo "Local DB Connection Successful!\n";
}
