<?php
/**
 * LeaveCalculator - Tính toán ngày phép năm theo quy định pháp luật Việt Nam
 *
 * Quy định ngày phép:
 * - Công việc bình thường: 12 ngày/năm
 * - Nặng nhọc/độc hại/nguy hiểm: 14 ngày/năm
 * - Đặc biệt nặng nhọc/độc hại: 16 ngày/năm
 * + Thêm 1 ngày/năm cho mỗi 5 năm thâm niên
 * - Chưa đủ 12 tháng: (số tháng / 12) × số ngày phép
 */
class LeaveCalculator
{
    /**
     * Định nghĩa loại công việc và ngày phép cơ bản
     */
    const JOB_TYPE_BASIC = 'basic'; // Công việc bình thường
    const JOB_TYPE_HAZARDOUS = 'hazardous'; // Công việc nặng nhọc, độc hại, nguy hiểm
    const JOB_TYPE_SPECIAL = 'special'; // Công việc đặc biệt nặng nhọc, độc hại

    /**
     * Ngày phép cơ bản theo loại công việc
     */
    private static $base_leave_days = [
        self::JOB_TYPE_BASIC => 12,
        self::JOB_TYPE_HAZARDOUS => 14,
        self::JOB_TYPE_SPECIAL => 16,
    ];

    /**
     * Bonus thâm niên: cứ 5 năm +1 ngày phép
     */
    const SENIORITY_BONUS_THRESHOLD = 5; // năm
    const SENIORITY_BONUS_DAYS = 1; // ngày

    /**
     * Lấy ngày phép cơ bản theo loại công việc
     * @param string $jobType - 'basic', 'hazardous', 'special'
     * @return int - ngày phép cơ bản
     */
    public static function getBaseLeaveByJobType($jobType)
    {
        $jobType = strtolower(trim((string)$jobType));
        return self::$base_leave_days[$jobType] ?? self::$base_leave_days[self::JOB_TYPE_BASIC];
    }

    /**
     * Tính bonus thâm niên
     * @param int $seniority - số năm thâm niên
     * @return int - bonus ngày phép
     */
    public static function calculateSeniorityBonus($seniority)
    {
        $seniority = (int)$seniority;
        if ($seniority < self::SENIORITY_BONUS_THRESHOLD) {
            return 0;
        }

        // Mỗi 5 năm +1 ngày
        $bonus = intdiv($seniority, self::SENIORITY_BONUS_THRESHOLD) * self::SENIORITY_BONUS_DAYS;
        return $bonus;
    }

    /**
     * Tính ngày phép cho năm thứ nhất (chưa đủ 12 tháng)
     * Công thức: (số tháng làm / 12) × ngày phép cơ bản
     * @param int $months - số tháng đã làm việc
     * @param string $jobType - loại công việc
     * @return float - ngày phép (có thể là số thập phân)
     */
    public static function calculateProRataLeave($months, $jobType = self::JOB_TYPE_BASIC)
    {
        $months = (int)$months;
        if ($months <= 0 || $months > 12) {
            return 0;
        }

        $baseLeave = self::getBaseLeaveByJobType($jobType);
        $proRataLeave = ($months / 12) * $baseLeave;

        // Làm tròn xuống theo chiều có lợi cho nhân viên: 0.5 ngày trở lên = 1 ngày
        return ceil($proRataLeave * 2) / 2; // Làm tròn đến 0.5 ngày
    }

    /**
     * Tính tổng ngày phép hàng năm (sau khi tính seniority)
     * @param string $jobType - loại công việc
     * @param int $seniority - năm thâm niên (0 nếu năm đầu)
     * @param int $workingMonths - số tháng đã làm (null = 12 tháng - đủ năm)
     * @return int|float
     */
    public static function calculateAnnualLeave($jobType = self::JOB_TYPE_BASIC, $seniority = 0, $workingMonths = null)
    {
        $jobType = strtolower(trim((string)$jobType));
        $seniority = (int)$seniority;
        $workingMonths = $workingMonths ? (int)$workingMonths : 12;

        // Tính ngày phép cơ bản (có thể tính pro-rata nếu chưa đủ 12 tháng)
        $baseLeave = $workingMonths < 12
            ? self::calculateProRataLeave($workingMonths, $jobType)
            : self::getBaseLeaveByJobType($jobType);

        // Tính bonus thâm niên
        $seniorityBonus = self::calculateSeniorityBonus($seniority);

        return $baseLeave + $seniorityBonus;
    }

    /**
     * Tính ngày phép còn lại
     * @param string $jobType - loại công việc
     * @param int $seniority - năm thâm niên
     * @param int $usedLeaves - số ngày phép đã sử dụng
     * @param int $workingMonths - số tháng đã làm
     * @return float - ngày phép còn lại (có thể âm nếu sử dụng quá)
     */
    public static function calculateRemainingLeave($jobType = self::JOB_TYPE_BASIC, $seniority = 0, $usedLeaves = 0, $workingMonths = null)
    {
        $totalLeave = self::calculateAnnualLeave($jobType, $seniority, $workingMonths);
        $usedLeaves = (float)$usedLeaves;

        return $totalLeave - $usedLeaves;
    }

    /**
     * Kiểm tra nhân viên có đủ ngày phép để xin không
     * @param float $requestedDays - số ngày xin
     * @param float $remainingLeave - ngày phép còn lại
     * @return array - ['approved' => bool, 'message' => string]
     */
    public static function validateLeaveRequest($requestedDays, $remainingLeave)
    {
        $requestedDays = (float)$requestedDays;
        $remainingLeave = (float)$remainingLeave;

        if ($requestedDays <= 0) {
            return [
                'approved' => false,
                'message' => 'Số ngày xin phép không hợp lệ (phải > 0)'
            ];
        }

        if ($requestedDays > $remainingLeave) {
            return [
                'approved' => false,
                'message' => sprintf('Không đủ ngày phép. Có: %.1f, yêu cầu: %.1f', $remainingLeave, $requestedDays)
            ];
        }

        return [
            'approved' => true,
            'message' => 'Đủ điều kiện xin phép'
        ];
    }

    /**
     * Tính ngày làm việc còn lại trong năm (loại bỏ lễ, cuối tuần, phép đã sử dụng)
     * @param string $year - YYYY
     * @param float $usedLeaves - số ngày phép đã sử dụng
     * @param int $totalHolidays - số ngày lễ (từ HolidayCalculator)
     * @param int $totalWeekends - số ngày cuối tuần
     * @return int
     */
    public static function getRemainingWorkingDaysInYear($year, $usedLeaves = 0, $totalHolidays = 0, $totalWeekends = 0)
    {
        $year = (int)$year;
        $usedLeaves = (float)$usedLeaves;

        // Tính tổng ngày trong năm
        $isLeapYear = (($year % 4 === 0 && $year % 100 !== 0) || $year % 400 === 0);
        $totalDaysInYear = $isLeapYear ? 366 : 365;

        // Tính ngày làm việc thực tế = tổng ngày - lễ - cuối tuần - phép đã dùng
        $remainingWorkDays = $totalDaysInYear - $totalHolidays - $totalWeekends - (int)$usedLeaves;

        return max(0, $remainingWorkDays);
    }

    /**
     * Lấy thông tin chi tiết về ngày phép của nhân viên
     * @param array $employeeData - ['jobType', 'seniority', 'usedLeaves', 'workingMonths']
     * @return array
     */
    public static function getLeaveDetails(array $employeeData)
    {
        $jobType = $employeeData['jobType'] ?? self::JOB_TYPE_BASIC;
        $seniority = (int)($employeeData['seniority'] ?? 0);
        $usedLeaves = (float)($employeeData['usedLeaves'] ?? 0);
        $workingMonths = $employeeData['workingMonths'] ?? 12;

        $totalLeave = self::calculateAnnualLeave($jobType, $seniority, $workingMonths);
        $remainingLeave = self::calculateRemainingLeave($jobType, $seniority, $usedLeaves, $workingMonths);
        $seniorityBonus = self::calculateSeniorityBonus($seniority);

        return [
            'job_type' => $jobType,
            'job_type_name' => self::getJobTypeName($jobType),
            'seniority' => $seniority,
            'seniority_years' => $seniority,
            'base_leave' => self::getBaseLeaveByJobType($jobType),
            'seniority_bonus' => $seniorityBonus,
            'total_leave' => $totalLeave,
            'used_leaves' => $usedLeaves,
            'remaining_leaves' => $remainingLeave,
            'is_first_year' => $workingMonths < 12,
            'working_months' => $workingMonths,
        ];
    }

    /**
     * Lấy tên loại công việc
     * @param string $jobType
     * @return string
     */
    public static function getJobTypeName($jobType)
    {
        $names = [
            self::JOB_TYPE_BASIC => 'Công việc bình thường',
            self::JOB_TYPE_HAZARDOUS => 'Công việc nặng nhọc/độc hại/nguy hiểm',
            self::JOB_TYPE_SPECIAL => 'Công việc đặc biệt nặng nhọc/độc hại',
        ];

        return $names[strtolower($jobType)] ?? 'Không xác định';
    }

    /**
     * Format ngày phép để hiển thị (0.5 = nửa ngày, 1 = 1 ngày, etc.)
     * @param float $days
     * @return string
     */
    public static function formatLeave($days)
    {
        $days = (float)$days;

        if ($days === (int)$days) {
            return (int)$days . ' ngày';
        }

        // Format thập phân
        $formatted = number_format($days, 1, ',', '.');

        if (fmod($days, 0.5) === 0) {
            return str_replace(['.0', ',0'], ['', ''], $formatted) . ' ngày';
        }

        return $formatted . ' ngày';
    }
}
?>
