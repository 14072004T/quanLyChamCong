<?php
require_once __DIR__ . '/HolidayCalculator.php';

class ExcelExporter
{
    /**
     * Xuất báo cáo chấm công chi tiết tháng
     * 
     * @param array $data - Dữ liệu từ ChamCongModel::getMonthlyAttendanceDetailNew
     * @param string $monthKey - Kỳ chấm công (YYYY-MM)
     * @param string $phongBan - Tên phòng ban
     * @param string $userName - Người xuất báo cáo
     */
    public function exportAttendanceReport($data, $monthKey, $phongBan = '', $userName = '')
    {
        $year = (int)substr($monthKey, 0, 4);
        $month = (int)substr($monthKey, 5, 2);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $currentDate = date('d/m/Y H:i:s');
        $deptName = $phongBan ?: 'Tất cả phòng ban';

        $fileName = 'Bao_Cao_Cham_Cong_' . ($phongBan ? $phongBan . '_' : '') . $monthKey . '.xls';

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        // Xuất BOM cho UTF-8 để Excel nhận diện tiếng Việt
        echo "\xEF\xBB\xBF";

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
    body { font-family: "Times New Roman", Times, serif; }
    .table-container { width: 100%; }
    table { border-collapse: collapse; width: 100%; }
    td, th { border: 1px solid #000000; padding: 5px; font-size: 11pt; vertical-align: middle; }
    
    /* Header Styles */
    .company-name { font-weight: bold; font-size: 14pt; text-transform: uppercase; border: none; }
    .report-title { font-weight: bold; font-size: 18pt; text-align: center; border: none; color: #1e40af; }
    .report-meta { font-style: italic; text-align: center; border: none; font-size: 11pt; }
    .info-cell { border: none; font-size: 11pt; }
    
    /* Table Styles */
    .header-cell { background-color: #4472C4; color: #FFFFFF; font-weight: bold; text-align: center; }
    .stt-cell { text-align: center; width: 40px; }
    .name-cell { text-align: left; width: 200px; font-weight: bold; }
    .dept-cell { text-align: left; width: 120px; }
    
    /* Data Cells */
    .day-cell { text-align: center; width: 30px; }
    .holiday-cell { background-color: #FFEB9C; color: #9C6500; font-weight: bold; }
    .weekend-cell { background-color: #FFEB9C; color: #9C6500; font-weight: bold; }
    .leave-cell { background-color: #C6EFCE; color: #006100; font-weight: bold; }
    .absent-cell { background-color: #FFC7CE; color: #9C0006; }
    
    /* Summary Cells */
    .summary-header { background-color: #D9E1F2; font-weight: bold; text-align: center; }
    .summary-value { font-weight: bold; text-align: center; background-color: #F8FAFC; }
    .summary-soGio { background-color: #FECACA; color: #b91c1c; font-weight: bold; }
    
    /* Signature Styles */
    .sig-container { border: none; margin-top: 30px; }
    .sig-cell { border: none; text-align: center; padding-top: 20px; width: 33%; }
    .sig-title { font-weight: bold; font-size: 12pt; }
    .sig-space { height: 80px; border: none; }
    
    /* Legend */
    .legend { border: none; font-size: 10pt; margin-top: 15px; }
</style>
</head>
<body>

<div class="table-container">
    <table>
        <tr>
            <td colspan="' . ($daysInMonth + 9) . '" class="company-name">CÔNG TY TNHH QUẢN LÝ NHÂN SỰ 2026</td>
        </tr>
        <tr>
            <td colspan="' . ($daysInMonth + 9) . '" class="report-title">BẢNG TỔNG HỢP CÔNG THÁNG ' . $month . '/' . $year . '</td>
        </tr>
        <tr>
            <td colspan="' . ($daysInMonth + 9) . '" class="report-meta">Phòng ban: ' . htmlspecialchars($deptName) . '</td>
        </tr>
        <tr>
            <td colspan="' . ($daysInMonth / 2) . '" class="info-cell"><strong>Người xuất:</strong> ' . htmlspecialchars($userName) . '</td>
            <td colspan="' . ($daysInMonth / 2 + 9) . '" class="info-cell" style="text-align: right;"><strong>Ngày xuất:</strong> ' . $currentDate . '</td>
        </tr>
        <tr style="height: 20px;"><td colspan="' . ($daysInMonth + 9) . '" style="border: none;"></td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="header-cell">STT</th>
                <th rowspan="2" class="header-cell">Mã NV</th>
                <th rowspan="2" class="header-cell">Họ và Tên</th>
                <th rowspan="2" class="header-cell">Phòng ban</th>
                <th colspan="' . $daysInMonth . '" class="header-cell">Ngày trong tháng</th>
                <th colspan="6" class="header-cell">Tổng cộng</th>
            </tr>
            <tr>';
        
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $i);
            $dayOfWeek = date('w', strtotime($dateStr));
            $isHoliday = HolidayCalculator::isHoliday($dateStr);
            $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
            $style = ($isHoliday || $isWeekend) ? 'background-color: #FFEB9C;' : '';
            $html .= '<th class="header-cell" style="' . $style . '">' . $i . '</th>';
        }

        $html .= '<th class="summary-header">Công</th>
            <th class="summary-header summary-soGio" style="background-color: #FECACA; color: #b91c1c;">Giờ làm (h)</th>
                <th class="summary-header">Phép</th>
                <th class="summary-header">Lễ</th>
                <th class="summary-header">Vắng</th>
                <th class="summary-header">OT (h)</th>
            </tr>
        </thead>
        <tbody>';

        $stt = 1;
        foreach ($data as $row) {
            $html .= '<tr>
                <td class="stt-cell">' . $stt++ . '</td>
                <td style="text-align: center;">' . ($row['maND'] ?? '') . '</td>
                <td class="name-cell">' . htmlspecialchars($row['hoTen'] ?? '') . '</td>
                <td class="dept-cell">' . htmlspecialchars($row['phongBan'] ?? '') . '</td>';

            $daily = $row['daily_breakdown'] ?? [];
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $i);
                $dayData = $daily[$dateStr] ?? null;
                
                $val = '';
                $class = 'day-cell';
                
                $isHoliday = HolidayCalculator::isHoliday($dateStr);
                if ($isHoliday) {
                    $val = 'L';
                    $class .= ' holiday-cell';
                } elseif ($dayData) {
                    $type = $dayData['day_type'] ?? '';
                    if ($type === 'weekend') {
                        $dayOfWeek = date('w', strtotime($dateStr));
                        $val = ($dayOfWeek == 0) ? 'CN' : 'T7';
                        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                            $class .= ' weekend-cell';
                        }
                    } elseif ($type === 'leave') {
                        $val = 'P';
                        $class .= ' leave-cell';
                    } elseif ($type === 'absent') {
                        $val = 'V';
                        $class .= ' absent-cell';
                    } elseif ($type === 'working') {
                        $workVal = (float)($dayData['work_value'] ?? 0);
                        if ($workVal == 1.0) $val = 'x';
                        elseif ($workVal > 0) $val = $workVal;
                        else $val = '';
                    }
                }

                $html .= '<td class="' . $class . '">' . $val . '</td>';
            }

            $html .= '<td class="summary-value">' . ($row['work_days'] ?? 0) . '</td>
                <td class="summary-value summary-soGio" style="background-color: #FECACA; color: #b91c1c;">' . ($row['work_hours'] ?? 0) . '</td>
                <td class="summary-value">' . ($row['leave_days_used'] ?? 0) . '</td>
                <td class="summary-value">' . ($row['holiday_days'] ?? 0) . '</td>
                <td class="summary-value">' . ($row['absent_days'] ?? 0) . '</td>
                <td class="summary-value">' . ($row['overtime_hours'] ?? 0) . '</td>
            </tr>';
        }

        $html .= '</tbody>
    </table>

    <table class="legend">
        <tr>
            <td colspan="' . ($daysInMonth + 9) . '" style="border: none; padding-top: 15px;">
                <strong>Ghi chú:</strong> 
                (x): Công đầy đủ; (0.5): Nửa công; (P): Nghỉ phép; (L): Nghỉ lễ; (CN): Chủ nhật; (T7): Thứ bảy; (V): Vắng không phép
            </td>
        </tr>
    </table>

    <table class="sig-container">
        <tr>
            <td class="sig-cell">
                <div class="sig-title">Người lập biểu</div>
                <div style="font-size: 9pt;">(Ký, họ tên)</div>
                <div class="sig-space"></div>
                <div style="font-weight: bold;">' . htmlspecialchars($userName) . '</div>
            </td>
            <td class="sig-cell">
                <div class="sig-title">Trưởng phòng</div>
                <div style="font-size: 9pt;">(Ký, họ tên)</div>
                <div class="sig-space"></div>
                <div style="font-weight: bold;">................................</div>
            </td>
            <td class="sig-cell">
                <div class="sig-title">Giám đốc</div>
                <div style="font-size: 9pt;">(Ký, họ tên)</div>
                <div class="sig-space"></div>
                <div style="font-weight: bold;">................................</div>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="border: none; text-align: center; font-style: italic; padding-top: 20px;">
                Ngày ' . date('d') . ' tháng ' . date('m') . ' năm ' . date('Y') . '
            </td>
        </tr>
    </table>
</div>

</body>
</html>';

        echo $html;
        exit;
    }

    /**
     * Xuất báo cáo theo loại (Excel/XLS)
     * @param array $data
     * @param string $loai  tonghop|chitiet|vipham|yeucau|tangca
     * @param string $title
     * @param string $fromDate
     * @param string $toDate
     * @param string $phongBan
     * @param string $userName
     */
    public function exportByType($data, $loai, $title, $fromDate, $toDate, $phongBan = '', $userName = '')
    {
        $deptName = $phongBan ?: 'Tất cả phòng ban';
        $dateRange = date('d/m/Y', strtotime($fromDate)) . ' — ' . date('d/m/Y', strtotime($toDate));
        $currentDate = date('d/m/Y H:i:s');
        $suffix = ($phongBan ? $phongBan . '_' : '') . $fromDate . '_' . $toDate;
        $fileName = 'BaoCao_' . strtoupper($loai) . '_' . $suffix . '.xls';

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        echo "\xEF\xBB\xBF";

        $headerStyle = 'background-color:#4472C4;color:#fff;font-weight:bold;text-align:center;border:1px solid #000;padding:6px;font-size:11pt;';
        $cellStyle   = 'border:1px solid #ccc;padding:5px;font-size:10pt;vertical-align:middle;';
        $titleStyle  = 'font-size:16pt;font-weight:bold;text-align:center;color:#1e40af;border:none;';
        $metaStyle   = 'font-size:10pt;text-align:center;color:#475569;border:none;font-style:italic;';

        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>body{font-family:"Times New Roman",Times,serif;}
table{border-collapse:collapse;width:100%;}
.th{' . $headerStyle . '}
.td{' . $cellStyle . '}
.alt{background-color:#f0f7ff;}
.num{text-align:center;}
.ok{color:#166534;font-weight:700;} .warn{color:#92400e;font-weight:700;} .err{color:#991b1b;font-weight:700;}
</style></head><body>';

        // Header block
        $html .= '<table><tr><td colspan="20" style="font-weight:bold;font-size:14pt;text-transform:uppercase;border:none;">CÔNG TY TNHH QUẢN LÝ NHÂN SỰ 2026</td></tr>
<tr><td colspan="20" style="' . $titleStyle . '">' . htmlspecialchars($title) . '</td></tr>
<tr><td colspan="20" style="' . $metaStyle . '">Phòng ban: ' . htmlspecialchars($deptName) . ' &nbsp;|&nbsp; Kỳ: ' . $dateRange . '</td></tr>
<tr><td colspan="10" style="border:none;font-size:10pt;"><strong>Người xuất:</strong> ' . htmlspecialchars($userName) . '</td>
<td colspan="10" style="border:none;font-size:10pt;text-align:right;"><strong>Ngày xuất:</strong> ' . $currentDate . '</td></tr>
<tr><td colspan="20" style="border:none;height:10px;"></td></tr></table>';

        switch ($loai) {
            case 'tonghop':
                $html .= $this->_buildTongHopTable($data, $fromDate);
                break;
            case 'chitiet':
                // Nếu data là cross-tab format (từ getChiTietAttendanceCrossTab)
                if (isset($data['dates'])) {
                    $html .= $this->_buildChiTietCrossTab($data);
                } else {
                    $html .= $this->_buildChiTietTable($data);
                }
                break;
            case 'vipham':
                $html .= $this->_buildViPhamTable($data);
                break;
            case 'yeucau':
                $html .= $this->_buildYeuCauTable($data);
                break;
            case 'tangca':
                $html .= $this->_buildTangCaTable($data);
                break;
            default:
                $html .= $this->_buildTongHopTable($data, $fromDate);
        }

        $html .= '<br><table><tr><td colspan="20" style="border:none;font-size:9pt;font-style:italic;color:#64748b;">
Ngày ' . date('d') . ' tháng ' . date('m') . ' năm ' . date('Y') . '</td></tr></table>';
        $html .= '</body></html>';
        echo $html;
        exit;
    }

    private function _buildTongHopTable($data, $fromDate)
    {
        $monthKey = substr($fromDate, 0, 7);
        $year = (int)substr($monthKey, 0, 4);
        $month = (int)substr($monthKey, 5, 2);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $h = '<table><thead><tr>';
        $h .= '<th class="th">STT</th><th class="th">Mã NV</th><th class="th">Họ và Tên</th><th class="th">Phòng ban</th>';
        $h .= '<th class="th">Ngày công</th><th class="th">Giờ làm (h)</th><th class="th">Nghỉ phép</th><th class="th">Ngày lễ</th><th class="th">Vắng</th><th class="th">OT (h)</th>';
        $h .= '</tr></thead><tbody>';
        $stt = 1;
        foreach ($data as $r) {
            $cls = $stt % 2 === 0 ? ' class="alt"' : '';
            $h .= '<tr' . $cls . '>';
            $h .= '<td class="td num">' . $stt++ . '</td>';
            $h .= '<td class="td num">' . ($r['maND'] ?? '') . '</td>';
            $h .= '<td class="td">' . htmlspecialchars($r['hoTen'] ?? '') . '</td>';
            $h .= '<td class="td">' . htmlspecialchars($r['phongBan'] ?? '') . '</td>';
            $h .= '<td class="td num"><strong>' . ($r['work_days'] ?? 0) . '</strong></td>';
            $h .= '<td class="td num">' . ($r['work_hours'] ?? 0) . '</td>';
            $h .= '<td class="td num">' . ($r['leave_days_used'] ?? 0) . '</td>';
            $h .= '<td class="td num">' . ($r['holiday_days'] ?? 0) . '</td>';
            $h .= '<td class="td num ' . ((float)($r['absent_days'] ?? 0) > 0 ? 'err' : '') . '">' . ($r['absent_days'] ?? 0) . '</td>';
            $h .= '<td class="td num">' . ($r['overtime_hours'] ?? 0) . '</td>';
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';
        return $h;
    }

    private function _buildChiTietTable($data)
    {
        $h = '<table><thead><tr>';
        $h .= '<th class="th">STT</th><th class="th">Mã NV</th><th class="th">Họ và Tên</th><th class="th">Phòng ban</th>';
        $h .= '<th class="th">Ngày làm việc</th><th class="th">Giờ vào</th><th class="th">Giờ ra</th><th class="th">Giờ làm</th><th class="th">OT</th><th class="th">Trạng thái</th>';
        $h .= '</tr></thead><tbody>';
        $stt = 1;
        foreach ($data as $r) {
            $cls = $stt % 2 === 0 ? ' class="alt"' : '';
            $gioVao = !empty($r['checkin_time']) ? date('H:i', strtotime($r['checkin_time'])) : (!empty($r['gioVao']) ? $r['gioVao'] : '--:--');
            $gioRa  = !empty($r['checkout_time']) ? date('H:i', strtotime($r['checkout_time'])) : (!empty($r['gioRa']) ? $r['gioRa'] : '--:--');
            $ngay   = !empty($r['ngayLamViec']) ? date('d/m/Y', strtotime($r['ngayLamViec'])) : '';
            $soGio  = $r['work_hours'] ?? $r['work_days'] ?? 0;
            $ot     = $r['ot_hours'] ?? 0;
            $status = $r['trangThai'] ?? ($r['work_days'] > 0 ? 'Có chấm công' : 'Vắng');
            $stCls  = stripos($status, 'vắng') !== false || stripos($status, 'absent') !== false ? 'err' : 'ok';
            $h .= '<tr' . $cls . '>';
            $h .= '<td class="td num">' . $stt++ . '</td>';
            $h .= '<td class="td num">' . ($r['maND'] ?? '') . '</td>';
            $h .= '<td class="td">' . htmlspecialchars($r['hoTen'] ?? '') . '</td>';
            $h .= '<td class="td">' . htmlspecialchars($r['phongBan'] ?? '') . '</td>';
            $h .= '<td class="td num">' . $ngay . '</td>';
            $h .= '<td class="td num">' . $gioVao . '</td>';
            $h .= '<td class="td num">' . $gioRa . '</td>';
            $h .= '<td class="td num">' . $soGio . '</td>';
            $h .= '<td class="td num">' . $ot . '</td>';
            $h .= '<td class="td ' . $stCls . '">' . htmlspecialchars($status) . '</td>';
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';
        return $h;
    }

    /**
     * Build bảng chi tiết chấm công dạng CROSS-TAB (timesheet)
     * Hàng = nhân viên, Cột = từng ngày, nhóm theo phòng ban
     * Format: CODE | Họ và Tên | Chức vụ | Phòng ban || [Ngày] IN | OUT | TT | ... || Tổng
     */
    private function _buildChiTietCrossTab(array $data): string
    {
        $dates     = $data['dates']     ?? [];
        $employees = $data['employees'] ?? [];
        $attMap    = $data['attMap']    ?? [];
        $leaveMap  = $data['leaveMap']  ?? [];

        if (empty($dates) || empty($employees)) {
            return '<table><tr><td style="text-align:center;padding:20px;color:#94a3b8">Không có dữ liệu trong kỳ này</td></tr></table>';
        }

        $dayVI = ['CN','T2','T3','T4','T5','T6','T7'];

        // Styles
        $thBase   = 'background-color:#4472C4;color:#fff;font-weight:bold;text-align:center;border:1px solid #2952a3;padding:5px 3px;font-size:9pt;white-space:nowrap;';
        $thWeekend= 'background-color:#6b7280;color:#fff;font-weight:bold;text-align:center;border:1px solid #4b5563;padding:5px 3px;font-size:9pt;';
        $tdBase   = 'border:1px solid #d1d5db;padding:4px 3px;font-size:9pt;vertical-align:middle;';
        $tdNum    = $tdBase . 'text-align:center;';
        $tdWeekend= $tdBase . 'text-align:center;background-color:#f3f4f6;color:#9ca3af;';
        $tdLeave  = $tdBase . 'text-align:center;background-color:#dbeafe;color:#1e40af;font-weight:700;';
        $tdAbsent = $tdBase . 'text-align:center;background-color:#fee2e2;color:#991b1b;font-weight:700;';
        $tdLate   = $tdBase . 'text-align:center;background-color:#fff7ed;color:#c2410c;font-weight:700;';
        $tdOk     = $tdBase . 'text-align:center;color:#166534;';
        $tdDept   = 'background-color:#1e3a8a;color:#fff;font-weight:bold;font-size:10pt;padding:8px 10px;border:1px solid #1e3a8a;';
        $tdTotal  = 'background-color:#f0fdf4;color:#166534;font-weight:bold;text-align:center;border:1px solid #86efac;font-size:9pt;padding:4px;';

        // Số cột cố định (trước các ngày)
        $fixedCols = 4; // Mã NV, Họ Tên, Chức vụ, Phòng ban
        $totalCols = $fixedCols + count($dates) * 3 + 1; // +1 cho cột Tổng

        $h = '<table style="border-collapse:collapse;font-family:Arial,sans-serif;">';
        $h .= '<thead>';

        // ── Hàng header 1: Tên ngày ──
        $h .= '<tr>';
        $h .= '<th rowspan="2" style="' . $thBase . 'width:55px">Mã NV</th>';
        $h .= '<th rowspan="2" style="' . $thBase . 'width:160px">Họ và Tên</th>';
        $h .= '<th rowspan="2" style="' . $thBase . 'width:90px">Chức vụ</th>';
        $h .= '<th rowspan="2" style="' . $thBase . 'width:90px">Phòng ban</th>';
        foreach ($dates as $date) {
            $dow = (int)date('w', strtotime($date));
            // Dùng format 'dd-mm' (gạch ngang) — tránh Excel parse 'dd.mm' thành số thập phân
            // và tránh parse 'dd/mm' theo locale Mỹ (MM/DD)
            $day = substr($date, 8, 2);  // '01'
            $mon = substr($date, 5, 2);  // '09'
            $dayLabel = $dayVI[$dow] . '<br>' . $day . '-' . $mon;
            $isWknd = ($dow === 0 || $dow === 6);
            $thStyle = $isWknd ? $thWeekend : $thBase;
            // mso-number-format:"\@" (được escape bằng &quot;) buộc Excel đọc cell là text
            $h .= '<th colspan="3" style="' . $thStyle . 'min-width:72px;mso-number-format:&quot;\@&quot;">' . $dayLabel . '</th>';
        }
        $h .= '<th rowspan="2" style="' . $thBase . 'background-color:#065f46;width:40px">Tổng<br>ngày</th>';
        $h .= '</tr>';

        // ── Hàng header 2: Vào / Ra / TT ──
        $h .= '<tr>';
        foreach ($dates as $date) {
            $dow = (int)date('w', strtotime($date));
            $isWknd = ($dow === 0 || $dow === 6);
            $thStyle = $isWknd ? $thWeekend : $thBase;
            $subStyle = 'font-size:8pt;padding:3px 2px;font-weight:600;';
            $h .= '<th style="' . $thStyle . $subStyle . '">Vào</th>';
            $h .= '<th style="' . $thStyle . $subStyle . '">Ra</th>';
            $h .= '<th style="' . $thStyle . $subStyle . '">TT</th>';
        }
        $h .= '</tr>';
        $h .= '</thead><tbody>';

        // ── Nhóm theo phòng ban ──
        $grouped = [];
        foreach ($employees as $emp) {
            $dept = $emp['phongBan'] ?: 'Chưa phân phòng ban';
            $grouped[$dept][] = $emp;
        }

        foreach ($grouped as $dept => $deptEmps) {
            // Row phòng ban
            $h .= '<tr><td colspan="' . $totalCols . '" style="' . $tdDept . '">📁 ' . htmlspecialchars($dept) . '</td></tr>';

            $deptWorkDays = 0;

            foreach ($deptEmps as $emp) {
                $maND = (int)$emp['maND'];
                $h .= '<tr>';
                $h .= '<td style="' . $tdNum . 'font-weight:600">' . $maND . '</td>';
                $h .= '<td style="' . $tdBase . 'font-weight:600">' . htmlspecialchars($emp['hoTen']) . '</td>';
                $h .= '<td style="' . $tdBase . 'color:#475569">' . htmlspecialchars($emp['chucVu'] ?? '') . '</td>';
                $h .= '<td style="' . $tdBase . 'color:#475569">' . htmlspecialchars($emp['phongBan'] ?? '') . '</td>';

                $empWorkDays = 0;

                foreach ($dates as $date) {
                    $dow   = (int)date('w', strtotime($date));
                    $isWknd = ($dow === 0 || $dow === 6);

                    if ($isWknd) {
                        // Cuối tuần — ô xám
                        $label = $dayVI[$dow];
                        $h .= '<td style="' . $tdWeekend . '">' . $label . '</td>';
                        $h .= '<td style="' . $tdWeekend . '"></td>';
                        $h .= '<td style="' . $tdWeekend . '">' . $label . '</td>';
                        continue;
                    }

                    // Nghỉ phép
                    if (isset($leaveMap[$maND][$date])) {
                        $leaveCode = $leaveMap[$maND][$date];
                        $h .= '<td style="' . $tdLeave . '">' . $leaveCode . '</td>';
                        $h .= '<td style="' . $tdLeave . '"></td>';
                        $h .= '<td style="' . $tdLeave . '">' . $leaveCode . '</td>';
                        continue;
                    }

                    // Có chấm công
                    if (isset($attMap[$maND][$date])) {
                        $att = $attMap[$maND][$date];
                        $vao = $att['gio_vao'] ? substr($att['gio_vao'], 0, 5) : '';
                        $ra  = $att['gio_ra']  ? substr($att['gio_ra'],  0, 5) : '';

                        // Tính số ngày công (1 = đủ ngày ≥6h, 0.5 = nửa ngày ≥3h, 0 = không đủ)
                        $dayCount = 0.0;
                        if ($att['gio_vao'] && $att['gio_ra']) {
                            $diffSec  = strtotime($date . ' ' . $att['gio_ra']) - strtotime($date . ' ' . $att['gio_vao']);
                            $workedH  = $diffSec / 3600;
                            $dayCount = $workedH >= 6 ? 1.0 : ($workedH >= 3 ? 0.5 : 0.0);
                        } elseif ($att['gio_vao'] || $att['gio_ra']) {
                            $dayCount = 0.5; // Chỉ có 1 lượt = nửa ngày
                        }

                        // TT: chỉ hiện V/T/S hoặc 1/0.5 nếu đúng giờ
                        $isLate  = $att['gio_vao'] && strtotime($att['gio_vao']) > strtotime('08:05:00');
                        $isEarly = $att['gio_ra']  && strtotime($att['gio_ra'])  < strtotime('16:55:00');
                        if ($isLate && $isEarly) { $tt = 'T+S'; $ttStyle = $tdLate; }
                        elseif ($isLate)          { $tt = 'T';   $ttStyle = $tdLate; }
                        elseif ($isEarly)         { $tt = 'S';   $ttStyle = $tdLate; }
                        else {
                            // Đúng giờ: hiện số ngày công (1 hoặc 0.5)
                            $tt = ($dayCount >= 1 ? '1' : ($dayCount > 0 ? '0.5' : ''));
                            $ttStyle = $tdOk;
                        }

                        $h .= '<td style="' . $tdNum . '">' . $vao . '</td>';
                        $h .= '<td style="' . $tdNum . '">' . $ra  . '</td>';
                        $h .= '<td style="' . $ttStyle . '">' . $tt . '</td>';
                        $empWorkDays += $dayCount;
                        $deptWorkDays += $dayCount;
                        continue;
                    }

                    // Ngày làm việc không có chấm công → Vắng
                    $h .= '<td style="' . $tdAbsent . '"></td>';
                    $h .= '<td style="' . $tdAbsent . '"></td>';
                    $h .= '<td style="' . $tdAbsent . '">V</td>';
                }

                // Cột tổng ngày (cho phép thập phân: 20.5, 19, ...)
                $totalDisplay = ($empWorkDays == (int)$empWorkDays)
                    ? (int)$empWorkDays
                    : $empWorkDays;
                $h .= '<td style="' . $tdTotal . '">' . $totalDisplay . '</td>';
                $h .= '</tr>';
            }
        }

        $h .= '</tbody>';

        // ── Chú thích màu sắc ──
        $h .= '<tfoot><tr><td colspan="' . $totalCols . '" style="border:none;padding:10px 4px;font-size:8.5pt;color:#475569">';
        $h .= '<strong>Chú thích:</strong>&nbsp;';
        $h .= '<span style="background:#dbeafe;color:#1e40af;padding:2px 6px;border-radius:3px;margin-right:6px">NP = Nghỉ phép</span>';
        $h .= '<span style="background:#fee2e2;color:#991b1b;padding:2px 6px;border-radius:3px;margin-right:6px">V = Vắng mặt</span>';
        $h .= '<span style="background:#fff7ed;color:#c2410c;padding:2px 6px;border-radius:3px;margin-right:6px">T = Đi trễ &nbsp;|&nbsp; S = Về sớm</span>';
        $h .= '<span style="background:#f3f4f6;color:#6b7280;padding:2px 6px;border-radius:3px">T7/CN = Cuối tuần</span>';
        $h .= '</td></tr></tfoot>';

        $h .= '</table>';
        return $h;
    }

    private function _buildViPhamTable($data)
    {
        $h = '<table><thead><tr>';
        $h .= '<th class="th">STT</th><th class="th">Mã NV</th><th class="th">Họ và Tên</th><th class="th">Phòng ban</th>';
        $h .= '<th class="th">Số lần đi trễ</th><th class="th">Tổng phút trễ</th><th class="th">Số lần về sớm</th><th class="th">Tổng phút sớm</th><th class="th">Ngày vắng</th>';
        $h .= '</tr></thead><tbody>';
        $stt = 1;
        foreach ($data as $r) {
            $cls = $stt % 2 === 0 ? ' class="alt"' : '';
            $late = (int)($r['late_count'] ?? 0);
            $lateMins = (int)($r['late_minutes'] ?? 0);
            $early = (int)($r['early_count'] ?? 0);
            $earlyMins = (int)($r['early_minutes'] ?? 0);
            $absent = (float)($r['absent_days'] ?? 0);
            $h .= '<tr' . $cls . '>';
            $h .= '<td class="td num">' . $stt++ . '</td>';
            $h .= '<td class="td num">' . ($r['maND'] ?? '') . '</td>';
            $h .= '<td class="td">' . htmlspecialchars($r['hoTen'] ?? '') . '</td>';
            $h .= '<td class="td">' . htmlspecialchars($r['phongBan'] ?? '') . '</td>';
            $h .= '<td class="td num ' . ($late > 0 ? 'warn' : '') . '">' . $late . '</td>';
            $h .= '<td class="td num ' . ($lateMins > 0 ? 'warn' : '') . '">' . $lateMins . ' phút</td>';
            $h .= '<td class="td num ' . ($early > 0 ? 'warn' : '') . '">' . $early . '</td>';
            $h .= '<td class="td num ' . ($earlyMins > 0 ? 'warn' : '') . '">' . $earlyMins . ' phút</td>';
            $h .= '<td class="td num ' . ($absent > 0 ? 'err' : '') . '">' . $absent . '</td>';
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';
        return $h;
    }

    private function _buildYeuCauTable($data)
    {
        $typeMap = [
            'leave' => 'Nghỉ phép', 'sick' => 'Nghỉ ốm', 'personal' => 'Nghỉ việc riêng',
            'emergency' => 'Nghỉ khẩn cấp', 'annual' => 'Phép năm', 'other' => 'Khác',
            'ot' => 'Tăng ca (OT)', 'correction' => 'Điều chỉnh công',
        ];
        $statusMap = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'];
        $h = '<table><thead><tr>';
        $h .= '<th class="th">STT</th><th class="th">Mã NV</th><th class="th">Nhân viên</th><th class="th">Phòng ban</th>';
        $h .= '<th class="th">Loại đơn</th><th class="th">Thời gian gửi</th><th class="th">Nội dung/Lý do</th><th class="th">Trạng thái</th><th class="th">Người duyệt</th>';
        $h .= '</tr></thead><tbody>';
        $stt = 1;
        foreach ($data as $r) {
            $cls = $stt % 2 === 0 ? ' class="alt"' : '';
            $type = $r['request_type'] ?? $r['loaiNghiPhep'] ?? 'leave';
            $typeLabel = $typeMap[$type] ?? ucfirst($type);
            $status = $r['trangThai'] ?? 'pending';
            $statusLabel = $statusMap[$status] ?? $status;
            $stCls = $status === 'approved' ? 'ok' : ($status === 'rejected' ? 'err' : 'warn');
            $ngayGui = !empty($r['ngayTao']) ? date('d/m/Y H:i', strtotime($r['ngayTao'])) : '';
            $lyDo = $r['lyDo'] ?? $r['ghiChu'] ?? '';
            $nguoiDuyet = $r['nguoiDuyetTen'] ?? $r['approver_name'] ?? ($r['nguoiDuyet'] ? 'ID:' . $r['nguoiDuyet'] : '—');
            $h .= '<tr' . $cls . '>';
            $h .= '<td class="td num">' . $stt++ . '</td>';
            $h .= '<td class="td num">' . ($r['maND'] ?? '') . '</td>';
            $h .= '<td class="td">' . htmlspecialchars($r['hoTen'] ?? '') . '</td>';
            $h .= '<td class="td">' . htmlspecialchars($r['phongBan'] ?? '') . '</td>';
            $h .= '<td class="td">' . htmlspecialchars($typeLabel) . '</td>';
            $h .= '<td class="td num">' . $ngayGui . '</td>';
            $h .= '<td class="td">' . htmlspecialchars(mb_strimwidth($lyDo, 0, 80, '…')) . '</td>';
            $h .= '<td class="td ' . $stCls . '">' . $statusLabel . '</td>';
            $h .= '<td class="td">' . htmlspecialchars($nguoiDuyet) . '</td>';
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';
        return $h;
    }

    private function _buildTangCaTable($data)
    {
        $statusMap = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'];
        $h = '<table><thead><tr>';
        $h .= '<th class="th">STT</th><th class="th">Mã NV</th><th class="th">Nhân viên</th><th class="th">Phòng ban</th>';
        $h .= '<th class="th">Ngày OT</th><th class="th">Số giờ OT</th><th class="th">Lý do</th><th class="th">Người duyệt</th><th class="th">Trạng thái</th><th class="th">Ngày tạo</th>';
        $h .= '</tr></thead><tbody>';
        $stt = 1;
        foreach ($data as $r) {
            $cls = $stt % 2 === 0 ? ' class="alt"' : '';
            $status = $r['trangThai'] ?? 'pending';
            $statusLabel = $statusMap[$status] ?? $status;
            $stCls = $status === 'approved' ? 'ok' : ($status === 'rejected' ? 'err' : 'warn');
            $ngayOT = !empty($r['ngayOT']) ? date('d/m/Y', strtotime($r['ngayOT'])) : '';
            $ngayTao = !empty($r['ngayTao']) ? date('d/m/Y', strtotime($r['ngayTao'])) : '';
            $nguoiDuyet = $r['tenNguoiDuyet'] ?? $r['approver_name'] ?? ($r['nguoiDuyet'] ? 'ID:' . $r['nguoiDuyet'] : '—');
            $h .= '<tr' . $cls . '>';
            $h .= '<td class="td num">' . $stt++ . '</td>';
            $h .= '<td class="td num">' . ($r['maND'] ?? '') . '</td>';
            $h .= '<td class="td">' . htmlspecialchars($r['hoTen'] ?? '') . '</td>';
            $h .= '<td class="td">' . htmlspecialchars($r['phongBan'] ?? '') . '</td>';
            $h .= '<td class="td num">' . $ngayOT . '</td>';
            $h .= '<td class="td num"><strong>' . (float)($r['soGioOT'] ?? 0) . ' giờ</strong></td>';
            $h .= '<td class="td">' . htmlspecialchars(mb_strimwidth($r['ghiChu'] ?? '', 0, 60, '…')) . '</td>';
            $h .= '<td class="td">' . htmlspecialchars($nguoiDuyet) . '</td>';
            $h .= '<td class="td ' . $stCls . '">' . $statusLabel . '</td>';
            $h .= '<td class="td num">' . $ngayTao . '</td>';
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';
        return $h;
    }

    /**
     * Xuất báo cáo dạng HTML in được (PDF)
     */
    public function exportPdfReport($data, $loai, $title, $fromDate, $toDate, $phongBan = '', $userName = '')
    {
        $deptName  = $phongBan ?: 'Tất cả phòng ban';
        $dateRange = date('d/m/Y', strtotime($fromDate)) . ' — ' . date('d/m/Y', strtotime($toDate));
        $currentDate = date('d/m/Y H:i');
        $typeMap = [
            'tonghop' => 'Báo Cáo Tổng Hợp Chấm Công',
            'chitiet' => 'Báo Cáo Chi Tiết Chấm Công Nhân Viên',
            'vipham'  => 'Báo Cáo Đi Trễ, Về Sớm, Vắng Mặt',
            'yeucau'  => 'Báo Cáo Tổng Hợp Đơn Yêu Cầu',
            'tangca'  => 'Báo Cáo Tăng Ca (OT)',
        ];
        $displayTitle = $typeMap[$loai] ?? $title;
        $iconMap = [
            'tonghop' => '📊', 'chitiet' => '📋', 'vipham' => '⚠️', 'yeucau' => '📄', 'tangca' => '⏱️'
        ];
        $icon = $iconMap[$loai] ?? '📊';

        $tableHtml = '';
        switch ($loai) {
            case 'tonghop': $tableHtml = $this->_buildPdfTongHop($data); break;
            case 'chitiet':
                // Dùng cross-tab nếu data có format mới (từ getChiTietAttendanceCrossTab)
                $tableHtml = isset($data['dates'])
                    ? $this->_buildChiTietCrossTab($data)   // tái sử dụng builder Excel (HTML tương thích)
                    : $this->_buildPdfChiTiet($data);
                break;
            case 'vipham':  $tableHtml = $this->_buildPdfViPham($data); break;
            case 'yeucau':  $tableHtml = $this->_buildPdfYeuCau($data); break;
            case 'tangca':  $tableHtml = $this->_buildPdfTangCa($data); break;
            default:        $tableHtml = $this->_buildPdfTongHop($data);
        }

        // Tính tổng số bản ghi (hỗ trợ cả format cũ lẫn cross-tab mới)
        $totalRows = isset($data['employees'])
            ? count($data['employees'])
            : count($data);

        echo '<!DOCTYPE html><html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . htmlspecialchars($displayTitle) . '</title>
<style>
@import url(\'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap\');
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: "Be Vietnam Pro", "Times New Roman", serif; background: #f8fafc; color: #1e293b; font-size: 11pt; }
.pdf-wrapper { max-width: 1100px; margin: 0 auto; padding: 24px; }
.pdf-header { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%); color: #fff; border-radius: 16px; padding: 28px 32px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; }
.pdf-header-left { flex: 1; }
.pdf-company { font-size: 10pt; font-weight: 600; opacity: .85; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 8px; }
.pdf-icon { font-size: 2rem; margin-bottom: 8px; }
.pdf-title { font-size: 1.6rem; font-weight: 800; margin-bottom: 6px; line-height: 1.2; }
.pdf-subtitle { font-size: .9rem; opacity: .8; }
.pdf-header-right { text-align: right; font-size: .8rem; opacity: .8; line-height: 1.8; flex-shrink: 0; padding-left: 20px; }
.pdf-meta-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
.pdf-meta-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; box-shadow: 0 2px 6px rgba(0,0,0,.04); }
.pdf-meta-label { font-size: .7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
.pdf-meta-value { font-size: .95rem; font-weight: 700; color: #0f172a; }
.pdf-table-wrap { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,.05); margin-bottom: 20px; }
table { width: 100%; border-collapse: collapse; font-size: 9pt; }
thead tr { background: linear-gradient(135deg, #1e40af, #2563eb); }
thead th { color: #fff; font-weight: 700; padding: 11px 10px; text-align: left; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
thead th.num { text-align: center; }
tbody tr { border-bottom: 1px solid #f1f5f9; }
tbody tr:nth-child(even) { background: #f8fafc; }
tbody tr:hover { background: #eff6ff; }
td { padding: 9px 10px; color: #334155; vertical-align: middle; }
td.num { text-align: center; font-weight: 600; }
td.name { font-weight: 600; color: #0f172a; }
td.dept { color: #64748b; font-size: .85rem; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: .75rem; font-weight: 700; }
.badge-ok { background: #dcfce7; color: #166534; }
.badge-warn { background: #fff7ed; color: #9a3412; }
.badge-err { background: #fee2e2; color: #991b1b; }
.badge-gray { background: #f1f5f9; color: #475569; }
.val-ok { color: #16a34a; font-weight: 700; }
.val-warn { color: #d97706; font-weight: 700; }
.val-err { color: #dc2626; font-weight: 700; }
.pdf-footer { text-align: center; color: #94a3b8; font-size: .8rem; padding: 12px 0; border-top: 1px solid #e2e8f0; margin-top: 8px; }
.sig-row { display: flex; justify-content: space-between; margin-top: 28px; }
.sig-block { text-align: center; width: 28%; }
.sig-block .sig-title { font-weight: 700; font-size: .9rem; margin-bottom: 60px; }
.sig-block .sig-name { font-weight: 600; border-top: 1px solid #cbd5e1; padding-top: 6px; font-size: .85rem; }
.print-btn { position: fixed; bottom: 24px; right: 24px; background: linear-gradient(135deg,#2563eb,#1d4ed8); color: #fff; border: none; border-radius: 50px; padding: 14px 24px; font-size: 1rem; font-weight: 700; cursor: pointer; box-shadow: 0 8px 24px rgba(37,99,235,.4); display: flex; align-items: center; gap: 8px; z-index: 999; transition: transform .2s; }
.print-btn:hover { transform: translateY(-2px); }
@media print {
    body { background: #fff; }
    .pdf-wrapper { max-width: 100%; padding: 10px; }
    .print-btn { display: none; }
    .pdf-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    thead tr { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
</head>
<body>
<div class="pdf-wrapper">
    <div class="pdf-header">
        <div class="pdf-header-left">
            <div class="pdf-company">🏢 Công ty TNHH Quản Lý Nhân Sự 2026</div>
            <div class="pdf-icon">' . $icon . '</div>
            <div class="pdf-title">' . htmlspecialchars($displayTitle) . '</div>
            <div class="pdf-subtitle">Phòng ban: ' . htmlspecialchars($deptName) . ' &nbsp;•&nbsp; Kỳ báo cáo: ' . $dateRange . '</div>
        </div>
        <div class="pdf-header-right">
            <div>Người xuất: <strong>' . htmlspecialchars($userName) . '</strong></div>
            <div>Ngày xuất: ' . $currentDate . '</div>
            <div>Tổng: ' . $totalRows . ' bản ghi</div>
        </div>
    </div>

    <div class="pdf-meta-grid">
        <div class="pdf-meta-card">
            <div class="pdf-meta-label">📁 Loại báo cáo</div>
            <div class="pdf-meta-value">' . htmlspecialchars($displayTitle) . '</div>
        </div>
        <div class="pdf-meta-card">
            <div class="pdf-meta-label">📅 Kỳ báo cáo</div>
            <div class="pdf-meta-value">' . $dateRange . '</div>
        </div>
        <div class="pdf-meta-card">
            <div class="pdf-meta-label">🏢 Phòng ban</div>
            <div class="pdf-meta-value">' . htmlspecialchars($deptName) . '</div>
        </div>
    </div>

    <div class="pdf-table-wrap">
        ' . $tableHtml . '
    </div>

    <div class="sig-row">
        <div class="sig-block">
            <div class="sig-title">Người lập báo cáo</div>
            <div class="sig-name">' . htmlspecialchars($userName) . '</div>
        </div>
        <div class="sig-block">
            <div class="sig-title">Trưởng phòng</div>
            <div class="sig-name">................................</div>
        </div>
        <div class="sig-block">
            <div class="sig-title">Ban Giám đốc</div>
            <div class="sig-name">................................</div>
        </div>
    </div>

    <div class="pdf-footer">Ngày ' . date('d') . ' tháng ' . date('m') . ' năm ' . date('Y') . ' &nbsp;•&nbsp; Hệ thống Quản Lý Chấm Công v2.5</div>
</div>

<button class="print-btn" onclick="window.print()">🖨️ In / Lưu PDF</button>

<script>
// Auto trigger print dialog after short delay
setTimeout(function() {
    // Do not auto-print, let user click
}, 500);
</script>
</body></html>';
    }

    private function _buildPdfTongHop($data)
    {
        $h = '<table><thead><tr>';
        $h .= '<th class="num">#</th><th>Mã NV</th><th>Họ và Tên</th><th>Phòng ban</th>';
        $h .= '<th class="num">Ngày công</th><th class="num">Giờ làm</th><th class="num">Nghỉ phép</th><th class="num">Ngày lễ</th><th class="num">Vắng</th><th class="num">OT (h)</th>';
        $h .= '</tr></thead><tbody>';
        if (empty($data)) {
            $h .= '<tr><td colspan="10" style="text-align:center;padding:30px;color:#94a3b8;">Không có dữ liệu trong kỳ này</td></tr>';
        }
        $stt = 1;
        foreach ($data as $r) {
            $absent = (float)($r['absent_days'] ?? 0);
            $h .= '<tr>';
            $h .= '<td class="num">' . $stt++ . '</td>';
            $h .= '<td class="num">' . ($r['maND'] ?? '') . '</td>';
            $h .= '<td class="name">' . htmlspecialchars($r['hoTen'] ?? '') . '</td>';
            $h .= '<td class="dept">' . htmlspecialchars($r['phongBan'] ?? '') . '</td>';
            $h .= '<td class="num"><strong>' . ($r['work_days'] ?? 0) . '</strong></td>';
            $h .= '<td class="num">' . ($r['work_hours'] ?? 0) . 'h</td>';
            $h .= '<td class="num">' . ($r['leave_days_used'] ?? 0) . '</td>';
            $h .= '<td class="num">' . ($r['holiday_days'] ?? 0) . '</td>';
            $h .= '<td class="num ' . ($absent > 0 ? 'val-err' : '') . '">' . $absent . '</td>';
            $h .= '<td class="num">' . ($r['overtime_hours'] ?? 0) . 'h</td>';
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';
        return $h;
    }

    private function _buildPdfChiTiet($data)
    {
        $h = '<table><thead><tr>';
        $h .= '<th class="num">#</th><th>Mã NV</th><th>Họ và Tên</th><th>Phòng ban</th>';
        $h .= '<th class="num">Ngày</th><th class="num">Giờ vào</th><th class="num">Giờ ra</th><th class="num">Giờ làm</th><th class="num">OT</th>';
        $h .= '</tr></thead><tbody>';
        if (empty($data)) {
            $h .= '<tr><td colspan="9" style="text-align:center;padding:30px;color:#94a3b8;">Không có dữ liệu trong kỳ này</td></tr>';
        }
        $stt = 1;
        foreach ($data as $r) {
            $gioVao = !empty($r['checkin_time']) ? date('H:i', strtotime($r['checkin_time'])) : (!empty($r['gioVao']) ? $r['gioVao'] : '—');
            $gioRa  = !empty($r['checkout_time']) ? date('H:i', strtotime($r['checkout_time'])) : (!empty($r['gioRa']) ? $r['gioRa'] : '—');
            $ngay   = !empty($r['ngayLamViec']) ? date('d/m/Y', strtotime($r['ngayLamViec'])) : '';
            $h .= '<tr>';
            $h .= '<td class="num">' . $stt++ . '</td>';
            $h .= '<td class="num">' . ($r['maND'] ?? '') . '</td>';
            $h .= '<td class="name">' . htmlspecialchars($r['hoTen'] ?? '') . '</td>';
            $h .= '<td class="dept">' . htmlspecialchars($r['phongBan'] ?? '') . '</td>';
            $h .= '<td class="num">' . $ngay . '</td>';
            $h .= '<td class="num">' . $gioVao . '</td>';
            $h .= '<td class="num">' . $gioRa . '</td>';
            $h .= '<td class="num">' . ($r['work_hours'] ?? $r['work_days'] ?? 0) . 'h</td>';
            $h .= '<td class="num">' . ($r['ot_hours'] ?? 0) . 'h</td>';
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';
        return $h;
    }

    private function _buildPdfViPham($data)
    {
        $h = '<table><thead><tr>';
        $h .= '<th class="num">#</th><th>Mã NV</th><th>Họ và Tên</th><th>Phòng ban</th>';
        $h .= '<th class="num">Đi trễ (lần)</th><th class="num">Phút trễ</th><th class="num">Về sớm (lần)</th><th class="num">Phút sớm</th><th class="num">Ngày vắng</th>';
        $h .= '</tr></thead><tbody>';
        if (empty($data)) {
            $h .= '<tr><td colspan="9" style="text-align:center;padding:30px;color:#94a3b8;">Không có dữ liệu vi phạm trong kỳ này</td></tr>';
        }
        $stt = 1;
        foreach ($data as $r) {
            $late = (int)($r['late_count'] ?? 0);
            $lateMins = (int)($r['late_minutes'] ?? 0);
            $early = (int)($r['early_count'] ?? 0);
            $earlyMins = (int)($r['early_minutes'] ?? 0);
            $absent = (float)($r['absent_days'] ?? 0);
            $h .= '<tr>';
            $h .= '<td class="num">' . $stt++ . '</td>';
            $h .= '<td class="num">' . ($r['maND'] ?? '') . '</td>';
            $h .= '<td class="name">' . htmlspecialchars($r['hoTen'] ?? '') . '</td>';
            $h .= '<td class="dept">' . htmlspecialchars($r['phongBan'] ?? '') . '</td>';
            $h .= '<td class="num ' . ($late > 0 ? 'val-warn' : '') . '">' . $late . '</td>';
            $h .= '<td class="num ' . ($lateMins > 0 ? 'val-warn' : '') . '">' . $lateMins . ' phút</td>';
            $h .= '<td class="num ' . ($early > 0 ? 'val-warn' : '') . '">' . $early . '</td>';
            $h .= '<td class="num ' . ($earlyMins > 0 ? 'val-warn' : '') . '">' . $earlyMins . ' phút</td>';
            $h .= '<td class="num ' . ($absent > 0 ? 'val-err' : '') . '">' . $absent . '</td>';
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';
        return $h;
    }

    private function _buildPdfYeuCau($data)
    {
        $typeMap = ['leave'=>'Nghỉ phép','sick'=>'Nghỉ ốm','personal'=>'Nghỉ riêng','emergency'=>'Khẩn cấp','annual'=>'Phép năm','other'=>'Khác','ot'=>'Tăng ca','correction'=>'Điều chỉnh'];
        $statusCls = ['pending'=>'badge-warn','approved'=>'badge-ok','rejected'=>'badge-err'];
        $statusLabel = ['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối'];
        $h = '<table><thead><tr>';
        $h .= '<th class="num">#</th><th>Nhân viên</th><th>Phòng ban</th><th>Loại đơn</th><th class="num">Thời gian gửi</th><th>Lý do</th><th>Trạng thái</th>';
        $h .= '</tr></thead><tbody>';
        if (empty($data)) {
            $h .= '<tr><td colspan="7" style="text-align:center;padding:30px;color:#94a3b8;">Không có đơn yêu cầu trong kỳ này</td></tr>';
        }
        $stt = 1;
        foreach ($data as $r) {
            $type = $r['request_type'] ?? $r['loaiNghiPhep'] ?? 'leave';
            $status = $r['trangThai'] ?? 'pending';
            $ngayGui = !empty($r['ngayTao']) ? date('d/m/Y', strtotime($r['ngayTao'])) : '';
            $h .= '<tr>';
            $h .= '<td class="num">' . $stt++ . '</td>';
            $h .= '<td class="name">' . htmlspecialchars($r['hoTen'] ?? '') . '</td>';
            $h .= '<td class="dept">' . htmlspecialchars($r['phongBan'] ?? '') . '</td>';
            $h .= '<td>' . htmlspecialchars($typeMap[$type] ?? ucfirst($type)) . '</td>';
            $h .= '<td class="num">' . $ngayGui . '</td>';
            $h .= '<td>' . htmlspecialchars(mb_strimwidth($r['lyDo'] ?? $r['ghiChu'] ?? '', 0, 60, '…')) . '</td>';
            $h .= '<td><span class="badge ' . ($statusCls[$status] ?? 'badge-gray') . '">' . ($statusLabel[$status] ?? $status) . '</span></td>';
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';
        return $h;
    }

    private function _buildPdfTangCa($data)
    {
        $statusCls = ['pending'=>'badge-warn','approved'=>'badge-ok','rejected'=>'badge-err'];
        $statusLabel = ['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối'];
        $h = '<table><thead><tr>';
        $h .= '<th class="num">#</th><th>Nhân viên</th><th>Phòng ban</th><th class="num">Ngày OT</th><th class="num">Số giờ OT</th><th>Lý do</th><th>Trạng thái</th><th class="num">Ngày tạo</th>';
        $h .= '</tr></thead><tbody>';
        if (empty($data)) {
            $h .= '<tr><td colspan="8" style="text-align:center;padding:30px;color:#94a3b8;">Không có đơn OT trong kỳ này</td></tr>';
        }
        $stt = 1;
        foreach ($data as $r) {
            $status = $r['trangThai'] ?? 'pending';
            $ngayOT = !empty($r['ngayOT']) ? date('d/m/Y', strtotime($r['ngayOT'])) : '';
            $ngayTao = !empty($r['ngayTao']) ? date('d/m/Y', strtotime($r['ngayTao'])) : '';
            $h .= '<tr>';
            $h .= '<td class="num">' . $stt++ . '</td>';
            $h .= '<td class="name">' . htmlspecialchars($r['hoTen'] ?? '') . '</td>';
            $h .= '<td class="dept">' . htmlspecialchars($r['phongBan'] ?? '') . '</td>';
            $h .= '<td class="num">' . $ngayOT . '</td>';
            $h .= '<td class="num val-ok">' . (float)($r['soGioOT'] ?? 0) . 'h</td>';
            $h .= '<td>' . htmlspecialchars(mb_strimwidth($r['ghiChu'] ?? '', 0, 50, '…')) . '</td>';
            $h .= '<td><span class="badge ' . ($statusCls[$status] ?? 'badge-gray') . '">' . ($statusLabel[$status] ?? $status) . '</span></td>';
            $h .= '<td class="num">' . $ngayTao . '</td>';
            $h .= '</tr>';
        }
        $h .= '</tbody></table>';
        return $h;
    }
}






