<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock environment
$_SESSION['user'] = ['maND' => 1];
$_GET['page'] = 'get-leave-detail';
$_GET['id'] = 1;

require_once 'app/models/ChamCongModel.php';
require_once 'app/controllers/ChamCongController.php';

$controller = new ChamCongController();
echo "--- START API OUTPUT ---\n";
$controller->getLeaveDetail();
echo "\n--- END API OUTPUT ---\n";
