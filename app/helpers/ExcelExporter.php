<?php

class ExcelExporter
{
    public function exportAttendanceReport($data, $monthKey, $department = '', $userName = '')
    {
        $month = (int)substr($monthKey, 5, 2);
        $year = (int)substr($monthKey, 0, 4);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $currentDate = date('d/m/Y H:i:s');

        $fileName = 'Bao_Cao_Cham_Cong_' . $monthKey . '.xls';

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $html = '<?xml version="1.0" encoding="UTF-8"?>
<html xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="utf-8">
<style>
body { font-family: Arial; margin: 0; }
table { border-collapse: collapse; }
td { border: 1px solid #000; padding: 4px 6px; font-size: 11px; }
.info-row { border: none; font-weight: normal; background: white; }
.title-row { background-color: #4472C4; color: white; font-weight: bold; text-align: center; font-size: 13px; padding: 8px; border: 1px solid #000; }
.header-cell { background-color: #4472C4; color: white; font-weight: bold; text-align: center; }
.data-cell { background-color: #D9E1F2; text-align: center; }
.name-cell { background-color: #D9E1F2; text-align: left; }
.dept-cell { background-color: #D9E1F2; text-align: left; }
.day-cell { background-color: #D9E1F2; text-align: center; padding: 2px; }
.summary-cell { background-color: #D9E1F2; text-align: center; }
.note-cell { background-color: #D9E1F2; text-align: left; }
.sig-cell { border: 1px solid #000; text-align: center; padding: 15px; }
.sig-title { font-weight: bold; font-size: 11px; }
.sig-line { border-top: 1px solid #000; margin-top: 40px; height: 20px; }
</style>
</head>
<body>

<table>
<tr>
    <td class="info-row" style="width: 50%"><strong>Người xuất báo cáo: </strong>' . htmlspecialchars($userName ?: 'Không xác định') . '</td>
    <td class="info-row" style="width: 50%; text-align: right;"><strong>Ngày xuất: </strong>' . $currentDate . '</td>
</tr>
</table>

<br/>

<table style="width: 100%; margin: 10px 0;">
<tr>
    <td colspan="' . ($daysInMonth + 8) . '" class="title-row">BÁO CÁO CHẤM CÔNG THÁNG ' . str_pad($month, 2, '0', STR_PAD_LEFT) . '/' . $year . '</td>
</tr>
</table>

<br/>

<table style="width: 100%;">
<tr>
    <td class="header-cell" style="width: 3%;">STT</td>
    <td class="header-cell" style="width: 12%;">Họ và tên</td>
    <td class="header-cell" style="width: 12%;">Phòng ban</td>';

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $html .= '<td class="header-cell" style="width: 2%;">' . $i . '</td>';
        }

        $html .= '<td class="header-cell" style="width: 6%;">Tổng ngày công</td>
    <td class="header-cell" style="width: 5%;">Nghỉ phép</td>
    <td class="header-cell" style="width: 5%;">Vắng</td>
    <td class="header-cell" style="width: 8%;">Ghi chú</td>
</tr>';

        $idx = 1;
        foreach ($data as $employee) {
            $html .= '<tr>
    <td class="data-cell">' . $idx . '</td>
    <td class="name-cell">' . htmlspecialchars($employee['hoTen'] ?? '') . '</td>
    <td class="dept-cell">' . htmlspecialchars($employee['phongBan'] ?? '') . '</td>';

            for ($i = 1; $i <= $daysInMonth; $i++) {
                $html .= '<td class="day-cell">x</td>';
            }

            $html .= '<td class="summary-cell"><strong>' . ($employee['work_days'] ?? 0) . '</strong></td>
    <td class="summary-cell">0</td>
    <td class="summary-cell">0</td>
    <td class="note-cell"></td>
</tr>';
            $idx++;
        }

        $html .= '</table>

<br/><br/>

<table style="width: 100%; border: none;">
<tr style="border: none;">
    <td style="border: 1px solid #000; width: 30%; text-align: center; padding: 20px;">
        <div class="sig-title">Người chấm công</div>
        <div style="font-size: 10px; margin: 40px 0 5px 0;">(Ký, họ tên)</div>
        <div style="border-top: 1px solid #000; margin-top: 40px; height: 20px;"></div>
    </td>
    <td style="border: none; width: 5%;"></td>
    <td style="border: 1px solid #000; width: 30%; text-align: center; padding: 20px;">
        <div class="sig-title">Trưởng bộ phận / Phòng ban</div>
        <div style="font-size: 10px; margin: 40px 0 5px 0;">(Ký, họ tên)</div>
        <div style="border-top: 1px solid #000; margin-top: 40px; height: 20px;"></div>
    </td>
    <td style="border: none; width: 5%;"></td>
    <td style="border: 1px solid #000; width: 30%; text-align: center; padding: 20px;">
        <div class="sig-title">Người duyệt</div>
        <div style="font-size: 10px; margin: 40px 0 5px 0;">(Ký, họ tên)</div>
        <div style="border-top: 1px solid #000; margin-top: 40px; height: 20px;"></div>
    </td>
</tr>
</table>

<br/>

<table style="width: 100%; border: none;">
<tr>
    <td style="border: none; text-align: center; font-size: 11px;">Ngày ____ tháng ____ năm ____</td>
</tr>
</table>

</body>
</html>';

        echo $html;
        exit;
    }
}






