<?php
require_once 'app/models/ketNoi.php';

class ChamCongModel
{
    private $conn;

    public function __construct()
    {
        $db = new KetNoi();
        $this->conn = $db->connect();
        $this->ensureTables();
    }

    private function ensureTables()
    {
        $this->conn->query("
            CREATE TABLE IF NOT EXISTS attendance_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                maND INT NOT NULL,
                action ENUM('IN', 'OUT') NOT NULL,
                method ENUM('LAN', 'QR') NOT NULL DEFAULT 'LAN',
                wifi_name VARCHAR(120) DEFAULT NULL,
                note VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS attendance_corrections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                maND INT NOT NULL,
                attendance_date DATE NOT NULL,
                old_time DATETIME DEFAULT NULL,
                new_time DATETIME NOT NULL,
                reason TEXT NOT NULL,
                status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                hr_note VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS attendance_wifi (
                id INT AUTO_INCREMENT PRIMARY KEY,
                wifi_name VARCHAR(120) NOT NULL UNIQUE,
                ip_range VARCHAR(50) DEFAULT NULL,
                gateway VARCHAR(50) DEFAULT NULL,
                description VARCHAR(255) DEFAULT NULL,
                ssid VARCHAR(120) DEFAULT NULL,
                password VARCHAR(120) DEFAULT NULL,
                location VARCHAR(255) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Add missing columns if they don't exist (for existing databases)
        $this->conn->query("ALTER TABLE attendance_wifi ADD COLUMN IF NOT EXISTS ip_range VARCHAR(50) DEFAULT NULL");
        $this->conn->query("ALTER TABLE attendance_wifi ADD COLUMN IF NOT EXISTS gateway VARCHAR(50) DEFAULT NULL");
        $this->conn->query("ALTER TABLE attendance_wifi ADD COLUMN IF NOT EXISTS description VARCHAR(255) DEFAULT NULL");
        $this->conn->query("ALTER TABLE attendance_wifi ADD COLUMN IF NOT EXISTS ssid VARCHAR(120) DEFAULT NULL");
        $this->conn->query("ALTER TABLE attendance_wifi ADD COLUMN IF NOT EXISTS password VARCHAR(120) DEFAULT NULL");
        $this->conn->query("ALTER TABLE attendance_wifi ADD COLUMN IF NOT EXISTS location VARCHAR(255) DEFAULT NULL");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS attendance_shifts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                shift_name VARCHAR(100) NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->query(" 
            CREATE TABLE IF NOT EXISTS attendance_employee_shift (
                id INT AUTO_INCREMENT PRIMARY KEY,
                maND INT NOT NULL,
                shift_id INT NOT NULL,
                effective_from DATE NOT NULL,
                effective_to DATE DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->query(" 
            CREATE TABLE IF NOT EXISTS attendance_monthly_approval (
                id INT AUTO_INCREMENT PRIMARY KEY,
                month_key CHAR(7) NOT NULL,
                department VARCHAR(120) NOT NULL DEFAULT '',
                hr_sender_id INT NOT NULL,
                manager_approver_id INT DEFAULT NULL,
                status ENUM('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
                submitted_at DATETIME DEFAULT NULL,
                approved_at DATETIME DEFAULT NULL,
                note VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_month_dept (month_key, department)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->conn->query("ALTER TABLE attendance_monthly_approval ADD COLUMN IF NOT EXISTS department VARCHAR(120) NOT NULL DEFAULT ''");
        $this->conn->query("UPDATE attendance_monthly_approval SET department = '' WHERE department IS NULL");

        $oldIndex = $this->conn->query("SHOW INDEX FROM attendance_monthly_approval WHERE Key_name = 'uk_month_key'");
        if ($oldIndex && $oldIndex->num_rows > 0) {
            $this->conn->query("ALTER TABLE attendance_monthly_approval DROP INDEX uk_month_key");
        }
        $newIndex = $this->conn->query("SHOW INDEX FROM attendance_monthly_approval WHERE Key_name = 'uk_month_dept'");
        if ($newIndex && $newIndex->num_rows === 0) {
            $this->conn->query("ALTER TABLE attendance_monthly_approval ADD UNIQUE KEY uk_month_dept (month_key, department)");
        }

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS leave_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                maND INT NOT NULL,
                leave_date DATE NOT NULL,
                leave_type VARCHAR(50) NOT NULL DEFAULT 'annual',
                is_half_day TINYINT(1) NOT NULL DEFAULT 0,
                reason TEXT DEFAULT NULL,
                status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                manager_note VARCHAR(255) DEFAULT NULL,
                manager_approver_id INT DEFAULT NULL,
                approved_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->conn->query("ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS leave_type VARCHAR(50) NOT NULL DEFAULT 'annual'");
        $this->conn->query("ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS is_half_day TINYINT(1) NOT NULL DEFAULT 0");
        $this->conn->query("ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS manager_note VARCHAR(255) DEFAULT NULL");
        $this->conn->query("ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS manager_approver_id INT DEFAULT NULL");
        $this->conn->query("ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS approved_at DATETIME DEFAULT NULL");
        $this->conn->query("ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS ot_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                maND INT NOT NULL,
                ot_date DATE NOT NULL,
                start_time TIME DEFAULT NULL,
                end_time TIME DEFAULT NULL,
                hours DECIMAL(5,2) NOT NULL DEFAULT 0,
                reason TEXT DEFAULT NULL,
                status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                manager_note VARCHAR(255) DEFAULT NULL,
                manager_approver_id INT DEFAULT NULL,
                approved_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->conn->query("ALTER TABLE ot_requests ADD COLUMN IF NOT EXISTS manager_note VARCHAR(255) DEFAULT NULL");
        $this->conn->query("ALTER TABLE ot_requests ADD COLUMN IF NOT EXISTS manager_approver_id INT DEFAULT NULL");
        $this->conn->query("ALTER TABLE ot_requests ADD COLUMN IF NOT EXISTS approved_at DATETIME DEFAULT NULL");
        $this->conn->query("ALTER TABLE ot_requests ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS shift_change_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                maND INT NOT NULL,
                request_date DATE NOT NULL,
                current_shift_id INT DEFAULT NULL,
                requested_shift_id INT DEFAULT NULL,
                current_shift_name VARCHAR(100) DEFAULT NULL,
                requested_shift_name VARCHAR(100) DEFAULT NULL,
                reason TEXT DEFAULT NULL,
                status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                manager_note VARCHAR(255) DEFAULT NULL,
                manager_approver_id INT DEFAULT NULL,
                approved_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->conn->query("ALTER TABLE shift_change_requests ADD COLUMN IF NOT EXISTS current_shift_id INT DEFAULT NULL");
        $this->conn->query("ALTER TABLE shift_change_requests ADD COLUMN IF NOT EXISTS requested_shift_id INT DEFAULT NULL");
        $this->conn->query("ALTER TABLE shift_change_requests ADD COLUMN IF NOT EXISTS current_shift_name VARCHAR(100) DEFAULT NULL");
        $this->conn->query("ALTER TABLE shift_change_requests ADD COLUMN IF NOT EXISTS requested_shift_name VARCHAR(100) DEFAULT NULL");
        $this->conn->query("ALTER TABLE shift_change_requests ADD COLUMN IF NOT EXISTS manager_note VARCHAR(255) DEFAULT NULL");
        $this->conn->query("ALTER TABLE shift_change_requests ADD COLUMN IF NOT EXISTS manager_approver_id INT DEFAULT NULL");
        $this->conn->query("ALTER TABLE shift_change_requests ADD COLUMN IF NOT EXISTS approved_at DATETIME DEFAULT NULL");
        $this->conn->query("ALTER TABLE shift_change_requests ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS don_nghi_phep (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                leave_type VARCHAR(50) NOT NULL DEFAULT 'personal',
                from_date DATE NOT NULL,
                to_date DATE NOT NULL,
                reason TEXT NOT NULL,
                evidence_file VARCHAR(255) DEFAULT NULL,
                status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                approved_by INT DEFAULT NULL,
                approved_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        // Migration for existing tables
        $this->conn->query("ALTER TABLE don_nghi_phep ADD COLUMN IF NOT EXISTS leave_type VARCHAR(50) NOT NULL DEFAULT 'personal'");
        $this->conn->query("ALTER TABLE don_nghi_phep ADD COLUMN IF NOT EXISTS evidence_file VARCHAR(255) DEFAULT NULL");
        $this->conn->query("ALTER TABLE don_nghi_phep ADD COLUMN IF NOT EXISTS approved_by INT DEFAULT NULL");
        $this->conn->query("ALTER TABLE don_nghi_phep ADD COLUMN IF NOT EXISTS approved_at DATETIME DEFAULT NULL");

        // Migration: attendance_corrections — add nullable columns for edit request feature
        $this->conn->query("ALTER TABLE attendance_corrections ADD COLUMN IF NOT EXISTS proposed_checkin DATETIME DEFAULT NULL");
        $this->conn->query("ALTER TABLE attendance_corrections ADD COLUMN IF NOT EXISTS proposed_checkout DATETIME DEFAULT NULL");
        $this->conn->query("ALTER TABLE attendance_corrections ADD COLUMN IF NOT EXISTS evidence_file VARCHAR(255) DEFAULT NULL");
    }

    public function chamCong($maND, $action, $method, $wifiName, $note, $clientIP = null)
    {
        // Get client IP if not provided (server-side only, cannot be spoofed)
        if ($clientIP === null) {
            $clientIP = $this->getServerIP();
        }

        $sql = "INSERT INTO attendance_logs (maND, action, method, wifi_name, note, created_at) 
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issss", $maND, $action, $method, $wifiName, $note);
        return $stmt->execute();
    }

    public function getLichSuTheoNhanVien($maND, $limit = 30)
    {
        $sql = "SELECT id, action, method, wifi_name, note, created_at
                FROM attendance_logs
                WHERE maND = ?
                ORDER BY created_at DESC
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $maND, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Alias function for clarity
    public function getLichSuChamCong($maND, $limit = 30)
    {
        return $this->getLichSuTheoNhanVien($maND, $limit);
    }

    public function taoYeuCauChinhSua($maND, $attendanceDate, $oldTime, $newTime, $reason)
    {
        $sql = "INSERT INTO attendance_corrections (maND, attendance_date, old_time, new_time, reason)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issss", $maND, $attendanceDate, $oldTime, $newTime, $reason);
        return $stmt->execute();
    }

    /**
     * Insert enhanced edit request with proposed times and evidence file.
     * All new fields are nullable — backward compatible with existing data.
     */
    public function insertEditRequest(array $data)
    {
        $maND = (int)($data['maND'] ?? 0);
        $attendanceDate = trim($data['attendance_date'] ?? '');
        $oldTime = !empty($data['old_time']) ? trim($data['old_time']) : null;
        $newTime = !empty($data['new_time']) ? trim($data['new_time']) : null;
        $reason = trim($data['reason'] ?? '');
        $proposedCheckin = !empty($data['proposed_checkin']) ? trim($data['proposed_checkin']) : null;
        $proposedCheckout = !empty($data['proposed_checkout']) ? trim($data['proposed_checkout']) : null;
        $evidenceFile = !empty($data['evidence_file']) ? trim($data['evidence_file']) : null;

        if ($maND <= 0 || $attendanceDate === '' || $reason === '') {
            return false;
        }

        // Build new_time from proposed_checkin if not provided (backward compat)
        if ($newTime === null && $proposedCheckin !== null) {
            $newTime = $proposedCheckin;
        }
        if ($newTime === null) {
            $newTime = date('Y-m-d H:i:s');
        }

        $sql = "INSERT INTO attendance_corrections 
                (maND, attendance_date, old_time, new_time, reason, proposed_checkin, proposed_checkout, evidence_file)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("isssssss", $maND, $attendanceDate, $oldTime, $newTime, $reason, $proposedCheckin, $proposedCheckout, $evidenceFile);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getYeuCauTheoNhanVien($maND)
    {
        $sql = "SELECT id, attendance_date, old_time, new_time, reason, status, hr_note, 
                       proposed_checkin, proposed_checkout, evidence_file,
                       created_at, updated_at
                FROM attendance_corrections
                WHERE maND = ?
                ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maND);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Alias for getYeuCauTheoNhanVien — clearer name.
     */
    public function getEditRequestsByUser($maND)
    {
        return $this->getYeuCauTheoNhanVien($maND);
    }

    /**
     * Get attendance records grouped by date for a user.
     * Returns work_date, first_in, last_out for each day.
     */
    public function getAttendanceByUser($maND, $limit = 30)
    {
        $maND = (int)$maND;
        $limit = max(1, min((int)$limit, 100));

        $sql = "SELECT 
                    DATE(created_at) AS work_date,
                    MIN(CASE WHEN action = 'IN' THEN created_at END) AS first_in,
                    MAX(CASE WHEN action = 'OUT' THEN created_at END) AS last_out
                FROM attendance_logs
                WHERE maND = ?
                GROUP BY DATE(created_at)
                ORDER BY work_date DESC
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("ii", $maND, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Get the assigned shift for a user on a given date.
     * Returns null if no shift assigned (caller must handle NULL safely).
     */
    public function getShiftForUser($maND, $date = null)
    {
        $maND = (int)$maND;
        if ($date === null) {
            $date = date('Y-m-d');
        }

        $sql = "SELECT s.id AS shift_id, s.shift_name, s.start_time, s.end_time
                FROM attendance_employee_shift aes
                JOIN attendance_shifts s ON s.id = aes.shift_id AND s.is_active = 1
                WHERE aes.maND = ?
                  AND aes.effective_from <= ?
                  AND (aes.effective_to IS NULL OR aes.effective_to >= ?)
                ORDER BY aes.effective_from DESC
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("iss", $maND, $date, $date);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Calculate attendance status based on shift times.
     * Handles: on-time, late, early leave, overtime, overnight shifts, missing check-out.
     *
     * @param string|null $checkIn  - Check-in datetime (e.g., '2026-05-05 08:15:00')
     * @param string|null $checkOut - Check-out datetime (nullable for missing check-out)
     * @param string|null $shiftStart - Shift start time (e.g., '08:00:00'), null = no shift
     * @param string|null $shiftEnd   - Shift end time (e.g., '17:00:00'), null = no shift
     * @return array ['statuses' => [], 'minutes_late' => int, 'minutes_early' => int, 'overtime_minutes' => int, 'labels' => []]
     */
    public function calculateShiftStatus($checkIn, $checkOut, $shiftStart, $shiftEnd)
    {
        $result = [
            'statuses' => [],
            'minutes_late' => 0,
            'minutes_early' => 0,
            'overtime_minutes' => 0,
            'labels' => [],
            'colors' => [],
        ];

        // Handle NULL shift safely — can't calculate without shift info
        if ($shiftStart === null || $shiftEnd === null) {
            $result['statuses'][] = 'no_shift';
            $result['labels'][] = 'Chưa phân ca';
            $result['colors'][] = '#94a3b8';
            return $result;
        }

        // Handle no check-in at all
        if ($checkIn === null) {
            $result['statuses'][] = 'absent';
            $result['labels'][] = 'Chưa chấm công';
            $result['colors'][] = '#94a3b8';
            return $result;
        }

        // Extract time parts for comparison
        $checkInDate = date('Y-m-d', strtotime($checkIn));
        $checkInTime = strtotime($checkIn);
        $shiftStartTime = strtotime($checkInDate . ' ' . $shiftStart);

        // Determine if overnight shift (end_time < start_time, e.g., 22:00 - 06:00)
        $isOvernight = $shiftEnd < $shiftStart;
        if ($isOvernight) {
            $shiftEndTime = strtotime($checkInDate . ' ' . $shiftEnd . ' +1 day');
        } else {
            $shiftEndTime = strtotime($checkInDate . ' ' . $shiftEnd);
        }

        // === CHECK-IN analysis ===
        $diffIn = ($checkInTime - $shiftStartTime) / 60; // minutes

        if ($diffIn > 1) {
            // Late (more than 1 minute grace)
            $result['statuses'][] = 'late';
            $result['minutes_late'] = (int)round($diffIn);
            $result['labels'][] = 'Đi trễ ' . $result['minutes_late'] . ' phút';
            $result['colors'][] = '#ef4444';
        } elseif ($diffIn < -1) {
            // Early arrival
            $result['statuses'][] = 'early_arrival';
            $result['labels'][] = 'Đến sớm ' . abs((int)round($diffIn)) . ' phút';
            $result['colors'][] = '#10b981';
        } else {
            $result['statuses'][] = 'on_time';
            $result['labels'][] = 'Đúng giờ';
            $result['colors'][] = '#10b981';
        }

        // === CHECK-OUT analysis ===
        if ($checkOut === null) {
            // Missing check-out — still in shift or forgot
            $result['statuses'][] = 'missing_checkout';
            $result['labels'][] = 'Chưa chấm ra';
            $result['colors'][] = '#f59e0b';
        } else {
            $checkOutTime = strtotime($checkOut);
            $diffOut = ($checkOutTime - $shiftEndTime) / 60; // minutes

            if ($diffOut < -1) {
                // Left early
                $result['statuses'][] = 'early_leave';
                $result['minutes_early'] = abs((int)round($diffOut));
                $result['labels'][] = 'Về sớm ' . $result['minutes_early'] . ' phút';
                $result['colors'][] = '#f97316';
            } elseif ($diffOut > 1) {
                // Overtime
                $result['statuses'][] = 'overtime';
                $result['overtime_minutes'] = (int)round($diffOut);
                $result['labels'][] = 'Tăng ca ' . $result['overtime_minutes'] . ' phút';
                $result['colors'][] = '#3b82f6';
            }
        }

        // If no special status for check-out, and check-in was on time — overall on_time
        if (count($result['statuses']) === 1 && $result['statuses'][0] === 'on_time' && $checkOut !== null) {
            // Just on_time, no additional status needed
        }

        return $result;
    }

    /**
     * Get today's full shift status for a user (for dashboard display).
     * Returns shift info + attendance status + labels.
     */
    public function getTodayShiftStatus($maND)
    {
        $maND = (int)$maND;
        $today = date('Y-m-d');

        // Get shift assignment (handles NULL safely)
        $shift = $this->getShiftForUser($maND, $today);

        // Get today's check-in/check-out
        $sql = "SELECT 
                    MIN(CASE WHEN action = 'IN' THEN created_at END) AS first_in,
                    MAX(CASE WHEN action = 'OUT' THEN created_at END) AS last_out
                FROM attendance_logs
                WHERE maND = ? AND DATE(created_at) = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['shift' => $shift, 'status' => ['statuses' => ['error'], 'labels' => ['Lỗi hệ thống'], 'colors' => ['#94a3b8'], 'minutes_late' => 0, 'minutes_early' => 0, 'overtime_minutes' => 0], 'first_in' => null, 'last_out' => null];
        }
        $stmt->bind_param("is", $maND, $today);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $firstIn = $row['first_in'] ?? null;
        $lastOut = $row['last_out'] ?? null;

        $shiftStart = $shift['start_time'] ?? null;
        $shiftEnd = $shift['end_time'] ?? null;

        $status = $this->calculateShiftStatus($firstIn, $lastOut, $shiftStart, $shiftEnd);

        return [
            'shift' => $shift,
            'status' => $status,
            'first_in' => $firstIn,
            'last_out' => $lastOut,
        ];
    }

    public function getThongKeTongQuan()
    {
        $data = [
            'total_logs' => 0,
            'total_in' => 0,
            'total_out' => 0,
            'pending_corrections' => 0,
            'pending_approvals' => 0,
            'total_logs_today' => 0,
            'in_today' => 0,
            'out_today' => 0,
            'pending_requests' => 0,
        ];

        $result = $this->conn->query("
            SELECT
                COUNT(*) AS total_logs_today,
                SUM(CASE WHEN action = 'IN' THEN 1 ELSE 0 END) AS in_today,
                SUM(CASE WHEN action = 'OUT' THEN 1 ELSE 0 END) AS out_today
            FROM attendance_logs
            WHERE DATE(created_at) = CURDATE()
        ");
        if ($result) {
            $row = $result->fetch_assoc();
            if ($row) {
                $data['total_logs_today'] = (int) ($row['total_logs_today'] ?? 0);
                $data['in_today'] = (int) ($row['in_today'] ?? 0);
                $data['out_today'] = (int) ($row['out_today'] ?? 0);
                $data['total_logs'] = $data['total_logs_today'];
                $data['total_in'] = $data['in_today'];
                $data['total_out'] = $data['out_today'];
            }
        }

        $result2 = $this->conn->query("SELECT COUNT(*) AS pending_requests FROM attendance_corrections WHERE status = 'pending'");
        if ($result2) {
            $row2 = $result2->fetch_assoc();
            $data['pending_requests'] = (int) ($row2['pending_requests'] ?? 0);
            $data['pending_corrections'] = $data['pending_requests'];
        }

        $result3 = $this->conn->query("SELECT COUNT(*) AS pending_approvals FROM attendance_monthly_approval WHERE status = 'submitted'");
        if ($result3) {
            $row3 = $result3->fetch_assoc();
            $data['pending_approvals'] = (int) ($row3['pending_approvals'] ?? 0);
        }

        return $data;
    }

    public function getEmployees($keyword = '', $activeOnly = false, $limit = 0)
    {
        $sql = "SELECT maND, maTK, hoTen, email, soDienThoai, chucVu, phongBan, trangThai, created_at
                FROM nguoidung";
        $conditions = [];
        $types = '';
        $params = [];

        if ($keyword !== '') {
            $conditions[] = "(hoTen LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%') OR phongBan LIKE CONCAT('%', ?, '%'))";
            $types .= 'sss';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        if ($activeOnly) {
            $conditions[] = "trangThai = 1";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY maND DESC";
        if ((int)$limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

        if ($types !== '') {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function saveEmployee(array $payload)
    {
        $maND = (int)($payload['maND'] ?? 0);
        $hoTen = trim($payload['hoTen'] ?? '');
        $email = trim($payload['email'] ?? '');
        $soDienThoai = trim($payload['soDienThoai'] ?? '');
        $chucVu = trim($payload['chucVu'] ?? 'Nhân viên');
        $phongBan = trim($payload['phongBan'] ?? '');
        $trangThai = (int)($payload['trangThai'] ?? 1);

        if ($hoTen === '' || $chucVu === '') {
            return false;
        }

        if ($maND > 0) {
            $sql = "UPDATE nguoidung SET hoTen = ?, email = ?, soDienThoai = ?, chucVu = ?, phongBan = ?, trangThai = ? WHERE maND = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssssii", $hoTen, $email, $soDienThoai, $chucVu, $phongBan, $trangThai, $maND);
            return $stmt->execute();
        }

        $this->conn->begin_transaction();
        try {
            $usernameSeed = $email !== '' ? strstr($email, '@', true) : 'user' . time();
            $username = preg_replace('/[^a-zA-Z0-9_]/', '', $usernameSeed);
            if ($username === '') {
                $username = 'user' . time();
            }
            $username .= rand(10, 99);

            $defaultPassword = md5('123456');
            $insertAccount = $this->conn->prepare("INSERT INTO taikhoan (tenDangNhap, matKhau, trangThai) VALUES (?, ?, 'Hoạt động')");
            $insertAccount->bind_param("ss", $username, $defaultPassword);
            if (!$insertAccount->execute()) {
                $this->conn->rollback();
                return false;
            }

            $maTK = (int)$this->conn->insert_id;
            $insertEmployee = $this->conn->prepare("INSERT INTO nguoidung (maTK, hoTen, email, soDienThoai, chucVu, phongBan, trangThai) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insertEmployee->bind_param("isssssi", $maTK, $hoTen, $email, $soDienThoai, $chucVu, $phongBan, $trangThai);
            if (!$insertEmployee->execute()) {
                $this->conn->rollback();
                return false;
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function getShifts()
    {
        $sql = "SELECT s.id, s.shift_name, s.start_time, s.end_time, s.is_active, s.created_at,
                       (SELECT COUNT(*) FROM attendance_employee_shift aes
                        WHERE aes.shift_id = s.id AND (aes.effective_to IS NULL OR aes.effective_to >= CURDATE())) AS assigned_count
                FROM attendance_shifts s
                ORDER BY s.created_at DESC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function saveShift(array $payload)
    {
        $id = (int)($payload['id'] ?? 0);
        $name = trim($payload['shift_name'] ?? '');
        $start = trim($payload['start_time'] ?? '');
        $end = trim($payload['end_time'] ?? '');
        $isActive = (int)($payload['is_active'] ?? 1);

        if ($name === '' || $start === '' || $end === '' || $start >= $end) {
            return false;
        }

        if ($id > 0) {
            $sql = "UPDATE attendance_shifts SET shift_name = ?, start_time = ?, end_time = ?, is_active = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssii", $name, $start, $end, $isActive, $id);
            return $stmt->execute();
        }

        $sql = "INSERT INTO attendance_shifts (shift_name, start_time, end_time, is_active) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $name, $start, $end, $isActive);
        return $stmt->execute();
    }

    public function assignShift($maND, $shiftId, $effectiveFrom)
    {
        $maND = (int)$maND;
        $shiftId = (int)$shiftId;
        if ($maND <= 0 || $shiftId <= 0 || !$effectiveFrom) {
            return false;
        }

        $closeCurrent = $this->conn->prepare("UPDATE attendance_employee_shift SET effective_to = DATE_SUB(?, INTERVAL 1 DAY) WHERE maND = ? AND effective_to IS NULL");
        $closeCurrent->bind_param("si", $effectiveFrom, $maND);
        $closeCurrent->execute();

        $insert = $this->conn->prepare("INSERT INTO attendance_employee_shift (maND, shift_id, effective_from) VALUES (?, ?, ?)");
        $insert->bind_param("iis", $maND, $shiftId, $effectiveFrom);
        return $insert->execute();
    }

    public function getMonthlyWorkSummary($monthKey, $department = '')
    {
        $monthStart = $monthKey . '-01';
        $defaultWorkMinutes = (int)$this->getSettingValue('DEFAULT_WORK_MINUTES', '480');

        $sql = "SELECT u.maND, u.hoTen, u.phongBan,
                       COUNT(CASE WHEN d.first_in IS NOT NULL THEN 1 END) AS work_days,
                       ROUND(SUM(d.work_minutes) / 60, 2) AS work_hours,
                       ROUND(SUM(CASE WHEN d.work_minutes > ? THEN d.work_minutes - ? ELSE 0 END) / 60, 2) AS overtime_hours
                FROM nguoidung u
                LEFT JOIN (
                    SELECT maND,
                           DATE(created_at) AS work_date,
                           MIN(CASE WHEN action = 'IN' THEN created_at END) AS first_in,
                           MAX(CASE WHEN action = 'OUT' THEN created_at END) AS last_out,
                           CASE
                               WHEN MIN(CASE WHEN action = 'IN' THEN created_at END) IS NOT NULL
                                AND MAX(CASE WHEN action = 'OUT' THEN created_at END) IS NOT NULL
                               THEN GREATEST(TIMESTAMPDIFF(MINUTE,
                                       MIN(CASE WHEN action = 'IN' THEN created_at END),
                                       MAX(CASE WHEN action = 'OUT' THEN created_at END)
                                   ), 0)
                               ELSE 0
                           END AS work_minutes
                    FROM attendance_logs
                    WHERE created_at >= ?
                      AND created_at < DATE_ADD(?, INTERVAL 1 MONTH)
                    GROUP BY maND, DATE(created_at)
                ) d ON d.maND = u.maND
                WHERE u.trangThai = 1
                  AND (? = '' OR u.phongBan = ?)
                GROUP BY u.maND, u.hoTen, u.phongBan
                ORDER BY u.hoTen";

        $stmt = $this->conn->prepare($sql);
            $dept = trim((string)$department);
            $stmt->bind_param("iissss", $defaultWorkMinutes, $defaultWorkMinutes, $monthStart, $monthStart, $dept, $dept);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getApprovedOtSchedule($monthKey)
    {
        $monthKey = trim($monthKey);
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            return [];
        }

        $monthStart = $monthKey . '-01';
        $sql = "SELECT maND, ot_date, start_time, end_time, hours, reason
                FROM ot_requests
                WHERE status = 'approved'
                  AND ot_date >= ?
                  AND ot_date < DATE_ADD(?, INTERVAL 1 MONTH)
                ORDER BY ot_date ASC, created_at ASC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("ss", $monthStart, $monthStart);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $schedule = [];
        foreach ($rows as $row) {
            $maND = (int)($row['maND'] ?? 0);
            $date = $row['ot_date'] ?? '';
            if ($maND <= 0 || $date === '') {
                continue;
            }

            if (!isset($schedule[$maND])) {
                $schedule[$maND] = [];
            }

            $schedule[$maND][$date] = [
                'label' => 'OT',
                'time' => trim(($row['start_time'] ? substr((string)$row['start_time'], 0, 5) : '') . ' - ' . ($row['end_time'] ? substr((string)$row['end_time'], 0, 5) : ''), ' -'),
                'hours' => (float)($row['hours'] ?? 0),
                'reason' => $row['reason'] ?? '',
            ];
        }

        return $schedule;
    }

    public function getManagerEmployeeRequests(array $filters = [], $limit = 300)
    {
        $limit = max(1, min((int)$limit, 500));
        $status = trim($filters['status'] ?? '');
        $type = trim($filters['type'] ?? '');
        $keyword = trim($filters['q'] ?? '');
        $date = trim($filters['date'] ?? '');
        $dateFrom = trim($filters['date_from'] ?? '');
        $dateTo = trim($filters['date_to'] ?? '');
        $department = trim($filters['department'] ?? '');

        $rows = [];
        if ($type === '' || $type === 'leave') {
            $rows = array_merge($rows, $this->getLeaveApprovalRequests($status, $keyword, $date, $dateFrom, $dateTo, $department, $limit));
        }
        if ($type === '' || $type === 'ot') {
            $rows = array_merge($rows, $this->getOtApprovalRequests($status, $keyword, $date, $dateFrom, $dateTo, $department, $limit));
        }
        if ($type === '' || $type === 'shift') {
            $rows = array_merge($rows, $this->getShiftChangeApprovalRequests($status, $keyword, $date, $dateFrom, $dateTo, $department, $limit));
        }

        usort($rows, function ($a, $b) {
            return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
        });

        return array_slice($rows, 0, $limit);
    }

    public function processManagerEmployeeRequest($type, $requestId, $action, $managerId, $note = '')
    {
        $type = trim((string)$type);
        $requestId = (int)$requestId;
        $managerId = (int)$managerId;
        if ($requestId <= 0 || !in_array($type, ['leave', 'ot', 'shift'], true) || !in_array($action, ['approve', 'reject'], true)) {
            return false;
        }

        $status = $action === 'approve' ? 'approved' : 'rejected';
        if ($type === 'leave') {
            $sql = "UPDATE leave_requests
                    SET status = ?, manager_note = ?, manager_approver_id = ?, approved_at = NOW(), updated_at = NOW()
                    WHERE id = ? AND status = 'pending'";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param('ssii', $status, $note, $managerId, $requestId);
            return $stmt->execute();
        }

        if ($type === 'ot') {
            $sql = "UPDATE ot_requests
                    SET status = ?, manager_note = ?, manager_approver_id = ?, approved_at = NOW(), updated_at = NOW()
                    WHERE id = ? AND status = 'pending'";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param('ssii', $status, $note, $managerId, $requestId);
            return $stmt->execute();
        }

        return $this->processShiftChangeRequest($requestId, $status, $managerId, $note);
    }

    private function getLeaveApprovalRequests($status, $keyword, $date, $dateFrom, $dateTo, $department, $limit)
    {
        $sql = "SELECT CONCAT('leave:', r.id) AS uid, r.id, 'leave' AS request_type,
                       r.maND, r.leave_date AS request_date, r.leave_type, r.is_half_day,
                       NULL AS start_time, NULL AS end_time, NULL AS hours,
                       NULL AS current_shift_name, NULL AS requested_shift_name,
                       r.reason, r.status, r.manager_note, r.created_at, r.updated_at,
                       n.hoTen, n.chucVu, n.phongBan
                FROM leave_requests r
                LEFT JOIN nguoidung n ON n.maND = r.maND";
        return $this->fetchApprovalRequestRows($sql, 'r.leave_date', $status, $keyword, $date, $dateFrom, $dateTo, $department, $limit);
    }

    private function getOtApprovalRequests($status, $keyword, $date, $dateFrom, $dateTo, $department, $limit)
    {
        $sql = "SELECT CONCAT('ot:', r.id) AS uid, r.id, 'ot' AS request_type,
                       r.maND, r.ot_date AS request_date, NULL AS leave_type, 0 AS is_half_day,
                       r.start_time, r.end_time, r.hours,
                       NULL AS current_shift_name, NULL AS requested_shift_name,
                       r.reason, r.status, r.manager_note, r.created_at, r.updated_at,
                       n.hoTen, n.chucVu, n.phongBan
                FROM ot_requests r
                LEFT JOIN nguoidung n ON n.maND = r.maND";
        return $this->fetchApprovalRequestRows($sql, 'r.ot_date', $status, $keyword, $date, $dateFrom, $dateTo, $department, $limit);
    }

    private function getShiftChangeApprovalRequests($status, $keyword, $date, $dateFrom, $dateTo, $department, $limit)
    {
        $sql = "SELECT CONCAT('shift:', r.id) AS uid, r.id, 'shift' AS request_type,
                       r.maND, r.request_date, NULL AS leave_type, 0 AS is_half_day,
                       NULL AS start_time, NULL AS end_time, NULL AS hours,
                       COALESCE(r.current_shift_name, cs.shift_name) AS current_shift_name,
                       COALESCE(r.requested_shift_name, ns.shift_name) AS requested_shift_name,
                       r.reason, r.status, r.manager_note, r.created_at, r.updated_at,
                       n.hoTen, n.chucVu, n.phongBan
                FROM shift_change_requests r
                LEFT JOIN nguoidung n ON n.maND = r.maND
                LEFT JOIN attendance_shifts cs ON cs.id = r.current_shift_id
                LEFT JOIN attendance_shifts ns ON ns.id = r.requested_shift_id";
        return $this->fetchApprovalRequestRows($sql, 'r.request_date', $status, $keyword, $date, $dateFrom, $dateTo, $department, $limit);
    }

    private function fetchApprovalRequestRows($baseSql, $dateColumn, $status, $keyword, $date, $dateFrom, $dateTo, $department, $limit)
    {
        $conditions = [];
        $types = '';
        $params = [];

        if ($status !== '' && in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $conditions[] = "r.status = ?";
            $types .= 's';
            $params[] = $status;
        }

        if ($keyword !== '') {
            $conditions[] = "(n.hoTen LIKE CONCAT('%', ?, '%') OR n.phongBan LIKE CONCAT('%', ?, '%') OR r.reason LIKE CONCAT('%', ?, '%'))";
            $types .= 'sss';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        if ($department !== '') {
            $conditions[] = "n.phongBan = ?";
            $types .= 's';
            $params[] = $department;
        }

        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $conditions[] = $dateColumn . " = ?";
            $types .= 's';
            $params[] = $date;
        }

        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $conditions[] = $dateColumn . " >= ?";
            $types .= 's';
            $params[] = $dateFrom;
        }

        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $conditions[] = $dateColumn . " <= ?";
            $types .= 's';
            $params[] = $dateTo;
        }

        $sql = $baseSql;
        if ($conditions) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY r.created_at DESC LIMIT " . (int)$limit;

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    private function processShiftChangeRequest($requestId, $status, $managerId, $note)
    {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("SELECT maND, request_date, requested_shift_id FROM shift_change_requests WHERE id = ? AND status = 'pending' FOR UPDATE");
            if (!$stmt) {
                $this->conn->rollback();
                return false;
            }
            $stmt->bind_param('i', $requestId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$row) {
                $this->conn->rollback();
                return false;
            }

            $stmt = $this->conn->prepare("UPDATE shift_change_requests
                    SET status = ?, manager_note = ?, manager_approver_id = ?, approved_at = NOW(), updated_at = NOW()
                    WHERE id = ? AND status = 'pending'");
            if (!$stmt) {
                $this->conn->rollback();
                return false;
            }
            $stmt->bind_param('ssii', $status, $note, $managerId, $requestId);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok && $status === 'approved' && (int)($row['requested_shift_id'] ?? 0) > 0) {
                $ok = $this->assignShift((int)$row['maND'], (int)$row['requested_shift_id'], $row['request_date']);
            }

            if (!$ok) {
                $this->conn->rollback();
                return false;
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function getAttendanceReport($fromDate, $toDate, $department = '')
    {
        $sql = "SELECT u.maND, u.hoTen, u.phongBan,
                       COUNT(DISTINCT DATE(l.created_at)) AS work_days,
                       SUM(CASE WHEN l.action = 'IN' THEN 1 ELSE 0 END) AS checkin_count,
                       SUM(CASE WHEN l.action = 'OUT' THEN 1 ELSE 0 END) AS checkout_count
                FROM nguoidung u
                LEFT JOIN attendance_logs l ON l.maND = u.maND
                    AND DATE(l.created_at) >= ?
                    AND DATE(l.created_at) <= ?
                WHERE u.trangThai = 1";

        if ($department !== '') {
            $sql .= " AND u.phongBan = ?";
        }

        $sql .= " GROUP BY u.maND, u.hoTen, u.phongBan ORDER BY u.hoTen";

        $stmt = $this->conn->prepare($sql);
        if ($department !== '') {
            $stmt->bind_param("sss", $fromDate, $toDate, $department);
        } else {
            $stmt->bind_param("ss", $fromDate, $toDate);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getDistinctDepartments()
    {
        $result = $this->conn->query("SELECT DISTINCT phongBan FROM nguoidung WHERE phongBan IS NOT NULL AND phongBan <> '' ORDER BY phongBan");
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        return array_map(function ($r) {
            return $r['phongBan'];
        }, $rows);
    }

    public function submitMonthlyApproval($monthKey, $hrSenderId, $department = '')
    {
        $monthKey = trim($monthKey);
        $hrSenderId = (int)$hrSenderId;
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey) || $hrSenderId <= 0) {
            return false;
        }

        $departments = [];
        $department = trim((string)$department);
        if ($department !== '') {
            $departments = [$department];
        } else {
            $departments = $this->getDistinctDepartments();
        }

        if (empty($departments)) {
            return false;
        }

        $okAll = true;
        foreach ($departments as $dept) {
            $dept = trim((string)$dept);
            if ($dept === '') {
                continue;
            }

            $find = $this->conn->prepare("SELECT id FROM attendance_monthly_approval WHERE month_key = ? AND department = ? ORDER BY id DESC LIMIT 1");
            if (!$find) {
                $okAll = false;
                continue;
            }
            $find->bind_param("ss", $monthKey, $dept);
            $find->execute();
            $existing = $find->get_result()->fetch_assoc();
            $find->close();

            if (!empty($existing['id'])) {
                $id = (int)$existing['id'];
                $update = $this->conn->prepare("UPDATE attendance_monthly_approval
                                                SET hr_sender_id = ?, status = 'submitted', submitted_at = NOW(), approved_at = NULL, manager_approver_id = NULL, note = NULL
                                                WHERE id = ?");
                if (!$update) {
                    $okAll = false;
                    continue;
                }
                $update->bind_param("ii", $hrSenderId, $id);
                $okAll = $update->execute() && $okAll;
                $update->close();
                continue;
            }

            $insert = $this->conn->prepare("INSERT INTO attendance_monthly_approval (month_key, department, hr_sender_id, status, submitted_at)
                                            VALUES (?, ?, ?, 'submitted', NOW())");
            if (!$insert) {
                $okAll = false;
                continue;
            }
            $insert->bind_param("ssi", $monthKey, $dept, $hrSenderId);
            $okAll = $insert->execute() && $okAll;
            $insert->close();
        }

        return $okAll;
    }

    public function getMonthlyApprovals($status = null, $department = '')
    {
        $sql = "SELECT a.id, a.month_key, a.hr_sender_id, a.manager_approver_id, a.status, a.submitted_at, a.approved_at, a.note,
                       a.department,
                       u.hoTen AS hr_name,
                       u2.hoTen AS approver_name
                FROM attendance_monthly_approval a
                LEFT JOIN nguoidung u ON u.maND = a.hr_sender_id
                LEFT JOIN nguoidung u2 ON u2.maND = a.manager_approver_id";

        $conditions = [];
        $types = '';
        $params = [];

        if ($status) {
            $conditions[] = "a.status = ?";
            $types .= 's';
            $params[] = $status;
        }

        $department = trim((string)$department);
        if ($department !== '') {
            $conditions[] = "a.department = ?";
            $types .= 's';
            $params[] = $department;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY COALESCE(a.approved_at, a.submitted_at, a.created_at) DESC, a.id DESC";

        if (!empty($params)) {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getMonthlyApprovalHistory($year = null, $limit = 50, $department = '')
    {
        $sql = "SELECT a.id, a.month_key, a.hr_sender_id, a.manager_approver_id, a.status, a.submitted_at, a.approved_at, a.note,
                       a.department,
                       u.hoTen AS hr_name,
                       u2.hoTen AS approver_name
                FROM attendance_monthly_approval a
                LEFT JOIN nguoidung u ON u.maND = a.hr_sender_id
                LEFT JOIN nguoidung u2 ON u2.maND = a.manager_approver_id
                WHERE a.status IN ('approved', 'rejected')";

        $params = [];
        $types = '';

        if ($year && preg_match('/^\d{4}$/', $year)) {
            $sql .= " AND a.month_key LIKE ?";
            $params[] = $year . '%';
            $types = 's';
        }

        $department = trim((string)$department);
        if ($department !== '') {
            $sql .= " AND a.department = ?";
            $params[] = $department;
            $types .= 's';
        }

        $sql .= " ORDER BY a.approved_at DESC, a.id DESC";

        if ($limit > 0) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
            $types .= 'i';
        }

        if (!empty($params)) {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getMonthlyApprovalsBySender($hrSenderId, array $statuses = [], $limit = 0)
    {
        $hrSenderId = (int)$hrSenderId;
        if ($hrSenderId <= 0) {
            return [];
        }

        $sql = "SELECT a.id, a.month_key, a.hr_sender_id, a.manager_approver_id, a.status, a.submitted_at, a.approved_at, a.note,
                       u.hoTen AS hr_name,
                       u2.hoTen AS approver_name
                FROM attendance_monthly_approval a
                LEFT JOIN nguoidung u ON u.maND = a.hr_sender_id
                LEFT JOIN nguoidung u2 ON u2.maND = a.manager_approver_id
                WHERE a.hr_sender_id = ?";

        $types = 'i';
        $params = [$hrSenderId];

        $statuses = array_values(array_filter($statuses, function ($status) {
            return in_array($status, ['draft', 'submitted', 'approved', 'rejected'], true);
        }));

        if (!empty($statuses)) {
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $sql .= " AND a.status IN ($placeholders)";
            $types .= str_repeat('s', count($statuses));
            foreach ($statuses as $status) {
                $params[] = $status;
            }
        }

        $sql .= " ORDER BY COALESCE(a.approved_at, a.submitted_at, a.created_at) DESC, a.id DESC";

        if ($limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getMonthlyApprovalByMonth($monthKey, $department = '')
    {
        $monthKey = trim($monthKey);
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            return null;
        }

        $department = trim((string)$department);
        if ($department === '') {
            $stmt = $this->conn->prepare("SELECT status, submitted_at, approved_at
                                          FROM attendance_monthly_approval
                                          WHERE month_key = ?");
            if (!$stmt) {
                return null;
            }
            $stmt->bind_param("s", $monthKey);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($rows)) {
                return null;
            }

            $status = 'approved';
            $latestSubmitted = '';
            $latestApproved = '';
            foreach ($rows as $row) {
                $rowStatus = $row['status'] ?? '';
                if ($rowStatus === 'submitted') {
                    $status = 'submitted';
                } elseif ($rowStatus === 'rejected' && $status !== 'submitted') {
                    $status = 'rejected';
                }

                $submittedAt = (string)($row['submitted_at'] ?? '');
                $approvedAt = (string)($row['approved_at'] ?? '');
                if ($submittedAt > $latestSubmitted) {
                    $latestSubmitted = $submittedAt;
                }
                if ($approvedAt > $latestApproved) {
                    $latestApproved = $approvedAt;
                }
            }

            return [
                'month_key' => $monthKey,
                'status' => $status,
                'submitted_at' => $latestSubmitted,
                'approved_at' => $latestApproved,
                'department' => 'ALL',
            ];
        }
        $sql = "SELECT a.id, a.month_key, a.status, a.submitted_at, a.approved_at, a.note,
                       a.department,
                       u.hoTen AS hr_name,
                       u2.hoTen AS approver_name
                FROM attendance_monthly_approval a
                LEFT JOIN nguoidung u ON u.maND = a.hr_sender_id
                LEFT JOIN nguoidung u2 ON u2.maND = a.manager_approver_id
                WHERE a.month_key = ?";

        $params = [$monthKey];
        $types = 's';
        if ($department !== '') {
            $sql .= " AND a.department = ?";
            $params[] = $department;
            $types .= 's';
        }
        $sql .= " ORDER BY a.id DESC LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getMonthlyApprovalDetail($approvalId, $department = '')
    {
        $approvalId = (int)$approvalId;
        if ($approvalId <= 0) {
            return null;
        }

        $department = trim((string)$department);
        $sql = "SELECT a.id, a.month_key, a.status, a.submitted_at, a.approved_at, a.note,
                       a.department,
                       u.hoTen AS hr_name,
                       u2.hoTen AS approver_name
                FROM attendance_monthly_approval a
                LEFT JOIN nguoidung u ON u.maND = a.hr_sender_id
                LEFT JOIN nguoidung u2 ON u2.maND = a.manager_approver_id
                WHERE a.id = ?";
        if ($department !== '') {
            $sql .= " AND a.department = ?";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        if ($department !== '') {
            $stmt->bind_param("is", $approvalId, $department);
        } else {
            $stmt->bind_param("i", $approvalId);
        }
        $stmt->execute();
        $approval = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$approval) {
            return null;
        }

        $monthKey = trim($approval['month_key'] ?? '');
        $rows = $this->getMonthlyWorkSummary($monthKey, $approval['department'] ?? '');
        $summary = [
            'employees' => count($rows),
            'total_work_days' => 0,
            'total_work_hours' => 0,
            'total_overtime_hours' => 0,
        ];

        foreach ($rows as $row) {
            $summary['total_work_days'] += (float)($row['work_days'] ?? 0);
            $summary['total_work_hours'] += (float)($row['work_hours'] ?? 0);
            $summary['total_overtime_hours'] += (float)($row['overtime_hours'] ?? 0);
        }

        return [
            'approval' => $approval,
            'summary' => $summary,
            'rows' => $rows,
        ];
    }

    public function updateMonthlyApproval($approvalId, $status, $managerId, $note = null, $department = '')
    {
        $approvalId = (int)$approvalId;
        $managerId = (int)$managerId;
        if (!in_array($status, ['approved', 'rejected'], true) || $approvalId <= 0) {
            return false;
        }

        $department = trim((string)$department);
        $sql = "UPDATE attendance_monthly_approval
                SET status = ?, manager_approver_id = ?, approved_at = NOW(), note = ?
                WHERE id = ? AND status = 'submitted'";
        if ($department !== '') {
            $sql .= " AND department = ?";
        }
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        if ($department !== '') {
            $stmt->bind_param("sisis", $status, $managerId, $note, $approvalId, $department);
        } else {
            $stmt->bind_param("sisi", $status, $managerId, $note, $approvalId);
        }
        return $stmt->execute();
    }

    public function getCorrectionRequests($status = null, array $filters = [], $limit = 0, $historyOnly = false)
    {
        $sql = "SELECT c.id, c.maND, c.attendance_date, c.old_time, c.new_time, c.reason, c.status, c.hr_note, c.created_at,
                       n.hoTen, n.chucVu, n.phongBan
                FROM attendance_corrections c
                LEFT JOIN nguoidung n ON n.maND = c.maND";

        $conditions = [];
        $types = '';
        $params = [];

        if ($status) {
            $conditions[] = "c.status = ?";
            $types .= 's';
            $params[] = $status;
        }

        if ($historyOnly) {
            $conditions[] = "c.status <> 'pending'";
        }

        $keyword = trim($filters['q'] ?? '');
        if ($keyword !== '') {
            $conditions[] = "(n.hoTen LIKE CONCAT('%', ?, '%') OR n.phongBan LIKE CONCAT('%', ?, '%') OR reason LIKE CONCAT('%', ?, '%'))";
            $types .= 'sss';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $date = trim($filters['date'] ?? '');
        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $conditions[] = "c.attendance_date = ?";
            $types .= 's';
            $params[] = $date;
        }

        $type = trim($filters['type'] ?? '');
        if ($type !== '') {
            $conditions[] = "c.reason LIKE CONCAT('%', ?, '%')";
            $types .= 's';
            $params[] = $type;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY c.created_at DESC";

        if ((int)$limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function processCorrection($correctionId, $action, $note = '')
    {
        $correctionId = (int)$correctionId;
        if ($correctionId <= 0) {
            return false;
        }

        $status = $action === 'approve' ? 'approved' : 'rejected';
        $sql = "UPDATE attendance_corrections SET status = ?, hr_note = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $status, $note, $correctionId);
        return $stmt->execute();
    }

    /**
     * Kiểm tra WiFi nội bộ
     * @return bool
     */
    public function checkWifi()
    {
        // Check if there are any active WiFi networks configured
        $sql = "SELECT COUNT(*) AS count FROM attendance_wifi WHERE is_active = 1";
        $result = $this->conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            return (int)$row['count'] > 0;
        }
        return false;
    }

    /**
     * Kiểm tra xem WiFi có được phép không
     * @param string $wifiName
     * @return bool
     */
    public function isWifiAllowed($wifiName)
    {
        $sql = "SELECT id FROM attendance_wifi WHERE wifi_name = ? AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("s", $wifiName);
        $stmt->execute();
        $result = $stmt->get_result();
        $allowed = $result->num_rows > 0;
        $stmt->close();
        return $allowed;
    }

    /**
     * Lấy tên WiFi đang hoạt động đầu tiên để fallback.
     * @return string|null
     */
    public function getFirstActiveWifiName()
    {
        $sql = "SELECT wifi_name FROM attendance_wifi WHERE is_active = 1 ORDER BY id ASC LIMIT 1";
        $result = $this->conn->query($sql);
        if (!$result) {
            return null;
        }
        $row = $result->fetch_assoc();
        return $row['wifi_name'] ?? null;
    }

    /**
     * Lấy danh sách cấu hình WiFi đang hoạt động (Tên + Dải IP)
     * @return array
     */
    public function getActiveWifiConfigurations()
    {
        $sql = "SELECT wifi_name, ip_range FROM attendance_wifi WHERE is_active = 1";
        $result = $this->conn->query($sql);
        if (!$result) return [];
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Lấy tất cả logs chấm công của hôm nay
     * @param int $maND
     * @return array
     */
    public function getTodayLogs($maND)
    {
        $sql = "SELECT id, action, method, wifi_name, note, created_at
                FROM attendance_logs
                WHERE maND = ? AND DATE(created_at) = CURDATE()
                ORDER BY created_at ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maND);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Lấy trạng thái chấm công trong ngày
     * @param int $maND
     * @return string|null - 'IN', 'OUT', hoặc null
     */
    public function getTrangThaiHomNay($maND)
    {
        $sql = "SELECT action FROM attendance_logs
                WHERE maND = ? AND DATE(created_at) = CURDATE()
                ORDER BY created_at DESC
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maND);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['action'] ?? null;
    }

    /**
     * Lấy lịch sử chấm công theo khoảng ngày
     * @param int $maND
     * @param string $from - YYYY-MM-DD
     * @param string $to - YYYY-MM-DD
     * @return array
     */
    public function getLichSu($maND, $from = null, $to = null)
    {
        if (!$from) {
            $from = date('Y-m-01'); // Đầu tháng hiện tại
        }
        if (!$to) {
            $to = date('Y-m-d'); // Hôm nay
        }

        $sql = "SELECT id, action, method, wifi_name, note, created_at
                FROM attendance_logs
                WHERE maND = ? 
                AND DATE(created_at) >= ? 
                AND DATE(created_at) <= ?
                ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $maND, $from, $to);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * ========== NETWORK VALIDATION (SERVER-SIDE ONLY) ==========
     * SECURITY: Only use server-side $_SERVER['REMOTE_ADDR']
     * NEVER trust frontend data for network validation
     */

    /**
     * Get server-side client IP address
     * Uses only REMOTE_ADDR (cannot be spoofed by frontend)
     * @return string
     */
    public function getServerIP()
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    /**
     * Check if IP is in allowed internal network from database
     * Uses database-managed IP ranges, not hardcoded
     * SECURITY: Only uses $_SERVER['REMOTE_ADDR']
     * 
     * @param string $ip - Server IP from $_SERVER['REMOTE_ADDR']
     * @return bool - true if IP matches any active allowed network range
     */
    public function isInternalNetwork($ip)
    {
        // Use isAllowedIp() which reads from database
        return $this->isAllowedIp($ip);
    }

    /**
     * Get all allowed IP ranges from database (IT managed)
     * SECURITY: Returns ONLY active networks from database - no hardcoded defaults
     * If no networks configured → returns empty array → all IPs rejected
     * 
     * @return array - List of allowed IP ranges (e.g., ['192.168.1', '192.168.2', '10.0.1'])
     */
    public function getAllowedNetworks()
    {
        $sql = "SELECT ip_range FROM attendance_wifi WHERE is_active = 1";
        $result = $this->conn->query($sql);
        
        if (!$result) {
            // If query fails, return empty (fail closed - no users allowed)
            return [];
        }

        $networks = [];
        while ($row = $result->fetch_assoc()) {
            $range = trim($row['ip_range']);
            if (!empty($range)) {
                $networks[] = $range;
            }
        }
        
        // Return ONLY what's in database - NO hardcoded defaults
        return $networks;
    }

    /**
     * Check if client IP is allowed to clock in
     * Verifies that the IP falls within one of the active allowed network ranges
     * SECURITY: Only uses $_SERVER['REMOTE_ADDR'] which cannot be spoofed
     * 
     * @param string $clientIp - Server IP from $_SERVER['REMOTE_ADDR']
     * @return bool - true if IP matches any active allowed network range
     */
    public function isAllowedIp($clientIp)
    {
        $clientIp = trim($clientIp);
        
        if (empty($clientIp)) {
            return false;
        }

        // Get all active network ranges from database
        $networks = $this->getAllowedNetworks();
        
        // Check if client IP matches any allowed range
        foreach ($networks as $range) {
            if (strpos($clientIp, $range) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Alias for backward compatibility
     * @deprecated Use isAllowedIp() instead
     */
    public function isInAllowedNetwork($ip)
    {
        return $this->isAllowedIp($ip);
    }

    /**
     * Validate attendance: ONLY CHECK SERVER IP (SECURITY CRITICAL)
     * Returns array with validation result
     * @return array ['valid' => bool, 'ip' => string, 'message' => string]
     */
    public function validateAttendanceNetwork()
    {
        $ip = $this->getServerIP();
        $isValid = $this->isInternalNetwork($ip);
        $message = '';
        
        if ($isValid) {
            $message = 'Bạn đang ở mạng nội bộ công ty';
        } else {
            $message = 'Bạn không ở trong mạng nội bộ công ty (IP: ' . htmlspecialchars($ip) . ')';
        }
        
        return [
            'valid' => $isValid,
            'ip' => $ip,
            'message' => $message
        ];
    }

    /**
     * ========== NETWORK MANAGEMENT (IT ONLY) ==========
     * Manage allowed IP ranges for attendance validation
     */

    /**
     * Get all networks with IP ranges, gateways, and descriptions
     * Returns safe data with null coalescing to prevent undefined key errors
     * @return array
     */
    public function getAllNetworks()
    {
        $sql = "SELECT id, wifi_name, ip_range, gateway, description, ssid, password, location, is_active, created_at 
                FROM attendance_wifi ORDER BY id DESC";
        $result = $this->conn->query($sql);
        
        if (!$result) {
            return [];
        }

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id' => (int)($row['id'] ?? 0),
                'wifi_name' => (string)($row['wifi_name'] ?? ''),
                'ip_range' => (string)($row['ip_range'] ?? ''),
                'gateway' => (string)($row['gateway'] ?? ''),
                'description' => (string)($row['description'] ?? ''),
                'ssid' => (string)($row['ssid'] ?? ''),
                'password' => (string)($row['password'] ?? ''),
                'location' => (string)($row['location'] ?? ''),
                'is_active' => (int)($row['is_active'] ?? 0),
                'created_at' => (string)($row['created_at'] ?? '')
            ];
        }

        return $data;
    }

    /**
     * Alias for backwards compatibility
     * @return array
     */
    public function getNetworkList()
    {
        return $this->getAllNetworks();
    }

    /**
     * Alias for backwards compatibility
     * @return array
     */
    public function getAllWifi()
    {
        return $this->getAllNetworks();
    }

    /**
     * Alias for backwards compatibility
     * @return array
     */
    public function getWifiList()
    {
        return $this->getAllNetworks();
    }

    /**
     * Get a network by ID
     * @param int $id
     * @return array|null
     */
    public function getNetworkById($id)
    {
        $id = (int)$id;
        if ($id <= 0) return null;
        $sql = "SELECT id, wifi_name, ip_range, gateway, description, ssid, password, location, is_active, created_at 
                FROM attendance_wifi WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    /**
     * Add new network with IP range and gateway
     * @param string $wifiName - Network name (SSID or label)
     * @param string $ipRange - IP range prefix (e.g., "192.168.1")
     * @param string $gateway - Gateway IP (e.g., "192.168.1.1")
     * @param string $description - Description
     * @param int $isActive
     * @return bool
     */
    public function addNetwork($wifiName, $ipRange, $gateway, $description = '', $isActive = 1, $ssid = null, $password = null, $location = null)
    {
        $wifiName = trim($wifiName);
        $ipRange = trim($ipRange);
        $gateway = trim($gateway);
        $description = trim($description);
        $isActive = (int)$isActive;
        $ssid = $ssid !== null ? trim($ssid) : null;
        $password = $password !== null ? trim($password) : null;
        $location = $location !== null ? trim($location) : null;
        
        if (empty($wifiName) || empty($ipRange) || empty($gateway)) {
            return false;
        }
        
        $sql = "INSERT INTO attendance_wifi (wifi_name, ip_range, gateway, description, is_active, ssid, password, location) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("ssssisss", $wifiName, $ipRange, $gateway, $description, $isActive, $ssid, $password, $location);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Add WiFi (backwards compatibility)
     * @param string $wifiName
     * @param int $isActive
     * @return bool
     */
    public function addWifi($wifiName, $isActive = 1)
    {
        // Legacy method - just insert with wifi_name only
        $wifiName = trim($wifiName);
        $isActive = (int)$isActive;
        
        if (empty($wifiName)) {
            return false;
        }
        
        $sql = "INSERT INTO attendance_wifi (wifi_name, is_active) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("si", $wifiName, $isActive);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Update network with all fields
     * @param int $networkId
     * @param string $wifiName
     * @param string $ipRange
     * @param string $gateway
     * @param string $description
     * @param int $isActive
     * @return bool
     */
    public function updateNetwork($networkId, $wifiName, $ipRange, $gateway, $description = '', $isActive = 1, $ssid = null, $password = null, $location = null)
    {
        $networkId = (int)$networkId;
        $wifiName = trim($wifiName);
        $ipRange = trim($ipRange);
        $gateway = trim($gateway);
        $description = trim($description);
        $isActive = (int)$isActive;
        $ssid = $ssid !== null ? trim($ssid) : null;
        $password = $password !== null ? trim($password) : null;
        $location = $location !== null ? trim($location) : null;
        
        if ($networkId <= 0 || empty($wifiName) || empty($ipRange) || empty($gateway)) {
            return false;
        }

        $sql = "UPDATE attendance_wifi 
                SET wifi_name = ?, ip_range = ?, gateway = ?, description = ?, is_active = ?, ssid = ?, password = ?, location = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("ssssisssi", $wifiName, $ipRange, $gateway, $description, $isActive, $ssid, $password, $location, $networkId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Update WiFi (backwards compatibility)
     * @param int $wifiId
     * @param string $wifiName
     * @param int|null $isActive
     * @return bool
     */
    public function updateWifi($wifiId, $wifiName, $isActive = null)
    {
        $wifiId = (int)$wifiId;
        $wifiName = trim($wifiName);
        
        if ($wifiId <= 0 || empty($wifiName)) {
            return false;
        }

        if ($isActive !== null) {
            $isActive = (int)$isActive;
            $sql = "UPDATE attendance_wifi SET wifi_name = ?, is_active = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sii", $wifiName, $isActive, $wifiId);
        } else {
            $sql = "UPDATE attendance_wifi SET wifi_name = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $wifiName, $wifiId);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Check if network name already exists
     * @param string $wifiName
     * @param int|null $excludeId
     * @return bool
     */
    public function checkNetworkExists($wifiName, $excludeId = null)
    {
        $wifiName = trim($wifiName);
        if (empty($wifiName)) {
            return false;
        }

        $sql = "SELECT COUNT(*) as count FROM attendance_wifi WHERE wifi_name = ?";
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $excludeId = (int)$excludeId;
            $stmt->bind_param("si", $wifiName, $excludeId);
        } else {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("s", $wifiName);
        }

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * Check if gateway already exists
     * @param string $gateway
     * @param int|null $excludeId
     * @return bool
     */
    public function checkGatewayExists($gateway, $excludeId = null)
    {
        $gateway = trim($gateway);
        if (empty($gateway)) {
            return false;
        }

        $sql = "SELECT COUNT(*) as count FROM attendance_wifi WHERE gateway = ?";
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $excludeId = (int)$excludeId;
            $stmt->bind_param("si", $gateway, $excludeId);
        } else {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("s", $gateway);
        }

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * Check WiFi exists (backwards compatibility)
     * @param string $wifiName
     * @param int|null $excludeId
     * @return bool
     */
    public function checkWifiExists($wifiName, $excludeId = null)
    {
        return $this->checkNetworkExists($wifiName, $excludeId);
    }

    /**
     * Toggle network (enable/disable)
     * @param int $networkId
     * @return bool
     */
    public function toggleNetwork($networkId)
    {
        $networkId = (int)$networkId;
        if ($networkId <= 0) {
            return false;
        }

        $sql = "UPDATE attendance_wifi SET is_active = !is_active WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $networkId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Toggle WiFi (backwards compatibility - redirects to toggleNetwork)
     * @param int $wifiId
     * @return bool
     */
    public function toggleWifi($wifiId)
    {
        return $this->toggleNetwork($wifiId);
    }

    /**
     * Delete network
     * @param int $networkId
     * @return bool
     */
    public function deleteNetwork($networkId)
    {
        $networkId = (int)$networkId;
        if ($networkId <= 0) {
            return false;
        }

        $sql = "DELETE FROM attendance_wifi WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("i", $networkId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    /**
     * Delete WiFi (backwards compatibility)
     * @param int $wifiId
     * @return bool
     */
    public function deleteWifi($wifiId)
    {
        return $this->deleteNetwork($wifiId);
    }

    /**
     * ========== NEW: ATTENDANCE CALCULATION WITH HOLIDAYS & LEAVES ==========
     */

    /**
     * Lấy thông tin chi tiết công tháng với phép, lễ, OT (UPDATED 2026)
     * @param string $monthKey - YYYY-MM
     * @return array
     */
    public function getMonthlyAttendanceDetailNew($monthKey)
    {
        require_once 'app/helpers/HolidayCalculator.php';
        require_once 'app/helpers/LeaveCalculator.php';
        require_once 'app/helpers/AttendanceCalculator.php';

        $monthKey = trim((string)$monthKey);
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            return [];
        }

        $monthStart = $monthKey . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        // Lấy danh sách tất cả nhân viên hoạt động
        $employees = $this->getEmployees('', true);

        $result = [];

        foreach ($employees as $employee) {
            $maND = (int)($employee['maND'] ?? 0);
            $hoTen = (string)($employee['hoTen'] ?? '');

            // Lấy dữ liệu chấm công trong tháng
            $attendanceData = $this->getMonthlyAttendanceRaw($maND, $monthStart, $monthEnd);

            // Lấy thông tin phép của nhân viên
            $leaveInfo = $this->getEmployeeLeaveInfo($maND);

            // Lấy các yêu cầu xin phép đã approve
            $leaveRequests = $this->getApprovedLeaveRequests($maND, $monthStart, $monthEnd);

            // Tính toán chi tiết
            $monthlyCalc = AttendanceCalculator::calculateMonthlyAttendance(
                $monthKey,
                $attendanceData,
                $leaveRequests,
                $leaveInfo
            );

            // Lấy số ngày làm việc tiêu chuẩn của tháng
            $standardWorkDays = HolidayCalculator::getWorkingDaysCountInMonth($monthKey);

            // So sánh với tiêu chuẩn
            $comparison = AttendanceCalculator::compareWithStandard(
                $monthlyCalc['totals']['total_work_days'] ?? 0,
                $standardWorkDays
            );

            $result[] = [
                'maND' => $maND,
                'hoTen' => $hoTen,
                'phongBan' => $employee['phongBan'] ?? '',
                'daily_breakdown' => $monthlyCalc['daily_breakdown'] ?? [],
                'work_days' => $monthlyCalc['totals']['total_work_days'] ?? 0,
                'work_hours' => $monthlyCalc['totals']['working_hours'] ?? 0,
                'overtime_hours' => $monthlyCalc['totals']['total_ot_hours'] ?? 0,
                'leave_days_used' => $monthlyCalc['totals']['total_leave_days'] ?? 0,
                'holiday_days' => $monthlyCalc['totals']['total_holiday_days'] ?? 0,
                'weekend_days' => $monthlyCalc['totals']['total_weekend_days'] ?? 0,
                'absent_days' => $monthlyCalc['totals']['total_absent_days'] ?? 0,
                'standard_work_days' => $standardWorkDays,
                'comparison' => $comparison,
                'leave_info' => $leaveInfo,
            ];
        }

        return $result;
    }

    public function getEmployeeDashboardStats($maND, $monthKey)
    {
        $maND = (int)$maND;
        $monthStart = $monthKey . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        // 1. Get raw attendance
        $rawAttendance = $this->getMonthlyAttendanceRaw($maND, $monthStart, $monthEnd);
        
        // 2. Get approved leaves
        $leaves = $this->getApprovedLeaveRequests($maND, $monthStart, $monthEnd);
        
        $totalWorkDays = 0;
        $totalLateMinutes = 0;
        $totalOTMinutes = 0;
        $totalLeaveDays = 0;

        // Fetch shift assignment for this user (most likely fixed for the month)
        $shift = $this->getShiftForUser($maND); 
        $shiftStart = $shift['start_time'] ?? null;
        $shiftEnd = $shift['end_time'] ?? null;

        // Loop through the month
        $daysInMonth = (int)date('t', strtotime($monthStart));
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf("%s-%02d", $monthKey, $d);
            
            // Check leave
            if (isset($leaves[$dateStr])) {
                $totalLeaveDays += (isset($leaves[$dateStr]['is_half_day']) && $leaves[$dateStr]['is_half_day']) ? 0.5 : 1.0;
            }

            // Check attendance
            if (isset($rawAttendance[$dateStr])) {
                $att = $rawAttendance[$dateStr];
                if (!empty($att['checkIn'])) {
                    $totalWorkDays += 1;
                    
                    // Calculate late/OT if shift is defined
                    if ($shiftStart && $shiftEnd) {
                        $status = $this->calculateShiftStatus($att['checkIn'], $att['checkOut'], $shiftStart, $shiftEnd);
                        $totalLateMinutes += (int)($status['minutes_late'] ?? 0);
                        $totalOTMinutes += (int)($status['overtime_minutes'] ?? 0);
                    }
                }
            }
        }

        return [
            'work_days' => $totalWorkDays,
            'late_minutes' => $totalLateMinutes,
            'ot_hours' => round($totalOTMinutes / 60, 1),
            'leave_days' => $totalLeaveDays
        ];
    }

    /**
     * Lấy dữ liệu chấm công thô (raw) trong khoảng ngày
     * @param int $maND
     * @param string $fromDate - YYYY-MM-DD
     * @param string $toDate - YYYY-MM-DD
     * @return array - [date => [checkIn, checkOut, ...]]
     */
    private function getMonthlyAttendanceRaw($maND, $fromDate, $toDate)
    {
        $maND = (int)$maND;
        $fromDate = trim((string)$fromDate);
        $toDate = trim((string)$toDate);

        $sql = "
            SELECT
                DATE(created_at) as attendance_date,
                MIN(CASE WHEN action = 'IN' THEN created_at END) as checkIn,
                MAX(CASE WHEN action = 'OUT' THEN created_at END) as checkOut
            FROM attendance_logs
            WHERE maND = ?
              AND DATE(created_at) >= ?
              AND DATE(created_at) <= ?
            GROUP BY DATE(created_at)
            ORDER BY attendance_date ASC
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('iss', $maND, $fromDate, $toDate);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Convert to [date => data] format
        $data = [];
        foreach ($rows as $row) {
            $date = $row['attendance_date'];
            $data[$date] = [
                'checkIn' => $row['checkIn'],
                'checkOut' => $row['checkOut'],
            ];
        }

        return $data;
    }

    /**
     * Lấy thông tin phép của nhân viên từ database
     * @param int $maND
     * @return array
     */
    public function getEmployeeLeaveInfo($maND)
    {
        $maND = (int)$maND;

        // Kiểm tra xem table `employee_leaves` có tồn tại không
        // Nếu chưa, sử dụng giá trị mặc định
        $table_exists = $this->conn->query("
            SHOW TABLES LIKE 'employee_leaves'
        ")->num_rows > 0;

        if ($table_exists) {
            $sql = "
                SELECT
                    job_type,
                    seniority_years,
                    annual_leave_remaining,
                    annual_leave_used,
                    start_date
                FROM employee_leaves
                WHERE maND = ?
                LIMIT 1
            ";

            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $maND);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($row) {
                    return [
                        'jobType' => $row['job_type'] ?? 'basic',
                        'seniority' => (int)($row['seniority_years'] ?? 0),
                        'usedLeaves' => (float)($row['annual_leave_used'] ?? 0),
                        'remainingLeaves' => (float)($row['annual_leave_remaining'] ?? 0),
                        'startDate' => $row['start_date'] ?? '',
                    ];
                }
            }
        }

        // Mặc định: công việc bình thường, 0 năm thâm niên, 12 ngày phép, chưa dùng
        return [
            'jobType' => 'basic',
            'seniority' => 0,
            'usedLeaves' => 0,
            'remainingLeaves' => 12,
            'startDate' => date('Y-01-01'),
        ];
    }

    /**
     * Lấy các yêu cầu xin phép đã được phê duyệt
     * @param int $maND
     * @param string $fromDate
     * @param string $toDate
     * @return array - [date => leave_type]
     */
    private function getApprovedLeaveRequests($maND, $fromDate, $toDate)
    {
        $maND = (int)$maND;
        $fromDate = trim((string)$fromDate);
        $toDate = trim((string)$toDate);

        // Kiểm tra xem table `leave_requests` có tồn tại không
        $table_exists = $this->conn->query("
            SHOW TABLES LIKE 'leave_requests'
        ")->num_rows > 0;

        if (!$table_exists) {
            return [];
        }

        $sql = "
            SELECT
                leave_date,
                leave_type,
                is_half_day
            FROM leave_requests
            WHERE maND = ?
              AND status = 'approved'
              AND leave_date >= ?
              AND leave_date <= ?
            ORDER BY leave_date ASC
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('iss', $maND, $fromDate, $toDate);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $data = [];
        foreach ($rows as $row) {
            $date = $row['leave_date'];
            $leaveType = $row['leave_type'] ?? 'annual';
            $isHalfDay = (int)($row['is_half_day'] ?? 0);

            if ($isHalfDay) {
                // Nửa ngày phép = 0.5 ngày
                $data[$date] = [
                    'type' => $leaveType,
                    'is_half_day' => true,
                    'work_value_deduction' => 0.5,
                ];
            } else {
                // Ngày phép đầy đủ
                $data[$date] = [
                    'type' => $leaveType,
                    'is_half_day' => false,
                    'work_value_deduction' => 1.0,
                ];
            }
        }

        return $data;
    }

    /**
     * Lấy thông tin ngày lễ tháng
     * @param string $monthKey - YYYY-MM
     * @return array
     */
    public function getHolidaysForMonth($monthKey)
    {
        require_once 'app/helpers/HolidayCalculator.php';

        $monthKey = trim((string)$monthKey);
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            return [];
        }

        $holidays = HolidayCalculator::getHolidaysInMonth($monthKey);

        return [
            'month' => $monthKey,
            'holidays' => $holidays,
            'count' => count($holidays),
            'working_days' => HolidayCalculator::getWorkingDaysCountInMonth($monthKey),
        ];
    }

    /**
     * ========== SYSTEM SETTINGS MANAGEMENT ==========
     */

    /**
     * Lấy tất cả settings
     * @return array
     */
    public function getAllSettings()
    {
        $sql = "SELECT id, setting_key, setting_value, updated_at FROM system_settings ORDER BY setting_key ASC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Lấy tất cả settings (legacy format key => value)
     * @return array
     */
    public function getSettings()
    {
        $sql = "SELECT setting_key, setting_value FROM system_settings ORDER BY setting_key ASC";
        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Lấy giá trị 1 setting
     * @param string $key
     * @param string $default
     * @return string|null
     */
    public function getSettingValue($key, $default = null)
    {
        $key = trim($key);
        $sql = "SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return $default;
        }
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $row['setting_value'] ?? $default;
    }

    /**
     * Cập nhật setting (insert or update)
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function updateSetting($key, $value)
    {
        $key = trim($key);
        $value = trim($value);
        
        if (empty($key)) {
            return false;
        }

        // Check if exists
        $checkSql = "SELECT id FROM system_settings WHERE setting_key = ? LIMIT 1";
        $checkStmt = $this->conn->prepare($checkSql);
        if (!$checkStmt) {
            return false;
        }
        $checkStmt->bind_param("s", $key);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();

        if ($exists) {
            // UPDATE
            $sql = "UPDATE system_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("ss", $value, $key);
        } else {
            // INSERT
            $sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("ss", $key, $value);
        }

        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // ============================
    // ĐƠN NGHỈ PHÉP (Leave Request)
    // ============================

    public function insertLeaveRequest($user_id, $leave_type, $from_date, $to_date, $reason, $evidence_file = null)
    {
        $user_id = (int)$user_id;
        $leave_type = trim($leave_type);
        $from_date = trim($from_date);
        $to_date = trim($to_date);
        $reason = trim($reason);

        $allowedTypes = ['sick', 'personal', 'emergency', 'wedding', 'funeral', 'other'];
        if ($user_id <= 0 || $from_date === '' || $to_date === '' || $reason === '') {
            return false;
        }
        if (!in_array($leave_type, $allowedTypes, true)) {
            $leave_type = 'personal';
        }
        if ($from_date > $to_date) {
            return false;
        }

        $sql = "INSERT INTO don_nghi_phep (user_id, leave_type, from_date, to_date, reason, evidence_file) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("isssss", $user_id, $leave_type, $from_date, $to_date, $reason, $evidence_file);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getLeaveRequestsByUser($user_id)
    {
        $user_id = (int)$user_id;
        $sql = "SELECT lr.*, nd.hoTen,
                       approver.hoTen AS approver_name
                FROM don_nghi_phep lr
                LEFT JOIN nguoidung nd ON nd.maND = lr.user_id
                LEFT JOIN nguoidung approver ON approver.maND = lr.approved_by
                WHERE lr.user_id = ?
                ORDER BY lr.created_at DESC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getAllLeaveRequests()
    {
        $sql = "SELECT lr.*, nd.hoTen, nd.phongBan,
                       approver.hoTen AS approver_name
                FROM don_nghi_phep lr
                LEFT JOIN nguoidung nd ON nd.maND = lr.user_id
                LEFT JOIN nguoidung approver ON approver.maND = lr.approved_by
                ORDER BY
                    CASE lr.status WHEN 'pending' THEN 0 ELSE 1 END,
                    lr.created_at DESC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function updateLeaveRequestStatus($id, $status, $approved_by = null)
    {
        $id = (int)$id;
        $status = trim($status);
        $approved_by = $approved_by !== null ? (int)$approved_by : null;

        if ($id <= 0 || !in_array($status, ['approved', 'rejected'], true)) {
            return false;
        }

        $sql = "UPDATE don_nghi_phep SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'pending'";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("sii", $status, $approved_by, $id);
        $result = $stmt->execute();
        $affected = $stmt->affected_rows > 0;
        $stmt->close();
        return $result && $affected;
    }

    /**
     * Lấy chi tiết đơn nghỉ phép theo ID
     * @param int $id
     * @return array|null
     */
    public function getLeaveRequestById($id)
    {
        $sql = "SELECT d.*, n.hoTen as approver_name 
                FROM don_nghi_phep d
                LEFT JOIN nguoidung n ON d.approved_by = n.maND
                WHERE d.id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getLeaveById($id)
    {
        return $this->getLeaveRequestById($id);
    }

    /**
     * Lấy chi tiết yêu cầu chỉnh sửa theo ID
     * @param int $id
     * @return array|null
     */
    public function getCorrectionById($id)
    {
        $sql = "SELECT * FROM attendance_corrections WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

