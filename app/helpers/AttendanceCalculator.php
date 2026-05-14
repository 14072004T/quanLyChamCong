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
    public static function calculateMonthlyAttendance($monthKey, $attendanceData = [], $leaveRequests = [], $employeeInfo = [])
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
                $employeeInfo
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
            'month_key' => $monthKey,
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
     * @return array
     */
    public static function calculateDailyAttendance($date, $checkInOutData = null, $leaveType = null, $employeeInfo = [])
    {
        $date = trim((string)$date);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return self::getEmptyDayData();
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
            ];
        }

        // Kiểm tra nếu có xin phép
        if ($leaveType) {
            $isHalfDay = false;
            $leaveId = null;
            $leaveReason = null;
            if (is_array($leaveType)) {
                $isHalfDay = !empty($leaveType['is_half_day']);
                $leaveId = $leaveType['leave_id'] ?? null;
                $leaveReason = $leaveType['reason'] ?? null;
                $leaveType = $leaveType['type'] ?? 'annual';
            }
            $leaveType = strtolower(trim((string)$leaveType));
            return [
                'date' => $date,
                'day_type' => 'leave',
                'leave_type' => $leaveType,
                'leave_id' => $leaveId,
                'leave_reason' => $leaveReason,
                'day_type_label' => self::getLeaveTypeLabel($leaveType),
                'work_value' => 0.0,
                'work_hours' => 0,
                'ot_hours' => 0,
                'has_attendance' => false,
            ];
        }

        // Nếu có dữ liệu chấm công, tính toán
        if ($checkInOutData && !empty($checkInOutData)) {
            $checkIn = $checkInOutData['checkIn'] ?? null;
            $checkOut = $checkInOutData['checkOut'] ?? null;

            if ($checkIn && $checkOut) {
                $workMinutes = self::calculateWorkMinutes($checkIn, $checkOut);
                
                // Trừ 60 phút nghỉ trưa nếu làm việc trên 5 tiếng (300 phút)
                if ($workMinutes >= 300) {
                    $workMinutes -= 60;
                }

                $workHours = round($workMinutes / 60, 2);

                // Standard work time: 480 minutes (8 hours)
                $standardMinutes = 480;
                $otHours = $workMinutes > $standardMinutes ? round(($workMinutes - $standardMinutes) / 60, 2) : 0;

                // Tính work value (1.0 = ngày đầy đủ, 0.5 = nửa ngày)
                $workValue = self::calculateWorkValue($workMinutes, $standardMinutes);

                return [
                    'date' => $date,
                    'day_type' => 'working',
                    'day_type_label' => 'Ngày làm việc',
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'work_minutes' => $workMinutes,
                    'work_hours' => $workHours,
                    'work_value' => $workValue,
                    'ot_hours' => $otHours,
                    'has_attendance' => true,
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
     * Tính giá trị công trong ngày (1.0 = đầy đủ, 0.5 = nửa, 0 = không)
     * @param int $workMinutes - số phút làm việc
     * @param int $standardMinutes - tiêu chuẩn (mặc định 480 = 8h)
     * @return float - 0.0, 0.5, 1.0
     */
    private static function calculateWorkValue($workMinutes, $standardMinutes = 480)
    {
        $workMinutes = (int)$workMinutes;
        $standardMinutes = (int)$standardMinutes;

        // 80% tiêu chuẩn = tính 1 ngày đầy đủ (giáp người lên 1.0)
        $threshold80Percent = round($standardMinutes * 0.80);

        if ($workMinutes >= $threshold80Percent) {
            return 1.0;
        }

        // 40% tiêu chuẩn = tính 0.5 ngày
        $threshold50Percent = round($standardMinutes * 0.40);
        if ($workMinutes >= $threshold50Percent) {
            return 0.5;
        }

        return 0.0;
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
            'hours' => round($dayData['work_hours'] ?? 0, 2),
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
     * Tính average work hours/ngày (bỏ qua lễ, cuối tuần, phép)
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
     * @return array ['diff' => float, 'percentage' => float, 'status' => string]
     */
    public static function compareWithStandard($actualWorkDays, $standardWorkDays)
    {
        $actualWorkDays = (float)$actualWorkDays;
        $standardWorkDays = (int)$standardWorkDays;

        $diff = $actualWorkDays - $standardWorkDays;
        $percentage = $standardWorkDays > 0 ? round(($actualWorkDays / $standardWorkDays) * 100, 1) : 0;

        $status = 'normal';
        if ($diff > 0) {
            $status = 'over';
        } elseif ($diff < 0) {
            $status = 'under';
        }

        return [
            'actual' => $actualWorkDays,
            'standard' => $standardWorkDays,
            'diff' => $diff,
            'percentage' => $percentage,
            'status' => $status,
        ];
    }
}
?>
