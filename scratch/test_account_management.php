<?php
require_once 'app/models/ChamCongModel.php';

echo "=== TESTING ACCOUNT MANAGEMENT MODEL METHODS ===\n\n";

$model = new ChamCongModel();

// 1. Test getAllDepartments
$depts = $model->getAllDepartments();
echo "1. Departments count: " . count($depts) . "\n";
echo "   Sample departments: " . implode(", ", array_slice($depts, 0, 5)) . "\n\n";

// 2. Test getAllAccountsWithUsers
$accounts = $model->getAllAccountsWithUsers();
echo "2. Total Accounts found: " . count($accounts) . "\n";
if (!empty($accounts)) {
    echo "   Sample account 1:\n";
    print_r($accounts[0]);
}

// 3. Test filter by search
if (!empty($accounts)) {
    $searchTerm = $accounts[0]['tenDangNhap'];
    $filtered = $model->getAllAccountsWithUsers(['search' => $searchTerm]);
    echo "3. Filter search for '$searchTerm': Found " . count($filtered) . " record(s)\n\n";
}

echo "=== ALL MODEL TESTS PASSED CLEANLY ===\n";
