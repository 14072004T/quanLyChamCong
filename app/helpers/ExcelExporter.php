<?php

class ExcelExporter
{
    /**
     * Xuất báo cáo chấm công chi tiết tháng
     * 
     * @param array $data - Dữ liệu từ ChamCongModel::getMonthlyAttendanceDetailNew
     * @param string $monthKey - Kỳ chấm công (YYYY-MM)
     * @param string $department - Tên phòng ban
     * @param string $userName - Người xuất báo cáo
     */
    public function exportAttendanceReport($data, $monthKey, $department = '', $userName = '')
    {
        $year = (int)substr($monthKey, 0, 4);
        $month = (int)substr($monthKey, 5, 2);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $currentDate = date('d/m/Y H:i:s');
        $deptName = $department ?: 'Tất cả phòng ban';

        $fileName = 'Bao_Cao_Cham_Cong_' . ($department ? $department . '_' : '') . $monthKey . '.xls';

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
    .weekend-cell { background-color: #F2F2F2; color: #7f8c8d; }
    .holiday-cell { background-color: #FFEB9C; color: #9C6500; font-weight: bold; }
    .leave-cell { background-color: #C6EFCE; color: #006100; font-weight: bold; }
    .absent-cell { background-color: #FFC7CE; color: #9C0006; }
    
    /* Summary Cells */
    .summary-header { background-color: #D9E1F2; font-weight: bold; text-align: center; }
    .summary-value { font-weight: bold; text-align: center; background-color: #F8FAFC; }
    
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
            <td colspan="' . ($daysInMonth + 8) . '" class="company-name">CÔNG TY TNHH QUẢN LÝ NHÂN SỰ 2026</td>
        </tr>
        <tr>
            <td colspan="' . ($daysInMonth + 8) . '" class="report-title">BẢNG TỔNG HỢP CÔNG THÁNG ' . $month . '/' . $year . '</td>
        </tr>
        <tr>
            <td colspan="' . ($daysInMonth + 8) . '" class="report-meta">Phòng ban: ' . htmlspecialchars($deptName) . '</td>
        </tr>
        <tr>
            <td colspan="' . ($daysInMonth / 2) . '" class="info-cell"><strong>Người xuất:</strong> ' . htmlspecialchars($userName) . '</td>
            <td colspan="' . ($daysInMonth / 2 + 8) . '" class="info-cell" style="text-align: right;"><strong>Ngày xuất:</strong> ' . $currentDate . '</td>
        </tr>
        <tr style="height: 20px;"><td colspan="' . ($daysInMonth + 8) . '" style="border: none;"></td></tr>
    </table>

    <table>
        <thead>
            <tr>
                <th rowspan="2" class="header-cell">STT</th>
                <th rowspan="2" class="header-cell">Mã NV</th>
                <th rowspan="2" class="header-cell">Họ và Tên</th>
                <th rowspan="2" class="header-cell">Phòng ban</th>
                <th colspan="' . $daysInMonth . '" class="header-cell">Ngày trong tháng</th>
                <th colspan="5" class="header-cell">Tổng cộng</th>
            </tr>
            <tr>';
        
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $i);
            $dayOfWeek = date('w', strtotime($dateStr));
            $isWeekend = ($dayOfWeek == 0 || $dayOfWeek == 6);
            $style = $isWeekend ? 'background-color: #D9D9D9;' : '';
            $html .= '<th class="header-cell" style="' . $style . '">' . $i . '</th>';
        }

        $html .= '<th class="summary-header">Công</th>
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
                
                if ($dayData) {
                    $type = $dayData['day_type'] ?? '';
                    if ($type === 'holiday') {
                        $val = 'L';
                        $class .= ' holiday-cell';
                    } elseif ($type === 'weekend') {
                        $dayOfWeek = date('w', strtotime($dateStr));
                        $val = ($dayOfWeek == 0) ? 'CN' : 'T7';
                        $class .= ' weekend-cell';
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
            <td colspan="' . ($daysInMonth + 8) . '" style="border: none; padding-top: 15px;">
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
}






