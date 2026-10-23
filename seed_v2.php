<?php
/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  SEED V2 – Bổ sung OT, Nghỉ phép, IN/OUT tháng 9/2026      ║
 * ║  Chỉ THÊM vào, KHÔNG xóa data cũ – đảm bảo nhất quán       ║
 * ║  http://localhost/quanLyChamCong/seed_v2.php                 ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once 'app/models/ketNoi.php';

try {
    $db   = new KetNoi();
    $conn = $db->connect();
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone='+07:00'");
    $conn->query("SET NAMES utf8mb4");
} catch (Exception $e) {
    die('<h2 style="color:red;font-family:sans-serif;padding:40px">Lỗi DB: ' . htmlspecialchars($e->getMessage()) . '</h2>');
}

// ═══ CONSTANTS ═══
$YEAR   = 2026;
$MONTH  = 9;
$FROM   = '2026-09-01';
$TO     = '2026-09-30';
$HOLIDAYS = ['2026-09-01', '2026-09-02'];
$IN_STD  = '08:00:00';   // giờ vào chuẩn
$OUT_STD = '17:00:00';   // giờ ra chuẩn
$LUNCH   = 1.0;          // giờ nghỉ trưa (trừ khi tính giờ làm)

// ═══ HELPERS ═══
function isWeekend(string $d): bool { $w = (int)date('w', strtotime($d)); return $w===0||$w===6; }
function isHoliday(string $d, array $h): bool { return in_array($d,$h,true); }
function isWorkDay(string $d, array $h): bool { return !isWeekend($d) && !isHoliday($d,$h); }
function addSec(string $base, int $sec): string { return date('H:i:s', strtotime($base)+$sec); }
function randMin(int $lo, int $hi): int { return mt_rand($lo,$hi); }

mt_srand(20260923);

// ═══ Working days tháng 9 ═══
$workDays = [];
for ($d=1; $d<=30; $d++) {
    $dt = sprintf('2026-09-%02d', $d);
    if (isWorkDay($dt, $HOLIDAYS)) $workDays[] = $dt;
}
// 21 ngày làm việc

// ═══ Nhân viên (loại bỏ điều hành) ═══
$empRes = $conn->query(
    "SELECT nd.maND, nd.hoTen, nd.phongBan, nd.chucVu
     FROM nguoidung nd
     WHERE nd.trangThai = 1
       AND LOWER(nd.phongBan) NOT LIKE '%điều hành%'
       AND LOWER(nd.phongBan) NOT LIKE '%dieu hanh%'
     ORDER BY nd.maND"
);
$employees = $empRes ? $empRes->fetch_all(MYSQLI_ASSOC) : [];
if (empty($employees)) die('<h2>Không có nhân viên!</h2>');

// ═══ Lấy manager ID ═══
$mgrRow = $conn->query("SELECT nd.maND FROM nguoidung nd INNER JOIN quanly q ON q.maND=nd.maND WHERE nd.trangThai=1 LIMIT 1")->fetch_assoc();
$mgrId  = (int)($mgrRow['maND'] ?? ($employees[0]['maND'] ?? 1));

// ═══ Pre-load dữ liệu hiện có ═══
// Attendance: [maND][date] => {vao, ra}
$existAtt = [];
$res = $conn->query(
    "SELECT lc.maND, DATE(lc.ngayTao) AS dt,
            MIN(CASE WHEN lc.hanhDong='IN'  THEN TIME(lc.ngayTao) END) AS vao,
            MAX(CASE WHEN lc.hanhDong='OUT' THEN TIME(lc.ngayTao) END) AS ra
     FROM lichsuchamcong lc
     WHERE DATE(lc.ngayTao) BETWEEN '$FROM' AND '$TO'
     GROUP BY lc.maND, DATE(lc.ngayTao)"
);
if ($res) while ($r=$res->fetch_assoc()) {
    $existAtt[$r['maND']][$r['dt']] = ['vao'=>$r['vao'],'ra'=>$r['ra']];
}

// OT: [maND][date] => soGio
$existOT = [];
$res = $conn->query("SELECT maND, ngayOT, soGioOT FROM don_ot WHERE ngayOT BETWEEN '$FROM' AND '$TO'");
if ($res) while ($r=$res->fetch_assoc()) {
    $existOT[$r['maND']][$r['ngayOT']] = (float)$r['soGioOT'];
}

// Leave: [maND][date] => loai
$existLeave = [];
$res = $conn->query("SELECT maND, tuNgay, denNgay, loaiNghiPhep FROM donnghiphep WHERE tuNgay<='$TO' AND denNgay>='$FROM' AND trangThai='approved'");
if ($res) while ($r=$res->fetch_assoc()) {
    $c = new DateTime($r['tuNgay']); $e = new DateTime($r['denNgay']);
    while ($c<=$e) {
        $existLeave[$r['maND']][$c->format('Y-m-d')] = $r['loaiNghiPhep'];
        $c->modify('+1 day');
    }
}

// ═══ THỐNG KÊ TRƯỚC KHI SEED ═══
$beforeAtt   = (int)$conn->query("SELECT COUNT(*) c FROM lichsuchamcong WHERE ngayTao>='$FROM' AND ngayTao<'2026-10-01'")->fetch_assoc()['c'];
$beforeOT    = (int)$conn->query("SELECT COUNT(*) c FROM don_ot WHERE ngayOT BETWEEN '$FROM' AND '$TO'")->fetch_assoc()['c'];
$beforeLeave = (int)$conn->query("SELECT COUNT(*) c FROM donnghiphep WHERE tuNgay BETWEEN '$FROM' AND '$TO'")->fetch_assoc()['c'];
$beforeTab   = (int)$conn->query("SELECT COUNT(*) c FROM tablet_face_scans WHERE thoiGianQuet>='$FROM' AND thoiGianQuet<'2026-10-01'")->fetch_assoc()['c'];

$cntOT=$cntLeave=$cntAtt=$cntTab=0;
$log=[];
$doSeed = isset($_GET['act']) && $_GET['act']==='seed';

// ═══ LOGIC SEED ═══
foreach ($employees as $emp) {
    $maND = (int)$emp['maND'];

    // Shuffle ngày làm việc
    $days = $workDays;
    shuffle($days);

    // --- Chọn ngày OT mới (tối đa 3 ngày, chưa có OT, chưa có nghỉ phép) ---
    $otDays = [];
    $otCount = mt_rand(1, 3);
    foreach ($days as $dt) {
        if (count($otDays) >= $otCount) break;
        if (isset($existOT[$maND][$dt])) continue;        // đã có OT
        if (isset($existLeave[$maND][$dt])) continue;     // ngày nghỉ phép
        $otDays[] = $dt;
    }

    // --- Chọn ngày nghỉ phép mới (1-2 ngày liên tiếp, chưa có data) ---
    $leaveDays = [];
    if (mt_rand(0,2) > 0) { // 66% có nghỉ phép thêm
        $leaveLen = mt_rand(1, 2);
        foreach ($days as $i => $dt) {
            if (count($leaveDays) > 0) break;
            // Không trùng OT, không có attendance cũ, không có leave cũ
            if (isset($existOT[$maND][$dt])) continue;
            if (isset($existLeave[$maND][$dt])) continue;
            if (isset($existAtt[$maND][$dt])) continue;  // đã có chấm công
            if (in_array($dt, $otDays)) continue;
            // Lấy liên tiếp
            $block = [];
            for ($li=0; $li<$leaveLen; $li++) {
                $candidate = isset($days[$i+$li]) ? $days[$i+$li] : null;
                if (!$candidate) break;
                if (isset($existLeave[$maND][$candidate]) || isset($existAtt[$maND][$candidate])) break;
                if (in_array($candidate, $otDays)) break;
                $block[] = $candidate;
            }
            if (count($block) === $leaveLen) $leaveDays = $block;
        }
    }

    // --- INSERT ĐƠN OT ---
    foreach ($otDays as $otDate) {
        $soGio   = round(mt_rand(15,35)/10, 1); // 1.5 - 3.5 giờ
        $ghiChu  = 'Tăng ca dự án tháng 9/' . $YEAR;
        $ngayTao = $otDate . ' 07:30:00';
        if ($doSeed) {
            $stmt = $conn->prepare("INSERT INTO don_ot (maND,ngayOT,soGioOT,ghiChu,nguoiDuyet,trangThai,ngayTao) VALUES (?,?,?,?,?,'approved',?)");
            if ($stmt) {
                $stmt->bind_param('iddsss', $maND, $otDate, $soGio, $ghiChu, $mgrId, $ngayTao);
                if ($stmt->execute()) { $cntOT++; $existOT[$maND][$otDate] = $soGio; }
                $stmt->close();
            }
        } else {
            $cntOT++;
        }
    }

    // --- INSERT ĐƠN NGHỈ PHÉP ---
    if (!empty($leaveDays)) {
        sort($leaveDays);
        $tuNgay = $leaveDays[0]; $denNgay = end($leaveDays);
        $lyDo   = 'Nghỉ phép cá nhân';
        $ngayGui= '2026-08-28';
        if ($doSeed) {
            $stmt = $conn->prepare("INSERT INTO donnghiphep (maND,loaiNghiPhep,tuNgay,denNgay,lyDo,trangThai,nguoiDuyet,ngayDuyet,ngayTao) VALUES (?,'personal',?,?,?,'approved',?,'2026-08-29 09:00:00',?)");
            if ($stmt) {
                $stmt->bind_param('issssi', $maND, $tuNgay, $denNgay, $lyDo, $mgrId, $ngayGui);
                if ($stmt->execute()) {
                    $cntLeave++;
                    foreach ($leaveDays as $ld) $existLeave[$maND][$ld] = 'personal';
                }
                $stmt->close();
            }
        } else {
            $cntLeave++;
        }
    }

    // --- INSERT IN/OUT cho từng ngày làm việc ---
    foreach ($workDays as $dt) {
        // Bỏ qua nếu đã có chấm công
        if (isset($existAtt[$maND][$dt])) continue;
        // Bỏ qua ngày nghỉ phép
        if (isset($existLeave[$maND][$dt])) continue;

        $isOTDay   = isset($existOT[$maND][$dt]);
        $otHours   = $isOTDay ? $existOT[$maND][$dt] : 0;

        // Giờ vào: 07:45 - 08:15 (ngẫu nhiên; nhân viên OT thường đến đúng giờ hơn)
        $lateProb = $isOTDay ? 5 : mt_rand(10, 30);
        $isLate   = mt_rand(1,100) <= $lateProb;
        if ($isLate) {
            $lateMin  = randMin(6, 45);
            $vao = addSec($IN_STD, $lateMin * 60);
        } else {
            $earlyMin = randMin(0, 15);
            $vao = addSec($IN_STD, -$earlyMin * 60);
        }

        // Giờ ra: OT day → 17:00 + otHours + 0-15 phút; Thường → 17:00 + 0-30 phút
        if ($isOTDay) {
            $extraMin = (int)($otHours * 60) + randMin(0, 15);
        } else {
            $earlyOut = mt_rand(1,100) <= 15;
            if ($earlyOut) {
                $extraMin = -randMin(10, 40); // về sớm
            } else {
                $extraMin = randMin(0, 30);
            }
        }
        $ra = addSec($OUT_STD, $extraMin * 60);

        $dtIn  = $dt . ' ' . $vao;
        $dtOut = $dt . ' ' . $ra;

        if ($doSeed) {
            // lichsuchamcong IN
            $stmt = $conn->prepare("INSERT INTO lichsuchamcong (maND,hanhDong,phuongThuc,ghiChu,ngayTao) VALUES (?,'IN','faceid','Chấm công tự động',?)");
            if ($stmt) { $stmt->bind_param('is',$maND,$dtIn); $stmt->execute(); $stmt->close(); }

            // lichsuchamcong OUT
            $stmt = $conn->prepare("INSERT INTO lichsuchamcong (maND,hanhDong,phuongThuc,ghiChu,ngayTao) VALUES (?,'OUT','faceid','Chấm công tự động',?)");
            if ($stmt) { $stmt->bind_param('is',$maND,$dtOut); $stmt->execute(); $stmt->close(); }

            // tablet_face_scans IN
            $stmt = $conn->prepare("INSERT INTO tablet_face_scans (maND,thoiGianQuet) VALUES (?,?)");
            if ($stmt) {
                $stmt->bind_param('is',$maND,$dtIn);  $stmt->execute();
                $stmt->bind_param('is',$maND,$dtOut); $stmt->execute();
                $stmt->close();
                $cntTab += 2;
            }

            // Cập nhật cache để tránh duplicate trong vòng lặp
            $existAtt[$maND][$dt] = ['vao'=>$vao,'ra'=>$ra];
            $cntAtt++;
        } else {
            $cntAtt++;
        }
    }
}

// ═══ Thống kê sau seed ═══
$afterAtt   = $doSeed ? (int)$conn->query("SELECT COUNT(*) c FROM lichsuchamcong WHERE ngayTao>='$FROM' AND ngayTao<'2026-10-01'")->fetch_assoc()['c'] : $beforeAtt;
$afterOT    = $doSeed ? (int)$conn->query("SELECT COUNT(*) c FROM don_ot WHERE ngayOT BETWEEN '$FROM' AND '$TO'")->fetch_assoc()['c'] : $beforeOT;
$afterLeave = $doSeed ? (int)$conn->query("SELECT COUNT(*) c FROM donnghiphep WHERE tuNgay BETWEEN '$FROM' AND '$TO'")->fetch_assoc()['c'] : $beforeLeave;
$afterTab   = $doSeed ? (int)$conn->query("SELECT COUNT(*) c FROM tablet_face_scans WHERE thoiGianQuet>='$FROM' AND thoiGianQuet<'2026-10-01'")->fetch_assoc()['c'] : $beforeTab;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>🌱 Seed V2 – Tháng 9/2026</title>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Be Vietnam Pro',sans-serif;background:linear-gradient(135deg,#0f172a,#1e3a8a);min-height:100vh;padding:32px 16px}
.wrap{max-width:800px;margin:0 auto}
.card{background:#fff;border-radius:20px;padding:28px 32px;box-shadow:0 24px 60px rgba(0,0,0,.35);margin-bottom:18px;color:#0f172a}
h1{font-size:1.7rem;font-weight:800;color:#1e40af;display:flex;align-items:center;gap:10px;margin-bottom:6px}
.sub{color:#64748b;font-size:.87rem;margin-bottom:22px}
/* Before/After stats */
.diff-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.diff-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px 10px;text-align:center}
.diff-box .lbl{font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
.diff-box .before{font-size:.95rem;color:#94a3b8}
.diff-box .arrow{font-size:1rem;color:#2563eb;margin:2px 0}
.diff-box .after{font-size:1.5rem;font-weight:800;color:#0f172a}
.diff-box .added{font-size:.75rem;font-weight:700;color:#16a34a;background:#dcfce7;border-radius:99px;padding:2px 8px;margin-top:4px;display:inline-block}
/* Preview stats */
.prev-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:20px}
.prev-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px}
.prev-box .lbl{font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px}
.prev-box .val{font-size:1.3rem;font-weight:800;color:#1e40af}
/* Employees */
.emp-list{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;max-height:200px;overflow-y:auto;margin-bottom:20px}
.emp-item{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:.8rem}
.emp-item b{color:#0f172a;font-weight:700}
.emp-item span{color:#64748b;font-size:.72rem}
/* Alerts */
.alert{padding:13px 16px;border-radius:10px;font-size:.86rem;margin-bottom:16px}
.alert-success{background:#dcfce7;color:#166534;border:1px solid #86efac}
.alert-info{background:#dbeafe;color:#1e40af;border:1px solid #93c5fd}
.alert-warn{background:#fef9c3;color:#854d0e;border:1px solid #fde047}
/* Buttons */
.actions{display:flex;gap:10px;flex-wrap:wrap}
.btn{padding:12px 22px;border:none;border-radius:10px;font-size:.88rem;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .2s}
.btn:hover{transform:translateY(-2px)}
.btn-primary{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.35)}
.btn-green{background:linear-gradient(135deg,#059669,#047857);color:#fff;box-shadow:0 4px 14px rgba(5,150,105,.3)}
.btn-gray{background:#e2e8f0;color:#475569}
/* Info table */
table.info{width:100%;border-collapse:collapse;font-size:.84rem;margin-bottom:16px}
table.info td{padding:9px 12px;border-bottom:1px solid #f1f5f9}
table.info td:first-child{font-weight:700;color:#64748b;width:40%}
table.info tr:last-child td{border:none}
table.info tr:nth-child(even) td{background:#f8fafc}
/* Warning */
.warn-box{background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;padding:13px 16px;font-size:.82rem;color:#92400e;margin-top:16px}
.warn-box code{background:#fef9c3;padding:1px 5px;border-radius:3px;font-family:monospace;font-size:.85em}
</style>
</head>
<body>
<div class="wrap">

<?php if ($doSeed): ?>
<!-- ═══ KẾT QUẢ SEED ═══ -->
<div class="card">
    <h1>🌱 Seed V2 — Hoàn thành!</h1>
    <p class="sub">Đã bổ sung OT, nghỉ phép và chấm công tháng 9/2026</p>
    <div class="alert alert-success">✅ Seed thành công! Data được bổ sung vào mà không xóa data cũ.</div>
    <div class="diff-grid">
        <div class="diff-box">
            <div class="lbl">🕐 Chấm công</div>
            <div class="before"><?= $beforeAtt ?></div>
            <div class="arrow">↓</div>
            <div class="after"><?= $afterAtt ?></div>
            <div class="added">+<?= $afterAtt-$beforeAtt ?></div>
        </div>
        <div class="diff-box">
            <div class="lbl">📸 Tablet</div>
            <div class="before"><?= $beforeTab ?></div>
            <div class="arrow">↓</div>
            <div class="after"><?= $afterTab ?></div>
            <div class="added">+<?= $afterTab-$beforeTab ?></div>
        </div>
        <div class="diff-box">
            <div class="lbl">⏱️ Đơn OT</div>
            <div class="before"><?= $beforeOT ?></div>
            <div class="arrow">↓</div>
            <div class="after"><?= $afterOT ?></div>
            <div class="added">+<?= $afterOT-$beforeOT ?></div>
        </div>
        <div class="diff-box">
            <div class="lbl">📄 Nghỉ phép</div>
            <div class="before"><?= $beforeLeave ?></div>
            <div class="arrow">↓</div>
            <div class="after"><?= $afterLeave ?></div>
            <div class="added">+<?= $afterLeave-$beforeLeave ?></div>
        </div>
    </div>
    <div class="actions">
        <a href="index.php?page=bao-cao-tong-hop&tuNgay=2026-09-01&denNgay=2026-09-30" class="btn btn-green">📊 Xem báo cáo tháng 9</a>
        <a href="?" class="btn btn-gray">↩ Quay lại</a>
    </div>
</div>

<?php else: ?>
<!-- ═══ PREVIEW ═══ -->
<div class="card">
    <h1>🌱 Seed V2 – Bổ sung data tháng 9/2026</h1>
    <p class="sub">Giữ nguyên data cũ. Chỉ thêm vào nơi còn thiếu. OT &amp; nghỉ phép đồng bộ với IN/OUT.</p>

    <div class="alert alert-info">
        ℹ️ <strong>Preview mode</strong> — xem trước số bản ghi sẽ được thêm. Bấm <strong>"Chạy Seed"</strong> để thực thi.
    </div>

    <!-- DB hiện tại -->
    <div style="margin-bottom:12px;font-weight:700;color:#1e40af;font-size:.9rem">📦 Dữ liệu tháng 9 hiện có trong DB:</div>
    <div class="diff-grid">
        <div class="diff-box">
            <div class="lbl">🕐 Chấm công</div>
            <div class="after"><?= $beforeAtt ?></div>
        </div>
        <div class="diff-box">
            <div class="lbl">📸 Tablet</div>
            <div class="after"><?= $beforeTab ?></div>
        </div>
        <div class="diff-box">
            <div class="lbl">⏱️ Đơn OT</div>
            <div class="after"><?= $beforeOT ?></div>
        </div>
        <div class="diff-box">
            <div class="lbl">📄 Nghỉ phép</div>
            <div class="after"><?= $beforeLeave ?></div>
        </div>
    </div>

    <!-- Sẽ thêm -->
    <div style="margin-bottom:12px;font-weight:700;color:#059669;font-size:.9rem">➕ Sẽ bổ sung thêm:</div>
    <div class="prev-grid">
        <div class="prev-box">
            <div class="lbl">⏱️ Đơn OT mới</div>
            <div class="val">+<?= $cntOT ?></div>
        </div>
        <div class="prev-box">
            <div class="lbl">📄 Đơn nghỉ phép mới</div>
            <div class="val">+<?= $cntLeave ?></div>
        </div>
        <div class="prev-box">
            <div class="lbl">🕐 Ngày chấm công mới</div>
            <div class="val">+<?= $cntAtt ?> ngày</div>
        </div>
        <div class="prev-box">
            <div class="lbl">📸 Tablet scans mới</div>
            <div class="val">+<?= $cntAtt * 2 ?></div>
        </div>
    </div>

    <!-- Quy tắc -->
    <table class="info">
        <tr><td>📅 Phạm vi</td><td>Tháng 9/2026 (01-09 → 30-09)</td></tr>
        <tr><td>👥 Nhân viên</td><td><?= count($employees) ?> người (không gồm phòng Điều hành)</td></tr>
        <tr><td>📋 Ngày làm việc</td><td><?= count($workDays) ?> ngày (trừ T7, CN, Quốc khánh 1-2/9)</td></tr>
        <tr><td>🔒 Bảo vệ data cũ</td><td>Chỉ thêm ngày chưa có IN/OUT; OT/Leave chưa tồn tại</td></tr>
        <tr><td>⏱️ OT rules</td><td>Giờ ra = 17:00 + số giờ OT + 0-15 phút; Ngày OT: ít đi trễ hơn</td></tr>
        <tr><td>📄 Nghỉ phép</td><td>66% nhân viên có 1-2 ngày nghỉ bổ sung; không trùng OT, không trùng data cũ</td></tr>
        <tr><td>🕐 Giờ vào/ra</td><td>Vào: 07:45-08:15 (±15'); Ra: 17:00+OT hoặc về sớm 15% xác suất</td></tr>
    </table>

    <!-- Nhân viên -->
    <div style="margin-bottom:10px;font-weight:700;color:#1e40af;font-size:.88rem">👥 Nhân viên (<?= count($employees) ?> người):</div>
    <div class="emp-list">
        <?php foreach ($employees as $e): ?>
        <div class="emp-item">
            <b>#<?= $e['maND'] ?> <?= htmlspecialchars($e['hoTen']) ?></b><br>
            <span><?= htmlspecialchars($e['phongBan'] ?? 'N/A') ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="actions">
        <a href="?act=seed" class="btn btn-primary" onclick="return confirm('Chạy seed? Sẽ thêm data vào DB, không xóa data cũ.')">
            🚀 Chạy Seed (thêm vào)
        </a>
        <a href="index.php?page=bao-cao-tong-hop&tuNgay=2026-09-01&denNgay=2026-09-30" class="btn btn-green">📊 Xem báo cáo</a>
        <a href="index.php" class="btn btn-gray">↩ Về trang chủ</a>
    </div>
</div>
<?php endif; ?>

<div class="warn-box">
    ⚠️ <strong>Bảo mật:</strong> Xóa file sau khi dùng xong!
    <code>seed_v2.php</code>
</div>

</div>
</body>
</html>
