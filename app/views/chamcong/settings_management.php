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

// Define setting metadata
$settingsMetadata = [
    'ALLOW_QR_CHECKIN' => [
        'name' => 'Chấm công QR',
        'type' => 'boolean',
        'description' => 'Sử dụng mã QR để chấm công qua di động',
    ],
    'ALLOW_OFFLINE_CHECKIN' => [
        'name' => 'Chấm công Offline',
        'type' => 'boolean',
        'description' => 'Cho phép chấm công khi mất kết nối mạng',
    ],
    'LATE_THRESHOLD_MINUTES' => [
        'name' => 'Ngưỡng đi trễ',
        'type' => 'number',
        'description' => 'Số phút tối đa cho phép vào muộn',
        'unit' => 'phút',
    ],
    'OVERTIME_THRESHOLD_MINUTES' => [
        'name' => 'Ngưỡng tăng ca',
        'type' => 'number',
        'description' => 'Số phút tối thiểu để bắt đầu tính OT',
        'unit' => 'phút',
    ]
];

// Get current values from database
$settingValues = [];
foreach ($settingsMetadata as $key => $meta) {
    $settingValues[$key] = '';
    foreach ($settings as $setting) {
        if ($setting['setting_key'] === $key) {
            $settingValues[$key] = $setting['setting_value'];
            break;
        }
    }
}
?>
<div class="tech-container">
<style>
.tech-container { max-width: 1100px; margin: 0 auto; padding: 20px; }
.panel { background: white; border-radius: 12px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
</style>

<div class="panel" style="margin-bottom: 24px; border-left: 4px solid #3b82f6;">
    <h2 style="margin: 0; font-size: 20px; color: #1e293b;"><i class="fas fa-cogs" style="margin-right: 10px; color: #3b82f6;"></i>Cấu hình hệ thống</h2>
    <p style="margin: 8px 0 0 0; color: #64748b; font-size: 14px;">Thiết lập các tham số vận hành cho hệ thống chấm công</p>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= implode(', ', $errors) ?></div>
<?php endif; ?>

<div class="panel">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
        <?php foreach ($settingsMetadata as $key => $meta): ?>
            <div class="setting-card-mini">
                <div class="setting-header">
                    <label for="setting_<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($meta['name']) ?></label>
                    <p><?= htmlspecialchars($meta['description']) ?></p>
                </div>
                
                <div class="setting-body">
                    <?php if ($meta['type'] === 'boolean'): ?>
                        <select id="setting_<?= htmlspecialchars($key) ?>" class="setting-input-mini" data-key="<?= htmlspecialchars($key) ?>">
                            <option value="1" <?= ($settingValues[$key] == '1' || $settingValues[$key] === 'true') ? 'selected' : '' ?>>Bật</option>
                            <option value="0" <?= ($settingValues[$key] == '0' || $settingValues[$key] === 'false') ? 'selected' : '' ?>>Tắt</option>
                        </select>
                    <?php else: ?>
                        <div style="position: relative; flex: 1;">
                            <input type="number" id="setting_<?= htmlspecialchars($key) ?>" class="setting-input-mini" data-key="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($settingValues[$key]) ?>" min="0">
                            <?php if (isset($meta['unit'])): ?>
                                <span class="unit-tag"><?= htmlspecialchars($meta['unit']) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <button type="button" class="btn-save-mini save-setting-btn" data-key="<?= htmlspecialchars($key) ?>">
                        <i class="fas fa-save"></i>
                    </button>
                </div>
                <div class="setting-message" data-key="<?= htmlspecialchars($key) ?>"></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.querySelectorAll('.save-setting-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const key = this.dataset.key;
        const inputElement = document.getElementById('setting_' + key);
        const value = inputElement.value;
        const messageDiv = document.querySelector('.setting-message[data-key="' + key + '"]');

        if (value === undefined || value === null || value.toString().trim() === '') {
            messageDiv.innerHTML = '<span style="color: #ef4444;">Không để trống</span>';
            return;
        }

        this.disabled = true;
        const originalContent = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const formData = new FormData();
        formData.append('setting_key', key);
        formData.append('setting_value', value);

        fetch('index.php?page=tech-update-settings', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                messageDiv.innerHTML = '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> Đã lưu</span>';
                setTimeout(() => messageDiv.innerHTML = '', 3000);
            } else {
                messageDiv.innerHTML = '<span style="color: #ef4444;">Lỗi: ' + (data.message || 'Không xác định') + '</span>';
            }
        })
        .catch(err => {
            messageDiv.innerHTML = '<span style="color: #ef4444;">Lỗi kết nối</span>';
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = originalContent;
        });
    });
});

document.querySelectorAll('.setting-input-mini').forEach(input => {
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const key = this.dataset.key;
            document.querySelector('.save-setting-btn[data-key="' + key + '"]').click();
        }
    });
});
</script>

<style>
.setting-card-mini { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; transition: all 0.2s; }
.setting-card-mini:hover { border-color: #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.setting-header { margin-bottom: 12px; }
.setting-header label { display: block; font-weight: 600; color: #1e293b; font-size: 14px; margin-bottom: 2px; }
.setting-header p { margin: 0; color: #64748b; font-size: 12px; line-height: 1.4; }
.setting-body { display: flex; gap: 8px; align-items: center; }
.setting-input-mini { flex: 1; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; font-family: inherit; }
.unit-tag { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 11px; color: #94a3b8; pointer-events: none; }
.btn-save-mini { background: #3b82f6; color: white; border: none; border-radius: 6px; width: 34px; height: 34px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.btn-save-mini:hover { background: #2563eb; transform: translateY(-1px); }
.setting-message { font-size: 11px; margin-top: 4px; height: 16px; }
.alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; display: flex; gap: 10px; align-items: center; }
.alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>
</div>
