<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_SESSION['user'] = ['maND' => 1];
$_GET['page'] = 'get-correction-detail';
$_GET['id'] = 2; // I saw ID 2 in the .sql dump

require_once 'app/models/ChamCongModel.php';
require_once 'app/controllers/ChamCongController.php';

$controller = new ChamCongController();
$controller->getCorrectionDetail();
