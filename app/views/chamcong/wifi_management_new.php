<?php
// Initialize variables
$wifiList = $wifiList ?? [];
$success = $success ?? null;
$errors = json_decode($errorsJson ?? '[]', true);

// Security: Ensure user is authenticated
if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit;
}
?>

<div class="panel" style="margin-bottom: 20px;">
    <h2><i class="fas fa-wifi"></i> Quản lý WiFi nội bộ</h2>
    <p style="color: #64748b;">Quản lý danh sách WiFi được phép sử dụng cho chấm công</p>
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

<!-- Add WiFi Form Section -->
<div class="panel" style="background: #f0fdf4; border-left: 4px solid #10b981; margin-bottom: 24px;">
    <h3 style="margin-top: 0;"><i class="fas fa-plus-circle" style="color: #10b981;"></i> Thêm WiFi mới</h3>
    
    <form id="addWifiForm" style="display: grid; gap: 12px; max-width: 500px;">
        <div class="form-group">
            <label for="wifi_name">Tên WiFi (SSID) <span style="color: #ef4444;">*</span></label>
            <input 
                type="text" 
                id="wifi_name"
                name="wifi_name" 
                placeholder="VD: OFFICE_NETWORK"
                maxlength="120"
                required
                style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;"
            >
            <small style="color: #64748b;">Nhập tên WiFi SSID (tối đa 120 ký tự)</small>
        </div>

        <div class="form-group">
            <label for="is_active_add">
                <input 
                    type="checkbox" 
                    id="is_active_add"
                    name="is_active" 
                    value="1" 
                    checked
                > Bật hoạt động
            </label>
        </div>

        <button type="submit" class="btn btn-success" id="addWifiBtn">
            <i class="fas fa-plus"></i> Thêm WiFi
        </button>
    </form>
    <div id="addWifiMessage" style="margin-top: 12px; display: none;"></div>
</div>

<!-- WiFi List Section -->
<div class="panel">
    <h3 style="margin-top: 0;"><i class="fas fa-list"></i> Danh sách WiFi (<?= count($wifiList) ?>)</h3>

    <?php if (empty($wifiList)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Chưa có WiFi nào được cấu hình. Vui lòng thêm WiFi mới ở phía trên.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1;">
                        <th style="padding: 12px; text-align: left; font-weight: 600;">Tên WiFi</th>
                        <th style="padding: 12px; text-align: center; font-weight: 600;">Trạng thái</th>
                        <th style="padding: 12px; text-align: center; font-weight: 600;">Ngày tạo</th>
                        <th style="padding: 12px; text-align: center; font-weight: 600;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wifiList as $wifi): ?>
                        <tr id="row-<?= (int)$wifi['id'] ?>" style="border-bottom: 1px solid #e2e8f0; hover-background: #f8fafc;">
                            <td style="padding: 12px;">
                                <strong><?= htmlspecialchars($wifi['wifi_name']) ?></strong>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <?php if ($wifi['is_active']): ?>
                                    <span class="badge" style="background: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                        <i class="fas fa-check"></i> Bật
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">
                                        <i class="fas fa-times"></i> Tắt
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px; text-align: center; font-size: 13px; color: #64748b;">
                                <?= date('d/m/Y H:i', strtotime($wifi['created_at'])) ?>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <!-- Toggle Button -->
                                <button 
                                    type="button" 
                                    class="btn btn-sm" 
                                    style="background: <?= $wifi['is_active'] ? '#fbbf24' : '#3b82f6' ?>; color: white; padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin-right: 4px;"
                                    onclick="toggleWifi(<?= (int)$wifi['id'] ?>)"
                                    title="<?= $wifi['is_active'] ? 'Tắt' : 'Bật' ?>"
                                >
                                    <i class="fas fa-power-off"></i> <?= $wifi['is_active'] ? 'Tắt' : 'Bật' ?>
                                </button>

                                <!-- Edit Button -->
                                <button 
                                    type="button" 
                                    class="btn btn-sm" 
                                    style="background: #0284c7; color: white; padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin-right: 4px;"
                                    onclick="showEditForm(<?= (int)$wifi['id'] ?>, '<?= htmlspecialchars(addslashes($wifi['wifi_name'])) ?>')"
                                    title="Sửa"
                                >
                                    <i class="fas fa-edit"></i> Sửa
                                </button>

                                <!-- Delete Button -->
                                <button 
                                    type="button" 
                                    class="btn btn-sm" 
                                    style="background: #dc2626; color: white; padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;"
                                    onclick="deleteWifi(<?= (int)$wifi['id'] ?>, '<?= htmlspecialchars(addslashes($wifi['wifi_name'])) ?>')"
                                    title="Xóa"
                                >
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Edit WiFi Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); z-index: 1000; overflow-y: auto;">
    <div style="background: white; margin: 50px auto; padding: 24px; border-radius: 8px; max-width: 500px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);">
        <h3 style="margin-top: 0; margin-bottom: 16px;">
            <i class="fas fa-edit"></i> Sửa WiFi
        </h3>
        
        <form id="editWifiForm" style="display: grid; gap: 12px;">
            <input type="hidden" id="edit_wifi_id" name="id">
            
            <div class="form-group">
                <label for="edit_wifi_name">Tên WiFi (SSID) <span style="color: #ef4444;">*</span></label>
                <input 
                    type="text" 
                    id="edit_wifi_name"
                    name="wifi_name" 
                    placeholder="VD: OFFICE_NETWORK"
                    maxlength="120"
                    required
                    style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box;"
                >
            </div>

            <div class="form-group">
                <label for="edit_is_active">
                    <input 
                        type="checkbox" 
                        id="edit_is_active"
                        name="is_active" 
                        value="1"
                    > Bật hoạt động
                </label>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <button type="submit" class="btn btn-primary" style="background: #3b82f6; color: white; padding: 8px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">
                    <i class="fas fa-save"></i> Lưu
                </button>
                <button type="button" class="btn btn-secondary" style="background: #e2e8f0; color: #1e293b; padding: 8px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
            </div>
            
            <div id="editWifiMessage" style="margin-top: 12px;"></div>
        </form>
    </div>
</div>

<script>
// Add WiFi
document.getElementById('addWifiForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const messageDiv = document.getElementById('addWifiMessage');
    const btn = document.getElementById('addWifiBtn');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang thêm...';
    
    fetch('index.php?page=tech-add-wifi', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
            document.getElementById('addWifiForm').reset();
            setTimeout(() => location.reload(), 1500);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ' + (data.message || data.errors?.join(', ')) + '</div>';
        }
    })
    .catch(err => {
        messageDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Lỗi kết nối</div>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus"></i> Thêm WiFi';
    });
});

// Show Edit Form
function showEditForm(id, name) {
    document.getElementById('edit_wifi_id').value = id;
    document.getElementById('edit_wifi_name').value = name;
    document.getElementById('editModal').style.display = 'block';
}

// Close Edit Modal
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.getElementById('editWifiForm').reset();
}

// Edit WiFi
document.getElementById('editWifiForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const messageDiv = document.getElementById('editWifiMessage');
    
    fetch('index.php?page=tech-update-wifi', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
            setTimeout(() => location.reload(), 1500);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ' + (data.message || data.errors?.join(', ')) + '</div>';
        }
    })
    .catch(err => {
        messageDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Lỗi kết nối</div>';
    });
});

// Toggle WiFi
function toggleWifi(id) {
    if (!confirm('Bạn có chắc chắn muốn thay đổi trạng thái WiFi này?')) return;
    
    fetch('index.php?page=tech-toggle-wifi', {
        method: 'POST',
        body: new URLSearchParams({id: id})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Lỗi khi cập nhật');
        }
    })
    .catch(err => alert('Lỗi kết nối'));
}

// Delete WiFi
function deleteWifi(id, name) {
    if (!confirm('Bạn có chắc chắn muốn xóa WiFi "' + name + '" không? Thao tác này không thể hoàn tác.')) return;
    
    fetch('index.php?page=tech-delete-wifi', {
        method: 'POST',
        body: new URLSearchParams({id: id})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Lỗi khi xóa');
        }
    })
    .catch(err => alert('Lỗi kết nối'));
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<style>
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

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
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

.btn:hover {
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

.btn-primary:hover {
    background: #2563eb;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-secondary {
    background: #e2e8f0;
    color: #1e293b;
}

.btn-secondary:hover {
    background: #cbd5e1;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group label {
    font-weight: 500;
    color: #1e293b;
    font-size: 14px;
}

.form-group input[type="text"],
.form-group select {
    padding: 8px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 14px;
}

.form-group input[type="text"]:focus,
.form-group select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table td, .table th {
    padding: 12px;
    text-align: left;
}

.table tr:hover {
    background: #f8fafc;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}
</style>
