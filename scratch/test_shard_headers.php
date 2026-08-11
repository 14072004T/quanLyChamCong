<?php
$url = 'http://localhost/quanLyChamCong/public/models/tiny_face_detector_model-shard1';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$res = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP CODE: " . $info['http_code'] . "\n";
echo "DOWNLOAD SIZE: " . $info['size_download'] . "\n";
echo "RESPONSE HEADERS AND BODY:\n" . substr($res, 0, 500) . "\n";
