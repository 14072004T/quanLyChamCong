<?php
/**
 * Script to approve timesheets for months 1, 2, 3, 4 for all employees
 */

$conn = new mysqli("127.0.0.1", "root", "", "dl_final");

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// Update months 01, 02, 03, 04 to approved
$months = ['2026-01', '2026-02', '2026-03', '2026-04'];
$totalUpdated = 0;

foreach ($months as $monthKey) {
    $sql = "UPDATE employee_timesheet_approval 
            SET status = 'approved', 
                approved_at = NOW()
            WHERE month_key = ? 
            AND status = 'submitted'";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "Error preparing statement: " . $conn->error . "\n";
        continue;
    }
    
    $stmt->bind_param('s', $monthKey);
    if ($stmt->execute()) {
        $affectedRows = $stmt->affected_rows;
        echo "Month $monthKey: Updated $affectedRows records\n";
        $totalUpdated += $affectedRows;
    } else {
        echo "Error executing statement for $monthKey: " . $stmt->error . "\n";
    }
    
    $stmt->close();
}

echo "\nTotal records updated: $totalUpdated\n";

// Verify the update
echo "\n--- Verification ---\n";
foreach ($months as $monthKey) {
    $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted
            FROM employee_timesheet_approval
            WHERE month_key = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $monthKey);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    echo "$monthKey: Total={$result['total']}, Approved={$result['approved']}, Submitted={$result['submitted']}\n";
}

$conn->close();
echo "\nDone!\n";
?>
