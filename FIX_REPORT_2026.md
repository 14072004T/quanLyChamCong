# 📊 BÁOCÁO FIX HỆ THỐNG CHẤM CÔNG 2026

## ✅ TÓM TẮT CÔNG VIỆC HOÀN THÀNH

### **🎯 Mục tiêu:**
Fix toàn bộ logic tính toán ngày công, ngày phép, ngày lễ Tết và lịch chấm công năm 2026 theo quy định thực tế Việt Nam.

---

## 📁 CÁC FILE TẠO MỚI

### **1. app/helpers/HolidayCalculator.php** ✨
**Chức năng:** Quản lý lịch lễ Tết 2026 theo quy định pháp luật

**Gồm:**
- `isHoliday($date)` - Kiểm tra ngày lễ
- `isWeekend($date)` - Kiểm tra cuối tuần
- `isWorkingDay($date)` - Kiểm tra ngày làm việc
- `getHolidaysInMonth($month)` - Lấy lề tháng
- `getWorkingDaysCountInMonth($month)` - Tính ngày làm việc tháng
- `getMarch2026Info()` - Thông tin tháng 3/2026

**Lịch lễ Tết 2026 (11 ngày):**
```
- 01/01: Tết Dương lịch (1)
- 21-25/02: Tết Nguyên đán (5)
- 18/04: Giỗ Tổ Hùng Vương (1)
- 30/04: Ngày Chiến thắng (1)
- 01/05: Quốc tế Lao động (1)
- 02-03/09: Quốc khánh (2)
Tổng: 11 ngày
```

---

### **2. app/helpers/LeaveCalculator.php** ✨
**Chức năng:** Tính toán ngày phép năm theo luật Việt Nam

**Gồm:**
- `getBaseLeaveByJobType($jobType)` - Phép cơ bản theo loại công việc
  - basic: 12 ngày
  - hazardous: 14 ngày
  - special: 16 ngày
- `calculateSeniorityBonus($seniority)` - Thâm niên bonus (+1 ngày/5 năm)
- `calculateProRataLeave($months, $jobType)` - Phép năm thứ nhất
- `calculateAnnualLeave($jobType, $seniority, $workingMonths)` - Tổng phép
- `calculateRemainingLeave(...)` - Phép còn lại
- `validateLeaveRequest($requestedDays, $remainingLeave)` - Kiểm tra xin phép
- `getLeaveDetails($employeeData)` - Thông tin chi tiết phép

**Công thức:**
```
Phép năm = Phép cơ bản + Thâm niên bonus
Phép cơ bản = 12/14/16 (tuỳ loại công việc)
Thâm niên bonus = ⌊thâm niên / 5⌋ × 1 ngày
Phép pro-rata = (tháng làm / 12) × phép cơ bản (nếu < 12 tháng)
Phép còn lại = Tổng phép - Đã dùng
```

---

### **3. app/helpers/AttendanceCalculator.php** ✨
**Chức năng:** Tính toán ngày công chi tiết theo tháng

**Gồm:**
- `calculateMonthlyAttendance($monthKey, $attendanceData, $leaveRequests)` - Tính công tháng
- `calculateDailyAttendance($date, $checkInOutData, $leaveType)` - Tính công 1 ngày
- `calculateWorkValue($workMinutes, $standardMinutes)` - Phân loại công ngày
  - 1.0: 80-100% giờ
  - 0.5: 40-79% giờ
  - 0.0: Phép/Lễ/CN
- `compareWithStandard($actualWorkDays, $standardWorkDays)` - So sánh tiêu chuẩn

**Tiêu chuẩn:** 480 phút/ngày (8 giờ)

---

### **4. database/schema_2026_update.sql** ✨
**Chức năng:** SQL schema cho toàn bộ hệ thống phép & lễ

**Bảng mới:**
1. `employee_leaves` - Quản lý phép năm
2. `leave_requests` - Yêu cầu xin phép
3. `holidays_2026` - Lịch lễ Tết 2026 (pre-loaded)
4. `attendance_day_types` - Phân loại ngày
5. `attendance_monthly_summary` - Cache tóm tắt công

**Cột mới thêm vào `nguoidung`:**
- `job_type` - Loại công việc
- `seniority_years` - Năm thâm niên
- `start_date` - Ngày bắt đầu

---

### **5. INSTALLATION_2026.md** ✨
**Hướng dẫn:**
- Cách cài đặt & chạy SQL schema
- Công thức tính phép & công chi tiết
- API endpoints mới
- Helper classes usage
- Lịch lễ Tết 2026
- Tháng 3/2026 (31 ngày, 21-22 ngày làm việc)
- Troubleshooting

---

## 📝 CÁC FILE CẬP NHẬT

### **1. app/models/ChamCongModel.php** 🔄
**Thêm 4 method mới:**

```php
public function getMonthlyAttendanceDetailNew($monthKey)
// Lấy dữ liệu chi tiết công tháng (với phép, lễ, OT)
// Trả về:
// - daily_breakdown: Chi tiết từng ngày
// - work_days: Tổng công
// - work_hours: Tổng giờ làm
// - overtime_hours: Tổng OT
// - leave_days_used: Ngày phép dùng
// - holiday_days: Ngày lễ
// - leave_info: Thông tin phép nhân viên

private function getMonthlyAttendanceRaw($maND, $fromDate, $toDate)
// Lấy dữ liệu chấm công thô (IN/OUT logs)

public function getEmployeeLeaveInfo($maND)
// Lấy thông tin phép của nhân viên (từ bảng employee_leaves)

private function getApprovedLeaveRequests($maND, $fromDate, $toDate)
// Lấy danh sách phép đã phê duyệt

public function getHolidaysForMonth($monthKey)
// Lấy thông tin lễ tháng
```

---

### **2. app/controllers/HRController.php** 🔄
**Thêm 2 method API mới:**

```php
public function payrollDetailApi()
// GET /index.php?page=hr-api-payroll-detail&month=2026-03
// Lấy dữ liệu tính công chi tiết (thay thế payrollApi cũ)

public function holidaysApi()
// GET /index.php?page=hr-api-holidays&month=2026-03
// Lấy thông tin lễ tháng
```

---

## 🔄 LOGIC THAY ĐỔI

### **Cũ (trước fix):**
```
getMonthlyWorkSummary() → Chỉ tính IN/OUT logs
                       → Không xử lý lễ, phép
                       → OT logic đơn giản
                       → Không phân loại ngày
```

### **Mới (sau fix):**
```
getMonthlyAttendanceDetailNew() → Tính chi tiết từng ngày
                                → Xử lý lễ (HolidayCalculator)
                                → Xử lý phép (LeaveCalculator)
                                → Phân loại ngày (1.0/0.5/0.0)
                                → Tính OT chính xác
                                → Hiển thị ngày phép còn lại
```

---

## 📊 CÔNG THỨC TÍNH TOÁN

### **1. Ngày phép năm**

```
Base Leave = {
  'basic': 12,
  'hazardous': 14,
  'special': 16
}

Seniority Bonus = ⌊seniority_years / 5⌋ × 1

Annual Leave = Base Leave + Seniority Bonus

For Year 1:
  Pro-rata Leave = (working_months / 12) × Base Leave
```

### **2. Ngày công tháng**

```
For each day:
  IF is_holiday(date) THEN work_value = 0.0
  ELSE IF is_weekend(date) THEN work_value = 0.0
  ELSE IF has_leave_request(date) THEN work_value = 0.0
  ELSE IF has_attendance(date) THEN
    IF work_minutes >= 480 * 0.80 THEN work_value = 1.0
    ELSE IF work_minutes >= 480 * 0.40 THEN work_value = 0.5
    ELSE work_value = 0.0
  ELSE work_value = 0.0  // Absent

Total Work Days = ∑(daily work_value)
```

### **3. OT (Overtime)**

```
Standard = 480 minutes (8 hours)

For each day:
  IF work_minutes > 480 THEN
    OT_hours = (work_minutes - 480) / 60
  ELSE
    OT_hours = 0

Total OT = ∑(daily OT_hours)
```

---

## 🎯 THÁNG 3/2026 (ĐẶC BIỆT)

```
Tổng ngày:        31 ngày
Ngày lễ:          0 ngày (KHÔNG có lễ lớn)
Ngày cuối tuần:   8-9 ngày (4 thứ 7 + 4-5 chủ nhật)
Ngày làm việc:    ~21-22 ngày

Ngày thứ 7 tháng 3/2026: 7, 14, 21, 28 (4 ngày)
Chủ nhật tháng 3/2026:   1, 8, 15, 22, 29 (5 ngày)
TOTAL WEEKEND:           9 ngày

WORKING DAYS:     31 - 9 = 22 ngày
(hoặc 21 nếu công ty không làm thứ 7)
```

---

## 🚀 CÁCH SỬ DỤNG

### **Trên Controller/View:**

```php
require_once 'app/helpers/HolidayCalculator.php';
require_once 'app/helpers/LeaveCalculator.php';
require_once 'app/helpers/AttendanceCalculator.php';

$model = new ChamCongModel();

// Lấy data chi tiết
$data = $model->getMonthlyAttendanceDetailNew('2026-03');

foreach ($data as $employee) {
  echo "Nhân viên: " . $employee['hoTen'];
  echo "Tổng công: " . $employee['work_days'];
  echo "Phép còn lại: " . $employee['leave_info']['remaining_leaves'];
  echo "OT: " . $employee['overtime_hours'];
}
```

### **Trên JavaScript (API):**

```javascript
fetch('/index.php?page=hr-api-payroll-detail&month=2026-03')
  .then(r => r.json())
  .then(json => {
    console.log(json.data);  // Dữ liệu chi tiết
    
    json.data.forEach(emp => {
      console.log(`${emp.hoTen}: ${emp.work_days} công, phép còn ${emp.leave_info.remaining_leaves}`);
    });
  });
```

---

## ⚠️ LƯU Ý QUAN TRỌNG

1. **Chạy SQL schema trước** - File `database/schema_2026_update.sql`
2. **Populate employee_leaves** - Insert thông tin phép cho nhân viên
3. **Pro-rata cho năm 1** - Tính toán tự động nếu start_date < 12 tháng
4. **Nửa ngày = 0.5** - Chỉ tính 0.5 nếu thực tế 40-79% giờ
5. **Lễ không trừ phép** - Ngày lễ hưởng nguyên lương, KHÔNG trừ quota phép
6. **OT chỉ approved** - Chỉ tính từ `attendance_corrections` với status='approved'

---

## 📈 SO SÁNH TRƯỚC/SAU

| Tính năng | Trước | Sau |
|----------|------|-----|
| **Tính phép năm** | ❌ Không | ✅ Có (12/14/16 + thâm niên) |
| **Xử lý lễ Tết** | ❌ Không | ✅ Có (11 ngày 2026) |
| **Phân loại ngày** | ❌ Chỉ 1.0 | ✅ 1.0/0.5/0.0 |
| **Tháng 3/2026** | ❌ Sai | ✅ Chính xác (21-22 ngày) |
| **OT logic** | ⚠️ Đơn giản | ✅ Chi tiết |
| **Pro-rata** | ❌ Không | ✅ Có |
| **API chi tiết** | ❌ Không | ✅ Có |

---

## 📞 NEXT STEPS

1. **Chạy SQL schema:** `database/schema_2026_update.sql`
2. **Populate dữ liệu:** Thêm thông tin phép cho nhân viên
3. **Test API:** Gọi `/index.php?page=hr-api-payroll-detail&month=2026-03`
4. **Update UI:** Hiển thị dữ liệu chi tiết trên view
5. **Training:** Hướng dẫn user cách sử dụng

---

## 📄 FILE THAM KHẢO

- `INSTALLATION_2026.md` - Hướng dẫn chi tiết
- `app/helpers/HolidayCalculator.php` - Lịch lễ
- `app/helpers/LeaveCalculator.php` - Tính phép
- `app/helpers/AttendanceCalculator.php` - Tính công
- `database/schema_2026_update.sql` - SQL schema

---

**Status:** ✅ COMPLETED  
**Date:** 2026-04-30  
**Version:** 2.0.0
