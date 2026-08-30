<?php
/**
 * AttendanceCalculator - Tính toán ngày công chi tiết tháng
 *
 * Phân loại ngày:
 * - 1.0 = ngày làm việc đầy đủ
 * - 0.5 = nửa ngày (sáng hoặc chiều)
 * - 0.0 = ngày phép / lễ / cuối tuần / lao động
 */
require_once 'HolidayCalculator.php';
require_once 'LeaveCalculator.php';

class AttendanceCalculator
{
    /**
     * Tính toán tổng công chi tiết cho một nhân viên trong tháng
     * @param string $monthKey - YYYY-MM
     * @param array $attendanceData - [date => [checkIn, checkOut, ...]]
     * @param array $leaveRequests - [date => type]
     * @param array $employeeInfo - [seniority, jobType, ...]
     * @return array
     */
    public static function calculateMonthlyAttendance($monthKey, $attendanceData = [], $leaveRequests = [], $employeeInfo = [], $shiftsMap = [])
    {
        $monthKey = trim((string)$monthKey);
        if (!preg_match('/^\d{4}-\d{2}$/', $monthKey)) {
            return [];
        }

        $year = (int)substr($monthKey, 0, 4);
        $month = (int)substr($monthKey, 5, 2);

        // Lấy ngày cuối cùng của tháng
        $lastDay = (int)date('t', strtotime("$year-$month-01"));

        $dailyBreakdown = [];
        $totals = [
            'total_work_days' => 0,
            'total_leave_days' => 0,
            'total_holiday_days' => 0,
            'total_weekend_days' => 0,
            'total_absent_days' => 0, // Vắng mặt không phép
            'total_ot_hours' => 0,
            'working_hours' => 0,
        ];

        for ($day = 1; $day <= $lastDay; $day++) {
            $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);

            $dayData = self::calculateDailyAttendance(
                $dateStr,
                $attendanceData[$dateStr] ?? null,
                $leaveRequests[$dateStr] ?? null,
                $employeeInfo,
                $shiftsMap[$dateStr] ?? null
            );

            $dailyBreakdown[$dateStr] = $dayData;

            // Tính tổng
            $totals['total_work_days'] += (float)$dayData['work_value'];
            $totals['total_leave_days'] += $dayData['day_type'] === 'leave' ? 1 : 0;
            $totals['total_holiday_days'] += $dayData['day_type'] === 'holiday' ? 1 : 0;
            $totals['total_weekend_days'] += $dayData['day_type'] === 'weekend' ? 1 : 0;
            $totals['total_absent_days'] += $dayData['day_type'] === 'absent' ? 1 : 0;
            $totals['total_ot_hours'] += (float)$dayData['ot_hours'];
            $totals['working_hours'] += (float)$dayData['work_hours'];
        }

        return [
            'thangNam' => $monthKey,
            'daily_breakdown' => $dailyBreakdown,
            'totals' => $totals,
        ];
    }

    /**
     * Tính toán chi tiết cho một ngày riêng lẻ
     * @param string $date - YYYY-MM-DD
     * @param array $checkInOutData - ['checkIn' => datetime, 'checkOut' => datetime]
     * @param string $leaveType - null, 'annual', 'unpaid', etc.
     * @param array $employeeInfo - thông tin nhân viên
     * @param array|null $shift - thông tin ca làm việc của ngày
     * @return array
     */
    public static function calculateDailyAttendance($date, $checkInOutData = null, $leaveType = null, $employeeInfo = [], $shift = null)
    {
        $date = trim((string)$date);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return self::getEmptyDayData();
        }

        // Kiểm tra ca OFF
        $isOffShift = false;
        if ($shift) {
            $shiftName = mb_strtolower(trim($shift['tenCa'] ?? ''), 'UTF-8');
            if ($shiftName === 'off' 
                || strpos($shiftName, 'off') !== false 
                || strpos($shiftName, 'nghỉ') !== false 
                || strpos($shiftName, 'nghi') !== false 
                || ($shift['gioBatDau'] ?? '') === ($shift['gioKetThuc'] ?? '')) {
                $isOffShift = true;
            }
        }

        // Kiểm tra loại ngày
        if (HolidayCalculator::isHoliday($date)) {
            return [
                'date' => $date,
                'day_type' => 'holiday',
                'day_type_label' => 'Ngày lễ',
                'work_value' => 0.0,
                'work_hours' => 0,
                'ot_hours' => 0,
                'has_attendance' => false,
                'shift_name' => $shift['tenCa'] ?? 'HC',
                'maCa' => $shift['maCa'] ?? $shift['id'] ?? null,
            ];
        }

        if ($isOffShift) {
            return [
                'date' => $date,
                'day_type' => 'off_shift',
                'day_type_label' => 'Không có lịch làm việc',
                'work_value' => 0.0,
                'work_hours' => 0,
                'ot_hours' => 0,
                'has_attendance' => false,
                'shift_name' => $shift['tenCa'] ?? 'OFF',
                'maCa' => $shift['maCa'] ?? $shift['id'] ?? null,
            ];
        }

        if (HolidayCalculator::isWeekend($date)) {
            return [
                'date' => $date,
                'day_type' => 'weekend',
                'day_type_label' => 'Cuối tuần',
                'work_value' => 0.0,
                'work_hours' => 0,
                'ot_hours' => 0,
                'has_attendance' => false,
                'shift_name' => $shift['tenCa'] ?? 'HC',
                'maCa' => $shift['maCa'] ?? $shift['id'] ?? null,
            ];
        }

        // Kiểm tra nếu có xin phép
        if ($leaveType) {
            $isHalfDay = false;
            $leaveId = null;
            $leaveReason = null;
            if (is_array($leaveType)) {
                $isHalfDay = !empty($leaveType['laNuaNgay']);
                $leaveId = $leaveType['leave_id'] ?? null;
                $leaveReason = $leaveType['lyDo'] ?? null;
                $leaveType = $leaveType['type'] ?? 'annual';
            }
            $leaveType = strtolower(trim((string)$leaveType));
            return [
                'date' => $date,
                'day_type' => 'leave',
                'loaiNghiPhep' => $leaveType,
                'leave_id' => $leaveId,
                'leave_reason' => $leaveReason,
                'day_type_label' => self::getLeaveTypeLabel($leaveType),
                'work_value' => 0.0,
                'work_hours' => 0,
                'ot_hours' => 0,
                'has_attendance' => false,
                'shift_name' => $shift['tenCa'] ?? 'HC',
                'maCa' => $shift['maCa'] ?? $shift['id'] ?? null,
            ];
        }

        // Nếu có dữ liệu chấm công, tính toán
        if ($checkInOutData && !empty($checkInOutData)) {
            $checkIn = $checkInOutData['checkIn'] ?? null;
            $checkOut = $checkInOutData['checkOut'] ?? null;

            if ($checkIn && $checkOut) {
                $shiftStart = $shift['gioBatDau'] ?? null;
                $shiftEnd = $shift['gioKetThuc'] ?? null;

                if ($shiftStart && $shiftEnd) {
                    // Kẹp giờ vào/ra trong đúng khung giờ ca đã thiết lập.
                    $workMinutes = self::calculateClampedWorkMinutes($checkIn, $checkOut, $shiftStart, $shiftEnd);
                    $shiftMinutes = self::calculateShiftMinutes($shiftStart, $shiftEnd);
                } else {
                    // Không có giờ ca (hiếm khi xảy ra) — dùng chênh lệch thô, chuẩn 8h.
                    $workMinutes = self::calculateWorkMinutes($checkIn, $checkOut);
                    $shiftMinutes = 480;
                }

                $workHours = round($workMinutes / 60, 2);

                // Tính work value: tỷ lệ giờ làm / tổng giờ ca, làm tròn LÊN tới các mốc 0.25.
                $workValue = self::calculateWorkValue($workMinutes, $shiftMinutes);

                // OT (tăng ca) được tính riêng qua cơ chế đăng ký OT, không tính lẫn vào đây.
                $otHours = 0;

                return [
                    'date' => $date,
                    'day_type' => 'working',
                    'day_type_label' => 'Ngày làm việc',
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'phutLamViec' => $workMinutes,
                    'work_hours' => $workHours,
                    'work_value' => $workValue,
                    'ot_hours' => $otHours,
                    'has_attendance' => true,
                    'shift_name' => $shift['tenCa'] ?? 'HC',
                    'maCa' => $shift['maCa'] ?? $shift['id'] ?? null,
                ];
            }
        }

        // Nếu không có dữ liệu chấm công và không phải lễ/cuối tuần/phép → vắng mặt
        return [
            'date' => $date,
            'day_type' => 'absent',
            'day_type_label' => 'Vắng mặt',
            'work_value' => 0.0,
            'work_hours' => 0,
            'ot_hours' => 0,
            'has_attendance' => false,
            'shift_name' => $shift['tenCa'] ?? 'HC',
            'maCa' => $shift['maCa'] ?? $shift['id'] ?? null,
        ];
    }

    /**
     * Tính số phút làm việc từ check-in và check-out
     * @param string $checkIn - datetime
     * @param string $checkOut - datetime
     * @return int - số phút
     */
    private static function calculateWorkMinutes($checkIn, $checkOut)
    {
        $checkIn = trim((string)$checkIn);
        $checkOut = trim((string)$checkOut);

        try {
            $inTime = strtotime($checkIn);
            $outTime = strtotime($checkOut);

            if (!$inTime || !$outTime) {
                return 0;
            }

            $diff = ($outTime - $inTime) / 60; // Convert to minutes
            return max(0, (int)$diff);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Tính số phút làm việc thực tế, kẹp giờ vào/ra trong đúng khung giờ ca:
     * - Nếu chấm vào sớm hơn giờ bắt đầu ca → tính từ giờ bắt đầu ca.
     * - Nếu chấm ra muộn hơn giờ kết thúc ca → chỉ tính đến giờ kết thúc ca.
     * @param string $checkIn - datetime chấm vào thực tế
     * @param string $checkOut - datetime chấm ra thực tế
     * @param string $shiftStart - giờ bắt đầu ca (HH:MM:SS)
     * @param string $shiftEnd - giờ kết thúc ca (HH:MM:SS)
     * @return int - số phút làm việc trong khung ca
     */
    private static function calculateClampedWorkMinutes($checkIn, $checkOut, $shiftStart, $shiftEnd)
    {
        $checkInTime = strtotime((string)$checkIn);
        $checkOutTime = strtotime((string)$checkOut);
        if (!$checkInTime || !$checkOutTime) {
            return 0;
        }

        $checkInDate = date('Y-m-d', $checkInTime);
        $shiftStartTime = strtotime($checkInDate . ' ' . $shiftStart);
        $isOvernight = $shiftEnd < $shiftStart;
        $shiftEndTime = $isOvernight
            ? strtotime($checkInDate . ' ' . $shiftEnd . ' +1 day')
            : strtotime($checkInDate . ' ' . $shiftEnd);

        $effectiveIn = max($checkInTime, $shiftStartTime);
        $effectiveOut = min($checkOutTime, $shiftEndTime);

        return max(0, (int)round(($effectiveOut - $effectiveIn) / 60));
    }

    /**
     * Tổng số phút của một ca làm việc (xử lý cả ca qua đêm).
     * @param string $shiftStart - HH:MM:SS
     * @param string $shiftEnd - HH:MM:SS
     * @return int - số phút, tối thiểu 1 để tránh chia cho 0
     */
    private static function calculateShiftMinutes($shiftStart, $shiftEnd)
    {
        $start = strtotime('2000-01-01 ' . $shiftStart);
        $end = strtotime('2000-01-01 ' . $shiftEnd);
        if ($end <= $start) {
            $end = strtotime('2000-01-02 ' . $shiftEnd);
        }
        return max(1, (int)round(($end - $start) / 60));
    }

    /**
     * Tính giá trị công trong ngày: tỷ lệ giờ làm / tổng giờ ca, làm tròn LÊN
     * tới mốc gần nhất trong {0, 0.25, 0.5, 0.75, 1.0}.
     * @param int $workMinutes - số phút làm việc (đã kẹp trong khung ca)
     * @param int $shiftMinutes - tổng số phút của ca làm việc trong ngày
     * @return float - 0.0, 0.25, 0.5, 0.75, hoặc 1.0
     */
    private static function calculateWorkValue($workMinutes, $shiftMinutes = 480)
    {
        $workMinutes = max(0, (int)$workMinutes);
        $shiftMinutes = max(1, (int)$shiftMinutes);

        $ratio = $workMinutes / $shiftMinutes;
        if ($ratio <= 0) {
            return 0.0;
        }

        foreach ([0.25, 0.5, 0.75, 1.0] as $milestone) {
            if ($ratio <= $milestone + 0.0001) {
                return $milestone;
            }
        }

        // Làm quá giờ ca cũng chỉ tính tối đa 1 công (OT tính riêng).
        return 1.0;
    }

    /**
     * Lấy label cho loại phép
     * @param string $leaveType
     * @return string
     */
    private static function getLeaveTypeLabel($leaveType)
    {
        $labels = [
            'annual' => 'Phép năm',
            'unpaid' => 'Phép không lương',
            'sick' => 'Phép bệnh',
            'maternity' => 'Phép thai sản',
            'compassionate' => 'Phép hôn nhân/tang chế',
            'other' => 'Phép khác',
        ];

        return $labels[$leaveType] ?? 'Phép khác';
    }

    /**
     * Template dữ liệu ngày trống
     * @return array
     */
    private static function getEmptyDayData()
    {
        return [
            'date' => '',
            'day_type' => 'unknown',
            'day_type_label' => 'Không xác định',
            'work_value' => 0.0,
            'work_hours' => 0,
            'ot_hours' => 0,
            'has_attendance' => false,
        ];
    }

    /**
     * Định dạng dữ liệu để hiển thị
     * @param array $dayData
     * @return array
     */
    public static function formatDayData($dayData)
    {
        return [
            'date' => $dayData['date'] ?? '',
            'type' => $dayData['day_type'] ?? 'unknown',
            'label' => $dayData['day_type_label'] ?? '',
            'work' => $dayData['work_value'] ?? 0,
            'soGio' => round($dayData['work_hours'] ?? 0, 2),
            'ot' => round($dayData['ot_hours'] ?? 0, 2),
        ];
    }

    /**
     * Tính tổng công tháng từ daily breakdown
     * @param array $dailyBreakdown - kết quả từ calculateMonthlyAttendance
     * @return float
     */
    public static function getTotalWorkDays($dailyBreakdown)
    {
        $total = 0;
        foreach ($dailyBreakdown as $day => $data) {
            $total += (float)($data['work_value'] ?? 0);
        }
        return $total;
    }

    /**
     * Tính average work soGio/ngày (bỏ qua lễ, cuối tuần, phép)
     * @param array $totals - từ calculateMonthlyAttendance
     * @return float
     */
    public static function getAverageWorkHours($totals)
    {
        $workDays = (float)($totals['total_work_days'] ?? 0);
        if ($workDays === 0) {
            return 0;
        }

        $totalWorkingHours = (float)($totals['working_hours'] ?? 0);
        return round($totalWorkingHours / $workDays, 2);
    }

    /**
     * So sánh công thực với công tiêu chuẩn của tháng
     * @param float $actualWorkDays
     * @param int $standardWorkDays - từ HolidayCalculator
     * @return array ['diff' => float, 'percentage' => float, 'trangThai' => string]
     */
    public static function compareWithStandard($actualWorkDays, $standardWorkDays)
    {
        $actualWorkDays = (float)$actualWorkDays;
        $standardWorkDays = (int)$standardWorkDays;

        $diff = $actualWorkDays - $standardWorkDays;
        $percentage = $standardWorkDays > 0 ? round(($actualWorkDays / $standardWorkDays) * 100, 1) : 0;

        $trangThai = 'normal';
        if ($diff > 0) {
            $trangThai = 'over';
        } elseif ($diff < 0) {
            $trangThai = 'under';
        }

        return [
            'actual' => $actualWorkDays,
            'standard' => $standardWorkDays,
            'diff' => $diff,
            'percentage' => $percentage,
            'trangThai' => $trangThai,
        ];
    }
}
?>
