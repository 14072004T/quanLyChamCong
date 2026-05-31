<?php

declare(strict_types=1);

mt_srand(20260531);

$startDate = new DateTime('2026-01-01');
$endDate = new DateTime('2026-05-31');
$employeeCount = 30;
$managerIds = [3, 10];

$departments = ['Sản xuất', 'Kho', 'QC', 'Bảo trì'];
$lastNames = ['Nguyen', 'Tran', 'Le', 'Pham', 'Hoang', 'Phan', 'Vu', 'Do', 'Bui', 'Dang'];
$middleNames = ['Van', 'Thi', 'Huu', 'Ngoc', 'Quang', 'Minh', 'Anh', 'Thanh', 'Gia'];
$firstNames = ['An', 'Binh', 'Chau', 'Dung', 'Hanh', 'Khanh', 'Lam', 'Minh', 'Nam', 'Phuc', 'Quan', 'Thao', 'Trang', 'Tuan', 'Vy'];

$leaveTypes = ['annual', 'personal', 'sick', 'emergency'];
$leaveReasons = [
    'family event',
    'medical checkup',
    'personal business',
    'urgent matters',
    'wedding in family',
    'rest day',
];

$correctionReasons = [
    'missed check-in',
    'forgot to check out',
    'traffic jam',
    'network issue',
    'device battery died',
    'client meeting offsite',
];

function randFrom(array $items)
{
    return $items[mt_rand(0, count($items) - 1)];
}

function sqlString(string $value): string
{
    return "'" . str_replace("'", "''", $value) . "'";
}

function sqlNullable(?string $value): string
{
    return $value === null ? 'NULL' : sqlString($value);
}

function timeFromMinutes(int $minutes): string
{
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;
    return sprintf('%02d:%02d:00', $hours, $mins);
}

$employees = [];
for ($i = 0; $i < $employeeCount; $i++) {
    $offset = $i + 1;
    $name = randFrom($lastNames) . ' ' . randFrom($middleNames) . ' ' . randFrom($firstNames);

    $employees[] = [
        'offset' => $offset,
        'id_expr' => "(@base_id + {$offset})",
        'name' => $name,
        'department' => randFrom($departments),
    ];
}

$lines = [];
$lines[] = 'START TRANSACTION;';
$lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
$lines[] = 'SET NAMES utf8mb4;';
$lines[] = 'SET CHARACTER SET utf8mb4;';
$lines[] = 'SET @base_id := (SELECT GREATEST((SELECT IFNULL(MAX(maTK), 0) FROM taikhoan), (SELECT IFNULL(MAX(maND), 0) FROM nguoidung)));';

// Accounts
$accountValues = [];
foreach ($employees as $employee) {
    $accountValues[] = sprintf(
        "(%s, %s, %s)",
        $employee['id_expr'],
        "CONCAT('nhanvien', {$employee['id_expr']})",
        sqlString('e10adc3949ba59abbe56e057f20f883e')
    );
}
$lines[] = 'INSERT INTO taikhoan (maTK, tenDangNhap, matKhau) VALUES';
$lines[] = implode(",\n", $accountValues) . ';';

// Employees
$employeeValues = [];
foreach ($employees as $employee) {
    $employeeValues[] = sprintf(
        "(%s, %s, %s, %s, %s, %s, '2026-01-01 08:00:00')",
        $employee['id_expr'],
        $employee['id_expr'],
        sqlString($employee['name']),
        "CONCAT('nv', {$employee['id_expr']}, '@company.local')",
        "CONCAT('0903', LPAD(({$employee['id_expr']} * 7919) % 1000000, 6, '0'))",
        sqlString($employee['department'])
    );
}
$lines[] = 'INSERT INTO nguoidung (maND, maTK, hoTen, email, soDienThoai, phongBan, created_at) VALUES';
$lines[] = implode(",\n", $employeeValues) . ';';

// Shift assignments
$shiftValues = [];
foreach ($employees as $employee) {
    $shiftValues[] = sprintf(
        "(%s, 1, '2026-01-01', NULL, '2026-01-01 08:00:00')",
        $employee['id_expr']
    );
}
$lines[] = 'INSERT INTO attendance_employee_shift (maND, shift_id, effective_from, effective_to, created_at) VALUES';
$lines[] = implode(",\n", $shiftValues) . ';';

// Attendance logs and daily summaries
$summaryValues = [];
$logValues = [];
$summaryChunk = 500;
$logChunk = 500;

$periodEnd = (clone $endDate)->modify('+1 day');
$period = new DatePeriod($startDate, new DateInterval('P1D'), $periodEnd);

foreach ($period as $date) {
    $dateStr = $date->format('Y-m-d');

    foreach ($employees as $employee) {
        $roll = mt_rand(1, 100);
        $status = 'normal';
        $firstIn = null;
        $lastOut = null;
        $workMinutes = 0;
        $overtimeMinutes = 0;
        $lateMinutes = 0;

        if ($roll <= 7) {
            $status = 'absent';
        } elseif ($roll <= 10) {
            $status = 'leave';
        } else {
            $lateRoll = mt_rand(1, 100) <= 20;
            if ($lateRoll) {
                $inMinutes = (8 * 60) + mt_rand(6, 60); // 08:06 - 09:00
            } else {
                $inMinutes = (7 * 60) + mt_rand(40, 85); // 07:40 - 08:25
            }

            $outMinutes = (17 * 60) + mt_rand(0, 20); // 17:00 - 17:20
            if (mt_rand(1, 100) <= 20) {
                $outMinutes += mt_rand(30, 120); // overtime
            }

            $firstIn = $dateStr . ' ' . timeFromMinutes($inMinutes);
            $lastOut = $dateStr . ' ' . timeFromMinutes($outMinutes);

            $workMinutes = max(0, $outMinutes - $inMinutes);
            $lateMinutes = max(0, $inMinutes - (8 * 60));
            $overtimeMinutes = max(0, $outMinutes - (17 * 60));
            $status = $lateMinutes > 0 ? 'late' : 'normal';

            $logValues[] = sprintf(
                "(%s, 'IN', 'LAN', NULL, NULL, NULL, %s)",
                $employee['id_expr'],
                sqlString($firstIn)
            );
            $logValues[] = sprintf(
                "(%s, 'OUT', 'LAN', NULL, NULL, NULL, %s)",
                $employee['id_expr'],
                sqlString($lastOut)
            );
        }

        $summaryValues[] = sprintf(
            "(%s, %s, %s, %s, %d, %d, %d, %s, %s)",
            $employee['id_expr'],
            sqlString($dateStr),
            sqlNullable($firstIn),
            sqlNullable($lastOut),
            $workMinutes,
            $overtimeMinutes,
            $lateMinutes,
            sqlString($status),
            sqlString($dateStr . ' 20:00:00')
        );

        if (count($summaryValues) >= $summaryChunk) {
            $lines[] = 'INSERT INTO attendance_daily_summary (maND, work_date, first_in, last_out, work_minutes, overtime_minutes, late_minutes, status, created_at) VALUES';
            $lines[] = implode(",\n", $summaryValues) . ';';
            $summaryValues = [];
        }

        if (count($logValues) >= $logChunk) {
            $lines[] = 'INSERT INTO attendance_logs (maND, action, method, wifi_name, device_info, note, created_at) VALUES';
            $lines[] = implode(",\n", $logValues) . ';';
            $logValues = [];
        }
    }
}

if (!empty($summaryValues)) {
    $lines[] = 'INSERT INTO attendance_daily_summary (maND, work_date, first_in, last_out, work_minutes, overtime_minutes, late_minutes, status, created_at) VALUES';
    $lines[] = implode(",\n", $summaryValues) . ';';
}

if (!empty($logValues)) {
    $lines[] = 'INSERT INTO attendance_logs (maND, action, method, wifi_name, device_info, note, created_at) VALUES';
    $lines[] = implode(",\n", $logValues) . ';';
}

// Leave requests (manager approvals)
$leaveValues = [];
for ($i = 0; $i < 10; $i++) {
    $employee = randFrom($employees);
    $baseDate = new DateTime('2026-02-01');
    $offsetDays = mt_rand(0, 115);
    $fromDate = (clone $baseDate)->modify('+' . $offsetDays . ' days');
    $duration = mt_rand(1, 3);
    $toDate = (clone $fromDate)->modify('+' . ($duration - 1) . ' days');

    $isHalfDay = $duration === 1 ? mt_rand(0, 1) : 0;
    $status = $i < 5 ? 'pending' : 'approved';
    $managerId = $status === 'approved' ? randFrom($managerIds) : null;
    $approvedAt = $status === 'approved' ? $fromDate->format('Y-m-d') . ' 12:00:00' : null;
    $managerNote = $status === 'approved' ? 'Approved' : null;

    $leaveValues[] = sprintf(
        "(%s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s)",
        $employee['id_expr'],
        sqlString($fromDate->format('Y-m-d')),
        sqlString(randFrom($leaveTypes)),
        $isHalfDay,
        sqlString(randFrom($leaveReasons)),
        sqlString($status),
        sqlNullable($managerNote),
        $managerId === null ? 'NULL' : (string)$managerId,
        sqlNullable($approvedAt),
        sqlString($fromDate->format('Y-m-d') . ' 09:00:00'),
        sqlString($fromDate->format('Y-m-d')),
        sqlString($toDate->format('Y-m-d'))
    );
}

$lines[] = 'INSERT INTO leave_requests (maND, leave_date, leave_type, is_half_day, reason, status, manager_note, manager_approver_id, approved_at, created_at, from_date, to_date) VALUES';
$lines[] = implode(",\n", $leaveValues) . ';';

// Attendance corrections (HR approvals)
$correctionStatuses = ['pending', 'pending', 'pending', 'approved', 'approved', 'approved', 'rejected', 'rejected', 'rejected', 'pending'];
$correctionValues = [];
for ($i = 0; $i < 10; $i++) {
    $employee = randFrom($employees);
    $baseDate = new DateTime('2026-01-01');
    $offsetDays = mt_rand(0, 150);
    $workDate = (clone $baseDate)->modify('+' . $offsetDays . ' days');

    $status = $correctionStatuses[$i];
    $oldTime = $workDate->format('Y-m-d') . ' ' . timeFromMinutes((8 * 60) + mt_rand(5, 45));
    $newTime = $workDate->format('Y-m-d') . ' ' . timeFromMinutes((8 * 60));

    $updatedAt = null;
    $hrNote = null;
    if ($status === 'approved') {
        $updatedAt = $workDate->format('Y-m-d') . ' 18:30:00';
        $hrNote = 'Approved';
    } elseif ($status === 'rejected') {
        $updatedAt = $workDate->format('Y-m-d') . ' 18:30:00';
        $hrNote = 'Rejected: insufficient evidence';
    }

    $correctionValues[] = sprintf(
        "(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        $employee['id_expr'],
        sqlString($workDate->format('Y-m-d')),
        sqlNullable($oldTime),
        sqlString($newTime),
        sqlString(randFrom($correctionReasons)),
        sqlString($status),
        sqlNullable($hrNote),
        sqlString($workDate->format('Y-m-d') . ' 17:30:00'),
        sqlNullable($updatedAt),
        sqlNullable($workDate->format('Y-m-d') . ' 08:00:00'),
        sqlNullable($workDate->format('Y-m-d') . ' 17:00:00')
    );
}

$lines[] = 'INSERT INTO attendance_corrections (maND, attendance_date, old_time, new_time, reason, status, hr_note, created_at, updated_at, proposed_checkin, proposed_checkout) VALUES';
$lines[] = implode(",\n", $correctionValues) . ';';

// Employee timesheet approvals (history for all employees)
$timesheetValues = [];
$months = ['2026-01', '2026-02', '2026-03', '2026-04', '2026-05'];
foreach ($months as $monthKey) {
    $monthEnd = date('Y-m-t', strtotime($monthKey . '-01'));
    foreach ($employees as $employee) {
        $status = (mt_rand(1, 100) <= 70) ? 'approved' : 'submitted';
        $submittedAt = $monthEnd . ' 09:00:00';
        $approvedAt = $status === 'approved' ? $monthEnd . ' 15:30:00' : null;
        $employeeNote = $status === 'approved' ? 'OK' : null;

        $timesheetValues[] = sprintf(
            "(%s, %s, 2, %s, %s, %s, %s)",
            $employee['id_expr'],
            sqlString($monthKey),
            sqlString($status),
            sqlString($submittedAt),
            sqlNullable($approvedAt),
            sqlNullable($employeeNote)
        );
    }
}

$lines[] = 'INSERT INTO employee_timesheet_approval (maND, month_key, hr_sender_id, status, submitted_at, approved_at, employee_note) VALUES';
$lines[] = implode(",\n", $timesheetValues) . ';';

$lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
$lines[] = 'COMMIT;';

$outputPath = __DIR__ . DIRECTORY_SEPARATOR . 'demo_seed_2026.sql';
file_put_contents($outputPath, implode("\n", $lines) . "\n");

echo "Generated: " . $outputPath . PHP_EOL;
