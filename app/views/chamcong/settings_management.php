<?php
// Initialize variables
$settings = $settings ?? [];
$success = $success ?? null;
$errors = json_decode($errorsJson ?? '[]', true);

// Security: Ensure user is authenticated
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit;
}

// Define setting metadata with icons and accent colors
$settingsMetadata = [
    'LATE_THRESHOLD_MINUTES' => [
        'name'    => 'Ngưỡng đi trễ',
        'type'    => 'number',
        'moTa'    => 'Số phút tối đa cho phép vào muộn. Vượt quá ngưỡng này sẽ bị ghi nhận là trễ giờ.',
        'unit'    => 'phút',
        'default' => '15',
        'icon'    => 'fa-clock',
        'color'   => '#f59e0b',
        'bg'      => '#fffbeb',
        'border'  => '#fde68a',
        'min'     => 0,
        'max'     => 120,
    ],
    'OVERTIME_THRESHOLD_MINUTES' => [
        'name'    => 'Ngưỡng tăng ca (OT)',
        'type'    => 'number',
        'moTa'    => 'Số phút làm thêm tối thiểu để bắt đầu tính tăng ca (OT). Dưới ngưỡng này không tính OT.',
        'unit'    => 'phút',
        'default' => '30',
        'icon'    => 'fa-bolt',
        'color'   => '#8b5cf6',
        'bg'      => '#faf5ff',
        'border'  => '#ddd6fe',
        'min'     => 0,
        'max'     => 240,
    ],
    'MAX_CORRECTION_DAYS' => [
        'name'    => 'Thời gian điều chỉnh công',
        'type'    => 'number',
        'moTa'    => 'Số ngày tối đa kể từ ngày làm việc mà nhân viên được phép gửi yêu cầu chỉnh sửa chấm công.',
        'unit'    => 'ngày',
        'default' => '7',
        'icon'    => 'fa-calendar-check',
        'color'   => '#10b981',
        'bg'      => '#f0fdf4',
        'border'  => '#bbf7d0',
        'min'     => 1,
        'max'     => 90,
    ],
    'SESSION_TIMEOUT_MINUTES' => [
        'name'    => 'Thời gian hết phiên',
        'type'    => 'number',
        'moTa'    => 'Số phút không hoạt động trước khi hệ thống tự động đăng xuất người dùng vì lý do bảo mật.',
        'unit'    => 'phút',
        'default' => '60',
        'icon'    => 'fa-shield-alt',
        'color'   => '#3b82f6',
        'bg'      => '#eff6ff',
        'border'  => '#bfdbfe',
        'min'     => 5,
        'max'     => 480,
    ],
    'MAX_LOGIN_ATTEMPTS' => [
        'name'    => 'Số lần đăng nhập sai',
        'type'    => 'number',
        'moTa'    => 'Số lần nhập sai mật khẩu tối đa. Sau khi đạt ngưỡng, tài khoản sẽ bị khóa tự động.',
        'unit'    => 'lần',
        'default' => '5',
        'icon'    => 'fa-lock',
        'color'   => '#ef4444',
        'bg'      => '#fef2f2',
        'border'  => '#fecaca',
        'min'     => 1,
        'max'     => 20,
    ],
];

// Get current values from database
$settingValues = [];
foreach ($settingsMetadata as $key => $meta) {
    $val = null;
    foreach ($settings as $setting) {
        if ($setting['tenCaiDat'] === $key) {
            $val = $setting['giaTri'];
            break;
        }
    }
    $settingValues[$key] = ($val !== null && $val !== '') ? $val : $meta['default'];
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.sett-wrap {
    max-width: 1100px;
    margin: 0 auto;
    padding: 24px 20px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ── PAGE HEADER ── */
.sett-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 18px;
    padding: 28px 32px;
    margin-bottom: 28px;
    box-shadow: 0 10px 30px -8px rgba(15,23,42,.3);
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
    overflow: hidden;
}
.sett-header::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(59,130,246,.18) 0%, transparent 70%);
    pointer-events: none;
}
.sett-header-icon {
    width: 56px; height: 56px;
    background: linear-gradient(135deg, #3b82f6, #6366f1);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; color: white;
    box-shadow: 0 4px 15px rgba(59,130,246,.4);
    flex-shrink: 0;
}
.sett-header-text h2 {
    margin: 0 0 5px;
    font-size: 22px; font-weight: 800;
    color: #fff;
    letter-spacing: -0.4px;
}
.sett-header-text p {
    margin: 0;
    color: #94a3b8;
    font-size: 14px;
}
.sett-header-badge {
    margin-left: auto;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 20px;
    padding: 6px 14px;
    color: #cbd5e1;
    font-size: 12px;
    font-weight: 600;
    display: flex; align-items: center; gap: 6px;
    flex-shrink: 0;
}
.sett-header-badge i { color: #4ade80; font-size: 8px; }

/* ── ALERT MESSAGES ── */
.sett-alert {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 18px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 20px;
    border: 1px solid;
}
.sett-alert-success {
    background: #f0fdf4; color: #166534;
    border-color: #bbf7d0;
}
.sett-alert-error {
    background: #fef2f2; color: #991b1b;
    border-color: #fecaca;
}

/* ── SECTION LABEL ── */
.sett-section-label {
    font-size: 11px; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; color: #94a3b8;
    margin-bottom: 14px;
    display: flex; align-items: center; gap: 8px;
}
.sett-section-label::after {
    content: '';
    flex: 1; height: 1px;
    background: #e2e8f0;
}

/* ── SETTINGS GRID ── */
.sett-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 18px;
    margin-bottom: 32px;
}

/* ── SETTING CARD ── */
.sett-card {
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    padding: 22px;
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
}
.sett-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 4px; height: 100%;
    background: var(--card-color, #3b82f6);
    border-radius: 16px 0 0 16px;
}
.sett-card:hover {
    border-color: var(--card-border, #bfdbfe);
    box-shadow: 0 8px 24px -4px rgba(0,0,0,.08);
    transform: translateY(-2px);
}
.sett-card.saving {
    border-color: var(--card-border, #bfdbfe);
    box-shadow: 0 0 0 3px rgba(59,130,246,.12);
}
.sett-card.saved {
    border-color: #bbf7d0;
    box-shadow: 0 0 0 3px rgba(16,185,129,.12);
}
.sett-card.error-state {
    border-color: #fecaca;
    box-shadow: 0 0 0 3px rgba(239,68,68,.12);
}

/* Card top row */
.sett-card-top {
    display: flex; align-items: flex-start; gap: 14px;
    margin-bottom: 16px;
}
.sett-card-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: var(--card-bg, #eff6ff);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    color: var(--card-color, #3b82f6);
    flex-shrink: 0;
}
.sett-card-info { flex: 1; min-width: 0; }
.sett-card-name {
    font-size: 15px; font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
    line-height: 1.3;
}
.sett-card-desc {
    font-size: 12px; color: #64748b;
    line-height: 1.5;
}

/* Input row */
.sett-input-row {
    display: flex; align-items: center; gap: 10px;
}
.sett-input-wrap {
    flex: 1;
    position: relative;
}
.sett-input {
    width: 100%;
    padding: 10px 50px 10px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 700;
    color: #1e293b;
    background: #f8fafc;
    transition: all 0.2s ease;
    box-sizing: border-box;
    font-family: inherit;
}
.sett-input:focus {
    outline: none;
    border-color: var(--card-color, #3b82f6);
    background: #fff;
    box-shadow: 0 0 0 3px rgba(59,130,246,.12);
}
.sett-unit {
    position: absolute;
    right: 12px; top: 50%;
    transform: translateY(-50%);
    font-size: 11px; font-weight: 700;
    color: var(--card-color, #3b82f6);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    pointer-events: none;
    background: var(--card-bg, #eff6ff);
    padding: 2px 6px;
    border-radius: 6px;
}

/* Save button */
.sett-save-btn {
    width: 42px; height: 42px;
    border-radius: 11px;
    border: none;
    background: var(--card-color, #3b82f6);
    color: #fff;
    cursor: pointer;
    font-size: 15px;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
}
.sett-save-btn:hover:not(:disabled) {
    filter: brightness(1.1);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,.18);
}
.sett-save-btn:disabled {
    opacity: 0.7;
    cursor: default;
}

/* Status message */
.sett-status {
    margin-top: 10px;
    min-height: 20px;
    font-size: 12px; font-weight: 600;
    display: flex; align-items: center; gap: 6px;
    transition: all 0.3s ease;
}
.sett-status.ok   { color: #10b981; }
.sett-status.fail { color: #ef4444; }

/* Range hint */
.sett-range-hint {
    margin-top: 8px;
    font-size: 11px; color: #94a3b8;
    display: flex; align-items: center; gap: 4px;
}

/* ── INFO PANEL ── */
.sett-info-panel {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border: 1px solid #bae6fd;
    border-radius: 14px;
    padding: 18px 22px;
    display: flex; gap: 14px; align-items: flex-start;
}
.sett-info-icon {
    width: 36px; height: 36px;
    background: #0ea5e9;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 16px;
    flex-shrink: 0;
}
.sett-info-text h4 {
    margin: 0 0 4px;
    font-size: 14px; font-weight: 700;
    color: #0c4a6e;
}
.sett-info-text p {
    margin: 0;
    font-size: 13px; color: #0369a1;
    line-height: 1.55;
}
</style>

<div class="sett-wrap">

    <!-- Page Header -->
    <div class="sett-header">
        <div class="sett-header-icon">
            <i class="fas fa-sliders-h"></i>
        </div>
        <div class="sett-header-text">
            <h2>Cấu hình Hệ thống</h2>
            <p>Thiết lập các tham số vận hành & bảo mật cho hệ thống chấm công</p>
        </div>
        <div class="sett-header-badge">
            <i class="fas fa-circle"></i> Hệ thống đang hoạt động
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($success)): ?>
        <div class="sett-alert sett-alert-success">
            <i class="fas fa-check-circle" style="font-size:18px;"></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="sett-alert sett-alert-error">
            <i class="fas fa-exclamation-circle" style="font-size:18px;"></i>
            <span><?= implode(', ', array_map('htmlspecialchars', $errors)) ?></span>
        </div>
    <?php endif; ?>

    <!-- Section Label -->
    <div class="sett-section-label">
        <i class="fas fa-cog"></i> Tham số hệ thống
    </div>

    <!-- Settings Cards Grid -->
    <div class="sett-grid">
        <?php foreach ($settingsMetadata as $key => $meta): ?>
        <div class="sett-card"
             id="card_<?= htmlspecialchars($key) ?>"
             style="--card-color:<?= $meta['color'] ?>;--card-bg:<?= $meta['bg'] ?>;--card-border:<?= $meta['border'] ?>;">

            <div class="sett-card-top">
                <div class="sett-card-icon">
                    <i class="fas <?= $meta['icon'] ?>"></i>
                </div>
                <div class="sett-card-info">
                    <div class="sett-card-name"><?= htmlspecialchars($meta['name']) ?></div>
                    <div class="sett-card-desc"><?= htmlspecialchars($meta['moTa']) ?></div>
                </div>
            </div>

            <div class="sett-input-row">
                <div class="sett-input-wrap">
                    <input
                        type="number"
                        id="sett_<?= htmlspecialchars($key) ?>"
                        class="sett-input"
                        data-key="<?= htmlspecialchars($key) ?>"
                        value="<?= htmlspecialchars($settingValues[$key]) ?>"
                        min="<?= $meta['min'] ?? 0 ?>"
                        max="<?= $meta['max'] ?? 9999 ?>"
                    >
                    <span class="sett-unit"><?= htmlspecialchars($meta['unit']) ?></span>
                </div>
                <button
                    class="sett-save-btn"
                    data-key="<?= htmlspecialchars($key) ?>"
                    title="Lưu cài đặt"
                >
                    <i class="fas fa-check"></i>
                </button>
            </div>

            <?php if (isset($meta['min']) || isset($meta['max'])): ?>
            <div class="sett-range-hint">
                <i class="fas fa-info-circle"></i>
                Khoảng cho phép: <?= $meta['min'] ?? 0 ?> – <?= $meta['max'] ?? '∞' ?> <?= htmlspecialchars($meta['unit']) ?>
            </div>
            <?php endif; ?>

            <div class="sett-status" id="status_<?= htmlspecialchars($key) ?>"></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Info Panel -->
    <div class="sett-info-panel">
        <div class="sett-info-icon">
            <i class="fas fa-info"></i>
        </div>
        <div class="sett-info-text">
            <h4>Lưu ý khi thay đổi cài đặt</h4>
            <p>Các tham số được áp dụng ngay lập tức sau khi lưu. Thay đổi ngưỡng đi trễ và OT ảnh hưởng đến cách tính công từ lần chấm công tiếp theo. Thay đổi số lần đăng nhập sai và thời gian hết phiên có hiệu lực ngay với tất cả phiên đăng nhập hiện tại.</p>
        </div>
    </div>

</div>

<script>
(function() {
    // Attach save handlers
    document.querySelectorAll('.sett-save-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            saveSetting(this.dataset.key);
        });
    });

    // Allow Enter key to save
    document.querySelectorAll('.sett-input').forEach(function(input) {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') saveSetting(this.dataset.key);
        });
    });

    function saveSetting(key) {
        var input   = document.getElementById('sett_' + key);
        var card    = document.getElementById('card_' + key);
        var statusEl = document.getElementById('status_' + key);
        var btn     = card.querySelector('.sett-save-btn');
        var value   = input ? input.value.trim() : '';

        if (value === '') {
            showStatus(statusEl, 'fail', 'Không được để trống');
            card.classList.add('error-state');
            setTimeout(function(){ card.classList.remove('error-state'); }, 2000);
            return;
        }

        // Disable button + show spinner
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        card.classList.add('saving');
        card.classList.remove('saved', 'error-state');
        statusEl.innerHTML = '';
        statusEl.className = 'sett-status';

        var formData = new FormData();
        formData.append('tenCaiDat', key);
        formData.append('giaTri', value);

        fetch('index.php?page=tech-update-settings', {
            method: 'POST',
            body: formData
        })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i>';
            card.classList.remove('saving');

            if (data.success) {
                card.classList.add('saved');
                showStatus(statusEl, 'ok', 'Đã lưu thành công');
                setTimeout(function(){
                    card.classList.remove('saved');
                    statusEl.innerHTML = '';
                    statusEl.className = 'sett-status';
                }, 3000);
            } else {
                card.classList.add('error-state');
                showStatus(statusEl, 'fail', 'Lỗi: ' + (data.message || 'Không xác định'));
                setTimeout(function(){ card.classList.remove('error-state'); }, 3000);
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i>';
            card.classList.remove('saving');
            card.classList.add('error-state');
            showStatus(statusEl, 'fail', 'Lỗi kết nối máy chủ');
            setTimeout(function(){ card.classList.remove('error-state'); }, 3000);
        });
    }

    function showStatus(el, type, msg) {
        var icon = type === 'ok'
            ? '<i class="fas fa-check-circle"></i>'
            : '<i class="fas fa-times-circle"></i>';
        el.className = 'sett-status ' + type;
        el.innerHTML = icon + ' ' + msg;
    }
})();
</script>
