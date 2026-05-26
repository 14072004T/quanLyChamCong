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
            CREATE TABLE IF NOT EXISTS lichSuChamCong (
                id INT AUTO_INCREMENT PRIMARY KEY,
                maND INT NOT NULL,
                hanhDong ENUM('IN', 'OUT') NOT NULL,
                phuongThuc ENUM('LAN', 'QR') NOT NULL DEFAULT 'LAN',
                tenWifi VARCHAR(120) DEFAULT NULL,
                ghiChu VARCHAR(255) DEFAULT NULL,
                ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS suaChamCong (
                id INT AUTO_INCREMENT PRIMARY KEY,
                maND INT NOT NULL,
                ngayChamCong DATE NOT NULL,
                gioCu DATETIME DEFAULT NULL,
                gioMoi DATETIME NOT NULL,
                lyDo TEXT NOT NULL,
                trangThai ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                ghiChuNS VARCHAR(255) DEFAULT NULL,
                ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ngayCapNhat DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS wifiChamCong (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tenWifi VARCHAR(120) NOT NULL UNIQUE,
                daiIP VARCHAR(50) DEFAULT NULL,
                congMacDinh VARCHAR(50) DEFAULT NULL,
                moTa VARCHAR(255) DEFAULT NULL,
                ssid VARCHAR(120) DEFAULT NULL,
                matKhau VARCHAR(120) DEFAULT NULL,
                viTri VARCHAR(255) DEFAULT NULL,
                hoatDong TINYINT(1) NOT NULL DEFAULT 1,
                ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Add missing columns if they don't exist (for existing databases)
        $this->conn->query("ALTER TABLE wifiChamCong ADD COLUMN IF NOT EXISTS daiIP VARCHAR(50) DEFAULT NULL");
        $this->conn->query("ALTER TABLE wifiChamCong ADD COLUMN IF NOT EXISTS congMacDinh VARCHAR(50) DEFAULT NULL");
        $this->conn->query("ALTER TABLE wifiChamCong ADD COLUMN IF NOT EXISTS moTa VARCHAR(255) DEFAULT NULL");
        $this->conn->query("ALTER TABLE wifiChamCong ADD COLUMN IF NOT EXISTS ssid VARCHAR(120) DEFAULT NULL");
        $this->conn->query("ALTER TABLE wifiChamCong ADD COLUMN IF NOT EXISTS matKhau VARCHAR(120) DEFAULT NULL");
        $this->conn->query("ALTER TABLE wifiChamCong ADD COLUMN IF NOT EXISTS viTri VARCHAR(255) DEFAULT NULL");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS caLamViec (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tenCa VARCHAR(100) NOT NULL,
                gioBatDau TIME NOT NULL,
                gioKetThuc TIME NOT NULL,
                hoatDong TINYINT(1) NOT NULL DEFAULT 1,
                ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->query(" 
            CREATE TABLE IF NOT EXISTS caNhanVien (
                id INT AUTO_INCREMENT PRIMARY KEY,
                maND INT NOT NULL,
                maCa INT NOT NULL,
                hieuLucTu DATE NOT NULL,
                hieuLucDen DATE DEFAULT NULL,
                ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->query(" 
            CREATE TABLE IF NOT EXISTS duyetCongThang (
                id INT AUTO_INCREMENT PRIMARY KEY,
                thangNam CHAR(7) NOT NULL,
                phongBan VARCHAR(120) NOT NULL DEFAULT '',
                maNguoiGuiNS INT NOT NULL,
                maNguoiDuyetQL INT DEFAULT NULL,
                trangThai ENUM('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
                ngayGui DATETIME DEFAULT NULL,
                ngayDuyet DATETIME DEFAULT NULL,
                ghiChu VARCHAR(255) DEFAULT NULL,
                ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ngayCapNhat DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_month_dept (thangNam, phongBan)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->conn->query("ALTER TABLE duyetCongThang ADD COLUMN IF NOT EXISTS phongBan VARCHAR(120) NOT NULL DEFAULT ''");
        $this->conn->query("UPDATE duyetCongThang SET phongBan = '' WHERE phongBan IS NULL");

        $oldIndex = $this->conn->query("SHOW INDEX FROM duyetCongThang WHERE Key_name = 'uk_month_key'");
        if ($oldIndex && $oldIndex->num_rows > 0) {
            $this->conn->query("ALTER TABLE duyetCongThang DROP INDEX uk_month_key");
        }
        $newIndex = $this->conn->query("SHOW INDEX FROM duyetCongThang WHERE Key_name = 'uk_month_dept'");
        if ($newIndex && $newIndex->num_rows === 0) {
            $this->conn->query("ALTER TABLE duyetCongThang ADD UNIQUE KEY uk_month_dept (thangNam, phongBan)");
        }

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS duyetCongNhanVien (
                id INT AUTO_INCREMENT PRIMARY KEY,
                thangNam CHAR(7) NOT NULL,
                maND INT NOT NULL,
                maNguoiGuiNS INT NOT NULL,
                trangThai ENUM('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
                ngayGui DATETIME DEFAULT NULL,
                ngayDuyet DATETIME DEFAULT NULL,
                ghiChu VARCHAR(255) DEFAULT NULL,
                ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ngayCapNhat DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uk_month_user (thangNam, maND)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS yeuCauNghiPhep (
                id INT AUTO_INCREMENT PRIMARY KEY,
                maND INT NOT NULL,
                ngayNghi DATE NOT NULL,
                loaiNghiPhep VARCHAR(50) NOT NULL DEFAULT 'annual',
                laNuaNgay TINYINT(1) NOT NULL DEFAULT 0,
                lyDo TEXT DEFAULT NULL,
                trangThai ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                ghiChuQL VARCHAR(255) DEFAULT NULL,
                maNguoiDuyetQL INT DEFAULT NULL,
                ngayDuyet DATETIME DEFAULT NULL,
                ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ngayCapNhat DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->conn->query("ALTER TABLE yeuCauNghiPhep ADD COLUMN IF NOT EXISTS loaiNghiPhep VARCHAR(50) NOT NULL DEFAULT 'annual'");
        $this->conn->query("ALTER TABLE yeuCauNghiPhep ADD COLUMN IF NOT EXISTS laNuaNgay TINYINT(1) NOT NULL DEFAULT 0");
        $this->conn->query("ALTER TABLE yeuCauNghiPhep ADD COLUMN IF NOT EXISTS ghiChuQL VARCHAR(255) DEFAULT NULL");
        $this->conn->query("ALTER TABLE yeuCauNghiPhep ADD COLUMN IF NOT EXISTS maNguoiDuyetQL INT DEFAULT NULL");
        $this->conn->query("ALTER TABLE yeuCauNghiPhep ADD COLUMN IF NOT EXISTS ngayDuyet DATETIME DEFAULT NULL");
        $this->conn->query("ALTER TABLE yeuCauNghiPhep ADD COLUMN IF NOT EXISTS ngayCapNhat DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS yeuCauTangCa (
                id INT AUTO_INCREMENT PRIMARY KEY,
                maND INT NOT NULL,
                ngayTangCa DATE NOT NULL,
                gioBatDau TIME DEFAULT NULL,
                gioKetThuc TIME DEFAULT NULL,
                soGio DECIMAL(5,2) NOT NULL DEFAULT 0,
                lyDo TEXT DEFAULT NULL,
                trangThai ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                ghiChuQL VARCHAR(255) DEFAULT NULL,
                maNguoiDuyetQL INT DEFAULT NULL,
                ngayDuyet DATETIME DEFAULT NULL,
                ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ngayCapNhat DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->conn->query("ALTER TABLE yeuCauTangCa ADD COLUMN IF NOT EXISTS ghiChuQL VARCHAR(255) DEFAULT NULL");
        $this->conn->query("ALTER TABLE yeuCauTangCa ADD COLUMN IF NOT EXISTS maNguoiDuyetQL INT DEFAULT NULL");
        $this->conn->query("ALTER TABLE yeuCauTangCa ADD COLUMN IF NOT EXISTS ngayDuyet DATETIME DEFAULT NULL");
        $this->conn->query("ALTER TABLE yeuCauTangCa ADD COLUMN IF NOT EXISTS ngayCapNhat DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS yeuCauDoiCa (
                id INT AUTO_INCREMENT PRIMARY KEY,
                maND INT NOT NULL,
                ngayYeuCau DATE NOT NULL,
                maCaHienTai INT DEFAULT NULL,
                maCaMoi INT DEFAULT NULL,
                tenCaHienTai VARCHAR(100) DEFAULT NULL,
                tenCaMoi VARCHAR(100) DEFAULT NULL,
                lyDo TEXT DEFAULT NULL,
                trangThai ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                ghiChuQL VARCHAR(255) DEFAULT NULL,
                maNguoiDuyetQL INT DEFAULT NULL,
                ngayDuyet DATETIME DEFAULT NULL,
                ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ngayCapNhat DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $this->conn->query("ALTER TABLE yeuCauDoiCa ADD COLUMN IF NOT EXISTS maCaHienTai INT DEFAULT NULL");
        $this->conn->query("ALTER TABLE yeuCauDoiCa ADD COLUMN IF NOT EXISTS maCaMoi INT DEFAULT NULL");
        $this->conn->query("ALTER TABLE yeuCauDoiCa ADD COLUMN IF NOT EXISTS tenCaHienTai VARCHAR(100) DEFAULT NULL");
        $this->conn->query("ALTER TABLE yeuCauDoiCa ADD COLUMN IF NOT EXISTS tenCaMoi VARCHAR(100) DEFAULT NULL");
        $this->conn->query("ALTER TABLE yeuCauDoiCa ADD COLUMN IF NOT EXISTS ghiChuQL VARCHAR(255) DEFAULT NULL");
        $this->conn->query("ALTER TABLE yeuCauDoiCa ADD COLUMN IF NOT EXISTS maNguoiDuyetQL INT DEFAULT NULL");
        $this->conn->query("ALTER TABLE yeuCauDoiCa ADD COLUMN IF NOT EXISTS ngayDuyet DATETIME DEFAULT NULL");
        $this->conn->query("ALTER TABLE yeuCauDoiCa ADD COLUMN IF NOT EXISTS ngayCapNhat DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS caiDatHeThong (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tenCaiDat VARCHAR(100) NOT NULL UNIQUE,
                giaTri VARCHAR(255) DEFAULT NULL,
                ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ngayCapNhat DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $this->conn->query("
            CREATE TABLE IF NOT EXISTS donNghiPhep (
                id INT AUTO_INCREMENT PRIMARY KEY,
                maND INT NOT NULL,
                loaiNghiPhep VARCHAR(50) NOT NULL DEFAULT 'personal',
                tuNgay DATE NOT NULL,
                denNgay DATE NOT NULL,
                lyDo TEXT NOT NULL,
                tepMinhChung VARCHAR(255) DEFAULT NULL,
                trangThai ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                nguoiDuyet INT DEFAULT NULL,
                ngayDuyet DATETIME DEFAULT NULL,
                ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        // Migration for existing tables
        $this->conn->query("ALTER TABLE donNghiPhep ADD COLUMN IF NOT EXISTS loaiNghiPhep VARCHAR(50) NOT NULL DEFAULT 'personal'");
        $this->conn->query("ALTER TABLE donNghiPhep ADD COLUMN IF NOT EXISTS tepMinhChung VARCHAR(255) DEFAULT NULL");
        $this->conn->query("ALTER TABLE donNghiPhep ADD COLUMN IF NOT EXISTS nguoiDuyet INT DEFAULT NULL");
        $this->conn->query("ALTER TABLE donNghiPhep ADD COLUMN IF NOT EXISTS ngayDuyet DATETIME DEFAULT NULL");

        // Migration: suaChamCong — add nullable columns for edit request feature
        $this->conn->query("ALTER TABLE suaChamCong ADD COLUMN IF NOT EXISTS gioVaoDeXuat DATETIME DEFAULT NULL");
        $this->conn->query("ALTER TABLE suaChamCong ADD COLUMN IF NOT EXISTS gioRaDeXuat DATETIME DEFAULT NULL");
        $this->conn->query("ALTER TABLE suaChamCong ADD COLUMN IF NOT EXISTS tepMinhChung VARCHAR(255) DEFAULT NULL");
    }

    public function chamCong($maND, $hanhDong, $phuongThuc, $wifiName, $ghiChu, $clientIP = null)
    {
        // Get client IP if not provided (server-side only, cannot be spoofed)
        if ($clientIP === null) {
            $clientIP = $this->getServerIP();
        }

        $sql = "INSERT INTO lichSuChamCong (maND, hanhDong, phuongThuc, tenWifi, ghiChu, ngayTao) 
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issss", $maND, $hanhDong, $phuongThuc, $wifiName, $ghiChu);
        return $stmt->execute();
    }

    public function getLichSuTheoNhanVien($maND, $limit = 30)
    {
        $sql = "SELECT id, hanhDong, phuongThuc, tenWifi, ghiChu, ngayTao
                FROM lichSuChamCong
                WHERE maND = ?
                ORDER BY ngayTao DESC
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

    public function taoYeuCauChinhSua($maND, $attendanceDate, $oldTime, $newTime, $lyDo)
    {
        $sql = "INSERT INTO suaChamCong (maND, ngayChamCong, gioCu, gioMoi, lyDo)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issss", $maND, $attendanceDate, $oldTime, $newTime, $lyDo);
        return $stmt->execute();
    }

    /**
     * Insert enhanced edit request with proposed times and evidence file.
     * All new fields are nullable — backward compatible with existing data.
     */
    public function insertEditRequest(array $data)
    {
        $maND = (int)($data['maND'] ?? 0);
        $attendanceDate = trim($data['ngayChamCong'] ?? '');
        $oldTime = !empty($data['gioCu']) ? trim($data['gioCu']) : null;
        $newTime = !empty($data['gioMoi']) ? trim($data['gioMoi']) : null;
        $lyDo = trim($data['lyDo'] ?? '');
        $proposedCheckin = !empty($data['gioVaoDeXuat']) ? trim($data['gioVaoDeXuat']) : null;
        $proposedCheckout = !empty($data['gioRaDeXuat']) ? trim($data['gioRaDeXuat']) : null;
        $evidenceFile = !empty($data['tepMinhChung']) ? trim($data['tepMinhChung']) : null;

        if ($maND <= 0 || $attendanceDate === '' || $lyDo === '') {
            return false;
        }

        // Build gioMoi from gioVaoDeXuat if not provided (backward compat)
        if ($newTime === null && $proposedCheckin !== null) {
            $newTime = $proposedCheckin;
        }
        if ($newTime === null) {
            $newTime = date('Y-m-d H:i:s');
        }

        $sql = "INSERT INTO suaChamCong 
                (maND, ngayChamCong, gioCu, gioMoi, lyDo, gioVaoDeXuat, gioRaDeXuat, tepMinhChung)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("isssssss", $maND, $attendanceDate, $oldTime, $newTime, $lyDo, $proposedCheckin, $proposedCheckout, $evidenceFile);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getYeuCauTheoNhanVien($maND)
    {
        $sql = "SELECT id, ngayChamCong, gioCu, gioMoi, lyDo, trangThai, ghiChuNS, 
                       gioVaoDeXuat, gioRaDeXuat, tepMinhChung,
                       ngayTao, ngayCapNhat
                FROM suaChamCong
                WHERE maND = ?
                ORDER BY ngayTao DESC";
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
     * Returns ngayLamViec, gioVaoDau, gioRaCuoi for each day.
     */
    public function getAttendanceByUser($maND, $limit = 30)
    {
        $maND = (int)$maND;
        $limit = max(1, min((int)$limit, 100));

        $sql = "SELECT 
                    DATE(ngayTao) AS ngayLamViec,
                    MIN(CASE WHEN hanhDong = 'IN' THEN ngayTao END) AS gioVaoDau,
                    MAX(CASE WHEN hanhDong = 'OUT' THEN ngayTao END) AS gioRaCuoi
                FROM lichSuChamCong
                WHERE maND = ?
                GROUP BY DATE(ngayTao)
                ORDER BY ngayLamViec DESC
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

        $sql = "SELECT s.id AS maCa, s.tenCa, s.gioBatDau, s.gioKetThuc
                FROM caNhanVien aes
                JOIN caLamViec s ON s.id = aes.maCa AND s.hoatDong = 1
                WHERE aes.maND = ?
                  AND aes.hieuLucTu <= ?
                  AND (aes.hieuLucDen IS NULL OR aes.hieuLucDen >= ?)
                ORDER BY aes.hieuLucTu DESC
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param("iss", $maND, $date, $date);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Fallback to default shift (Hành chính) if no explicit assignment exists
        if (!$row) {
            $res = $this->conn->query("SELECT id AS maCa, tenCa, gioBatDau, gioKetThuc FROM caLamViec WHERE hoatDong = 1 ORDER BY id ASC LIMIT 1");
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
            }
        }

        return $row ?: null;
    }

    /**
     * Calculate attendance trangThai based on shift times.
     * Handles: on-time, late, early leave, overtime, overnight shifts, missing check-out.
     *
     * @param string|null $checkIn  - Check-in datetime (e.g., '2026-05-05 08:15:00')
     * @param string|null $checkOut - Check-out datetime (nullable for missing check-out)
     * @param string|null $shiftStart - Shift start time (e.g., '08:00:00'), null = no shift
     * @param string|null $shiftEnd   - Shift end time (e.g., '17:00:00'), null = no shift
     * @return array ['statuses' => [], 'minutes_late' => int, 'minutes_early' => int, 'phutTangCa' => int, 'labels' => []]
     */
    public function calculateShiftStatus($checkIn, $checkOut, $shiftStart, $shiftEnd)
    {
        $result = [
            'statuses' => [],
            'minutes_late' => 0,
            'minutes_early' => 0,
            'phutTangCa' => 0,
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

        // Determine if overnight shift (gioKetThuc < gioBatDau, e.g., 22:00 - 06:00)
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
                $result['phutTangCa'] = (int)round($diffOut);
                $result['labels'][] = 'Tăng ca ' . $result['phutTangCa'] . ' phút';
                $result['colors'][] = '#3b82f6';
            }
        }

        // If no special trangThai for check-out, and check-in was on time — overall on_time
        if (count($result['statuses']) === 1 && $result['statuses'][0] === 'on_time' && $checkOut !== null) {
            // Just on_time, no additional trangThai needed
        }

        return $result;
    }

    /**
     * Get today's full shift trangThai for a user (for dashboard display).
     * Returns shift info + attendance trangThai + labels.
     */
    public function getTodayShiftStatus($maND)
    {
        $maND = (int)$maND;
        $today = date('Y-m-d');

        // Get shift assignment (handles NULL safely)
        $shift = $this->getShiftForUser($maND, $today);

        // Get today's check-in/check-out
        $sql = "SELECT 
                    MIN(CASE WHEN hanhDong = 'IN' THEN ngayTao END) AS gioVaoDau,
                    MAX(CASE WHEN hanhDong = 'OUT' THEN ngayTao END) AS gioRaCuoi
                FROM lichSuChamCong
                WHERE maND = ? AND DATE(ngayTao) = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['shift' => $shift, 'trangThai' => ['statuses' => ['error'], 'labels' => ['Lỗi hệ thống'], 'colors' => ['#94a3b8'], 'minutes_late' => 0, 'minutes_early' => 0, 'phutTangCa' => 0], 'gioVaoDau' => null, 'gioRaCuoi' => null];
        }
        $stmt->bind_param("is", $maND, $today);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $firstIn = $row['gioVaoDau'] ?? null;
        $lastOut = $row['gioRaCuoi'] ?? null;

        $shiftStart = $shift['gioBatDau'] ?? null;
        $shiftEnd = $shift['gioKetThuc'] ?? null;

        $trangThai = $this->calculateShiftStatus($firstIn, $lastOut, $shiftStart, $shiftEnd);

        return [
            'shift' => $shift,
            'trangThai' => $trangThai,
            'gioVaoDau' => $firstIn,
            'gioRaCuoi' => $lastOut,
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
                SUM(CASE WHEN hanhDong = 'IN' THEN 1 ELSE 0 END) AS in_today,
                SUM(CASE WHEN hanhDong = 'OUT' THEN 1 ELSE 0 END) AS out_today
            FROM lichSuChamCong
            WHERE DATE(ngayTao) = CURDATE()
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

        $result2 = $this->conn->query("SELECT COUNT(*) AS pending_requests FROM suaChamCong WHERE trangThai = 'pending'");
        if ($result2) {
            $row2 = $result2->fetch_assoc();
            $data['pending_requests'] = (int) ($row2['pending_requests'] ?? 0);
            $data['pending_corrections'] = $data['pending_requests'];
        }

        $result3 = $this->conn->query("SELECT COUNT(*) AS pending_approvals FROM duyetCongThang WHERE trangThai = 'submitted'");
        if ($result3) {
            $row3 = $result3->fetch_assoc();
            $data['pending_approvals'] = (int) ($row3['pending_approvals'] ?? 0);
        }

        return $data;
    }

    public function getEmployees($keyword = '', $activeOnly = false, $limit = 0)
    {
        $sql = "SELECT maND, maTK, hoTen, email, soDienThoai, chucVu, phongBan, trangThai, ngayTao
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

    public function getEmployeesByDepartment($phongBan = '', $keyword = '', $activeOnly = false)
    {
        $phongBan = trim((string)$phongBan);
        $keyword = trim((string)$keyword);
        
        $sql = "SELECT maND, maTK, hoTen, email, soDienThoai, chucVu, phongBan, trangThai, ngayTao
                FROM nguoidung
                WHERE trangThai = 1
                  AND chucVu = 'Nhân viên'";
        $types = '';
        $params = [];

        // Filter by phongBan
        if ($phongBan !== '') {
            $sql .= " AND phongBan = ?";
            $types .= 's';
            $params[] = $phongBan;
        }

        // Filter by keyword
        if ($keyword !== '') {
            $sql .= " AND (hoTen LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%'))";
            $types .= 'ss';
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $sql .= " ORDER BY hoTen ASC";

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
        $sql = "SELECT s.id, s.tenCa, s.gioBatDau, s.gioKetThuc, s.hoatDong, s.ngayTao,
                       (SELECT COUNT(DISTINCT aes.maND) FROM caNhanVien aes
                        JOIN nguoidung nd ON nd.maND = aes.maND
                        WHERE aes.maCa = s.id 
                          AND (aes.hieuLucDen IS NULL OR aes.hieuLucDen >= CURDATE())
                          AND nd.chucVu = 'Nhân viên' AND nd.trangThai = 1) AS assigned_count
                FROM caLamViec s
                ORDER BY s.ngayTao DESC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function saveShift(array $payload)
    {
        $id = (int)($payload['id'] ?? 0);
        $name = trim($payload['tenCa'] ?? '');
        $start = trim($payload['gioBatDau'] ?? '');
        $end = trim($payload['gioKetThuc'] ?? '');
        $isActive = (int)($payload['hoatDong'] ?? 1);

        if ($name === '' || $start === '' || $end === '' || $start >= $end) {
            return false;
        }

        if ($id > 0) {
            $sql = "UPDATE caLamViec SET tenCa = ?, gioBatDau = ?, gioKetThuc = ?, hoatDong = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssii", $name, $start, $end, $isActive, $id);
            return $stmt->execute();
        }

        $sql = "INSERT INTO caLamViec (tenCa, gioBatDau, gioKetThuc, hoatDong) VALUES (?, ?, ?, ?)";
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

        $closeCurrent = $this->conn->prepare("UPDATE caNhanVien SET hieuLucDen = DATE_SUB(?, INTERVAL 1 DAY) WHERE maND = ? AND hieuLucDen IS NULL");
        $closeCurrent->bind_param("si", $effectiveFrom, $maND);
        $closeCurrent->execute();

        $insert = $this->conn->prepare("INSERT INTO caNhanVien (maND, maCa, hieuLucTu) VALUES (?, ?, ?)");
        $insert->bind_param("iis", $maND, $shiftId, $effectiveFrom);
        return $insert->execute();
    }

    public function getMonthlyWorkSummary($monthKey, $phongBan = '')
    {
        $monthStart = $monthKey . '-01';
        $defaultWorkMinutes = (int)$this->getSettingValue('DEFAULT_WORK_MINUTES', '480');

        $sql = "SELECT u.maND, u.hoTen, u.phongBan,
                       COUNT(CASE WHEN d.gioVaoDau IS NOT NULL THEN 1 END) AS work_days,
                       ROUND(SUM(d.phutLamViec) / 60, 2) AS work_hours,
                       ROUND(SUM(CASE WHEN d.phutLamViec > ? THEN d.phutLamViec - ? ELSE 0 END) / 60, 2) AS overtime_hours
                FROM nguoidung u
                LEFT JOIN (
                    SELECT maND,
                           DATE(ngayTao) AS ngayLamViec,
                           MIN(CASE WHEN hanhDong = 'IN' THEN ngayTao END) AS gioVaoDau,
                           MAX(CASE WHEN hanhDong = 'OUT' THEN ngayTao END) AS gioRaCuoi,
                           CASE
                               WHEN MIN(CASE WHEN hanhDong = 'IN' THEN ngayTao END) IS NOT NULL
                                AND MAX(CASE WHEN hanhDong = 'OUT' THEN ngayTao END) IS NOT NULL
                               THEN GREATEST(TIMESTAMPDIFF(MINUTE,
                                       MIN(CASE WHEN hanhDong = 'IN' THEN ngayTao END),
                                       MAX(CASE WHEN hanhDong = 'OUT' THEN ngayTao END)
                                   ), 0)
                               ELSE 0
                           END AS phutLamViec
                    FROM lichSuChamCong
                    WHERE ngayTao >= ?
                      AND ngayTao < DATE_ADD(?, INTERVAL 1 MONTH)
                    GROUP BY maND, DATE(ngayTao)
                ) d ON d.maND = u.maND
                WHERE u.trangThai = 1
                  AND u.chucVu = 'Nhân viên'
                  AND (? = '' OR u.phongBan = ?)
                GROUP BY u.maND, u.hoTen, u.phongBan
                ORDER BY u.hoTen";

        $stmt = $this->conn->prepare($sql);
            $dept = trim((string)$phongBan);
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
        $sql = "SELECT maND, ngayTangCa, gioBatDau, gioKetThuc, soGio, lyDo
                FROM yeuCauTangCa
                WHERE trangThai = 'approved'
                  AND ngayTangCa >= ?
                  AND ngayTangCa < DATE_ADD(?, INTERVAL 1 MONTH)
                ORDER BY ngayTangCa ASC, ngayTao ASC";

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
            $date = $row['ngayTangCa'] ?? '';
            if ($maND <= 0 || $date === '') {
                continue;
            }

            if (!isset($schedule[$maND])) {
                $schedule[$maND] = [];
            }

            $schedule[$maND][$date] = [
                'label' => 'OT',
                'time' => trim(($row['gioBatDau'] ? substr((string)$row['gioBatDau'], 0, 5) : '') . ' - ' . ($row['gioKetThuc'] ? substr((string)$row['gioKetThuc'], 0, 5) : ''), ' -'),
                'soGio' => (float)($row['soGio'] ?? 0),
                'lyDo' => $row['lyDo'] ?? '',
            ];
        }

        return $schedule;
    }

    public function getManagerEmployeeRequests(array $filters = [], $limit = 300)
    {
        $limit = max(1, min((int)$limit, 500));
        $trangThai = trim($filters['trangThai'] ?? '');
        $type = trim($filters['type'] ?? '');
        $keyword = trim($filters['q'] ?? '');
        $date = trim($filters['date'] ?? '');
        $dateFrom = trim($filters['date_from'] ?? '');
        $dateTo = trim($filters['date_to'] ?? '');
        $phongBan = trim($filters['phongBan'] ?? '');

        $rows = [];
        if ($type === '' || $type === 'leave') {
            $rows = array_merge($rows, $this->getLeaveApprovalRequests($trangThai, $keyword, $date, $dateFrom, $dateTo, $phongBan, $limit));
        }
        if ($type === '' || $type === 'ot') {
            $rows = array_merge($rows, $this->getOtApprovalRequests($trangThai, $keyword, $date, $dateFrom, $dateTo, $phongBan, $limit));
        }
        if ($type === '' || $type === 'shift') {
            $rows = array_merge($rows, $this->getShiftChangeApprovalRequests($trangThai, $keyword, $date, $dateFrom, $dateTo, $phongBan, $limit));
        }

        usort($rows, function ($a, $b) {
            return strcmp((string)($b['ngayTao'] ?? ''), (string)($a['ngayTao'] ?? ''));
        });

        return array_slice($rows, 0, $limit);
    }

    public function processManagerEmployeeRequest($type, $requestId, $hanhDong, $managerId, $ghiChu = '')
    {
        $type = trim((string)$type);
        $requestId = (int)$requestId;
        $managerId = (int)$managerId;
        if ($requestId <= 0 || !in_array($type, ['leave', 'ot', 'shift'], true) || !in_array($hanhDong, ['approve', 'reject'], true)) {
            return false;
        }

        $trangThai = $hanhDong === 'approve' ? 'approved' : 'rejected';
        if ($type === 'leave') {
            $sql = "UPDATE yeuCauNghiPhep
                    SET trangThai = ?, ghiChuQL = ?, maNguoiDuyetQL = ?, ngayDuyet = NOW(), ngayCapNhat = NOW()
                    WHERE id = ? AND trangThai = 'pending'";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param('ssii', $trangThai, $ghiChu, $managerId, $requestId);
            return $stmt->execute();
        }

        if ($type === 'ot') {
            $sql = "UPDATE yeuCauTangCa
                    SET trangThai = ?, ghiChuQL = ?, maNguoiDuyetQL = ?, ngayDuyet = NOW(), ngayCapNhat = NOW()
                    WHERE id = ? AND trangThai = 'pending'";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->bind_param('ssii', $trangThai, $ghiChu, $managerId, $requestId);
            return $stmt->execute();
        }

        return $this->processShiftChangeRequest($requestId, $trangThai, $managerId, $ghiChu);
    }

    private function getLeaveApprovalRequests($trangThai, $keyword, $date, $dateFrom, $dateTo, $phongBan, $limit)
    {
        $sql = "SELECT CONCAT('leave:', r.id) AS uid, r.id, 'leave' AS request_type,
                       r.maND, r.ngayNghi AS ngayYeuCau, r.loaiNghiPhep, r.laNuaNgay,
                       NULL AS gioBatDau, NULL AS gioKetThuc, NULL AS soGio,
                       NULL AS tenCaHienTai, NULL AS tenCaMoi,
                       r.lyDo, r.trangThai, r.ghiChuQL, r.ngayTao, r.ngayCapNhat,
                       n.hoTen, n.chucVu, n.phongBan
                FROM yeuCauNghiPhep r
                LEFT JOIN nguoidung n ON n.maND = r.maND";
        return $this->fetchApprovalRequestRows($sql, 'r.ngayNghi', $trangThai, $keyword, $date, $dateFrom, $dateTo, $phongBan, $limit);
    }

    private function getOtApprovalRequests($trangThai, $keyword, $date, $dateFrom, $dateTo, $phongBan, $limit)
    {
        $sql = "SELECT CONCAT('ot:', r.id) AS uid, r.id, 'ot' AS request_type,
                       r.maND, r.ngayTangCa AS ngayYeuCau, NULL AS loaiNghiPhep, 0 AS laNuaNgay,
                       r.gioBatDau, r.gioKetThuc, r.soGio,
                       NULL AS tenCaHienTai, NULL AS tenCaMoi,
                       r.lyDo, r.trangThai, r.ghiChuQL, r.ngayTao, r.ngayCapNhat,
                       n.hoTen, n.chucVu, n.phongBan
                FROM yeuCauTangCa r
                LEFT JOIN nguoidung n ON n.maND = r.maND";
        return $this->fetchApprovalRequestRows($sql, 'r.ngayTangCa', $trangThai, $keyword, $date, $dateFrom, $dateTo, $phongBan, $limit);
    }

    private function getShiftChangeApprovalRequests($trangThai, $keyword, $date, $dateFrom, $dateTo, $phongBan, $limit)
    {
        $sql = "SELECT CONCAT('shift:', r.id) AS uid, r.id, 'shift' AS request_type,
                       r.maND, r.ngayYeuCau, NULL AS loaiNghiPhep, 0 AS laNuaNgay,
                       NULL AS gioBatDau, NULL AS gioKetThuc, NULL AS soGio,
                       COALESCE(r.tenCaHienTai, cs.tenCa) AS tenCaHienTai,
                       COALESCE(r.tenCaMoi, ns.tenCa) AS tenCaMoi,
                       r.lyDo, r.trangThai, r.ghiChuQL, r.ngayTao, r.ngayCapNhat,
                       n.hoTen, n.chucVu, n.phongBan
                FROM yeuCauDoiCa r
                LEFT JOIN nguoidung n ON n.maND = r.maND
                LEFT JOIN caLamViec cs ON cs.id = r.maCaHienTai
                LEFT JOIN caLamViec ns ON ns.id = r.maCaMoi";
        return $this->fetchApprovalRequestRows($sql, 'r.ngayYeuCau', $trangThai, $keyword, $date, $dateFrom, $dateTo, $phongBan, $limit);
    }

    private function fetchApprovalRequestRows($baseSql, $dateColumn, $trangThai, $keyword, $date, $dateFrom, $dateTo, $phongBan, $limit)
    {
        $conditions = [];
        $types = '';
        $params = [];

        if ($trangThai !== '' && in_array($trangThai, ['pending', 'approved', 'rejected'], true)) {
            $conditions[] = "r.trangThai = ?";
            $types .= 's';
            $params[] = $trangThai;
        }

        if ($keyword !== '') {
            $conditions[] = "(n.hoTen LIKE CONCAT('%', ?, '%') OR n.phongBan LIKE CONCAT('%', ?, '%') OR r.lyDo LIKE CONCAT('%', ?, '%'))";
            $types .= 'sss';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        if ($phongBan !== '') {
            $conditions[] = "n.phongBan = ?";
            $types .= 's';
            $params[] = $phongBan;
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
        $sql .= " ORDER BY r.ngayTao DESC LIMIT " . (int)$limit;

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

    private function processShiftChangeRequest($requestId, $trangThai, $managerId, $ghiChu)
    {
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("SELECT maND, ngayYeuCau, maCaMoi FROM yeuCauDoiCa WHERE id = ? AND trangThai = 'pending' FOR UPDATE");
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

            $stmt = $this->conn->prepare("UPDATE yeuCauDoiCa
                    SET trangThai = ?, ghiChuQL = ?, maNguoiDuyetQL = ?, ngayDuyet = NOW(), ngayCapNhat = NOW()
                    WHERE id = ? AND trangThai = 'pending'");
            if (!$stmt) {
                $this->conn->rollback();
                return false;
            }
            $stmt->bind_param('ssii', $trangThai, $ghiChu, $managerId, $requestId);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok && $trangThai === 'approved' && (int)($row['maCaMoi'] ?? 0) > 0) {
                $ok = $this->assignShift((int)$row['maND'], (int)$row['maCaMoi'], $row['ngayYeuCau']);
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

    public function getAttendanceReport($fromDate, $toDate, $phongBan = '')
    {
        $validDepts = ['Sản xuất', 'Kho', 'QC', 'Bảo trì'];
        $placeholders = implode(',', array_fill(0, count($validDepts), '?'));
        
        $sql = "SELECT u.maND, u.hoTen, u.phongBan,
                       COUNT(DISTINCT DATE(l.ngayTao)) AS work_days,
                       SUM(CASE WHEN l.hanhDong = 'IN' THEN 1 ELSE 0 END) AS checkin_count,
                       SUM(CASE WHEN l.hanhDong = 'OUT' THEN 1 ELSE 0 END) AS checkout_count
                FROM nguoidung u
                LEFT JOIN lichSuChamCong l ON l.maND = u.maND
                    AND DATE(l.ngayTao) >= ?
                    AND DATE(l.ngayTao) <= ?
                WHERE u.trangThai = 1
                  AND u.chucVu = 'Nhân viên'
                  AND u.phongBan IN ($placeholders)";

        $params = [$fromDate, $toDate];
        $types = 'ss';
        
        foreach ($validDepts as $dept) {
            $params[] = $dept;
            $types .= 's';
        }

        if ($phongBan !== '') {
            $sql .= " AND u.phongBan = ?";
            $params[] = $phongBan;
            $types .= 's';
        }

        $sql .= " GROUP BY u.maND, u.hoTen, u.phongBan ORDER BY u.hoTen";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
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

    public function getValidDepartments()
    {
        return ['Sản xuất', 'Kho', 'QC', 'Bảo trì'];
    }

    public function submitMonthlyApproval($monthKey, $hrSenderId, $phongBan = '')
    {
        $monthKey = trim($monthKey);
        $hrSenderId = (int)$hrSenderId;
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey) || $hrSenderId <= 0) {
            return false;
        }

        $departments = [];
        $phongBan = trim((string)$phongBan);
        if ($phongBan !== '') {
            $departments = [$phongBan];
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

            $find = $this->conn->prepare("SELECT id FROM duyetCongThang WHERE thangNam = ? AND phongBan = ? ORDER BY id DESC LIMIT 1");
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
                $update = $this->conn->prepare("UPDATE duyetCongThang
                                                SET maNguoiGuiNS = ?, trangThai = 'submitted', ngayGui = NOW(), ngayDuyet = NULL, maNguoiDuyetQL = NULL, ghiChu = NULL
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

            $insert = $this->conn->prepare("INSERT INTO duyetCongThang (thangNam, phongBan, maNguoiGuiNS, trangThai, ngayGui)
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

    public function getMonthlyApprovals($trangThai = null, $phongBan = '')
    {
        $sql = "SELECT a.id, a.thangNam, a.maNguoiGuiNS, a.maNguoiDuyetQL, a.trangThai, a.ngayGui, a.ngayDuyet, a.ghiChu,
                       a.phongBan,
                       u.hoTen AS hr_name,
                       u2.hoTen AS approver_name
                FROM duyetCongThang a
                LEFT JOIN nguoidung u ON u.maND = a.maNguoiGuiNS
                LEFT JOIN nguoidung u2 ON u2.maND = a.maNguoiDuyetQL";

        $conditions = [];
        $types = '';
        $params = [];

        if ($trangThai) {
            $conditions[] = "a.trangThai = ?";
            $types .= 's';
            $params[] = $trangThai;
        }

        $phongBan = trim((string)$phongBan);
        if ($phongBan !== '') {
            $conditions[] = "a.phongBan = ?";
            $types .= 's';
            $params[] = $phongBan;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY COALESCE(a.ngayDuyet, a.ngayGui, a.ngayTao) DESC, a.id DESC";

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

    public function getMonthlyApprovalHistory($year = null, $limit = 50, $phongBan = '')
    {
        $sql = "SELECT a.id, a.thangNam, a.maNguoiGuiNS, a.maNguoiDuyetQL, a.trangThai, a.ngayGui, a.ngayDuyet, a.ghiChu,
                       a.phongBan,
                       u.hoTen AS hr_name,
                       u2.hoTen AS approver_name
                FROM duyetCongThang a
                LEFT JOIN nguoidung u ON u.maND = a.maNguoiGuiNS
                LEFT JOIN nguoidung u2 ON u2.maND = a.maNguoiDuyetQL
                WHERE a.trangThai IN ('approved', 'rejected')";

        $params = [];
        $types = '';

        if ($year && preg_match('/^\d{4}$/', $year)) {
            $sql .= " AND a.thangNam LIKE ?";
            $params[] = $year . '%';
            $types = 's';
        }

        $phongBan = trim((string)$phongBan);
        if ($phongBan !== '') {
            $sql .= " AND a.phongBan = ?";
            $params[] = $phongBan;
            $types .= 's';
        }

        $sql .= " ORDER BY a.ngayDuyet DESC, a.id DESC";

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

        $sql = "SELECT a.id, a.thangNam, a.maNguoiGuiNS, a.maNguoiDuyetQL, a.trangThai, a.ngayGui, a.ngayDuyet, a.ghiChu,
                       u.hoTen AS hr_name,
                       u2.hoTen AS approver_name
                FROM duyetCongThang a
                LEFT JOIN nguoidung u ON u.maND = a.maNguoiGuiNS
                LEFT JOIN nguoidung u2 ON u2.maND = a.maNguoiDuyetQL
                WHERE a.maNguoiGuiNS = ?";

        $types = 'i';
        $params = [$hrSenderId];

        $statuses = array_values(array_filter($statuses, function ($trangThai) {
            return in_array($trangThai, ['draft', 'submitted', 'approved', 'rejected'], true);
        }));

        if (!empty($statuses)) {
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $sql .= " AND a.trangThai IN ($placeholders)";
            $types .= str_repeat('s', count($statuses));
            foreach ($statuses as $trangThai) {
                $params[] = $trangThai;
            }
        }

        $sql .= " ORDER BY COALESCE(a.ngayDuyet, a.ngayGui, a.ngayTao) DESC, a.id DESC";

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

    public function getMonthlyApprovalByMonth($monthKey, $phongBan = '')
    {
        $monthKey = trim($monthKey);
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            return null;
        }

        $phongBan = trim((string)$phongBan);
        if ($phongBan === '') {
            $stmt = $this->conn->prepare("SELECT trangThai, ngayGui, ngayDuyet
                                          FROM duyetCongThang
                                          WHERE thangNam = ?");
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

            $trangThai = 'approved';
            $latestSubmitted = '';
            $latestApproved = '';
            foreach ($rows as $row) {
                $rowStatus = $row['trangThai'] ?? '';
                if ($rowStatus === 'submitted') {
                    $trangThai = 'submitted';
                } elseif ($rowStatus === 'rejected' && $trangThai !== 'submitted') {
                    $trangThai = 'rejected';
                }

                $submittedAt = (string)($row['ngayGui'] ?? '');
                $approvedAt = (string)($row['ngayDuyet'] ?? '');
                if ($submittedAt > $latestSubmitted) {
                    $latestSubmitted = $submittedAt;
                }
                if ($approvedAt > $latestApproved) {
                    $latestApproved = $approvedAt;
                }
            }

            return [
                'thangNam' => $monthKey,
                'trangThai' => $trangThai,
                'ngayGui' => $latestSubmitted,
                'ngayDuyet' => $latestApproved,
                'phongBan' => 'ALL',
            ];
        }
        $sql = "SELECT a.id, a.thangNam, a.trangThai, a.ngayGui, a.ngayDuyet, a.ghiChu,
                       a.phongBan,
                       u.hoTen AS hr_name,
                       u2.hoTen AS approver_name
                FROM duyetCongThang a
                LEFT JOIN nguoidung u ON u.maND = a.maNguoiGuiNS
                LEFT JOIN nguoidung u2 ON u2.maND = a.maNguoiDuyetQL
                WHERE a.thangNam = ?";

        $params = [$monthKey];
        $types = 's';
        if ($phongBan !== '') {
            $sql .= " AND a.phongBan = ?";
            $params[] = $phongBan;
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

    public function getMonthlyApprovalDetail($approvalId, $phongBan = '')
    {
        $approvalId = (int)$approvalId;
        if ($approvalId <= 0) {
            return null;
        }

        $phongBan = trim((string)$phongBan);
        $sql = "SELECT a.id, a.thangNam, a.trangThai, a.ngayGui, a.ngayDuyet, a.ghiChu,
                       a.phongBan,
                       u.hoTen AS hr_name,
                       u2.hoTen AS approver_name
                FROM duyetCongThang a
                LEFT JOIN nguoidung u ON u.maND = a.maNguoiGuiNS
                LEFT JOIN nguoidung u2 ON u2.maND = a.maNguoiDuyetQL
                WHERE a.id = ?";
        if ($phongBan !== '') {
            $sql .= " AND a.phongBan = ?";
        }
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        if ($phongBan !== '') {
            $stmt->bind_param("is", $approvalId, $phongBan);
        } else {
            $stmt->bind_param("i", $approvalId);
        }
        $stmt->execute();
        $approval = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$approval) {
            return null;
        }

        $monthKey = trim($approval['thangNam'] ?? '');
        $rows = $this->getMonthlyWorkSummary($monthKey, $approval['phongBan'] ?? '');
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

    public function updateMonthlyApproval($approvalId, $trangThai, $managerId, $ghiChu = null, $phongBan = '')
    {
        $approvalId = (int)$approvalId;
        $managerId = (int)$managerId;
        if (!in_array($trangThai, ['approved', 'rejected'], true) || $approvalId <= 0) {
            return false;
        }

        $phongBan = trim((string)$phongBan);
        $sql = "UPDATE duyetCongThang
                SET trangThai = ?, maNguoiDuyetQL = ?, ngayDuyet = NOW(), ghiChu = ?
                WHERE id = ? AND trangThai = 'submitted'";
        if ($phongBan !== '') {
            $sql .= " AND phongBan = ?";
        }
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        if ($phongBan !== '') {
            $stmt->bind_param("sisis", $trangThai, $managerId, $ghiChu, $approvalId, $phongBan);
        } else {
            $stmt->bind_param("sisi", $trangThai, $managerId, $ghiChu, $approvalId);
        }
        return $stmt->execute();
    }

    public function getCorrectionRequests($trangThai = null, array $filters = [], $limit = 0, $historyOnly = false)
    {
        $sql = "SELECT c.id, c.maND, c.ngayChamCong, c.gioCu, c.gioMoi, c.lyDo, c.trangThai, c.ghiChuNS, c.ngayTao, c.ngayCapNhat, c.tepMinhChung,
                       n.hoTen, n.chucVu, n.phongBan
                FROM suaChamCong c
                LEFT JOIN nguoidung n ON n.maND = c.maND";

        $conditions = [];
        $types = '';
        $params = [];

        if ($trangThai) {
            $conditions[] = "c.trangThai = ?";
            $types .= 's';
            $params[] = $trangThai;
        }

        if ($historyOnly) {
            $conditions[] = "c.trangThai <> 'pending'";
        }

        $keyword = trim($filters['q'] ?? '');
        if ($keyword !== '') {
            $conditions[] = "(n.hoTen LIKE CONCAT('%', ?, '%') OR n.phongBan LIKE CONCAT('%', ?, '%') OR lyDo LIKE CONCAT('%', ?, '%'))";
            $types .= 'sss';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $date = trim($filters['date'] ?? '');
        if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $conditions[] = "c.ngayChamCong = ?";
            $types .= 's';
            $params[] = $date;
        }

        $type = trim($filters['type'] ?? '');
        if ($type !== '') {
            $conditions[] = "c.lyDo LIKE CONCAT('%', ?, '%')";
            $types .= 's';
            $params[] = $type;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY c.ngayTao DESC";

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

    public function processCorrection($correctionId, $hanhDong, $ghiChu = '')
    {
        $correctionId = (int)$correctionId;
        if ($correctionId <= 0) {
            return false;
        }

        $trangThai = $hanhDong === 'approve' ? 'approved' : 'rejected';
        $sql = "UPDATE suaChamCong SET trangThai = ?, ghiChuNS = ?, ngayCapNhat = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $trangThai, $ghiChu, $correctionId);
        return $stmt->execute();
    }

    /**
     * Kiểm tra WiFi nội bộ
     * @return bool
     */
    public function checkWifi()
    {
        // Check if there are any active WiFi networks configured
        $sql = "SELECT COUNT(*) AS count FROM wifiChamCong WHERE hoatDong = 1";
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
        $sql = "SELECT id FROM wifiChamCong WHERE tenWifi = ? AND hoatDong = 1 LIMIT 1";
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
        $sql = "SELECT tenWifi FROM wifiChamCong WHERE hoatDong = 1 ORDER BY id ASC LIMIT 1";
        $result = $this->conn->query($sql);
        if (!$result) {
            return null;
        }
        $row = $result->fetch_assoc();
        return $row['tenWifi'] ?? null;
    }

    /**
     * Lấy danh sách cấu hình WiFi đang hoạt động (Tên + Dải IP)
     * @return array
     */
    public function getActiveWifiConfigurations()
    {
        $sql = "SELECT tenWifi, daiIP FROM wifiChamCong WHERE hoatDong = 1";
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
        $sql = "SELECT id, hanhDong, phuongThuc, tenWifi, ghiChu, ngayTao
                FROM lichSuChamCong
                WHERE maND = ? AND DATE(ngayTao) = CURDATE()
                ORDER BY ngayTao ASC";
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
        $sql = "SELECT hanhDong FROM lichSuChamCong
                WHERE maND = ? AND DATE(ngayTao) = CURDATE()
                ORDER BY ngayTao DESC
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $maND);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['hanhDong'] ?? null;
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

        $sql = "SELECT id, hanhDong, phuongThuc, tenWifi, ghiChu, ngayTao
                FROM lichSuChamCong
                WHERE maND = ? 
                AND DATE(ngayTao) >= ? 
                AND DATE(ngayTao) <= ?
                ORDER BY ngayTao DESC";
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
        $sql = "SELECT daiIP FROM wifiChamCong WHERE hoatDong = 1";
        $result = $this->conn->query($sql);
        
        if (!$result) {
            // If query fails, return empty (fail closed - no users allowed)
            return [];
        }

        $networks = [];
        while ($row = $result->fetch_assoc()) {
            $range = trim($row['daiIP']);
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
        $sql = "SELECT id, tenWifi, daiIP, congMacDinh, moTa, ssid, matKhau, viTri, hoatDong, ngayTao 
                FROM wifiChamCong ORDER BY id DESC";
        $result = $this->conn->query($sql);
        
        if (!$result) {
            return [];
        }

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id' => (int)($row['id'] ?? 0),
                'tenWifi' => (string)($row['tenWifi'] ?? ''),
                'daiIP' => (string)($row['daiIP'] ?? ''),
                'congMacDinh' => (string)($row['congMacDinh'] ?? ''),
                'moTa' => (string)($row['moTa'] ?? ''),
                'ssid' => (string)($row['ssid'] ?? ''),
                'matKhau' => (string)($row['matKhau'] ?? ''),
                'viTri' => (string)($row['viTri'] ?? ''),
                'hoatDong' => (int)($row['hoatDong'] ?? 0),
                'ngayTao' => (string)($row['ngayTao'] ?? '')
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
        $sql = "SELECT id, tenWifi, daiIP, congMacDinh, moTa, ssid, matKhau, viTri, hoatDong, ngayTao 
                FROM wifiChamCong WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    /**
     * Add new network with IP range and congMacDinh
     * @param string $wifiName - Network name (SSID or label)
     * @param string $ipRange - IP range prefix (e.g., "192.168.1")
     * @param string $congMacDinh - Gateway IP (e.g., "192.168.1.1")
     * @param string $moTa - Description
     * @param int $isActive
     * @return bool
     */
    public function addNetwork($wifiName, $ipRange, $congMacDinh, $moTa = '', $isActive = 1, $ssid = null, $matKhau = null, $viTri = null)
    {
        $wifiName = trim($wifiName);
        $ipRange = trim($ipRange);
        $congMacDinh = trim($congMacDinh);
        $moTa = trim($moTa);
        $isActive = (int)$isActive;
        $ssid = $ssid !== null ? trim($ssid) : null;
        $matKhau = $matKhau !== null ? trim($matKhau) : null;
        $viTri = $viTri !== null ? trim($viTri) : null;
        
        if (empty($wifiName) || empty($ipRange) || empty($congMacDinh)) {
            return false;
        }
        
        $sql = "INSERT INTO wifiChamCong (tenWifi, daiIP, congMacDinh, moTa, hoatDong, ssid, matKhau, viTri) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("ssssisss", $wifiName, $ipRange, $congMacDinh, $moTa, $isActive, $ssid, $matKhau, $viTri);
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
        // Legacy phuongThuc - just insert with tenWifi only
        $wifiName = trim($wifiName);
        $isActive = (int)$isActive;
        
        if (empty($wifiName)) {
            return false;
        }
        
        $sql = "INSERT INTO wifiChamCong (tenWifi, hoatDong) VALUES (?, ?)";
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
     * @param string $congMacDinh
     * @param string $moTa
     * @param int $isActive
     * @return bool
     */
    public function updateNetwork($networkId, $wifiName, $ipRange, $congMacDinh, $moTa = '', $isActive = 1, $ssid = null, $matKhau = null, $viTri = null)
    {
        $networkId = (int)$networkId;
        $wifiName = trim($wifiName);
        $ipRange = trim($ipRange);
        $congMacDinh = trim($congMacDinh);
        $moTa = trim($moTa);
        $isActive = (int)$isActive;
        $ssid = $ssid !== null ? trim($ssid) : null;
        $matKhau = $matKhau !== null ? trim($matKhau) : null;
        $viTri = $viTri !== null ? trim($viTri) : null;
        
        if ($networkId <= 0 || empty($wifiName) || empty($ipRange) || empty($congMacDinh)) {
            return false;
        }

        $sql = "UPDATE wifiChamCong 
                SET tenWifi = ?, daiIP = ?, congMacDinh = ?, moTa = ?, hoatDong = ?, ssid = ?, matKhau = ?, viTri = ? 
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("ssssisssi", $wifiName, $ipRange, $congMacDinh, $moTa, $isActive, $ssid, $matKhau, $viTri, $networkId);
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
            $sql = "UPDATE wifiChamCong SET tenWifi = ?, hoatDong = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sii", $wifiName, $isActive, $wifiId);
        } else {
            $sql = "UPDATE wifiChamCong SET tenWifi = ? WHERE id = ?";
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

        $sql = "SELECT COUNT(*) as count FROM wifiChamCong WHERE tenWifi = ?";
        
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
     * Check if congMacDinh already exists
     * @param string $congMacDinh
     * @param int|null $excludeId
     * @return bool
     */
    public function checkGatewayExists($congMacDinh, $excludeId = null)
    {
        $congMacDinh = trim($congMacDinh);
        if (empty($congMacDinh)) {
            return false;
        }

        $sql = "SELECT COUNT(*) as count FROM wifiChamCong WHERE congMacDinh = ?";
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $excludeId = (int)$excludeId;
            $stmt->bind_param("si", $congMacDinh, $excludeId);
        } else {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("s", $congMacDinh);
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

        $sql = "UPDATE wifiChamCong SET hoatDong = !hoatDong WHERE id = ?";
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

        $sql = "DELETE FROM wifiChamCong WHERE id = ?";
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
    public function getMonthlyAttendanceDetailNew($monthKey, $phongBan = '')
    {
        require_once 'app/helpers/HolidayCalculator.php';
        require_once 'app/helpers/LeaveCalculator.php';
        require_once 'app/helpers/AttendanceCalculator.php';

        $monthKey = trim((string)$monthKey);
        $phongBan = trim((string)$phongBan);
        $validDepts = ['Sản xuất', 'Kho', 'QC', 'Bảo trì'];
        
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            return [];
        }

        $monthStart = $monthKey . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        // Lấy danh sách tất cả nhân viên hoạt động
        $allEmployees = $this->getEmployees('', true);
        
        // Chỉ lấy nhân viên (filter theo phòng ban nếu có)
        $employees = array_filter($allEmployees, function($e) use ($monthEnd, $phongBan, $validDepts) {
            if (mb_strtolower(trim($e['chucVu'] ?? ''), 'UTF-8') !== 'nhân viên') {
                return false;
            }
            
            // Filter theo phòng ban hợp lệ
            $empDept = (string)($e['phongBan'] ?? '');
            if (!in_array($empDept, $validDepts, true)) {
                return false;
            }
            
            // Filter theo phòng ban cụ thể nếu có
            if ($phongBan !== '') {
                if ($empDept !== $phongBan) {
                    return false;
                }
            }
            
            // Bỏ qua nhân viên được tạo sau tháng đang xem
            $createdAt = !empty($e['ngayTao']) ? substr($e['ngayTao'], 0, 10) : null;
            if ($createdAt && $createdAt > $monthEnd) {
                return false;
            }
            return true;
        });

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
                'work_days' => round((float)($monthlyCalc['totals']['total_work_days'] ?? 0), 1),
                'work_hours' => round((float)($monthlyCalc['totals']['working_hours'] ?? 0), 2),
                'overtime_hours' => round((float)($monthlyCalc['totals']['total_ot_hours'] ?? 0), 2),
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
        $shiftStart = $shift['gioBatDau'] ?? null;
        $shiftEnd = $shift['gioKetThuc'] ?? null;

        // Loop through the month
        $daysInMonth = (int)date('t', strtotime($monthStart));
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf("%s-%02d", $monthKey, $d);
            
            // Check leave
            if (isset($leaves[$dateStr])) {
                $totalLeaveDays += (isset($leaves[$dateStr]['laNuaNgay']) && $leaves[$dateStr]['laNuaNgay']) ? 0.5 : 1.0;
            }

            // Check attendance
            if (isset($rawAttendance[$dateStr])) {
                $att = $rawAttendance[$dateStr];
                if (!empty($att['checkIn'])) {
                    $totalWorkDays += 1;
                    
                    // Calculate late/OT if shift is defined
                    if ($shiftStart && $shiftEnd) {
                        $trangThai = $this->calculateShiftStatus($att['checkIn'], $att['checkOut'], $shiftStart, $shiftEnd);
                        $totalLateMinutes += (int)($trangThai['minutes_late'] ?? 0);
                        $totalOTMinutes += (int)($trangThai['phutTangCa'] ?? 0);
                    }
                }
            }
        }

        return [
            'work_days' => $totalWorkDays,
            'phutDiTre' => $totalLateMinutes,
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

        // 1. Get raw logs from lichSuChamCong
        $sql = "
            SELECT
                DATE(ngayTao) as ngayChamCong,
                MIN(CASE WHEN hanhDong = 'IN' THEN ngayTao END) as checkIn,
                MAX(CASE WHEN hanhDong = 'OUT' THEN ngayTao END) as checkOut
            FROM lichSuChamCong
            WHERE maND = ?
              AND DATE(ngayTao) >= ?
              AND DATE(ngayTao) <= ?
            GROUP BY DATE(ngayTao)
            ORDER BY ngayChamCong ASC
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
            $date = $row['ngayChamCong'];
            $data[$date] = [
                'checkIn' => $row['checkIn'],
                'checkOut' => $row['checkOut'],
            ];
        }

        // 2. Fetch approved corrections and OVERRIDE raw logs
        $sqlCorr = "SELECT ngayChamCong, gioVaoDeXuat, gioRaDeXuat 
                    FROM suaChamCong 
                    WHERE maND = ? AND trangThai = 'approved' 
                    AND ngayChamCong >= ? AND ngayChamCong <= ?";
        $stmtCorr = $this->conn->prepare($sqlCorr);
        if ($stmtCorr) {
            $stmtCorr->bind_param('iss', $maND, $fromDate, $toDate);
            $stmtCorr->execute();
            $corrections = $stmtCorr->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtCorr->close();

            foreach ($corrections as $corr) {
                $date = $corr['ngayChamCong'];
                if (!isset($data[$date])) {
                    $data[$date] = ['checkIn' => null, 'checkOut' => null];
                }
                
                // Override only if proposed values are provided in the request
                if (!empty($corr['gioVaoDeXuat'])) {
                    $data[$date]['checkIn'] = $corr['gioVaoDeXuat'];
                }
                if (!empty($corr['gioRaDeXuat'])) {
                    $data[$date]['checkOut'] = $corr['gioRaDeXuat'];
                }
            }
        }

        ksort($data); // Keep chronological order
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
     * @return array - [date => loaiNghiPhep]
     */
    private function getApprovedLeaveRequests($maND, $fromDate, $toDate)
    {
        $maND = (int)$maND;
        $fromDate = trim((string)$fromDate);
        $toDate = trim((string)$toDate);

        // Sử dụng bảng `donNghiPhep` thay vì `yeuCauNghiPhep` (vì yeuCauNghiPhep không tồn tại hoặc không dùng nữa)
        $sql = "
            SELECT
                id,
                tuNgay,
                denNgay,
                loaiNghiPhep,
                lyDo
            FROM donNghiPhep
            WHERE maND = ?
              AND trangThai = 'approved'
              AND (
                  (tuNgay >= ? AND tuNgay <= ?) OR 
                  (denNgay >= ? AND denNgay <= ?) OR
                  (tuNgay <= ? AND denNgay >= ?)
              )
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('issssss', $maND, $fromDate, $toDate, $fromDate, $toDate, $fromDate, $toDate);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $data = [];
        foreach ($rows as $row) {
            $currentDate = new DateTime($row['tuNgay']);
            $endDate = new DateTime($row['denNgay']);
            $leaveType = $row['loaiNghiPhep'] ?? 'annual';
            $leaveId = $row['id'] ?? 0;
            $lyDo = $row['lyDo'] ?? '';

            while ($currentDate <= $endDate) {
                $dateStr = $currentDate->format('Y-m-d');
                if ($dateStr >= $fromDate && $dateStr <= $toDate) {
                    $data[$dateStr] = [
                        'type' => $leaveType,
                        'laNuaNgay' => false,
                        'work_value_deduction' => 1.0,
                        'leave_id' => $leaveId,
                        'lyDo' => $lyDo
                    ];
                }
                $currentDate->modify('+1 day');
            }
        }

        return $data;
    }

    /**
     * Kiểm tra xem nhân viên có đang trong thời gian nghỉ phép đã được duyệt vào ngày hôm nay hay không
     * @param int $maND
     * @return bool
     */
    public function hasApprovedLeaveForToday($maND)
    {
        $maND = (int)$maND;
        $today = date('Y-m-d');
        
        $sql = "
            SELECT 1
            FROM donNghiPhep
            WHERE maND = ?
              AND trangThai = 'approved'
              AND tuNgay <= ? 
              AND denNgay >= ?
            LIMIT 1
        ";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        
        $stmt->bind_param('iss', $maND, $today, $today);
        $stmt->execute();
        $result = $stmt->get_result();
        $hasLeave = $result->num_rows > 0;
        $stmt->close();
        
        return $hasLeave;
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
        $sql = "SELECT id, tenCaiDat, giaTri, ngayCapNhat FROM caiDatHeThong ORDER BY tenCaiDat ASC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Lấy tất cả settings (legacy format key => value)
     * @return array
     */
    public function getSettings()
    {
        $sql = "SELECT tenCaiDat, giaTri FROM caiDatHeThong ORDER BY tenCaiDat ASC";
        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['tenCaiDat']] = $row['giaTri'];
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
        $sql = "SELECT giaTri FROM caiDatHeThong WHERE tenCaiDat = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return $default;
        }
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $row['giaTri'] ?? $default;
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
        $checkSql = "SELECT id FROM caiDatHeThong WHERE tenCaiDat = ? LIMIT 1";
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
            $sql = "UPDATE caiDatHeThong SET giaTri = ?, ngayCapNhat = CURRENT_TIMESTAMP WHERE tenCaiDat = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param("ss", $value, $key);
        } else {
            // INSERT
            $sql = "INSERT INTO caiDatHeThong (tenCaiDat, giaTri) VALUES (?, ?)";
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

    public function insertLeaveRequest($maND, $loaiNghiPhep, $tuNgay, $denNgay, $lyDo, $tepMinhChung = null)
    {
        $maND = (int)$maND;
        $loaiNghiPhep = trim($loaiNghiPhep);
        $tuNgay = trim($tuNgay);
        $denNgay = trim($denNgay);
        $lyDo = trim($lyDo);

        $allowedTypes = ['sick', 'personal', 'emergency', 'wedding', 'funeral', 'other'];
        if ($maND <= 0 || $tuNgay === '' || $denNgay === '' || $lyDo === '') {
            return false;
        }
        if (!in_array($loaiNghiPhep, $allowedTypes, true)) {
            $loaiNghiPhep = 'personal';
        }
        if ($tuNgay > $denNgay) {
            return false;
        }

        $sql = "INSERT INTO donNghiPhep (maND, loaiNghiPhep, tuNgay, denNgay, lyDo, tepMinhChung) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("isssss", $maND, $loaiNghiPhep, $tuNgay, $denNgay, $lyDo, $tepMinhChung);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getLeaveRequestsByUser($maND)
    {
        $maND = (int)$maND;
        $sql = "SELECT lr.*, nd.hoTen,
                       approver.hoTen AS approver_name
                FROM donNghiPhep lr
                LEFT JOIN nguoidung nd ON nd.maND = lr.maND
                LEFT JOIN nguoidung approver ON approver.maND = lr.nguoiDuyet
                WHERE lr.maND = ?
                ORDER BY lr.ngayTao DESC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("i", $maND);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getAllLeaveRequests()
    {
        $sql = "SELECT lr.*, nd.hoTen, nd.phongBan,
                       approver.hoTen AS approver_name
                FROM donNghiPhep lr
                LEFT JOIN nguoidung nd ON nd.maND = lr.maND
                LEFT JOIN nguoidung approver ON approver.maND = lr.nguoiDuyet
                ORDER BY
                    CASE lr.trangThai WHEN 'pending' THEN 0 ELSE 1 END,
                    lr.ngayTao DESC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function updateLeaveRequestStatus($id, $trangThai, $nguoiDuyet = null)
    {
        $id = (int)$id;
        $trangThai = trim($trangThai);
        $nguoiDuyet = $nguoiDuyet !== null ? (int)$nguoiDuyet : null;

        if ($id <= 0 || !in_array($trangThai, ['approved', 'rejected'], true)) {
            return false;
        }

        $sql = "UPDATE donNghiPhep SET trangThai = ?, nguoiDuyet = ?, ngayDuyet = NOW() WHERE id = ? AND trangThai = 'pending'";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("sii", $trangThai, $nguoiDuyet, $id);
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
                FROM donNghiPhep d
                LEFT JOIN nguoidung n ON d.nguoiDuyet = n.maND
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
        $sql = "SELECT * FROM suaChamCong WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =============================================
    // EMPLOYEE TIMESHEET APPROVAL (Bảng công tháng cho nhân viên)
    // =============================================

    /**
     * HR gửi bảng công đến từng nhân viên có dữ liệu chấm công trong tháng
     */
    public function submitTimesheetToEmployees($monthKey, $hrSenderId)
    {
        $monthKey = trim($monthKey);
        $hrSenderId = (int)$hrSenderId;
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey) || $hrSenderId <= 0) {
            return false;
        }

        // Lấy danh sách nhân viên có dữ liệu chấm công trong tháng
        $startDate = $monthKey . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        $sql = "SELECT DISTINCT n.maND
                FROM nguoidung n
                INNER JOIN tongHopNgayCong ads ON ads.maND = n.maND
                    AND ads.ngayLamViec >= ? AND ads.ngayLamViec <= ?
                WHERE n.trangThai = 1 AND n.chucVu = 'Nhân viên'";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($employees)) return false;

        $okAll = true;
        foreach ($employees as $emp) {
            $maND = (int)$emp['maND'];

            // Kiểm tra nếu đã tồn tại
            $find = $this->conn->prepare("SELECT id FROM duyetCongNhanVien WHERE thangNam = ? AND maND = ? LIMIT 1");
            if (!$find) { $okAll = false; continue; }
            $find->bind_param('si', $monthKey, $maND);
            $find->execute();
            $existing = $find->get_result()->fetch_assoc();
            $find->close();

            if (!empty($existing['id'])) {
                // Cập nhật lại (gửi lại)
                $update = $this->conn->prepare("UPDATE duyetCongNhanVien SET maNguoiGuiNS = ?, trangThai = 'submitted', ngayGui = NOW(), ngayDuyet = NULL, ghiChu = NULL WHERE id = ?");
                if (!$update) { $okAll = false; continue; }
                $id = (int)$existing['id'];
                $update->bind_param('ii', $hrSenderId, $id);
                $okAll = $update->execute() && $okAll;
                $update->close();
            } else {
                // Tạo mới
                $insert = $this->conn->prepare("INSERT INTO duyetCongNhanVien (thangNam, maND, maNguoiGuiNS, trangThai, ngayGui) VALUES (?, ?, ?, 'submitted', NOW())");
                if (!$insert) { $okAll = false; continue; }
                $insert->bind_param('sii', $monthKey, $maND, $hrSenderId);
                $okAll = $insert->execute() && $okAll;
                $insert->close();
            }
        }

        return $okAll;
    }

    /**
     * Lấy danh sách bảng công tháng đã gửi cho 1 nhân viên
     */
    public function getEmployeeTimesheetList($maND)
    {
        $maND = (int)$maND;
        if ($maND <= 0) return [];

        $sql = "SELECT eta.id, eta.thangNam, eta.trangThai, eta.ngayGui, eta.ngayDuyet, eta.ghiChu,
                       u.hoTen AS hr_name
                FROM duyetCongNhanVien eta
                LEFT JOIN nguoidung u ON u.maND = eta.maNguoiGuiNS
                WHERE eta.maND = ?
                ORDER BY eta.thangNam DESC, eta.id DESC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('i', $maND);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Lấy chi tiết bảng công tháng cho 1 nhân viên (dữ liệu chấm công)
     */
    public function getEmployeeTimesheetDetail($timesheetId, $maND)
    {
        $timesheetId = (int)$timesheetId;
        $maND = (int)$maND;
        if ($timesheetId <= 0 || $maND <= 0) return null;

        $sql = "SELECT eta.id, eta.thangNam, eta.trangThai, eta.ngayGui, eta.ngayDuyet, eta.ghiChu,
                       u.hoTen AS hr_name
                FROM duyetCongNhanVien eta
                LEFT JOIN nguoidung u ON u.maND = eta.maNguoiGuiNS
                WHERE eta.id = ? AND eta.maND = ?
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param('ii', $timesheetId, $maND);
        $stmt->execute();
        $approval = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$approval) return null;

        $monthKey = trim($approval['thangNam']);
        $monthStart = $monthKey . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        // --- DYNAMIC CALCULATION ---
        require_once 'app/helpers/AttendanceCalculator.php';
        
        $attendanceData = $this->getMonthlyAttendanceRaw($maND, $monthStart, $monthEnd);
        $leaveInfo = $this->getEmployeeLeaveInfo($maND);
        $leaveRequests = $this->getApprovedLeaveRequests($maND, $monthStart, $monthEnd);
        
        // Fetch shift assignment
        $shift = $this->getShiftForUser($maND);
        $shiftStart = $shift['gioBatDau'] ?? null;
        $shiftEnd = $shift['gioKetThuc'] ?? null;

        $monthlyCalc = AttendanceCalculator::calculateMonthlyAttendance(
            $monthKey,
            $attendanceData,
            $leaveRequests,
            $leaveInfo
        );

        $dailyRows = [];
        $totalLateMinutes = 0;
        foreach ($monthlyCalc['daily_breakdown'] as $date => $day) {
            $lateMinutes = 0;
            if ($day['day_type'] === 'working' && !empty($day['check_in']) && $shiftStart && $shiftEnd) {
                $trangThai = $this->calculateShiftStatus($day['check_in'], $day['check_out'], $shiftStart, $shiftEnd);
                $lateMinutes = (int)($trangThai['minutes_late'] ?? 0);
                $totalLateMinutes += $lateMinutes;
            }

            $dailyRows[] = [
                'ngayLamViec' => $date,
                'gioVaoDau' => $day['check_in'] ?? null,
                'gioRaCuoi' => $day['check_out'] ?? null,
                'phutLamViec' => $day['phutLamViec'] ?? 0,
                'phutTangCa' => ($day['ot_hours'] ?? 0) * 60,
                'phutDiTre' => $lateMinutes,
                'trangThai' => $day['day_type']
            ];
        }

        return [
            'approval' => $approval,
            'daily' => $dailyRows,
            'summary' => [
                'work_days' => round($monthlyCalc['totals']['total_work_days'], 1),
                'total_work_hours' => round($monthlyCalc['totals']['working_hours'], 2),
                'total_ot_hours' => round($monthlyCalc['totals']['total_ot_hours'], 2),
                'total_late_hours' => round($totalLateMinutes / 60, 2),
            ],
        ];
    }

    /**
     * Nhân viên duyệt bảng công
     */
    public function approveEmployeeTimesheet($timesheetId, $maND, $ghiChu = '')
    {
        $timesheetId = (int)$timesheetId;
        $maND = (int)$maND;
        if ($timesheetId <= 0 || $maND <= 0) return false;

        $sql = "UPDATE duyetCongNhanVien
                SET trangThai = 'approved', ngayDuyet = NOW(), ghiChu = ?
                WHERE id = ? AND maND = ? AND trangThai = 'submitted'";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('sii', $ghiChu, $timesheetId, $maND);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    /**
     * Đếm bảng công chờ nhân viên duyệt
     */
    public function countPendingTimesheets($maND)
    {
        $maND = (int)$maND;
        if ($maND <= 0) return 0;
        $sql = "SELECT COUNT(*) AS cnt FROM duyetCongNhanVien WHERE maND = ? AND trangThai = 'submitted'";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0;
        $stmt->bind_param('i', $maND);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Lấy danh sách bảng công chờ nhân viên duyệt (cho notification)
     */
    public function getPendingTimesheets($maND, $limit = 5)
    {
        $maND = (int)$maND;
        if ($maND <= 0) return [];
        $sql = "SELECT eta.id, eta.thangNam, eta.ngayGui,
                       u.hoTen AS hr_name
                FROM duyetCongNhanVien eta
                LEFT JOIN nguoidung u ON u.maND = eta.maNguoiGuiNS
                WHERE eta.maND = ? AND eta.trangThai = 'submitted'
                ORDER BY eta.ngayGui DESC
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('ii', $maND, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * HR: Lấy tổng quan trạng thái bảng công nhân viên đã gửi theo tháng
     */
    public function getTimesheetApprovalSummary($monthKey = null)
    {
        $sql = "SELECT eta.thangNam,
                       COUNT(*) AS total,
                       SUM(CASE WHEN eta.trangThai = 'submitted' THEN 1 ELSE 0 END) AS pending,
                       SUM(CASE WHEN eta.trangThai = 'approved' THEN 1 ELSE 0 END) AS approved,
                       MAX(eta.ngayGui) AS last_submitted
                FROM duyetCongNhanVien eta";
        $params = [];
        $types = '';
        if ($monthKey) {
            $sql .= " WHERE eta.thangNam = ?";
            $params[] = $monthKey;
            $types = 's';
        }
        $sql .= " GROUP BY eta.thangNam ORDER BY eta.thangNam DESC";

        if ($types) {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return [];
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    /**
     * HR: Lấy chi tiết trạng thái phê duyệt của từng nhân viên theo kỳ công
     */
    public function getTimesheetApprovalDetails($monthKey)
    {
        $sql = "SELECT eta.id, eta.maND, u.hoTen, u.phongBan, eta.trangThai, eta.ngayGui, eta.ngayDuyet
                FROM duyetCongNhanVien eta
                JOIN nguoidung u ON u.maND = eta.maND
                WHERE eta.thangNam = ?
                ORDER BY u.phongBan, u.hoTen";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('s', $monthKey);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}


