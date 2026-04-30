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
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Add missing columns if they don't exist (for existing databases)
        $this->conn->query("ALTER TABLE attendance_wifi ADD COLUMN IF NOT EXISTS ip_range VARCHAR(50) DEFAULT NULL");
        $this->conn->query("ALTER TABLE attendance_wifi ADD COLUMN IF NOT EXISTS gateway VARCHAR(50) DEFAULT NULL");
        $this->conn->query("ALTER TABLE attendance_wifi ADD COLUMN IF NOT EXISTS description VARCHAR(255) DEFAULT NULL");

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
                hr_sender_id INT NOT NULL,
                manager_approver_id INT DEFAULT NULL,
                status ENUM('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
                submitted_at DATETIME DEFAULT NULL,
                approved_at DATETIME DEFAULT NULL,
                note VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_month_key (month_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS system_settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
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

    public function getYeuCauTheoNhanVien($maND)
    {
        $sql = "SELECT id, attendance_date, old_time, new_time, reason, status, hr_note, created_at, updated_at
                FROM attendance_corrections
                WHERE maND = ?
                ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maND);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

    public function getMonthlyWorkSummary($monthKey)
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
                GROUP BY u.maND, u.hoTen, u.phongBan
                ORDER BY u.hoTen";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiss", $defaultWorkMinutes, $defaultWorkMinutes, $monthStart, $monthStart);
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
        $sql = "SELECT c.maND, c.attendance_date, c.new_time, c.reason
                FROM attendance_corrections c
                WHERE c.status = 'approved'
                  AND c.attendance_date >= ?
                  AND c.attendance_date < DATE_ADD(?, INTERVAL 1 MONTH)
                  AND (
                    LOWER(c.reason) LIKE '%ot%'
                    OR LOWER(c.reason) LIKE '%overtime%'
                  )
                ORDER BY c.attendance_date ASC, c.created_at ASC";

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
            $date = $row['attendance_date'] ?? '';
            if ($maND <= 0 || $date === '') {
                continue;
            }

            if (!isset($schedule[$maND])) {
                $schedule[$maND] = [];
            }

            $schedule[$maND][$date] = [
                'label' => 'OT',
                'time' => !empty($row['new_time']) ? substr((string)$row['new_time'], 11, 5) : '',
                'reason' => $row['reason'] ?? '',
            ];
        }

        return $schedule;
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

    public function submitMonthlyApproval($monthKey, $hrSenderId)
    {
        $monthKey = trim($monthKey);
        $hrSenderId = (int)$hrSenderId;
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey) || $hrSenderId <= 0) {
            return false;
        }

        // Keep one record per month even if old DB schema does not have unique index.
        $find = $this->conn->prepare("SELECT id FROM attendance_monthly_approval WHERE month_key = ? ORDER BY id DESC LIMIT 1");
        if (!$find) {
            return false;
        }
        $find->bind_param("s", $monthKey);
        $find->execute();
        $existing = $find->get_result()->fetch_assoc();
        $find->close();

        if (!empty($existing['id'])) {
            $id = (int)$existing['id'];
            $update = $this->conn->prepare("UPDATE attendance_monthly_approval
                                            SET hr_sender_id = ?, status = 'submitted', submitted_at = NOW(), approved_at = NULL, manager_approver_id = NULL, note = NULL
                                            WHERE id = ?");
            if (!$update) {
                return false;
            }
            $update->bind_param("ii", $hrSenderId, $id);
            $ok = $update->execute();
            $update->close();
            return $ok;
        }

        $insert = $this->conn->prepare("INSERT INTO attendance_monthly_approval (month_key, hr_sender_id, status, submitted_at)
                                        VALUES (?, ?, 'submitted', NOW())");
        if (!$insert) {
            return false;
        }
        $insert->bind_param("si", $monthKey, $hrSenderId);
        $ok = $insert->execute();
        $insert->close();
        return $ok;
    }

    public function getMonthlyApprovals($status = null)
    {
        $sql = "SELECT a.id, a.month_key, a.hr_sender_id, a.manager_approver_id, a.status, a.submitted_at, a.approved_at, a.note,
                       u.hoTen AS hr_name,
                       u2.hoTen AS approver_name
                FROM attendance_monthly_approval a
                LEFT JOIN nguoidung u ON u.maND = a.hr_sender_id
                LEFT JOIN nguoidung u2 ON u2.maND = a.manager_approver_id";

        if ($status) {
            $sql .= " WHERE a.status = ?";
        }

        $sql .= " ORDER BY COALESCE(a.approved_at, a.submitted_at, a.created_at) DESC, a.id DESC";

        if ($status) {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $status);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getMonthlyApprovalHistory($year = null, $limit = 50)
    {
        $sql = "SELECT a.id, a.month_key, a.hr_sender_id, a.manager_approver_id, a.status, a.submitted_at, a.approved_at, a.note,
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

    public function getMonthlyApprovalByMonth($monthKey)
    {
        $monthKey = trim($monthKey);
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            return null;
        }

        $stmt = $this->conn->prepare("SELECT a.id, a.month_key, a.status, a.submitted_at, a.approved_at, a.note,
                                             u.hoTen AS hr_name,
                                             u2.hoTen AS approver_name
                                      FROM attendance_monthly_approval a
                                      LEFT JOIN nguoidung u ON u.maND = a.hr_sender_id
                                      LEFT JOIN nguoidung u2 ON u2.maND = a.manager_approver_id
                                      WHERE a.month_key = ?
                                      ORDER BY a.id DESC
                                      LIMIT 1");
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("s", $monthKey);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getMonthlyApprovalDetail($approvalId)
    {
        $approvalId = (int)$approvalId;
        if ($approvalId <= 0) {
            return null;
        }

        $stmt = $this->conn->prepare("SELECT a.id, a.month_key, a.status, a.submitted_at, a.approved_at, a.note,
                                             u.hoTen AS hr_name,
                                             u2.hoTen AS approver_name
                                      FROM attendance_monthly_approval a
                                      LEFT JOIN nguoidung u ON u.maND = a.hr_sender_id
                                      LEFT JOIN nguoidung u2 ON u2.maND = a.manager_approver_id
                                      WHERE a.id = ?
                                      LIMIT 1");
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $approvalId);
        $stmt->execute();
        $approval = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$approval) {
            return null;
        }

        $monthKey = trim($approval['month_key'] ?? '');
        $rows = $this->getMonthlyWorkSummary($monthKey);
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

    public function updateMonthlyApproval($approvalId, $status, $managerId, $note = null)
    {
        $approvalId = (int)$approvalId;
        $managerId = (int)$managerId;
        if (!in_array($status, ['approved', 'rejected'], true) || $approvalId <= 0) {
            return false;
        }

        $sql = "UPDATE attendance_monthly_approval
                SET status = ?, manager_approver_id = ?, approved_at = NOW(), note = ?
            WHERE id = ? AND status = 'submitted'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sisi", $status, $managerId, $note, $approvalId);
        return $stmt->execute();
    }

    public function getCorrectionRequests($status = null, array $filters = [], $limit = 0, $historyOnly = false)
    {
        $sql = "SELECT c.id, c.maND, c.attendance_date, c.old_time, c.new_time, c.reason, c.status, c.hr_note, c.created_at,
                       n.hoTen
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
            $conditions[] = "(n.hoTen LIKE CONCAT('%', ?, '%') OR reason LIKE CONCAT('%', ?, '%'))";
            $types .= 'ss';
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
        $sql = "SELECT id, wifi_name, ip_range, gateway, description, is_active, created_at 
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
     * Add new network with IP range and gateway
     * @param string $wifiName - Network name (SSID or label)
     * @param string $ipRange - IP range prefix (e.g., "192.168.1")
     * @param string $gateway - Gateway IP (e.g., "192.168.1.1")
     * @param string $description - Description
     * @param int $isActive
     * @return bool
     */
    public function addNetwork($wifiName, $ipRange, $gateway, $description = '', $isActive = 1)
    {
        $wifiName = trim($wifiName);
        $ipRange = trim($ipRange);
        $gateway = trim($gateway);
        $description = trim($description);
        $isActive = (int)$isActive;
        
        if (empty($wifiName) || empty($ipRange) || empty($gateway)) {
            return false;
        }
        
        $sql = "INSERT INTO attendance_wifi (wifi_name, ip_range, gateway, description, is_active) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("ssssi", $wifiName, $ipRange, $gateway, $description, $isActive);
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
    public function updateNetwork($networkId, $wifiName, $ipRange, $gateway, $description = '', $isActive = 1)
    {
        $networkId = (int)$networkId;
        $wifiName = trim($wifiName);
        $ipRange = trim($ipRange);
        $gateway = trim($gateway);
        $description = trim($description);
        $isActive = (int)$isActive;
        
        if ($networkId <= 0 || empty($wifiName) || empty($ipRange) || empty($gateway)) {
            return false;
        }

        $sql = "UPDATE attendance_wifi 
                SET wifi_name = ?, ip_range = ?, gateway = ?, description = ?, is_active = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("ssssi", $wifiName, $ipRange, $gateway, $description, $isActive, $networkId);
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
}
