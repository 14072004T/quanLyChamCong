<?php 
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit();
}

$otRequests  = $otRequests  ?? [];
$successMsg  = $successMsg  ?? '';
$errorMsg    = $errorMsg    ?? '';

// Giá trị bộ lọc hiện tại (để giữ trạng thái form)
$filterStatus   = htmlspecialchars($_GET['status']   ?? '');
$filterEmployee = htmlspecialchars($_GET['employee'] ?? '');
$filterMonth    = htmlspecialchars($_GET['month']    ?? '');

$statusLabels = [
    'pending'  => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Từ chối',
];
$thuViet = ['CN','T2','T3','T4','T5','T6','T7'];
?>
<?php include 'app/views/layouts/header.php'; ?>
<?php include 'app/views/layouts/nav.php'; ?>

<style>
/* ===== MANAGER OT LIST - PREMIUM UI ===== */
.mot-wrapper {
    min-height: 100vh;
    background: linear-gradient(135deg, #f0f4ff 0%, #e8ecf8 100%);
    padding: 24px 16px;
    font-family: 'Inter', sans-serif;
}

.mot-container { max-width: 1200px; margin: 0 auto; }

/* Header */
.mot-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
    padding: 20px 24px;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(12px);
    border-radius: 18px;
    box-shadow: 0 4px 20px rgba(79,110,247,0.08);
    border: 1px solid rgba(255,255,255,0.6);
    flex-wrap: wrap;
}

.mot-header-left { display: flex; align-items: center; gap: 16px; }
.mot-icon {
    width: 56px; height: 56px;
    border-radius: 14px;
    background: linear-gradient(135deg, #4f6ef7 0%, #3b5de7 100%);
    color: white;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    box-shadow: 0 8px 16px rgba(79,110,247,0.25);
}

.mot-header h2 { margin: 0 0 4px; font-size: 20px; font-weight: 700; color: #0f172a; }
.mot-header p  { margin: 0; font-size: 13px; color: #64748b; }

/* Stats row */
.mot-stats {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.mot-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 40px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
    text-decoration: none;
    border: 2px solid transparent;
}

.mot-stat-pill.all    { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
.mot-stat-pill.pending { background: #fffbeb; color: #d97706; border-color: #fde68a; }
.mot-stat-pill.approved{ background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
.mot-stat-pill.rejected{ background: #fef2f2; color: #dc2626; border-color: #fecaca; }
.mot-stat-pill:hover, .mot-stat-pill.active { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.mot-stat-pill.all.active     { background: #2563eb; color: #fff; }
.mot-stat-pill.pending.active { background: #d97706; color: #fff; }
.mot-stat-pill.approved.active{ background: #16a34a; color: #fff; }
.mot-stat-pill.rejected.active{ background: #dc2626; color: #fff; }

/* Filter card */
.mot-filter-card {
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
    border: 1px solid rgba(255,255,255,0.7);
}

.mot-filter-form {
    display: flex;
    gap: 12px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.mot-filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
    min-width: 160px;
}

.mot-filter-group label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.mot-filter-group input,
.mot-filter-group select {
    padding: 10px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 13px;
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    background: #f8fafc;
    transition: all 0.2s;
}

.mot-filter-group input:focus,
.mot-filter-group select:focus {
    outline: none;
    border-color: #4f6ef7;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(79,110,247,0.12);
}

.btn-filter {
    padding: 10px 20px;
    background: linear-gradient(135deg, #4f6ef7, #3b5de7);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    box-shadow: 0 4px 10px rgba(79,110,247,0.2);
    white-space: nowrap;
}

.btn-filter:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(79,110,247,0.3); }

.btn-reset {
    padding: 10px 16px;
    background: #f1f5f9;
    color: #475569;
    border: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-reset:hover { background: #e2e8f0; }

/* Table Card */
.mot-card {
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(10px);
    border-radius: 18px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    border: 1px solid rgba(255,255,255,0.8);
    overflow: hidden;
}

.mot-table {
    width: 100%;
    border-collapse: collapse;
}

.mot-table th {
    padding: 14px 18px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.mot-table td {
    padding: 16px 18px;
    font-size: 14px;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.mot-table tr:last-child td { border-bottom: none; }

.mot-table tr:hover td { background: #f8fafc; }

/* Row highlight by status */
.mot-table tr.row-pending  td:first-child { border-left: 3px solid #f59e0b; }
.mot-table tr.row-approved td:first-child { border-left: 3px solid #10b981; }
.mot-table tr.row-rejected td:first-child { border-left: 3px solid #ef4444; }

/* Employee avatar */
.emp-cell { display: flex; align-items: center; gap: 12px; }
.emp-avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 15px; color: #4338ca;
    flex-shrink: 0;
}
.emp-name { font-weight: 600; color: #0f172a; font-size: 14px; }
.emp-dept { font-size: 11px; color: #64748b; margin-top: 1px; }

/* Badges */
.badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.4px;
}
.badge-pending  { background: #fef3c7; color: #d97706; }
.badge-approved { background: #dcfce7; color: #16a34a; }
.badge-rejected { background: #fee2e2; color: #dc2626; }

/* Hour pill */
.hour-pill {
    background: #eff6ff;
    color: #2563eb;
    padding: 3px 10px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 13px;
}

/* Action buttons */
.btn-act {
    border: none;
    padding: 7px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.btn-approve { background: #10b981; color: white; }
.btn-approve:hover { background: #059669; transform: translateY(-1px); }
.btn-reject  { background: #ef4444; color: white; }
.btn-reject:hover  { background: #dc2626; transform: translateY(-1px); }

/* Alerts */
.mot-alert {
    padding: 14px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 600;
    animation: fadeSlide 0.4s ease;
}
@keyframes fadeSlide { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
.mot-alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
.mot-alert-error   { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

/* Empty state */
.mot-empty {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}
.mot-empty-icon {
    width: 80px; height: 80px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px;
    font-size: 32px;
    color: #cbd5e1;
}

/* Lock notice */
.mot-lock-notice {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 18px;
    background: #fef3c7;
    border: 1px solid #fde68a;
    border-radius: 12px;
    font-size: 13px;
    color: #92400e;
    font-weight: 600;
    margin-bottom: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .mot-table { display: none; }
    .mot-cards-mobile { display: flex !important; flex-direction: column; gap: 14px; padding: 16px; }
    .mot-mobile-card {
        background: #fff;
        border-radius: 14px;
        padding: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        position: relative;
        overflow: hidden;
    }
    .mot-mobile-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
    .mot-mobile-card.st-pending::before  { background: #f59e0b; }
    .mot-mobile-card.st-approved::before { background: #10b981; }
    .mot-mobile-card.st-rejected::before { background: #ef4444; }
    .mot-filter-form { flex-direction: column; }
}
.mot-cards-mobile { display: none; }

@media (min-width: 769px) {
    .mot-cards-mobile { display: none !important; }
}
</style>

<div class="main-container">
    <?php include 'app/views/layouts/sidebar.php'; ?>
    <div class="dashboard-container mot-wrapper">
        <div class="mot-container">

            <!-- Header -->
            <div class="mot-header">
                <div class="mot-header-left">
                    <div class="mot-icon"><i class="fas fa-business-time"></i></div>
                    <div>
                        <h2>Quản lý Đơn Làm Thêm Giờ</h2>
                        <p>Xem và phê duyệt toàn bộ yêu cầu OT gửi đến bạn.</p>
                    </div>
                </div>
                <!-- Tổng số đơn -->
                <?php
                    $countAll      = count($otRequests);
                    $countPending  = count(array_filter($otRequests, fn($r) => $r['trangThai'] === 'pending'));
                    $countApproved = count(array_filter($otRequests, fn($r) => $r['trangThai'] === 'approved'));
                    $countRejected = count(array_filter($otRequests, fn($r) => $r['trangThai'] === 'rejected'));
                ?>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <span style="background:#eff6ff; color:#2563eb; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700;">
                        Tổng: <?= $countAll ?>
                    </span>
                    <span style="background:#fffbeb; color:#d97706; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700;">
                        Chờ: <?= $countPending ?>
                    </span>
                    <span style="background:#f0fdf4; color:#16a34a; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:700;">
                        Duyệt: <?= $countApproved ?>
                    </span>
                </div>
            </div>

            <!-- Alerts -->
            <?php if (!empty($successMsg)): ?>
                <div class="mot-alert mot-alert-success"><i class="fas fa-check-circle fa-lg"></i> <?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="mot-alert mot-alert-error"><i class="fas fa-exclamation-triangle fa-lg"></i> <?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <!-- Lock notice if on day >= 30 -->
            <?php if ((int)date('d') >= 30): ?>
                <div class="mot-lock-notice">
                    <i class="fas fa-lock"></i>
                    Hệ thống đã khóa sổ (ngày <?= date('d') ?>). Không thể duyệt thêm đơn OT trong tháng này.
                </div>
            <?php endif; ?>

            <!-- Filter -->
            <div class="mot-filter-card">
                <form method="GET" action="index.php" class="mot-filter-form">
                    <input type="hidden" name="page" value="manager-ot-requests">

                    <div class="mot-filter-group">
                        <label><i class="fas fa-user"></i> Tên nhân viên</label>
                        <input type="text" name="employee" placeholder="Tìm theo tên..." value="<?= $filterEmployee ?>">
                    </div>

                    <div class="mot-filter-group">
                        <label><i class="fas fa-tag"></i> Trạng thái</label>
                        <select name="status">
                            <option value="">— Tất cả —</option>
                            <option value="pending"  <?= $filterStatus === 'pending'  ? 'selected' : '' ?>>Chờ duyệt</option>
                            <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Đã duyệt</option>
                            <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Từ chối</option>
                        </select>
                    </div>

                    <div class="mot-filter-group">
                        <label><i class="fas fa-calendar"></i> Tháng</label>
                        <input type="month" name="month" value="<?= $filterMonth ?>">
                    </div>

                    <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Lọc</button>
                    <a href="index.php?page=manager-ot-requests" class="btn-reset"><i class="fas fa-redo"></i> Xóa lọc</a>
                </form>
            </div>

            <!-- Table (Desktop) -->
            <div class="mot-card">
                <?php if (empty($otRequests)): ?>
                    <div class="mot-empty">
                        <div class="mot-empty-icon"><i class="fas fa-inbox"></i></div>
                        <p style="font-size:16px; font-weight:600; color:#475569; margin-bottom:4px;">Không có đơn OT nào.</p>
                        <p style="font-size:13px;">Thử thay đổi bộ lọc hoặc kiểm tra lại.</p>
                    </div>
                <?php else: ?>
                    <!-- Desktop table -->
                    <table class="mot-table">
                        <thead>
                            <tr>
                                <th>Nhân viên</th>
                                <th>Ngày OT</th>
                                <th>Số giờ</th>
                                <th>Nội dung</th>
                                <th>Ngày gửi</th>
                                <th>Trạng thái</th>
                                <th style="text-align:right;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($otRequests as $req): ?>
                                <?php
                                    $st  = $req['trangThai'] ?? 'pending';
                                    $dow = (int)date('w', strtotime($req['ngayOT']));
                                    $thuViet2 = ['CN','T2','T3','T4','T5','T6','T7'];
                                    $dayLabel = $thuViet2[$dow] . ' ' . date('d/m/Y', strtotime($req['ngayOT']));
                                ?>
                                <tr class="row-<?= $st ?>">
                                    <td>
                                        <div class="emp-cell">
                                            <div class="emp-avatar"><?= mb_substr($req['employee_name'] ?? '?', 0, 1) ?></div>
                                            <div>
                                                <div class="emp-name"><?= htmlspecialchars($req['employee_name'] ?? '') ?></div>
                                                <div class="emp-dept"><?= htmlspecialchars($req['phongBan'] ?? '') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="color:#1e293b;"><?= $dayLabel ?></strong>
                                    </td>
                                    <td><span class="hour-pill"><?= $req['soGioOT'] ?> giờ</span></td>
                                    <td style="max-width:220px;">
                                        <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px;" title="<?= htmlspecialchars($req['ghiChu']) ?>">
                                            <?= htmlspecialchars($req['ghiChu']) ?>
                                        </div>
                                    </td>
                                    <td style="color:#64748b; font-size:12px; white-space:nowrap;">
                                        <?= date('d/m/Y H:i', strtotime($req['ngayTao'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $st ?>">
                                            <i class="fas <?= $st === 'pending' ? 'fa-clock' : ($st === 'approved' ? 'fa-check' : 'fa-times') ?>"></i>
                                            <?= $statusLabels[$st] ?? $st ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right; white-space:nowrap;">
                                        <?php if ($st === 'pending' && (int)date('d') < 30): ?>
                                            <form method="POST" action="index.php?page=approve-ot-request" style="display:inline-flex; gap:6px;">
                                                <input type="hidden" name="id" value="<?= $req['id'] ?>">
                                                <?php
                                                    // Preserve filter params in redirect
                                                    $qs = http_build_query([
                                                        'status'   => $_GET['status']   ?? '',
                                                        'employee' => $_GET['employee'] ?? '',
                                                        'month'    => $_GET['month']    ?? '',
                                                    ]);
                                                ?>
                                                <input type="hidden" name="redirect_qs" value="<?= htmlspecialchars($qs) ?>">
                                                <button type="submit" name="trangThai" value="approve" class="btn-act btn-approve"
                                                        onclick="return confirm('Bạn chắc chắn muốn DUYỆT đơn OT này?');">
                                                    <i class="fas fa-check"></i> Duyệt
                                                </button>
                                                <button type="submit" name="trangThai" value="reject" class="btn-act btn-reject"
                                                        onclick="return confirm('Bạn chắc chắn muốn TỪ CHỐI đơn OT này?');">
                                                    <i class="fas fa-times"></i> Từ chối
                                                </button>
                                            </form>
                                        <?php elseif ($st !== 'pending'): ?>
                                            <span style="font-size:12px; color:#94a3b8; font-style:italic;">Đã xử lý</span>
                                        <?php else: ?>
                                            <span style="font-size:12px; color:#f59e0b; font-weight:600;"><i class="fas fa-lock"></i> Khóa sổ</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Mobile cards -->
                    <div class="mot-cards-mobile">
                        <?php foreach ($otRequests as $req): ?>
                            <?php
                                $st  = $req['trangThai'] ?? 'pending';
                                $dow = (int)date('w', strtotime($req['ngayOT']));
                                $thuViet2 = ['CN','T2','T3','T4','T5','T6','T7'];
                                $dayLabel = $thuViet2[$dow] . ' ' . date('d/m/Y', strtotime($req['ngayOT']));
                            ?>
                            <div class="mot-mobile-card st-<?= $st ?>">
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                                    <div>
                                        <div class="emp-name"><?= htmlspecialchars($req['employee_name'] ?? '') ?></div>
                                        <div class="emp-dept"><?= htmlspecialchars($req['phongBan'] ?? '') ?></div>
                                    </div>
                                    <span class="badge badge-<?= $st ?>">
                                        <?= $statusLabels[$st] ?? $st ?>
                                    </span>
                                </div>

                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:10px;">
                                    <div style="background:#f8fafc; padding:8px 12px; border-radius:8px;">
                                        <div style="font-size:10px; color:#64748b; margin-bottom:2px;">NGÀY OT</div>
                                        <div style="font-size:13px; font-weight:600;"><?= $dayLabel ?></div>
                                    </div>
                                    <div style="background:#eff6ff; padding:8px 12px; border-radius:8px;">
                                        <div style="font-size:10px; color:#2563eb; margin-bottom:2px;">SỐ GIỜ</div>
                                        <div style="font-size:16px; font-weight:700; color:#2563eb;"><?= $req['soGioOT'] ?> giờ</div>
                                    </div>
                                </div>

                                <div style="font-size:13px; color:#475569; background:#f8fafc; padding:10px 12px; border-radius:8px; margin-bottom:10px;">
                                    <?= htmlspecialchars($req['ghiChu']) ?>
                                </div>

                                <div style="font-size:11px; color:#94a3b8; margin-bottom:10px;">
                                    Gửi lúc: <?= date('d/m/Y H:i', strtotime($req['ngayTao'])) ?>
                                </div>

                                <?php if ($st === 'pending' && (int)date('d') < 30): ?>
                                    <form method="POST" action="index.php?page=approve-ot-request" style="display:flex; gap:8px;">
                                        <input type="hidden" name="id" value="<?= $req['id'] ?>">
                                        <button type="submit" name="trangThai" value="approve" class="btn-act btn-approve" style="flex:1; justify-content:center;"
                                                onclick="return confirm('Bạn chắc chắn muốn DUYỆT đơn OT này?');">
                                            <i class="fas fa-check"></i> Duyệt
                                        </button>
                                        <button type="submit" name="trangThai" value="reject" class="btn-act btn-reject" style="flex:1; justify-content:center;"
                                                onclick="return confirm('Bạn chắc chắn muốn TỪ CHỐI đơn OT này?');">
                                            <i class="fas fa-times"></i> Từ chối
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.mot-alert').forEach(function(a) {
        setTimeout(function() {
            a.style.transition = 'all 0.4s ease';
            a.style.opacity = '0';
            a.style.transform = 'translateY(-8px)';
            setTimeout(function() { a.remove(); }, 400);
        }, 5000);
    });
});
</script>

<?php include 'app/views/layouts/footer.php'; ?>
