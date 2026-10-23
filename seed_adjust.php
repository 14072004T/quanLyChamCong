<?php
/**
 * seed_adjust.php – Điều chỉnh đơn OT & nghỉ phép tháng 9/2026
 * - Đơn OT: giữ 10-20 đơn, đủ trạng thái pending/approved/rejected
 * - Nghỉ phép: xóa bớt còn 5-15 đơn, đủ trạng thái
 */
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once 'app/models/ketNoi.php';

$db = new KetNoi(); $conn = $db->connect();
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone='+07:00'");

$FROM = '2026-09-01'; $TO = '2026-09-30';
$TARGET_OT_MIN = 10; $TARGET_OT_MAX = 20;
$TARGET_LV_MIN = 5;  $TARGET_LV_MAX = 15;

// ─── Manager ID ───
$mgrRow = $conn->query("SELECT nd.maND FROM nguoidung nd INNER JOIN quanly q ON q.maND=nd.maND WHERE nd.trangThai=1 LIMIT 1")->fetch_assoc();
$mgrId  = (int)($mgrRow['maND'] ?? 1);

// ─── Working days ───
$workDays = [];
$holidays = ['2026-09-01','2026-09-02'];
for ($d=1;$d<=30;$d++){
    $dt=sprintf('2026-09-%02d',$d);
    $dow=(int)date('w',strtotime($dt));
    if($dow!==0&&$dow!==6&&!in_array($dt,$holidays,true)) $workDays[]=$dt;
}

// ─── Nhân viên (không điều hành) ───
$empRes=$conn->query("SELECT maND,hoTen,phongBan FROM nguoidung WHERE trangThai=1 AND LOWER(phongBan) NOT LIKE '%điều hành%' AND LOWER(phongBan) NOT LIKE '%dieu hanh%' ORDER BY maND");
$employees=$empRes?$empRes->fetch_all(MYSQLI_ASSOC):[];
if(empty($employees)) die('No employees');

mt_srand(20260924);

$doRun = isset($_GET['act']) && $_GET['act']==='run';
$log   = [];

// ════════════════════════════════════════
// ── PHẦN 1: Điều chỉnh đơn OT ──
// ════════════════════════════════════════

// Đếm OT hiện tại
$curOT = (int)$conn->query("SELECT COUNT(*) c FROM don_ot WHERE ngayOT BETWEEN '$FROM' AND '$TO'")->fetch_assoc()['c'];
$log[] = "📦 OT hiện có: $curOT";

$targetOT = mt_rand($TARGET_OT_MIN, $TARGET_OT_MAX);
$addOT = max(0, $targetOT - $curOT);
$log[] = "🎯 OT mục tiêu: $targetOT (cần thêm: $addOT)";

// Load OT đã có
$existOT = [];
$res=$conn->query("SELECT maND,ngayOT FROM don_ot WHERE ngayOT BETWEEN '$FROM' AND '$TO'");
if($res) while($r=$res->fetch_assoc()) $existOT[$r['maND']][$r['ngayOT']]=true;

// Trạng thái với tỷ lệ: 60% approved, 20% pending, 20% rejected
$otStatuses = ['approved','approved','approved','pending','rejected'];
$otAdded = 0;

if($addOT > 0){
    $candidates=[];
    foreach($employees as $emp){
        foreach($workDays as $dt){
            if(!isset($existOT[$emp['maND']][$dt])){
                $candidates[]=[$emp['maND'],$dt];
            }
        }
    }
    shuffle($candidates);
    foreach(array_slice($candidates,0,$addOT) as [$maND,$dt]){
        $soGio = round(mt_rand(15,35)/10,1);
        $trangThai = $otStatuses[mt_rand(0,count($otStatuses)-1)];
        $nguoiDuyet = ($trangThai!=='pending')?$mgrId:null;
        $ngayTao = $dt.' 07:00:00';
        $ghiChu  = 'Tăng ca dự án tháng 9/2026';
        if($doRun){
            if($nguoiDuyet){
                $stmt=$conn->prepare("INSERT INTO don_ot (maND,ngayOT,soGioOT,ghiChu,nguoiDuyet,trangThai,ngayTao) VALUES(?,?,?,?,?,?,?)");
                $stmt->bind_param('iddsiss',$maND,$dt,$soGio,$ghiChu,$nguoiDuyet,$trangThai,$ngayTao);
            } else {
                $stmt=$conn->prepare("INSERT INTO don_ot (maND,ngayOT,soGioOT,ghiChu,trangThai,ngayTao) VALUES(?,?,?,?,?,?)");
                $stmt->bind_param('iddsss',$maND,$dt,$soGio,$ghiChu,$trangThai,$ngayTao);
            }
            if($stmt&&$stmt->execute()) $otAdded++;
            if($stmt) $stmt->close();
        } else {
            $otAdded++;
        }
    }
}
$log[] = "✅ OT đã thêm: $otAdded";

// ════════════════════════════════════════
// ── PHẦN 2: Điều chỉnh đơn nghỉ phép ──
// ════════════════════════════════════════

$curLV=(int)$conn->query("SELECT COUNT(*) c FROM donnghiphep WHERE tuNgay BETWEEN '$FROM' AND '$TO'")->fetch_assoc()['c'];
$log[] = "📦 Nghỉ phép hiện có: $curLV";

$targetLV = mt_rand($TARGET_LV_MIN, $TARGET_LV_MAX);
$log[] = "🎯 Nghỉ phép mục tiêu: $targetLV";

$delLV = 0;
$addLV = 0;

if($curLV > $TARGET_LV_MAX){
    // Xóa bớt (ưu tiên xóa pending trước, rồi rejected)
    $excess = $curLV - $targetLV;
    $delRes = $conn->query("SELECT madonxin FROM donnghiphep WHERE tuNgay BETWEEN '$FROM' AND '$TO' ORDER BY trangThai='pending' DESC, trangThai='rejected' DESC LIMIT $excess");
    if($delRes){
        while($r=$delRes->fetch_assoc()){
            if($doRun){
                $conn->query("DELETE FROM donnghiphep WHERE madonxin=".(int)$r['madonxin']);
                $delLV++;
            } else {
                $delLV++;
            }
        }
    }
    $log[] = "🗑️ Nghỉ phép đã xóa: $delLV";
} elseif($curLV < $TARGET_LV_MIN){
    // Thêm đơn nghỉ phép mới với đủ trạng thái
    $needed = $targetLV - $curLV;
    $lvStatuses = ['approved','approved','pending','rejected','pending'];
    $lvTypes    = ['personal','sick','annual','personal','emergency'];

    // Load leave đã có
    $existLV=[];
    $r2=$conn->query("SELECT maND,tuNgay,denNgay FROM donnghiphep WHERE tuNgay BETWEEN '$FROM' AND '$TO'");
    if($r2) while($rr=$r2->fetch_assoc()){
        $c=new DateTime($rr['tuNgay']); $e=new DateTime($rr['denNgay']);
        while($c<=$e){ $existLV[$rr['maND']][$c->format('Y-m-d')]=true; $c->modify('+1 day'); }
    }

    for($i=0;$i<$needed;$i++){
        $emp=$employees[mt_rand(0,count($employees)-1)];
        $maND=(int)$emp['maND'];
        // Chọn ngày chưa có leave
        shuffle($workDays);
        $tuNgay=$denNgay=null;
        foreach($workDays as $dt){
            if(!isset($existLV[$maND][$dt])){
                $tuNgay=$denNgay=$dt;
                break;
            }
        }
        if(!$tuNgay) continue;
        $trangThai=$lvStatuses[mt_rand(0,count($lvStatuses)-1)];
        $loai=$lvTypes[mt_rand(0,count($lvTypes)-1)];
        $nguoiDuyet=($trangThai!=='pending')?$mgrId:null;
        $ngayTao='2026-08-25 09:00:00';
        if($doRun){
            if($nguoiDuyet){
                $stmt=$conn->prepare("INSERT INTO donnghiphep (maND,loaiNghiPhep,tuNgay,denNgay,lyDo,trangThai,nguoiDuyet,ngayDuyet,ngayTao) VALUES(?,?,?,?,'Nghỉ phép cá nhân',?,?,'2026-08-26',?)");
                $stmt->bind_param('issssss',$maND,$loai,$tuNgay,$denNgay,$trangThai,$nguoiDuyet,$ngayTao);
            } else {
                $stmt=$conn->prepare("INSERT INTO donnghiphep (maND,loaiNghiPhep,tuNgay,denNgay,lyDo,trangThai,ngayTao) VALUES(?,?,?,?,'Nghỉ phép cá nhân',?,?)");
                $stmt->bind_param('isssss',$maND,$loai,$tuNgay,$denNgay,$trangThai,$ngayTao);
            }
            if($stmt&&$stmt->execute()){
                $addLV++;
                $existLV[$maND][$tuNgay]=true;
            }
            if($stmt) $stmt->close();
        } else { $addLV++; }
    }
    $log[] = "✅ Nghỉ phép đã thêm: $addLV";
} else {
    // Trong range, chỉ đảm bảo có đủ trạng thái
    $statusCheck=$conn->query("SELECT trangThai,COUNT(*) cnt FROM donnghiphep WHERE tuNgay BETWEEN '$FROM' AND '$TO' GROUP BY trangThai")->fetch_all(MYSQLI_ASSOC);
    $hasPending=false; $hasRejected=false;
    foreach($statusCheck as $sc){
        if($sc['trangThai']==='pending') $hasPending=true;
        if($sc['trangThai']==='rejected') $hasRejected=true;
    }
    // Cập nhật 1 đơn sang pending nếu chưa có
    if(!$hasPending&&$doRun){
        $conn->query("UPDATE donnghiphep SET trangThai='pending', nguoiDuyet=NULL WHERE tuNgay BETWEEN '$FROM' AND '$TO' AND trangThai='approved' ORDER BY RAND() LIMIT 1");
        $log[]="🔄 Chuyển 1 đơn sang pending";
    }
    // Cập nhật 1 đơn sang rejected nếu chưa có
    if(!$hasRejected&&$doRun){
        $conn->query("UPDATE donnghiphep SET trangThai='rejected', nguoiDuyet=$mgrId WHERE tuNgay BETWEEN '$FROM' AND '$TO' AND trangThai='approved' ORDER BY RAND() LIMIT 1");
        $log[]="🔄 Chuyển 1 đơn sang rejected";
    }
    if(!$hasPending||!$hasRejected) $log[]="🔄 Đã đảm bảo đủ trạng thái nghỉ phép";
    else $log[]="✅ Nghỉ phép đã đủ range và đủ trạng thái";
}

// ─── Đảm bảo OT có đủ trạng thái ───
if($doRun){
    $otStatusCheck=$conn->query("SELECT trangThai,COUNT(*) cnt FROM don_ot WHERE ngayOT BETWEEN '$FROM' AND '$TO' GROUP BY trangThai")->fetch_all(MYSQLI_ASSOC);
    $otHasPending=false; $otHasRejected=false;
    foreach($otStatusCheck as $s){
        if($s['trangThai']==='pending') $otHasPending=true;
        if($s['trangThai']==='rejected') $otHasRejected=true;
    }
    if(!$otHasPending) {
        $conn->query("UPDATE don_ot SET trangThai='pending',nguoiDuyet=NULL WHERE ngayOT BETWEEN '$FROM' AND '$TO' AND trangThai='approved' ORDER BY RAND() LIMIT 2");
        $log[]="🔄 Chuyển 2 đơn OT sang pending";
    }
    if(!$otHasRejected) {
        $conn->query("UPDATE don_ot SET trangThai='rejected',nguoiDuyet=$mgrId WHERE ngayOT BETWEEN '$FROM' AND '$TO' AND trangThai='approved' ORDER BY RAND() LIMIT 1");
        $log[]="🔄 Chuyển 1 đơn OT sang rejected";
    }
}

// ─── Thống kê sau ───
$afterOT  = (int)$conn->query("SELECT COUNT(*) c FROM don_ot WHERE ngayOT BETWEEN '$FROM' AND '$TO'")->fetch_assoc()['c'];
$afterLV  = (int)$conn->query("SELECT COUNT(*) c FROM donnghiphep WHERE tuNgay BETWEEN '$FROM' AND '$TO'")->fetch_assoc()['c'];
$otByStatus = $conn->query("SELECT trangThai,COUNT(*) cnt FROM don_ot WHERE ngayOT BETWEEN '$FROM' AND '$TO' GROUP BY trangThai")->fetch_all(MYSQLI_ASSOC);
$lvByStatus = $conn->query("SELECT trangThai,COUNT(*) cnt FROM donnghiphep WHERE tuNgay BETWEEN '$FROM' AND '$TO' GROUP BY trangThai")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8">
<title>Seed Adjust</title>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Be Vietnam Pro',sans-serif;background:linear-gradient(135deg,#0f172a,#1e3a8a);min-height:100vh;padding:32px 16px}
.w{max-width:700px;margin:0 auto}.card{background:#fff;border-radius:16px;padding:24px 28px;margin-bottom:16px;box-shadow:0 20px 50px rgba(0,0,0,.3)}
h1{font-size:1.5rem;font-weight:800;color:#1e40af;margin-bottom:6px}
.sub{color:#64748b;font-size:.85rem;margin-bottom:20px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px}
.box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px}
.box .lbl{font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:6px}
.box .val{font-size:1.6rem;font-weight:800;color:#0f172a}
.box .sub2{font-size:.78rem;color:#64748b;margin-top:4px}
.status-grid{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.badge{padding:3px 10px;border-radius:99px;font-size:.75rem;font-weight:700}
.badge.approved{background:#dcfce7;color:#166534}
.badge.pending{background:#fef9c3;color:#854d0e}
.badge.rejected{background:#fee2e2;color:#991b1b}
.log{background:#0f172a;border-radius:8px;padding:14px;font-family:monospace;font-size:.8rem;color:#94a3b8;margin-bottom:16px}
.log span{display:block;padding:2px 0}
.log .ok{color:#4ade80}.log .info{color:#60a5fa}.log .warn{color:#fbbf24}
.actions{display:flex;gap:10px;flex-wrap:wrap}
.btn{padding:11px 20px;border:none;border-radius:9px;font-size:.88rem;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .2s}
.btn:hover{transform:translateY(-2px)}
.btn-run{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.35)}
.btn-green{background:linear-gradient(135deg,#059669,#047857);color:#fff}
.btn-gray{background:#e2e8f0;color:#475569}
.alert-ok{background:#dcfce7;color:#166534;border:1px solid #86efac;padding:11px 14px;border-radius:8px;margin-bottom:12px;font-size:.86rem}
.alert-info{background:#dbeafe;color:#1e40af;border:1px solid #93c5fd;padding:11px 14px;border-radius:8px;margin-bottom:12px;font-size:.86rem}
.warn-box{background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:11px 14px;font-size:.8rem;color:#92400e}
</style></head><body><div class="w">
<div class="card">
    <h1>🔧 Điều chỉnh OT & Nghỉ phép – T9/2026</h1>
    <p class="sub">OT: <?=$TARGET_OT_MIN?>–<?=$TARGET_OT_MAX?> đơn &nbsp;|&nbsp; Nghỉ phép: <?=$TARGET_LV_MIN?>–<?=$TARGET_LV_MAX?> đơn &nbsp;|&nbsp; Đủ trạng thái pending/approved/rejected</p>

    <?php if($doRun): ?>
    <div class="alert-ok">✅ Đã chạy thành công!</div>
    <?php else: ?>
    <div class="alert-info">ℹ️ Preview mode – Bấm <strong>"Chạy"</strong> để thực thi</div>
    <?php endif; ?>

    <!-- Log -->
    <div class="log">
    <?php foreach($log as $l): ?>
        <span class="ok"><?=htmlspecialchars($l)?></span>
    <?php endforeach; ?>
    </div>

    <!-- Kết quả -->
    <div class="grid2">
        <div class="box">
            <div class="lbl">⏱️ Đơn OT tháng 9</div>
            <div class="val"><?=$afterOT?></div>
            <div class="sub2">Mục tiêu: <?=$TARGET_OT_MIN?>–<?=$TARGET_OT_MAX?></div>
            <div class="status-grid">
            <?php foreach($otByStatus as $s): ?>
                <span class="badge <?=$s['trangThai']?>"><?=$s['trangThai']?>: <?=$s['cnt']?></span>
            <?php endforeach; ?>
            </div>
        </div>
        <div class="box">
            <div class="lbl">📄 Nghỉ phép tháng 9</div>
            <div class="val"><?=$afterLV?></div>
            <div class="sub2">Mục tiêu: <?=$TARGET_LV_MIN?>–<?=$TARGET_LV_MAX?></div>
            <div class="status-grid">
            <?php foreach($lvByStatus as $s): ?>
                <span class="badge <?=$s['trangThai']?>"><?=$s['trangThai']?>: <?=$s['cnt']?></span>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="actions">
        <?php if(!$doRun): ?>
        <a href="?act=run" class="btn btn-run" onclick="return confirm('Chạy điều chỉnh DB?')">🚀 Chạy</a>
        <?php endif; ?>
        <a href="index.php?page=bao-cao-tong-hop&tuNgay=2026-09-01&denNgay=2026-09-30" class="btn btn-green">📊 Xem báo cáo</a>
        <a href="?" class="btn btn-gray">↩ Reset</a>
    </div>
</div>
<div class="warn-box">⚠️ Xóa file sau khi dùng: <code>seed_adjust.php</code></div>
</div></body></html>
