<?php
require_once 'app/models/ChamCongModel.php';
$model = new ChamCongModel();
$res = $model->getConn()->query("SHOW TABLES");
while($row = $res->fetch_array()) {
    echo $row[0] . "\n";
}
