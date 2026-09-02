<?php
// Chạy 1 lần trên production để tìm các cặp nhân viên có embedding khuôn mặt quá giống nhau
// (nguyên nhân gây nhận nhầm người trên tablet). Xoá file này sau khi dùng xong.
// FaceModel.php require các file khác bằng đường dẫn tương đối tính từ thư mục gốc dự án
// (giống khi chạy qua index.php), nên phải chdir về gốc trước khi require nó ở đây.
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../app/models/ketNoi.php';
require_once __DIR__ . '/../app/models/FaceModel.php';

function normalizeEmbedding($embedding)
{
    $squaredNorm = 0.0;
    foreach ($embedding as $value) {
        $squaredNorm += ((float)$value) * ((float)$value);
    }
    $norm = sqrt($squaredNorm);
    if ($norm <= 0.0) return [];
    return array_map(static function ($v) use ($norm) { return (float)$v / $norm; }, $embedding);
}

function euclideanDistance($v1, $v2)
{
    $sum = 0.0;
    for ($i = 0; $i < count($v1); $i++) {
        $diff = $v1[$i] - $v2[$i];
        $sum += $diff * $diff;
    }
    return sqrt($sum);
}

function cosineSimilarity($v1, $v2)
{
    $dot = 0.0; $n1 = 0.0; $n2 = 0.0;
    for ($i = 0; $i < count($v1); $i++) {
        $dot += $v1[$i] * $v2[$i];
        $n1 += $v1[$i] * $v1[$i];
        $n2 += $v2[$i] * $v2[$i];
    }
    if ($n1 <= 0 || $n2 <= 0) return 0.0;
    return $dot / (sqrt($n1) * sqrt($n2));
}

$faceModel = new FaceModel();
$profiles = $faceModel->getAllFaceProfiles();

$parsed = [];
foreach ($profiles as $p) {
    $emb = json_decode($p['embedding'], true);
    if (!is_array($emb)) continue;
    $parsed[] = ['maND' => $p['maND'], 'name' => $faceModel->getUserName($p['maND']), 'vec' => normalizeEmbedding($emb)];
}

echo "<pre>Tổng số hồ sơ khuôn mặt: " . count($parsed) . "\n\n";
echo "Các cặp có nguy cơ nhận nhầm (distance <= 0.60 hoặc cosine >= 0.80):\n";
echo str_pad('NV A', 25) . str_pad('NV B', 25) . str_pad('Distance', 12) . "Cosine\n";
echo str_repeat('-', 74) . "\n";

$found = false;
for ($i = 0; $i < count($parsed); $i++) {
    for ($j = $i + 1; $j < count($parsed); $j++) {
        $d = euclideanDistance($parsed[$i]['vec'], $parsed[$j]['vec']);
        $c = cosineSimilarity($parsed[$i]['vec'], $parsed[$j]['vec']);
        if ($d <= 0.60 || $c >= 0.80) {
            $found = true;
            $labelA = $parsed[$i]['maND'] . '-' . $parsed[$i]['name'];
            $labelB = $parsed[$j]['maND'] . '-' . $parsed[$j]['name'];
            echo str_pad($labelA, 25) . str_pad($labelB, 25) . str_pad(number_format($d, 4), 12) . number_format($c, 4) . "\n";
        }
    }
}
if (!$found) echo "(Không có cặp nào đáng ngờ)\n";
echo "</pre>";
