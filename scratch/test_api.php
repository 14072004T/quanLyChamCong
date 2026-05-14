<?php
$_SESSION['user'] = ['maND' => 1, 'hoTen' => 'Test HR'];
$_SESSION['role'] = 'hr';
$_GET['month'] = '2026-04';
require_once 'app/controllers/HRController.php';
$c = new HRController();
$c->timesheetApprovalDetailsApi();
