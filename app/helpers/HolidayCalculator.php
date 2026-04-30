<?php
/**
 * HolidayCalculator - Quản lý lịch lễ Tết 2026 theo quy định pháp luật Việt Nam
 *
 * Lịch lễ Tết hưởng nguyên lương năm 2026:
 * - Tết Dương lịch: 01/01/2026 (1 ngày)
 * - Tết Nguyên đán Bính Ngọ: 21-25/02/2026 (5 ngày theo luật)
 * - Giỗ Tổ Hùng Vương: 18/04/2026 (1 ngày)
 * - Ngày Chiến thắng: 30/04/2026 (1 ngày)
 * - Quốc tế Lao động: 01/05/2026 (1 ngày)
 * - Quốc khánh: 02-03/09/2026 (2 ngày)
 * Tổng: 11 ngày lễ hưởng nguyên lương
 */
class HolidayCalculator
{
    /**
     * Lịch lễ chính thức 2026 - không trừ vào ngày phép, hưởng nguyên lương
     * Format: YYYY-MM-DD
     */
    private static $holidays_2026 = [
        '2026-01-01', // Tết Dương lịch
        '2026-02-21', // Tết Nguyên đán (ngày 1 Tết)
        '2026-02-22', // Tết Nguyên đán (ngày 2 Tết)
        '2026-02-23', // Tết Nguyên đán (ngày 3 Tết)
        '2026-02-24', // Tết Nguyên đán (ngày 4 Tết)
        '2026-02-25', // Tết Nguyên đán (ngày 5 Tết)
        '2026-04-18', // Giỗ Tổ Hùng Vương
        '2026-04-30', // Ngày Chiến thắng
        '2026-05-01', // Quốc tế Lao động
        '2026-09-02', // Quốc khánh
        '2026-09-03', // Quốc khánh (ngày 2)
    ];

    /**
     * Ngày Thứ 7 và Chủ Nhật - công ty không làm việc
     * Nếu cần hỗ trợ công ty làm thứ 7, có thể cấu hình qua database
     */
    private static $weekend_days = [6, 0]; // 6 = Thứ 7 (Saturday), 0 = Chủ Nhật (Sunday)

    /**
     * Kiểm tra một ngày có phải ngày lễ chính thức không
     * @param string $date - YYYY-MM-DD
     * @return bool
     */
    public static function isHoliday($date)
    {
        $date = trim((string)$date);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        return in_array($date, self::$holidays_2026, true);
    }

    /**
     * Kiểm tra một ngày có phải cuối tuần (T7/CN) không
     * @param string $date - YYYY-MM-DD
     * @return bool
     */
    public static function isWeekend($date)
    {
        $date = trim((string)$date);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        $dayOfWeek = (int)date('w', strtotime($date)); // 0 = Sun, 6 = Sat
        return in_array($dayOfWeek, self::$weekend_days, true);
    }

    /**
     * Kiểm tra một ngày có phải ngày làm việc không (không phải lễ, không phải cuối tuần)
     * @param string $date - YYYY-MM-DD
     * @return bool
     */
    public static function isWorkingDay($date)
    {
        return !self::isHoliday($date) && !self::isWeekend($date);
    }

    /**
     * Lấy danh sách tất cả ngày lễ trong tháng/năm
     * @param string $month - YYYY-MM (ví dụ: 2026-02)
     * @return array - ['2026-02-21', '2026-02-22', ...]
     */
    public static function getHolidaysInMonth($month)
    {
        $month = trim((string)$month);
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return [];
        }

        return array_filter(self::$holidays_2026, function ($holiday) use ($month) {
            return strpos($holiday, $month) === 0;
        });
    }

    /**
     * Lấy danh sách tất cả ngày lệ trong năm
     * @param string $year - YYYY
     * @return array
     */
    public static function getHolidaysInYear($year)
    {
        $year = trim((string)$year);
        if (!preg_match('/^\d{4}$/', $year)) {
            return [];
        }

        return array_filter(self::$holidays_2026, function ($holiday) use ($year) {
            return strpos($holiday, $year) === 0;
        });
    }

    /**
     * Tính số ngày làm việc trong tháng (loại bỏ lễ, cuối tuần)
     * @param string $month - YYYY-MM
     * @return int
     */
    public static function getWorkingDaysCountInMonth($month)
    {
        $month = trim((string)$month);
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return 0;
        }

        // Lấy ngày cuối cùng của tháng
        $firstDay = $month . '-01';
        $lastDay = date('Y-m-d', strtotime('last day of ' . $month));

        $count = 0;
        $currentDate = new DateTime($firstDay);
        $endDate = new DateTime($lastDay);
        $endDate->modify('+1 day'); // Include last day

        while ($currentDate < $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            if (self::isWorkingDay($dateStr)) {
                $count++;
            }
            $currentDate->modify('+1 day');
        }

        return $count;
    }

    /**
     * Tính số ngày lễ trong tháng
     * @param string $month - YYYY-MM
     * @return int
     */
    public static function getHolidaysCountInMonth($month)
    {
        return count(self::getHolidaysInMonth($month));
    }

    /**
     * Tính số ngày cuối tuần trong tháng
     * @param string $month - YYYY-MM
     * @return int
     */
    public static function getWeekendsCountInMonth($month)
    {
        $month = trim((string)$month);
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return 0;
        }

        $firstDay = $month . '-01';
        $lastDay = date('Y-m-d', strtotime('last day of ' . $month));

        $count = 0;
        $currentDate = new DateTime($firstDay);
        $endDate = new DateTime($lastDay);
        $endDate->modify('+1 day');

        while ($currentDate < $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            if (self::isWeekend($dateStr)) {
                $count++;
            }
            $currentDate->modify('+1 day');
        }

        return $count;
    }

    /**
     * Lấy loại ngày (để display trên bảng công)
     * @param string $date - YYYY-MM-DD
     * @return string - 'holiday', 'weekend', 'working', 'leave', 'ot', etc.
     */
    public static function getDayType($date, $attendanceData = null)
    {
        $date = trim((string)$date);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return 'invalid';
        }

        if (self::isHoliday($date)) {
            return 'holiday';
        }

        if (self::isWeekend($date)) {
            return 'weekend';
        }

        // Nếu có attendance data, kiểm tra xem có chấm công không
        if ($attendanceData) {
            if (isset($attendanceData['is_ot']) && $attendanceData['is_ot']) {
                return 'ot';
            }
            if (isset($attendanceData['leave_type']) && $attendanceData['leave_type']) {
                return $attendanceData['leave_type']; // 'annual_leave', 'unpaid_leave', etc.
            }
            if (isset($attendanceData['has_attendance']) && $attendanceData['has_attendance']) {
                return 'working';
            }
        }

        return 'working';
    }

    /**
     * Lấy tất cả ngày lễ 2026 (phục vụ admin)
     * @return array
     */
    public static function getAllHolidays()
    {
        return self::$holidays_2026;
    }

    /**
     * Tính số ngày lễ Tết 2026 tổng cộng
     * @return int
     */
    public static function getTotalHolidaysCount()
    {
        return count(self::$holidays_2026);
    }

    /**
     * Cập nhật danh sách ngày lễ (để thêm ngày lợi dụng công ty)
     * @param array $additionalHolidays - ['2026-02-20', ...]
     * @return void
     */
    public static function addHolidays(array $additionalHolidays)
    {
        foreach ($additionalHolidays as $date) {
            $date = trim((string)$date);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && !in_array($date, self::$holidays_2026, true)) {
                self::$holidays_2026[] = $date;
            }
        }
        sort(self::$holidays_2026);
    }

    /**
     * Kiểm tra tháng 3/2026 cụ thể
     * Tháng 3/2026: 31 ngày, không có lễ lớn, cuối tuần thường
     * @return array
     */
    public static function getMarch2026Info()
    {
        $month = '2026-03';
        return [
            'total_days' => 31,
            'holidays' => self::getHolidaysInMonth($month),
            'weekends' => self::getWeekendsCountInMonth($month),
            'working_days' => self::getWorkingDaysCountInMonth($month),
        ];
    }
}
?>
