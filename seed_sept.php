<?php
/**
 * ╔══════════════════════════════════════════════════════╗
 * ║   SEED DATA THÁNG 9/2026 – Hệ thống Chấm Công       ║
 * ║   Truy cập: http://localhost/quanLyChamCong/seed_sept.php ║
 * ║   XÓA FILE NÀY SAU KHI DÙNG XONG!                   ║
 * ╚══════════════════════════════════════════════════════╝
 */

date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once 'app/models/ketNoi.php';

// ═══ Kết nối DB ═══
try {
    $db   = new KetNoi();
    $conn = $db->connect();
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone='+07:00'");
    $conn->query("SET NAMES utf8mb4");
} catch (Exception $e) {
    die('<h2 style="color:red;font-family:sans-serif;padding:40px">Lỗi kết nối DB: ' . htmlspecialchars($e->getMessage()) . '</h2>');
}

// ═══ Hằng số ═══
$YEAR  = 2026;
$MONTH = 9;
$MONTH_STR   = '2026-09';
$FROM_DATE   = '2026-09-01';
$TO_DATE     = '2026-09-30';
$HOLIDAYS    = ['2026-09-01', '2026-09-02']; // Quốc khánh & nghỉ bù
$CHECKIN_STD = '08:00:00';
$CHECKOUT_STD= '17:00:00';
$DAYS_IN_MONTH = 30;

// ═══ Hành động ═══
$action  = $_GET['act'] ?? 'preview';  // preview | reset | append
$result  = [];
$errors  = [];

// ───────────────────────────────────────────────────────────
// Helper functions
// ───────────────────────────────────────────────────────────
function isWeekend(string $date): bool {
    $dow = (int)date('w', strtotime($date));
    return $dow === 0 || $dow === 6;
}
function addMinutes(string $datetimeOrTime, int $minutes): string {
    return date('H:i:s', strtotime($datetimeOrTime) + $minutes * 60);
}
function mkDT(string $date, string $time): string {
    return $date . ' ' . $time;
}
function lateComment(string $checkin): string {
    $diff = strtotime($checkin) - strtotime('08:00:00');
    if ($diff > 60) return 'Đi muộn ' . round($diff / 60) . ' phút';
    return 'Đúng giờ';
}
function earlyComment(string $checkout): string {
    $diff = strtotime('17:00:00') - strtotime($checkout);
    if ($diff > 60) return 'Về sớm ' . round($diff / 60) . ' phút';
    return 'Đã lưu';
}

// ─── Lấy nhân viên active ───
$empRows = [];
$res = $conn->query("SELECT nd.maND, nd.hoTen, nd.phongBan, nd.chucVu
                     FROM nguoidung nd WHERE nd.trangThai = 1 ORDER BY nd.maND");
if ($res) while ($r = $res->fetch_assoc()) $empRows[] = $r;

if (empty($empRows)) {
    die('<h2 style="color:red;font-family:sans-serif;padding:40px">Không tìm thấy nhân viên active nào!</h2>');
}

// ─── Lấy manager đầu tiên (để gán nguoiDuyet OT) ───
$mgrId = 0;
$mRes = $conn->query("SELECT nd.maND FROM nguoidung nd INNER JOIN quanly q ON q.maND = nd.maND WHERE nd.trangThai=1 LIMIT 1");
if ($mRes && $row = $mRes->fetch_assoc()) $mgrId = (int)$row['maND'];
if ($mgrId === 0) {
    // Fallback: dùng HR
    $hRes = $conn->query("SELECT nd.maND FROM nguoidung nd INNER JOIN nhansu n ON n.maND=nd.maND WHERE nd.trangThai=1 LIMIT 1");
    if ($hRes && $row = $hRes->fetch_assoc()) $mgrId = (int)$row['maND'];
}
if ($mgrId === 0) $mgrId = (int)$empRows[0]['maND'];

// ─── Tạo ngày làm việc tháng 9 ───
$allWorkDays = [];
for ($d = 1; $d <= $DAYS_IN_MONTH; $d++) {
    $date = sprintf('2026-09-%02d', $d);
    if (!isWeekend($date) && !in_array($date, $HOLIDAYS)) {
        $allWorkDays[] = $date;
    }
}
// Có 21 ngày làm việc trong tháng 9/2026

// ───────────────────────────────────────────────────────────
// Thực hiện xóa dữ liệu tháng 9 (chỉ seed data)
// ───────────────────────────────────────────────────────────
if ($action === 'reset') {
    $tables = [
        "DELETE FROM lichsuchamcong   WHERE ngayTao   >= '$FROM_DATE' AND ngayTao   < '2026-10-01'",
        "DELETE FROM tablet_face_scans WHERE thoiGianQuet >= '$FROM_DATE' AND thoiGianQuet < '2026-10-01'",
        "DELETE FROM donnghiphep      WHERE tuNgay    >= '$FROM_DATE' AND tuNgay    <= '$TO_DATE'",
        "DELETE FROM don_ot           WHERE ngayOT    >= '$FROM_DATE' AND ngayOT    <= '$TO_DATE'",
        "DELETE FROM suachamcong      WHERE ngayChamCong >= '$FROM_DATE' AND ngayChamCong <= '$TO_DATE'",
        "DELETE FROM duyetcongnhanvien WHERE thangNam = '$MONTH_STR'",
        "DELETE FROM duyetcongthang   WHERE thangNam = '$MONTH_STR'",
    ];
    foreach ($tables as $sql) {
        $conn->query($sql);
    }
    $result[] = ['type' => 'info', 'msg' => '🗑️ Đã xóa dữ liệu tháng 9/2026 cũ'];
}

// ───────────────────────────────────────────────────────────
// Seed data (action = reset hoặc append)
// ───────────────────────────────────────────────────────────
$cntAtt = $cntLeave = $cntOT = $cntCorr = $cntTablet = 0;

if ($action === 'reset' || $action === 'append') {
    mt_srand(20260901); // seed cố định → kết quả nhất quán

    // ─── Phân loại nhân viên ───
    $profiles = [];
    foreach ($empRows as $emp) {
        $maND = (int)$emp['maND'];
        $cv   = mb_strtolower($emp['chucVu'] ?? '');
        $isSenior = str_contains($cv, 'giám đốc') || str_contains($cv, 'quản lý') || str_contains($cv, 'trưởng');

        $profiles[$maND] = [
            'late_prob'     => $isSenior ? mt_rand(2,8)   : mt_rand(15, 40),
            'early_prob'    => $isSenior ? mt_rand(2,6)   : mt_rand(8,  25),
            'absent_count'  => $isSenior ? mt_rand(0,1)   : mt_rand(0,   3),
            'leave_count'   => mt_rand(1, 4),
            'ot_count'      => mt_rand(0, 5),
            'late_min_avg'  => mt_rand(5, 50),
            'early_min_avg' => mt_rand(10, 45),
            'ot_hours'      => round(mt_rand(15, 40) / 10, 1),
        ];
    }

    foreach ($empRows as $emp) {
        $maND = (int)$emp['maND'];
        $p    = $profiles[$maND];

        // Shuffle ngày làm việc để random chọn
        $days = $allWorkDays;
        shuffle($days);

        $absentDates = array_slice($days, 0, $p['absent_count']);
        $leaveDates  = array_slice($days, $p['absent_count'], $p['leave_count']);
        $otDates     = array_slice($days, $p['absent_count'] + $p['leave_count'], $p['ot_count']);

        // ─── ĐƠN NGHỈ PHÉP ───
        if (!empty($leaveDates)) {
            sort($leaveDates);
            $tuNgay   = $leaveDates[0];
            $denNgay  = end($leaveDates);
            $lyDo     = 'Nghỉ phép theo kế hoạch Q3 2026';
            $ngayGui  = '2026-08-' . sprintf('%02d', mt_rand(20, 28));
            $stmt = $conn->prepare(
                "INSERT INTO donnghiphep
                 (maND, loaiNghiPhep, tuNgay, denNgay, lyDo, trangThai, nguoiDuyet, ngayDuyet, ngayTao)
                 VALUES (?, 'personal', ?, ?, ?, 'approved', ?, '2026-08-29 09:00:00', ?)"
            );
            if ($stmt) {
                $stmt->bind_param('issssi', $maND, $tuNgay, $denNgay, $lyDo, $mgrId, $ngayGui);
                if ($stmt->execute()) $cntLeave++;
                $stmt->close();
            }
        }

        // ─── ĐƠN OT ───
        foreach ($otDates as $otDate) {
            $soGio  = $p['ot_hours'];
            $ghiChu = 'Tăng ca hoàn thành sprint ' . strtoupper(substr(md5($otDate.$maND), 0, 4));
            $ngayTaoOT = $otDate . ' 07:45:00';
            $stmt = $conn->prepare(
                "INSERT INTO don_ot (maND, ngayOT, soGioOT, ghiChu, nguoiDuyet, trangThai, ngayTao)
                 VALUES (?, ?, ?, ?, ?, 'approved', ?)"
            );
            if ($stmt) {
                $stmt->bind_param('iddsss', $maND, $otDate, $soGio, $ghiChu, $mgrId, $ngayTaoOT);
                if ($stmt->execute()) $cntOT++;
                $stmt->close();
            }
        }

        // ─── CHẤM CÔNG TỪNG NGÀY ───
        foreach ($allWorkDays as $date) {
            // Bỏ qua ngày nghỉ phép / vắng
            if (in_array($date, $leaveDates))  continue;
            if (in_array($date, $absentDates)) continue;

            $isOTDay   = in_array($date, $otDates);
            $isLate    = mt_rand(1, 100) <= $p['late_prob'];
            $isEarly   = !$isOTDay && mt_rand(1, 100) <= $p['early_prob'];

            // Giờ vào
            if ($isLate) {
                $lateMin  = max(5, min(90, $p['late_min_avg'] + mt_rand(-10, 15)));
                $checkin  = addMinutes($CHECKIN_STD, $lateMin);
            } else {
                $checkin  = addMinutes($CHECKIN_STD, -mt_rand(0, 15));
            }

            // Giờ ra
            if ($isEarly) {
                $earlyMin = max(5, min(55, $p['early_min_avg'] + mt_rand(-8, 12)));
                $checkout = addMinutes($CHECKOUT_STD, -$earlyMin);
            } elseif ($isOTDay) {
                $checkout = addMinutes($CHECKOUT_STD, (int)($p['ot_hours'] * 60));
            } else {
                $checkout = addMinutes($CHECKOUT_STD, mt_rand(0, 25));
            }

            $dtIn  = mkDT($date, $checkin);
            $dtOut = mkDT($date, $checkout);

            $noteIn  = lateComment($checkin);
            $noteOut = earlyComment($checkout);

            // lichsuchamcong – IN
            $stmt = $conn->prepare(
                "INSERT INTO lichsuchamcong (maND, hanhDong, phuongThuc, ghiChu, ngayTao)
                 VALUES (?, 'IN', 'faceid', ?, ?)"
            );
            if ($stmt) {
                $stmt->bind_param('iss', $maND, $noteIn, $dtIn);
                $stmt->execute(); $stmt->close();
            }

            // lichsuchamcong – OUT
            $stmt = $conn->prepare(
                "INSERT INTO lichsuchamcong (maND, hanhDong, phuongThuc, ghiChu, ngayTao)
                 VALUES (?, 'OUT', 'faceid', ?, ?)"
            );
            if ($stmt) {
                $stmt->bind_param('iss', $maND, $noteOut, $dtOut);
                $stmt->execute(); $stmt->close();
                $cntAtt++;
            }

            // tablet_face_scans – IN & OUT
            $stmt = $conn->prepare("INSERT INTO tablet_face_scans (maND, thoiGianQuet) VALUES (?,?)");
            if ($stmt) {
                $stmt->bind_param('is', $maND, $dtIn);  $stmt->execute();
                $stmt->bind_param('is', $maND, $dtOut); $stmt->execute();
                $stmt->close();
                $cntTablet += 2;
            }
        }

        // ─── YÊU CẦU ĐIỀU CHỈNH CÔNG (xác suất 25%) ───
        if (mt_rand(0, 3) === 0 && !empty($allWorkDays)) {
            $corrDay = $allWorkDays[array_rand($allWorkDays)];
            if (!in_array($corrDay, $absentDates) && !in_array($corrDay, $leaveDates)) {
                $gioVao = mkDT($corrDay, '09:00:00');
                $gioRa  = mkDT($corrDay, '17:30:00');
                $lyDo   = 'Quên chấm công do họp ngoài văn phòng';
                $ngayTaoC = mkDT($corrDay, '18:30:00');
                $stmt = $conn->prepare(
                    "INSERT INTO suachamcong
                     (maND, ngayChamCong, gioCu, gioMoi, lyDo, gioVaoDeXuat, gioRaDeXuat, trangThai, ngayTao)
                     VALUES (?, ?, NULL, ?, ?, ?, ?, 'approved', ?)"
                );
                if ($stmt) {
                    $stmt->bind_param('issssss', $maND, $corrDay, $gioVao, $lyDo, $gioVao, $gioRa, $ngayTaoC);
                    if ($stmt->execute()) $cntCorr++;
                    $stmt->close();
                }
            }
        }
    }

    // ─── TẠO BẢNG DUYỆT CÔNG THÁNG (duyetcongthang) ───
    $departments = array_unique(array_filter(array_column($empRows, 'phongBan')));
    foreach ($departments as $dept) {
        $conn->query(
            "INSERT IGNORE INTO duyetcongthang
             (thangNam, phongBan, maNguoiGuiNS, trangThai, ngayGui, ngayTao)
             VALUES ('$MONTH_STR', " . $conn->real_escape_string($dept) . ", $mgrId, 'submitted', '2026-10-01 08:00:00', '2026-10-01 08:00:00')"
        );
    }

    $result[] = ['type' => 'success', 'msg' => "✅ Đã tạo " . count($empRows) . " nhân viên × dữ liệu tháng 9"];
    $result[] = ['type' => 'success', 'msg' => "🕐 Chấm công: $cntAtt ngày × nhân viên"];
    $result[] = ['type' => 'success', 'msg' => "📸 Tablet scans: $cntTablet records"];
    $result[] = ['type' => 'success', 'msg' => "📄 Đơn nghỉ phép: $cntLeave đơn"];
    $result[] = ['type' => 'success', 'msg' => "⏱️ Đơn OT: $cntOT đơn"];
    $result[] = ['type' => 'success', 'msg' => "📝 Yêu cầu điều chỉnh: $cntCorr yêu cầu"];
}

// ─── Thống kê hiện tại trong DB ───
$statAtt = (int)($conn->query("SELECT COUNT(*) c FROM lichsuchamcong WHERE ngayTao>='$FROM_DATE' AND ngayTao<'2026-10-01'")->fetch_assoc()['c'] ?? 0);
$statOT  = (int)($conn->query("SELECT COUNT(*) c FROM don_ot WHERE ngayOT>='$FROM_DATE' AND ngayOT<='$TO_DATE'")->fetch_assoc()['c'] ?? 0);
$statLeave = (int)($conn->query("SELECT COUNT(*) c FROM donnghiphep WHERE tuNgay>='$FROM_DATE' AND tuNgay<='$TO_DATE'")->fetch_assoc()['c'] ?? 0);
$statTablet = (int)($conn->query("SELECT COUNT(*) c FROM tablet_face_scans WHERE thoiGianQuet>='$FROM_DATE' AND thoiGianQuet<'2026-10-01'")->fetch_assoc()['c'] ?? 0);
$statCorr = (int)($conn->query("SELECT COUNT(*) c FROM suachamcong WHERE ngayChamCong>='$FROM_DATE' AND ngayChamCong<='$TO_DATE'")->fetch_assoc()['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🌱 Seed Data Tháng 9/2026</title>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Be Vietnam Pro',sans-serif;background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 50%,#0f172a 100%);min-height:100vh;padding:32px 16px;color:#e2e8f0}
.wrap{max-width:820px;margin:0 auto}
.header{text-align:center;margin-bottom:28px}
.header h1{font-size:2rem;font-weight:800;color:#fff;display:flex;align-items:center;justify-content:center;gap:12px}
.header p{color:#94a3b8;margin-top:6px;font-size:.9rem}
.card{background:#fff;border-radius:20px;padding:32px;box-shadow:0 24px 60px rgba(0,0,0,.35);margin-bottom:20px;color:#0f172a}
.card h2{font-size:1rem;font-weight:800;color:#1e40af;margin-bottom:18px;display:flex;align-items:center;gap:8px;text-transform:uppercase;letter-spacing:.05em}
/* Stats */
.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:24px}
.stat-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px 12px;text-align:center}
.stat-box .ico{font-size:1.4rem;margin-bottom:6px}
.stat-box .val{font-size:1.6rem;font-weight:800;color:#1e40af;line-height:1}
.stat-box .lbl{font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-top:4px}
/* Result log */
.result-list{list-style:none;margin-bottom:20px}
.result-list li{padding:10px 16px;border-radius:8px;margin-bottom:6px;font-size:.88rem;font-weight:600;display:flex;align-items:center;gap:8px}
.result-list li.success{background:#dcfce7;color:#166534}
.result-list li.info{background:#dbeafe;color:#1e40af}
.result-list li.warn{background:#fef9c3;color:#854d0e}
/* Employees */
.emp-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;max-height:220px;overflow-y:auto;padding-right:4px;margin-bottom:20px}
.emp-item{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;font-size:.8rem}
.emp-item .name{font-weight:700;color:#0f172a;margin-bottom:2px}
.emp-item .dept{color:#64748b}
/* Actions */
.actions{display:flex;gap:10px;flex-wrap:wrap}
.btn{padding:12px 20px;border:none;border-radius:10px;font-size:.88rem;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px;transition:all .2s;white-space:nowrap}
.btn:hover{transform:translateY(-2px)}
.btn-red{background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;box-shadow:0 4px 14px rgba(220,38,38,.35)}
.btn-red:hover{box-shadow:0 8px 24px rgba(220,38,38,.5)}
.btn-blue{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.35)}
.btn-blue:hover{box-shadow:0 8px 24px rgba(37,99,235,.5)}
.btn-green{background:linear-gradient(135deg,#059669,#047857);color:#fff;box-shadow:0 4px 14px rgba(5,150,105,.35)}
.btn-green:hover{box-shadow:0 8px 24px rgba(5,150,105,.5)}
.btn-gray{background:#e2e8f0;color:#475569}
.btn-gray:hover{background:#cbd5e1}
/* Warning box */
.warning-box{background:#fef3c7;border:1px solid #fcd34d;border-radius:12px;padding:16px 18px;font-size:.84rem;color:#92400e;margin-top:20px;line-height:1.6}
.warning-box code{background:#fef9c3;padding:2px 6px;border-radius:4px;font-family:monospace;font-size:.85em}
/* Schedule info */
.schedule-info{background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:14px 16px;font-size:.83rem;color:#166534;margin-bottom:20px}
.progress-bar{height:6px;background:#e2e8f0;border-radius:999px;overflow:hidden;margin-top:4px}
.progress-fill{height:100%;background:linear-gradient(90deg,#2563eb,#3b82f6);border-radius:999px;transition:width .5s}
</style>
</head>
<body>
<div class="wrap">
    <!-- Header -->
    <div class="header">
        <h1><i class="fas fa-seedling"></i> Seed Data Tháng 9/2026</h1>
        <p>Tạo dữ liệu chấm công thực tế để xem báo cáo tổng hợp đầy đủ</p>
    </div>

    <!-- Kết quả thao tác -->
    <?php if (!empty($result)): ?>
    <div class="card">
        <h2><i class="fas fa-check-circle"></i> Kết quả</h2>
        <ul class="result-list">
            <?php foreach ($result as $r): ?>
            <li class="<?= $r['type'] ?>"><?= htmlspecialchars($r['msg']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Thống kê hiện tại trong DB -->
    <div class="card">
        <h2><i class="fas fa-database"></i> Dữ liệu hiện có trong DB (Tháng 9/2026)</h2>
        <div class="stats">
            <div class="stat-box">
                <div class="ico">🕐</div>
                <div class="val"><?= $statAtt ?></div>
                <div class="lbl">Lượt chấm công</div>
            </div>
            <div class="stat-box">
                <div class="ico">📸</div>
                <div class="val"><?= $statTablet ?></div>
                <div class="lbl">Tablet scans</div>
            </div>
            <div class="stat-box">
                <div class="ico">📄</div>
                <div class="val"><?= $statLeave ?></div>
                <div class="lbl">Đơn nghỉ phép</div>
            </div>
            <div class="stat-box">
                <div class="ico">⏱️</div>
                <div class="val"><?= $statOT ?></div>
                <div class="lbl">Đơn OT</div>
            </div>
            <div class="stat-box">
                <div class="ico">📝</div>
                <div class="val"><?= $statCorr ?></div>
                <div class="lbl">Điều chỉnh công</div>
            </div>
        </div>

        <?php if ($statAtt > 0): ?>
        <div class="schedule-info">
            <i class="fas fa-info-circle"></i> <strong>Đã có dữ liệu!</strong>
            Tháng 9 hiện có <strong><?= $statAtt ?></strong> lượt chấm công.
            Dùng <strong>"Xóa &amp; Seed lại"</strong> để tạo mới hoàn toàn, hoặc <strong>"Thêm vào"</strong> để bổ sung.
        </div>
        <?php else: ?>
        <div class="schedule-info" style="background:#fef9c3;border-color:#fcd34d;color:#854d0e">
            <i class="fas fa-exclamation-triangle"></i> <strong>Chưa có dữ liệu tháng 9.</strong>
            Bấm <strong>"Xóa &amp; Seed lại"</strong> để tạo dữ liệu mẫu đầy đủ.
        </div>
        <?php endif; ?>
    </div>

    <!-- Danh sách nhân viên -->
    <div class="card">
        <h2><i class="fas fa-users"></i> Nhân viên sẽ được seed (<?= count($empRows) ?> người)</h2>
        <div class="emp-grid">
            <?php foreach ($empRows as $e): ?>
            <div class="emp-item">
                <div class="name">#<?= $e['maND'] ?> — <?= htmlspecialchars($e['hoTen']) ?></div>
                <div class="dept"><i class="fas fa-building" style="font-size:.7rem"></i> <?= htmlspecialchars($e['phongBan'] ?? 'Chưa phân PB') ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="actions">
            <a href="?act=reset" class="btn btn-red"
               onclick="return confirm('Xóa toàn bộ dữ liệu tháng 9 và seed lại? Thao tác này không thể hoàn tác!')">
                <i class="fas fa-trash-alt"></i> Xóa &amp; Seed lại
            </a>
            <a href="?act=append" class="btn btn-blue">
                <i class="fas fa-plus-circle"></i> Chỉ thêm vào (giữ data cũ)
            </a>
            <a href="index.php?page=bao-cao-tong-hop&tuNgay=2026-09-01&denNgay=2026-09-30" class="btn btn-green">
                <i class="fas fa-chart-bar"></i> Xem báo cáo tháng 9
            </a>
            <a href="index.php?page=bao-cao-tong-hop" class="btn btn-gray">
                <i class="fas fa-arrow-left"></i> Về báo cáo
            </a>
        </div>
    </div>

    <!-- Thông tin seed -->
    <div class="card">
        <h2><i class="fas fa-info-circle"></i> Thông tin dữ liệu sẽ tạo</h2>
        <table style="width:100%;border-collapse:collapse;font-size:.85rem">
            <tr style="background:#f8fafc">
                <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-weight:700;color:#64748b">📅 Tháng</td>
                <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0">Tháng 9/2026 (1/9 — 30/9)</td>
            </tr>
            <tr>
                <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-weight:700;color:#64748b">🏖️ Ngày lễ</td>
                <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0">1/9 (Nghỉ bù Quốc khánh), 2/9 (Quốc khánh)</td>
            </tr>
            <tr style="background:#f8fafc">
                <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-weight:700;color:#64748b">📋 Ngày làm việc</td>
                <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0">21 ngày (trừ T7, CN, lễ)</td>
            </tr>
            <tr>
                <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-weight:700;color:#64748b">🕐 Giờ chuẩn</td>
                <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0">Vào: 08:00 — Ra: 17:00</td>
            </tr>
            <tr style="background:#f8fafc">
                <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-weight:700;color:#64748b">⚠️ Vi phạm</td>
                <td style="padding:10px 12px;border-bottom:1px solid #e2e8f0">Đi trễ 10-40%, về sớm 5-25%, vắng 0-3 ngày</td>
            </tr>
            <tr>
                <td style="padding:10px 12px;font-weight:700;color:#64748b">📊 Dữ liệu tạo</td>
                <td style="padding:10px 12px">Chấm công, tablet scans, đơn nghỉ phép, đơn OT, yêu cầu điều chỉnh, bảng duyệt công</td>
            </tr>
        </table>
    </div>

    <!-- Cảnh báo xóa file -->
    <div class="warning-box">
        <i class="fas fa-shield-alt"></i> <strong>Bảo mật:</strong>
        Hãy xóa file này sau khi seed xong!<br>
        <code>D:\xampp\htdocs\quanLyChamCong\seed_sept.php</code>
    </div>
</div>
</body>
</html>
