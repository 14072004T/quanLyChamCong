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

// Define setting metadata with validation rules and descriptions
$settingsMetadata = [
    'ALLOW_QR_CHECKIN' => [
        'name' => 'Cho phép chấm công bằng QR',
        'type' => 'boolean',
        'description' => 'Bật/tắt tính năng chấm công qua mã QR',
        'values' => [true => 'Bật', false => 'Tắt']
    ],
    'ALLOW_OFFLINE_CHECKIN' => [
        'name' => 'Cho phép chấm công ngoài tuyến',
        'type' => 'boolean',
        'description' => 'Cho phép chấm công khi không có kết nối internet',
        'values' => [true => 'Bật', false => 'Tắt']
    ],
    'MAX_CORRECTION_DAYS' => [
        'name' => 'Tối đa ngày chỉnh sửa',
        'type' => 'number',
        'description' => 'Số ngày tối đa được phép chỉnh sửa bản ghi chấm công (phải > 0)',
        'unit' => 'ngày'
    ],
    'DEFAULT_WORK_MINUTES' => [
        'name' => 'Phút làm việc mặc định',
        'type' => 'number',
        'description' => 'Số phút làm việc mặc định mỗi ngày (phải > 0)',
        'unit' => 'phút'
    ],
    'TIMEZONE' => [
        'name' => 'Múi giờ',
        'type' => 'text',
        'description' => 'Múi giờ hệ thống (VD: Asia/Ho_Chi_Minh)',
        'maxlength' => 50
    ],
    'COMPANY_NAME' => [
        'name' => 'Tên công ty',
        'type' => 'text',
        'description' => 'Tên công ty hiển thị trong báo cáo',
        'maxlength' => 255
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

<div class="panel" style="margin-bottom: 20px;">
    <h2><i class="fas fa-cogs"></i> Cấu hình hệ thống</h2>
    <p style="color: #64748b;">Quản lý các cài đặt toàn cục của hệ thống chấm công</p>
</div>

<!-- Success Message -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success" style="margin-bottom: 16px;">
        <i class="fas fa-check-circle"></i>
        <strong><?= htmlspecialchars($success) ?></strong>
    </div>
<?php endif; ?>

<!-- Error Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="margin-bottom: 16px;">
        <i class="fas fa-exclamation-circle"></i>
        <div>
            <strong>Có lỗi xảy ra:</strong>
            <ul style="margin: 8px 0 0 20px; padding: 0;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<!-- Settings Form -->
<div class="panel">
    <h3 style="margin-top: 0; margin-bottom: 20px;">
        <i class="fas fa-sliders-h"></i> Cài đặt hệ thống
    </h3>

    <div style="display: grid; gap: 24px;">
        <?php foreach ($settingsMetadata as $key => $meta): ?>
            <div class="setting-group" style="padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <div style="display: grid; grid-template-columns: 1fr 200px; gap: 16px; align-items: start;">
                    <!-- Left: Label & Description -->
                    <div>
                        <label for="setting_<?= htmlspecialchars($key) ?>" style="font-weight: 600; color: #1e293b; display: block; margin-bottom: 6px;">
                            <?= htmlspecialchars($meta['name']) ?>
                        </label>
                        <p style="color: #64748b; font-size: 13px; margin: 0 0 12px 0;">
                            <?= htmlspecialchars($meta['description']) ?>
                        </p>

                        <!-- Input Field -->
                        <?php if ($meta['type'] === 'boolean'): ?>
                            <select 
                                id="setting_<?= htmlspecialchars($key) ?>"
                                class="setting-input"
                                data-key="<?= htmlspecialchars($key) ?>"
                                style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;"
                            >
                                <option value="">-- Chọn --</option>
                                <option value="1" <?= ($settingValues[$key] === '1' || $settingValues[$key] === 'true') ? 'selected' : '' ?>>Bật</option>
                                <option value="0" <?= ($settingValues[$key] === '0' || $settingValues[$key] === 'false') ? 'selected' : '' ?>>Tắt</option>
                            </select>
                        <?php elseif ($meta['type'] === 'number'): ?>
                            <input 
                                type="number"
                                id="setting_<?= htmlspecialchars($key) ?>"
                                class="setting-input"
                                data-key="<?= htmlspecialchars($key) ?>"
                                value="<?= htmlspecialchars($settingValues[$key]) ?>"
                                min="0"
                                style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; width: 100%; box-sizing: border-box;"
                                placeholder="Nhập số"
                            >
                            <?php if (isset($meta['unit'])): ?>
                                <small style="color: #64748b; display: block; margin-top: 6px;">
                                    Đơn vị: <?= htmlspecialchars($meta['unit']) ?>
                                </small>
                            <?php endif; ?>
                        <?php else: ?>
                            <input 
                                type="text"
                                id="setting_<?= htmlspecialchars($key) ?>"
                                class="setting-input"
                                data-key="<?= htmlspecialchars($key) ?>"
                                value="<?= htmlspecialchars($settingValues[$key]) ?>"
                                maxlength="<?= htmlspecialchars($meta['maxlength'] ?? 255) ?>"
                                style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; width: 100%; box-sizing: border-box;"
                                placeholder="Nhập giá trị"
                            >
                        <?php endif; ?>
                    </div>

                    <!-- Right: Save Button & Status -->
                    <div style="text-align: right;">
                        <button 
                            type="button" 
                            class="btn btn-primary save-setting-btn"
                            data-key="<?= htmlspecialchars($key) ?>"
                            style="background: #3b82f6; color: white; padding: 8px 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; width: 100%;"
                        >
                            <i class="fas fa-save"></i> Lưu
                        </button>
                        <div class="setting-message" data-key="<?= htmlspecialchars($key) ?>" style="margin-top: 8px; font-size: 12px; display: none;"></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Reference Section -->
<div class="panel" style="background: #eff6ff; border-left: 4px solid #0284c7; margin-top: 24px;">
    <h3 style="margin-top: 0; color: #0c4a6e;">
        <i class="fas fa-book"></i> Hướng dẫn cài đặt
    </h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
        <div>
            <h4 style="color: #0c4a6e; margin-top: 0;">Cài đặt Boolean</h4>
            <p style="font-size: 13px; color: #0c4a6e;">
                <strong>Chấp nhận:</strong> 1, 0, true, false, yes, no<br>
                <strong>Ví dụ:</strong> ALLOW_QR_CHECKIN = 1
            </p>
        </div>

        <div>
            <h4 style="color: #0c4a6e; margin-top: 0;">Cài đặt Số</h4>
            <p style="font-size: 13px; color: #0c4a6e;">
                <strong>Yêu cầu:</strong> Số nguyên dương (> 0)<br>
                <strong>Ví dụ:</strong> MAX_CORRECTION_DAYS = 30
            </p>
        </div>

        <div>
            <h4 style="color: #0c4a6e; margin-top: 0;">Cài đặt Văn bản</h4>
            <p style="font-size: 13px; color: #0c4a6e;">
                <strong>Tối đa:</strong> 255 ký tự<br>
                <strong>Ví dụ:</strong> COMPANY_NAME = Công ty ABC
            </p>
        </div>
    </div>
</div>

<script>
// Save individual settings
document.querySelectorAll('.save-setting-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const key = this.dataset.key;
        const inputElement = document.getElementById('setting_' + key);
        const value = inputElement.value;
        const messageDiv = document.querySelector('.setting-message[data-key="' + key + '"]');

        // Basic validation
        if (!value || (value && value.trim() === '')) {
            messageDiv.innerHTML = '<span style="color: #ef4444;"><i class="fas fa-exclamation-circle"></i> Giá trị không được để trống</span>';
            messageDiv.style.display = 'block';
            return;
        }

        this.disabled = true;
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

        const formData = new FormData();
        formData.append('key', key);
        formData.append('value', value);

        fetch('index.php?page=tech-update-settings', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                messageDiv.innerHTML = '<span style="color: #10b981;"><i class="fas fa-check-circle"></i> Lưu thành công</span>';
                messageDiv.style.display = 'block';
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            } else {
                const errorMsg = data.message || (data.errors && data.errors.join(', ')) || 'Lỗi không xác định';
                messageDiv.innerHTML = '<span style="color: #ef4444;"><i class="fas fa-exclamation-circle"></i> ' + errorMsg + '</span>';
                messageDiv.style.display = 'block';
            }
        })
        .catch(err => {
            messageDiv.innerHTML = '<span style="color: #ef4444;"><i class="fas fa-exclamation-circle"></i> Lỗi kết nối</span>';
            messageDiv.style.display = 'block';
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = originalText;
        });
    });
});

// Allow Enter key to save
document.querySelectorAll('.setting-input').forEach(input => {
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const key = this.dataset.key;
            document.querySelector('.save-setting-btn[data-key="' + key + '"]').click();
        }
    });
});
</script>

<style>
.panel {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-info {
    background: #dbeafe;
    color: #0c4a6e;
    border: 1px solid #bfdbfe;
}

.setting-group {
    transition: all 0.2s;
}

.setting-group:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}

.btn {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid transparent;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}

.btn:hover:not(:disabled) {
    opacity: 0.9;
    transform: translateY(-1px);
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #2563eb;
}

select, input[type="text"], input[type="number"] {
    padding: 8px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
}

select:focus, input[type="text"]:focus, input[type="number"]:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

@media (max-width: 768px) {
    .setting-group {
        grid-template-columns: 1fr !important;
    }
    
    .setting-group > div:last-child {
        text-align: left;
    }
}
</style>
