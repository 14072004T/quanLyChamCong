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
<div class="tech-container">
<style>
.tech-container { max-width: 1100px; margin: 0 auto; padding: 20px; }
</style>
<div class="tech-header-mini">
    <div style="display: flex; align-items: center; gap: 12px;">
        <i class="fas fa-wifi" style="font-size: 24px; color: #3b82f6;"></i>
        <div>
            <h2 style="margin: 0; font-size: 18px; color: #1e293b;">Quản lý mạng nội bộ</h2>
            <p style="margin: 0; font-size: 13px; color: #64748b;">Cấu hình WiFi và dải IP được phép chấm công</p>
        </div>
    </div>
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

<details class="panel-collapsible" style="margin-bottom: 24px;">
    <summary style="cursor: pointer; font-weight: 600; color: #10b981; display: flex; align-items: center; gap: 8px; list-style: none; padding: 10px 0;">
        <i class="fas fa-plus-circle"></i> Thêm mạng mới
    </summary>
    
    <form id="addWifiForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; padding: 16px; background: #f0fdf4; border-radius: 8px; margin-top: 10px;">
        <div class="form-group">
            <label for="wifi_name">Tên hiển thị <span style="color: #ef4444;">*</span></label>
            <input type="text" id="wifi_name" name="wifi_name" placeholder="VD: Wifi Công ty" maxlength="120" required class="form-input-compact">
        </div>
        <div class="form-group">
            <label for="ssid">SSID</label>
            <input type="text" id="ssid" name="ssid" placeholder="VD: CongTy_5G" maxlength="120" class="form-input-compact">
        </div>
        <div class="form-group">
            <label for="location">Vị trí</label>
            <input type="text" id="location" name="location" placeholder="VD: Tầng 1" maxlength="255" class="form-input-compact">
        </div>
        <div class="form-group">
            <label for="ip_range">Dải IP <span style="color: #ef4444;">*</span></label>
            <input type="text" id="ip_range" name="ip_range" placeholder="VD: 192.168.1" required class="form-input-compact">
        </div>
        <div class="form-group">
            <label for="gateway">Gateway <span style="color: #ef4444;">*</span></label>
            <input type="text" id="gateway" name="gateway" placeholder="VD: 192.168.1.1" required class="form-input-compact">
        </div>
        <div class="form-group" style="display: flex; align-items: center; padding-top: 24px;">
            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 13px;">
                <input type="checkbox" name="is_active" value="1" checked> Bật ngay
            </label>
        </div>
        <div style="grid-column: 1 / -1; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn-success-compact" id="addWifiBtn">
                <i class="fas fa-plus"></i> Thêm mạng
            </button>
        </div>
    </form>
    <div id="addWifiMessage" style="margin-top: 12px; display: none;"></div>
</details>

<!-- WiFi List Section -->
<div class="panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3 style="margin: 0;"><i class="fas fa-list"></i> Danh sách mạng (<?= count($wifiList) ?>)</h3>
        <input type="text" id="searchInput" placeholder="Tìm kiếm mạng..." class="form-input" style="width: 250px; padding: 8px 12px;" onkeyup="filterTable()">
    </div>

    <?php if (empty($wifiList)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Chưa có mạng nào được cấu hình. Vui lòng thêm mạng mới ở phía trên.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table-compact" id="wifiTable">
                <thead>
                    <tr>
                        <th>Mạng / SSID</th>
                        <th>Vị trí</th>
                        <th>Cấu hình IP</th>
                        <th style="text-align: center;">Trạng thái</th>
                        <th style="text-align: center;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($wifiList as $wifi): ?>
                        <tr id="row-<?= (int)($wifi['id'] ?? 0) ?>">
                            <td>
                                <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($wifi['wifi_name'] ?? '') ?></div>
                                <div style="font-size: 11px; color: #64748b;"><?= htmlspecialchars($wifi['ssid'] ?? '-') ?></div>
                            </td>
                            <td style="font-size: 12px; color: #475569;">
                                <?= htmlspecialchars($wifi['location'] ?? '-') ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; font-family: monospace; font-size: 12px;">
                                    <span class="ip-badge" title="Dải IP">IP: <?= htmlspecialchars($wifi['ip_range'] ?? '') ?>.*</span>
                                    <span class="ip-badge" title="Gateway">GW: <?= htmlspecialchars($wifi['gateway'] ?? '') ?></span>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <?php if ((int)($wifi['is_active'] ?? 0)): ?>
                                    <span class="badge-active">Bật</span>
                                <?php else: ?>
                                    <span class="badge-inactive">Tắt</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-group">
                                    <button onclick="toggleWifi(<?= (int)($wifi['id'] ?? 0) ?>)" class="btn-icon-mini <?= (int)($wifi['is_active'] ?? 0) ? 'btn-warn' : 'btn-success' ?>" title="Bật/Tắt">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                    <button onclick="showEditForm(<?= (int)($wifi['id'] ?? 0) ?>)" class="btn-icon-mini btn-primary" title="Sửa">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button onclick="deleteWifi(<?= (int)($wifi['id'] ?? 0) ?>, '<?= htmlspecialchars(addslashes($wifi['wifi_name'] ?? '')) ?>')" class="btn-icon-mini btn-danger" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Edit WiFi Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); z-index: 1000; overflow-y: auto; backdrop-filter: blur(4px);">
    <div style="background: white; margin: 40px auto; padding: 32px; border-radius: 12px; max-width: 650px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">
            <h3 style="margin: 0; font-size: 20px; color: #0f172a;"><i class="fas fa-edit" style="color: #0ea5e9; margin-right: 8px;"></i> Chỉnh sửa thông tin mạng</h3>
            <button onclick="closeEditModal()" style="background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer;"><i class="fas fa-times"></i></button>
        </div>
        
        <form id="editWifiForm" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <input type="hidden" id="edit_wifi_id" name="id">
            
            <div class="form-group">
                <label for="edit_wifi_name">Tên hiển thị <span style="color: #ef4444;">*</span></label>
                <input type="text" id="edit_wifi_name" name="wifi_name" required class="form-input">
            </div>

            <div class="form-group">
                <label for="edit_ssid">SSID (Tên phát WiFi)</label>
                <input type="text" id="edit_ssid" name="ssid" class="form-input">
            </div>



            <div class="form-group">
                <label for="edit_location">Vị trí</label>
                <input type="text" id="edit_location" name="location" class="form-input">
            </div>

            <div class="form-group">
                <label for="edit_ip_range">Dải IP <span style="color: #ef4444;">*</span></label>
                <input type="text" id="edit_ip_range" name="ip_range" required class="form-input">
            </div>

            <div class="form-group">
                <label for="edit_gateway">Gateway <span style="color: #ef4444;">*</span></label>
                <input type="text" id="edit_gateway" name="gateway" required class="form-input">
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="edit_description">Mô tả</label>
                <textarea id="edit_description" name="description" rows="2" class="form-input"></textarea>
            </div>

            <div class="form-group" style="grid-column: 1 / -1;">
                <label for="edit_is_active" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="edit_is_active" name="is_active" value="1" style="width: 16px; height: 16px;">
                    <span>Bật hoạt động</span>
                </label>
            </div>

            <div style="grid-column: 1 / -1; display: flex; gap: 12px; margin-top: 8px;">
                <button type="submit" class="btn btn-primary" style="background: #0ea5e9; color: white; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
                <button type="button" class="btn btn-secondary" style="background: #f1f5f9; color: #475569; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;" onclick="closeEditModal()">
                    Hủy
                </button>
            </div>
            
            <div id="editWifiMessage" style="grid-column: 1 / -1; margin-top: 8px;"></div>
        </form>
    </div>
</div>

<script>


function filterTable() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toLowerCase();
    const table = document.getElementById("wifiTable");
    if(!table) return;
    const tr = table.getElementsByTagName("tr");

    for (let i = 1; i < tr.length; i++) {
        const row = tr[i];
        const textContent = row.textContent || row.innerText;
        if (textContent.toLowerCase().indexOf(filter) > -1) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    }
}

// Add WiFi
document.getElementById('addWifiForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const messageDiv = document.getElementById('addWifiMessage');
    const btn = document.getElementById('addWifiBtn');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    messageDiv.style.display = 'block';
    
    fetch('index.php?page=tech-add-wifi', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
            setTimeout(() => location.reload(), 1000);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ' + (data.message || (data.errors ? data.errors.join('<br>') : 'Có lỗi xảy ra')) + '</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Thêm mạng';
        }
    })
    .catch(err => {
        messageDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Lỗi kết nối máy chủ</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus"></i> Thêm mạng';
    });
});

// Show Edit Form - Securely fetching data
function showEditForm(id) {
    document.getElementById('edit_wifi_id').value = id;
    
    // Fetch details via API to get password securely without HTML exposure
    fetch('index.php?page=tech-get-wifi&id=' + id)
    .then(res => res.json())
    .then(response => {
        if (response.success && response.data) {
            const data = response.data;
            document.getElementById('edit_wifi_name').value = data.wifi_name || '';
            document.getElementById('edit_ssid').value = data.ssid || '';
            document.getElementById('edit_location').value = data.location || '';
            document.getElementById('edit_ip_range').value = data.ip_range || '';
            document.getElementById('edit_gateway').value = data.gateway || '';
            document.getElementById('edit_description').value = data.description || '';
            document.getElementById('edit_is_active').checked = parseInt(data.is_active) === 1;
            
            document.getElementById('editModal').style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent background scroll
        } else {
            alert('Không thể tải thông tin mạng: ' + (response.message || 'Lỗi'));
        }
    })
    .catch(err => {
        alert('Lỗi kết nối khi lấy thông tin mạng');
    });
}

// Close Edit Modal
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('editWifiForm').reset();
}

// Edit WiFi
document.getElementById('editWifiForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const messageDiv = document.getElementById('editWifiMessage');
    const btn = this.querySelector('button[type="submit"]');
    
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
    
    fetch('index.php?page=tech-update-wifi', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
            setTimeout(() => location.reload(), 1000);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ' + (data.message || (data.errors ? data.errors.join('<br>') : 'Có lỗi xảy ra')) + '</div>';
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(err => {
        messageDiv.innerHTML = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Lỗi kết nối</div>';
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
});

// Toggle WiFi
function toggleWifi(id) {
    if (!confirm('Bạn có chắc chắn muốn thay đổi trạng thái mạng này?')) return;
    
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
    if (!confirm('Bạn có chắc chắn muốn xóa mạng "' + name + '" không? Thao tác này không thể hoàn tác.')) return;
    
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
.tech-header-mini { background: white; padding: 16px 20px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.form-input-compact { width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; box-sizing: border-box; transition: all 0.2s; }
.form-input-compact:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.btn-success-compact { background: #10b981; color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.2s; }
.btn-success-compact:hover { background: #059669; transform: translateY(-1px); }
.table-compact { width: 100%; border-collapse: collapse; font-size: 13px; }
.table-compact th { text-align: left; padding: 10px; background: #f8fafc; color: #64748b; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
.table-compact td { padding: 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.ip-badge { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; border: 1px solid #e2e8f0; color: #475569; }
.badge-active { background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.badge-inactive { background: #fee2e2; color: #b91c1c; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.action-group { display: flex; gap: 4px; justify-content: center; }
.btn-icon-mini { width: 28px; height: 28px; border-radius: 6px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; color: white; transition: all 0.2s; }
.btn-icon-mini:hover { transform: scale(1.1); }
.btn-success { background: #10b981; }
.btn-warn { background: #f59e0b; }
.btn-primary { background: #3b82f6; }
.btn-danger { background: #ef4444; }
.panel-collapsible summary::-webkit-details-marker { display: none; }
.panel-collapsible summary:hover { color: #059669; }
.form-group { display: flex; flex-direction: column; margin-bottom: 8px; }
.form-group label { margin-bottom: 4px; font-weight: 500; color: #334155; font-size: 13px; }
.form-input { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
.alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px; display: flex; gap: 10px; }
.alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.alert-info { background: #eff6ff; color: #1e3a8a; border: 1px solid #bfdbfe; }
</style>
</div>
