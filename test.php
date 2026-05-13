<?php
require 'app/models/ChamCongModel.php';
$m = new ChamCongModel();
$employees = $m->getEmployees('', true, 0);
echo json_encode($employees, JSON_UNESCAPED_UNICODE);
