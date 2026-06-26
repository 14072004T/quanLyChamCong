<?php
/**
 * Script to rebuild timesheet_approval data from scratch
 * 1. Delete all old records
 * 2. Create new records for all employees for months 01-05/2026
 * 3. Approve months 01-04 automatically
 */

require_once 'c:\xampp\htdocs\quanLyChamCong\app\models\ketNoi.php';

$ketNoi = new KetNoi();
$conn = $ketNoi->connect();

if (!$conn) {
    die("Connection failed\n");
}

echo "=== REBUILD TIMESHEET APPROVAL DATA ===\n\n";

// Step 1: Get all active employees
echo "Step 1: Getting all active employees...\n";
$sql = "SELECT DISTINCT maND FROM nguoidung WHERE maND > 0 AND hoTen IS NOT NULL ORDER BY maND";
$result = $conn->query($sql);
if (!$result) {
    die("Failed to get employees: " . $conn->error . "\n");
}

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = (int)$row['maND'];
}
$result->close();

echo "Found " . count($employees) . " employees\n\n";

// Step 2: Delete all existing records
echo "Step 2: Deleting all existing timesheet_approval records...\n";
$sql = "DELETE FROM employee_timesheet_approval";
if (!$conn->query($sql)) {
    die("Failed to delete: " . $conn->error . "\n");
}
$deletedRows = $conn->affected_rows;
echo "Deleted $deletedRows old records\n\n";

// Step 3: Insert new records for all employees x all months (01-05/2026)
echo "Step 3: Creating new timesheet approval records...\n";
$months = ['2026-01', '2026-02', '2026-03', '2026-04', '2026-05'];
$hrSenderId = 2; // Default HR ID (Tran Thi HR)
$newRecords = 0;

foreach ($employees as $maND) {
    foreach ($months as $monthKey) {
        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO employee_timesheet_approval 
                (month_key, maND, hr_sender_id, status, submitted_at, created_at)
                VALUES (?, ?, ?, 'submitted', ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error . "\n");
        }
        
        $stmt->bind_param('siiss', $monthKey, $maND, $hrSenderId, $now, $now);
        if (!$stmt->execute()) {
            echo "Warning: Failed to insert for employee $maND month $monthKey: " . $stmt->error . "\n";
            $stmt->close();
            continue;
        }
        $newRecords++;
        $stmt->close();
    }
}

echo "Created $newRecords new timesheet approval records\n\n";

// Step 4: Approve months 01-04 automatically
echo "Step 4: Approving months 01-04 automatically...\n";
$approveMonths = ['2026-01', '2026-02', '2026-03', '2026-04'];
$approvedCount = 0;

foreach ($approveMonths as $monthKey) {
    $now = date('Y-m-d H:i:s');
    $sql = "UPDATE employee_timesheet_approval 
            SET status='approved', approved_at=? 
            WHERE month_key=? AND status='submitted'";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error . "\n");
    }
    
    $stmt->bind_param('ss', $now, $monthKey);
    if (!$stmt->execute()) {
        echo "Warning: Failed to approve month $monthKey: " . $stmt->error . "\n";
        $stmt->close();
        continue;
    }
    $affectedRows = $stmt->affected_rows;
    $approvedCount += $affectedRows;
    echo "  Month $monthKey: Approved $affectedRows records\n";
    $stmt->close();
}

echo "\nTotal records approved: $approvedCount\n\n";

// Step 5: Verify results
echo "Step 5: Verification\n";
$sql = "SELECT month_key, status, COUNT(*) as count 
        FROM employee_timesheet_approval 
        GROUP BY month_key, status 
        ORDER BY month_key DESC";
$result = $conn->query($sql);
if (!$result) {
    die("Verification failed: " . $conn->error . "\n");
}

echo "Status breakdown:\n";
while ($row = $result->fetch_assoc()) {
    $monthKey = $row['month_key'];
    $status = $row['status'];
    $count = $row['count'];
    echo "  $monthKey - $status: $count records\n";
}
$result->close();

echo "\n=== COMPLETED ===\n";
echo "Total records created: $newRecords\n";
echo "Total records approved: $approvedCount\n";
