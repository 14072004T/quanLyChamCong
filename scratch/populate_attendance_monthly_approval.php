<?php
/**
 * Script to populate attendance_monthly_approval table
 * Create records for all departments for months 01-05/2026
 * Approve months 01-04, keep month 05 as submitted
 */

require_once 'c:\xampp\htdocs\quanLyChamCong\app\models\ketNoi.php';

$ketNoi = new KetNoi();
$conn = $ketNoi->connect();

if (!$conn) {
    die("Connection failed\n");
}

echo "=== POPULATE ATTENDANCE_MONTHLY_APPROVAL ===\n\n";

// Step 1: Get all unique departments
echo "Step 1: Getting all departments...\n";
$sql = "SELECT DISTINCT phongBan FROM nguoidung WHERE phongBan IS NOT NULL AND phongBan != '' ORDER BY phongBan";
$result = $conn->query($sql);
if (!$result) {
    die("Failed to get departments: " . $conn->error . "\n");
}

$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = trim($row['phongBan']);
}
$result->close();

// Remove empty values
$departments = array_filter($departments);
echo "Found " . count($departments) . " departments\n";
foreach ($departments as $dept) {
    echo "  - $dept\n";
}
echo "\n";

// Step 2: Get HR and Manager IDs
echo "Step 2: Getting HR and Manager IDs...\n";
$sql = "SELECT maND FROM nguoidung WHERE hoTen LIKE '%Tran%HR%' OR hoTen LIKE '%HR%' LIMIT 1";
$result = $conn->query($sql);
$hrResult = $result ? $result->fetch_assoc() : null;
$hrSenderId = $hrResult ? (int)$hrResult['maND'] : 2;
echo "HR Sender ID: $hrSenderId\n";

$sql = "SELECT maND FROM nguoidung WHERE vaiTro = 'manager' LIMIT 1";
$result = $conn->query($sql);
$mgrResult = $result ? $result->fetch_assoc() : null;
$managerApproverId = $mgrResult ? (int)$mgrResult['maND'] : null;
echo "Manager Approver ID: " . ($managerApproverId ?? 'NULL') . "\n\n";

// Step 3: Delete existing records
echo "Step 3: Deleting existing records...\n";
$sql = "DELETE FROM attendance_monthly_approval";
if (!$conn->query($sql)) {
    die("Failed to delete: " . $conn->error . "\n");
}
$deletedRows = $conn->affected_rows;
echo "Deleted $deletedRows old records\n\n";

// Step 4: Insert new records
echo "Step 4: Creating new attendance_monthly_approval records...\n";
$months = ['2026-01', '2026-02', '2026-03', '2026-04', '2026-05'];
$insertedCount = 0;

foreach ($departments as $department) {
    foreach ($months as $monthKey) {
        $now = date('Y-m-d H:i:s');
        $status = in_array($monthKey, ['2026-01', '2026-02', '2026-03', '2026-04']) ? 'approved' : 'submitted';
        $approvedAt = ($status === 'approved') ? $now : null;
        $submittedAt = in_array($monthKey, ['2026-01', '2026-02', '2026-03', '2026-04']) ? $now : $now;
        
        $sql = "INSERT INTO attendance_monthly_approval 
                (month_key, hr_sender_id, manager_approver_id, status, submitted_at, approved_at, department, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error . "\n");
        }
        
        $stmt->bind_param(
            'siissssss',
            $monthKey,
            $hrSenderId,
            $managerApproverId,
            $status,
            $submittedAt,
            $approvedAt,
            $department,
            $now,
            $now
        );
        
        if (!$stmt->execute()) {
            echo "Warning: Failed to insert for $department $monthKey: " . $stmt->error . "\n";
            $stmt->close();
            continue;
        }
        $insertedCount++;
        $stmt->close();
    }
}

echo "Created $insertedCount new records\n\n";

// Step 5: Verify
echo "Step 5: Verification\n";
$sql = "SELECT month_key, status, COUNT(*) as count 
        FROM attendance_monthly_approval 
        GROUP BY month_key, status 
        ORDER BY month_key DESC, status";
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

echo "\n";
$sql = "SELECT COUNT(*) as total FROM attendance_monthly_approval";
$result = $conn->query($sql);
$totalRow = $result->fetch_assoc();
echo "Total records: " . $totalRow['total'] . "\n";

echo "\n=== COMPLETED ===\n";
echo "Total records inserted: $insertedCount\n";
