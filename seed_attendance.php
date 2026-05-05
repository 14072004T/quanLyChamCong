<?php
require_once 'app/models/ketNoi.php';

try {
    $db = new KetNoi();
    $conn = $db->connect();

    // Disable foreign key checks to make seeding easier
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Clear existing data for 2026 to avoid duplicates
    $conn->query("DELETE FROM attendance_logs WHERE created_at >= '2026-01-01'");
    $conn->query("DELETE FROM attendance_daily_summary WHERE work_date >= '2026-01-01'");

    $employees = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
    $shifts = [
        1 => ['start' => '08:00:00', 'end' => '17:00:00'],
        2 => ['start' => '14:00:00', 'end' => '22:00:00']
    ];

    $startDate = new DateTime('2026-01-01');
    $endDate = new DateTime('2026-05-05');

    $interval = new DateInterval('P1D');
    $period = new DatePeriod($startDate, $interval, $endDate->modify('+1 day'));

    $conn->begin_transaction();

    foreach ($period as $date) {
        $dateStr = $date->format('Y-m-d');
        $dayOfWeek = $date->format('N'); // 1 (Mon) to 7 (Sun)

        if ($dayOfWeek >= 7) continue; // Only skip Sunday, assume Saturday might work or just keep it simple

        foreach ($employees as $maND) {
            // Random chance for leave/absent (5%)
            $absentChance = rand(1, 100);
            if ($absentChance <= 5) {
                $status = ($absentChance <= 3) ? 'leave' : 'absent';
                $stmtSummary = $conn->prepare("INSERT INTO attendance_daily_summary (maND, work_date, first_in, last_out, work_minutes, overtime_minutes, late_minutes, status) VALUES (?, ?, NULL, NULL, 0, 0, 0, ?)");
                $stmtSummary->bind_param("iss", $maND, $dateStr, $status);
                $stmtSummary->execute();
                continue;
            }

            // Assign shift
            $shiftId = ($maND == 11) ? 2 : 1;
            $shift = $shifts[$shiftId];

            // Randomize IN time
            // 85% on time (-10 to 0), 15% late (1 to 30)
            $isLate = rand(1, 100) <= 15;
            $inOffset = $isLate ? rand(1, 30) : rand(-15, 0);
            
            $inTime = clone $date;
            $shiftStartParts = explode(':', $shift['start']);
            $inTime->setTime($shiftStartParts[0], $shiftStartParts[1]);
            $inTime->modify("$inOffset minutes");

            // Randomize OUT time
            // Stay late (0 to 60 minutes)
            $outOffset = rand(0, 45);
            $outTime = clone $date;
            $shiftEndParts = explode(':', $shift['end']);
            $outTime->setTime($shiftEndParts[0], $shiftEndParts[1]);
            $outTime->modify("$outOffset minutes");

            // Insert IN Log
            $stmt = $conn->prepare("INSERT INTO attendance_logs (maND, action, method, wifi_name, created_at) VALUES (?, 'IN', 'LAN', 'Wifi Công ty', ?)");
            $inTimeStr = $inTime->format('Y-m-d H:i:s');
            $stmt->bind_param("is", $maND, $inTimeStr);
            $stmt->execute();

            // Insert OUT Log
            $stmt = $conn->prepare("INSERT INTO attendance_logs (maND, action, method, wifi_name, created_at) VALUES (?, 'OUT', 'LAN', 'Wifi Công ty', ?)");
            $outTimeStr = $outTime->format('Y-m-d H:i:s');
            $stmt->bind_param("is", $maND, $outTimeStr);
            $stmt->execute();

            // Calculate Summary
            $totalMinutes = ($outTime->getTimestamp() - $inTime->getTimestamp()) / 60;
            $workMinutes = max(0, $totalMinutes - 60); // 1h lunch
            
            $lateMinutes = 0;
            $shiftStartTimestamp = (clone $date)->setTime($shiftStartParts[0], $shiftStartParts[1])->getTimestamp();
            if ($inTime->getTimestamp() > $shiftStartTimestamp) {
                $lateMinutes = ($inTime->getTimestamp() - $shiftStartTimestamp) / 60;
            }

            $overtimeMinutes = 0;
            $shiftEndTimestamp = (clone $date)->setTime($shiftEndParts[0], $shiftEndParts[1])->getTimestamp();
            if ($outTime->getTimestamp() > $shiftEndTimestamp) {
                $overtimeMinutes = ($outTime->getTimestamp() - $shiftEndTimestamp) / 60;
            }

            $status = ($lateMinutes > 0) ? 'late' : 'normal';

            $stmtSummary = $conn->prepare("INSERT INTO attendance_daily_summary (maND, work_date, first_in, last_out, work_minutes, overtime_minutes, late_minutes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtSummary->bind_param("isssiiis", $maND, $dateStr, $inTimeStr, $outTimeStr, $workMinutes, $overtimeMinutes, $lateMinutes, $status);
            $stmtSummary->execute();
        }
    }

    $conn->commit();
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    echo "Successfully seeded randomized attendance data.\n";

} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "Error: " . $e->getMessage() . "\n";
}
?>
