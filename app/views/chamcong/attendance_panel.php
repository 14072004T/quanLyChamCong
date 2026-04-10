<?php
// Attendance Panel - Network-based Attendance System
// Requires: User to be authenticated
// Features: LAN validation, WiFi whitelist, real-time status

if (!isset($_SESSION['user'])) {
    header('Location: index.php?page=login');
    exit;
}

$user = $_SESSION['user'];
$maND = $user['maND'] ?? $user['maTK'] ?? '';
$hoTen = $user['hoTen'] ?? 'User';
?>

<div class="attendance-panel" style="max-width: 900px; margin: 0 auto; padding: 20px;">
    <!-- Page Header -->
    <div style="margin-bottom: 30px; text-align: center;">
        <h1 style="font-size: 28px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">
            <i class="fas fa-clock"></i> Chấm Công
        </h1>
        <p style="color: #64748b; font-size: 15px;">
            Xin chào, <strong><?= htmlspecialchars($hoTen) ?></strong>
        </p>
    </div>

    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        
        <!-- Network Status Card -->
        <div class="status-card" style="background: white; border-radius: 12px; padding: 24px; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; margin-bottom: 16px; color: #1e293b; font-weight: 600;">
                <i class="fas fa-network-wired"></i> Network Status
            </h3>
            
            <div style="margin-bottom: 16px;">
                <label style="font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                    Server IP Address
                </label>
                <div id="ip-display" style="font-size: 16px; font-weight: 600; color: #0f172a; margin-top: 6px; font-family: monospace;">
                    <span style="color: #94a3b8;">Loading...</span>
                </div>
            </div>

            <div style="margin-bottom: 0;">
                <label style="font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                    Connection Status
                </label>
                <div id="network-validation" style="margin-top: 8px;">
                    <span class="status-badge warning" style="background: #fef3c7; color: #92400e; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 500; display: inline-block;">
                        <i class="fas fa-hourglass-half"></i> Checking...
                    </span>
                </div>
            </div>
        </div>

        <!-- Attendance Status Card -->
        <div class="attendance-status-card" style="background: white; border-radius: 12px; padding: 24px; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0; margin-bottom: 16px; color: #1e293b; font-weight: 600;">
                <i class="fas fa-check-square"></i> Today's Status
            </h3>
            
            <div style="margin-bottom: 16px;">
                <label style="font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                    Check In
                </label>
                <div id="checkin-time" style="font-size: 16px; font-weight: 600; color: #0f172a; margin-top: 6px;">
                    <span style="color: #94a3b8;">—</span>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                    Check Out
                </label>
                <div id="checkout-time" style="font-size: 16px; font-weight: 600; color: #0f172a; margin-top: 6px;">
                    <span style="color: #94a3b8;">—</span>
                </div>
            </div>

            <div style="margin-bottom: 0;">
                <label style="font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">
                    Total Hours
                </label>
                <div id="total-hours" style="font-size: 16px; font-weight: 600; color: #0f172a; margin-top: 6px;">
                    <span style="color: #94a3b8;">—</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 30px;">
        <button id="checkin-btn" class="action-btn btn-checkin" style="padding: 16px; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; background: #10b981; color: white; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
            <i class="fas fa-sign-in-alt"></i> Check In
        </button>
        <button id="checkout-btn" class="action-btn btn-checkout" style="padding: 16px; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; background: #ef4444; color: white; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);">
            <i class="fas fa-sign-out-alt"></i> Check Out
        </button>
    </div>

    <!-- Message Display -->
    <div id="message-container" style="margin-bottom: 30px;">
        <!-- Messages will be inserted here -->
    </div>

    <!-- History Section -->
    <div style="background: white; border-radius: 12px; padding: 24px; border: 2px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0; margin-bottom: 16px; color: #1e293b; font-weight: 600;">
            <i class="fas fa-history"></i> Recent Attendance
        </h3>
        <div id="history-list" style="max-height: 300px; overflow-y: auto;">
            <div style="text-align: center; color: #94a3b8; padding: 20px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 20px;"></i>
            </div>
        </div>
    </div>
</div>

<style>
.attendance-panel {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
}

.status-badge.success {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.error {
    background: #fee2e2;
    color: #991b1b;
}

.status-badge.warning {
    background: #fef3c7;
    color: #92400e;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.action-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-checkin:disabled {
    background: #d1d5db;
}

.btn-checkout:disabled {
    background: #d1d5db;
}

.message {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 12px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.message.success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}

.message.error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

.message.warning {
    background: #fef3c7;
    color: #92400e;
    border-left: 4px solid #f59e0b;
}

.message.info {
    background: #dbeafe;
    color: #0c4a6e;
    border-left: 4px solid #0284c7;
}

#history-list table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

#history-list th {
    background: #f8fafc;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
}

#history-list td {
    padding: 12px;
    border-bottom: 1px solid #e2e8f0;
    color: #1e293b;
}

#history-list tr:hover {
    background: #f8f afc;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiBase = 'index.php?page=';
    const localeStr = 'en-US'; // Can be changed to 'vi-VN' for Vietnamese

    // Initialize on page load
    initializeAttendancePanel();

    // Event listeners
    document.getElementById('checkin-btn').addEventListener('click', performCheckIn);
    document.getElementById('checkout-btn').addEventListener('click', performCheckOut);

    /**
     * Initialize the attendance panel
     */
    function initializeAttendancePanel() {
        validateNetwork();
        loadTodayAttendance();
        loadRecentHistory();

        // Refresh every 60 seconds
        setInterval(() => {
            validateNetwork();
            loadTodayAttendance();
        }, 60000);
    }

    /**
     * Validate network access (INFORMATIONAL ONLY - does NOT block)
     * Actual IP validation happens during clock-in/out submission only
     */
    function validateNetwork() {
        fetch(apiBase + 'attendance-validate-network', {
            method: 'GET'
        })
        .then(res => res.json())
        .then(data => {
            // Display IP
            const ipDisplay = document.getElementById('ip-display');
            ipDisplay.innerHTML = `<code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 14px;">${data.ip || 'Unknown'}</code>`;

            // Display status (INFORMATIONAL ONLY - does NOT disable buttons)
            const validationDiv = document.getElementById('network-validation');
            if (data.is_allowed) {
                validationDiv.innerHTML = `
                    <span class="status-badge success" style="background: #d1fae5; color: #065f46; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 500; display: inline-block;">
                        <i class="fas fa-check-circle"></i> Mạng nội bộ - có thể chấm công
                    </span>
                `;
            } else {
                validationDiv.innerHTML = `
                    <span class="status-badge warning" style="background: #fed7aa; color: #92400e; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 500; display: inline-block;">
                        <i class="fas fa-wifi"></i> Kết nối từ bên ngoài - vui lòng kết nối WiFi công ty để chấm công
                    </span>
                `;
            }

            // NOTE: Buttons are NOT disabled - user can still try to clock in
            // If not in allowed network, they will get error message during submission
            // This allows users to see the UI and understand what's needed
        })
        .catch(err => {
            console.error('Network validation error:', err);
            // Silent fail - do not block UI if network check fails
        });
    }

    /**
     * Load today's attendance times
     */
    function loadTodayAttendance() {
        fetch(apiBase + 'attendance-today', {
            method: 'GET'
        })
        .then(res => res.json())
        .then(data => {
            const checkinTime = document.getElementById('checkin-time');
            const checkoutTime = document.getElementById('checkout-time');
            const totalHours = document.getElementById('total-hours');

            if (data.success) {
                checkinTime.innerHTML = data.checkIn 
                    ? `<time>${data.checkIn.split(' ')[1]}</time>` 
                    : '<span style="color: #94a3b8;">—</span>';
                checkoutTime.innerHTML = data.checkOut 
                    ? `<time>${data.checkOut.split(' ')[1]}</time>` 
                    : '<span style="color: #94a3b8;">—</span>';
                totalHours.innerHTML = data.total_hours > 0 
                    ? `<span>${data.total_hours} hours</span>` 
                    : '<span style="color: #94a3b8;">—</span>';
            }
        })
        .catch(err => console.error('Error loading today attendance:', err));
    }

    /**
     * Load recent attendance history
     */
    function loadRecentHistory() {
        fetch(apiBase + 'attendance-history?limit=10', {
            method: 'GET'
        })
        .then(res => res.json())
        .then(data => {
            const historyList = document.getElementById('history-list');
            
            if (data.success && data.data.length > 0) {
                let html = `
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                data.data.forEach(record => {
                    html += `
                        <tr>
                            <td>${record.date}</td>
                            <td>${record.checkIn || '—'}</td>
                            <td>${record.checkOut || '—'}</td>
                            <td>${record.hours > 0 ? record.hours + ' h' : '—'}</td>
                        </tr>
                    `;
                });
                
                html += `
                        </tbody>
                    </table>
                `;
                historyList.innerHTML = html;
            } else {
                historyList.innerHTML = '<div style="text-align: center; color: #94a3b8; padding: 20px;">No attendance records found</div>';
            }
        })
        .catch(err => {
            console.error('Error loading history:', err);
            document.getElementById('history-list').innerHTML = '<div style="text-align: center; color: #94a3b8; padding: 20px;">Error loading history</div>';
        });
    }

    /**
     * Perform check in (server-side validation only)
     */
    function performCheckIn() {
        const btn = document.getElementById('checkin-btn');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang chấm công...';

        fetch(apiBase + 'attendance-check-in', {
            method: 'POST'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✓ Chấm công vào thành công!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showMessage(data.message || 'Chấm công vào thất bại', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Check In';
            }
        })
        .catch(err => {
            showMessage('Lỗi kết nối khi chấm công', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Check In';
        });
    }

    /**
     * Perform check out (server-side validation only)
     */
    function performCheckOut() {
        const btn = document.getElementById('checkout-btn');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang chấm công...';

        fetch(apiBase + 'attendance-check-out', {
            method: 'POST'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showMessage('✓ Chấm công ra thành công!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showMessage(data.message || 'Chấm công ra thất bại', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Check Out';
            }
        })
        .catch(err => {
            showMessage('Lỗi kết nối khi chấm công', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Check Out';
        });
    }

    /**
     * Show message to user
     */
    function showMessage(text, type = 'info') {
        const container = document.getElementById('message-container');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.innerHTML = `
            <div style="flex: 1;">
                <strong>${text}</strong>
            </div>
            <button style="background: none; border: none; color: inherit; cursor: pointer; font-size: 18px; padding: 0;" onclick="this.parentElement.style.display='none';">
                ×
            </button>
        `;
        container.appendChild(messageDiv);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (messageDiv.parentElement) {
                messageDiv.remove();
            }
        }, 5000);
    }

    /**
     * Update button states based on network validity
     */
    // NOTE: Buttons are no longer disabled based on network status
    // Users can attempt to clock in from any network
    // IP validation happens during submission - inline error message if not in allowed network
});
</script>
