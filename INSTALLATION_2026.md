# 📋 HƯỚNG DẪN: Hệ thống Quản lý Chấm Công 2026 (Updated)

## 📌 TỔNG QUAN CẬP NHẬT

Hệ thống chấm công đã được **cập nhật hoàn toàn** để tuân thủ đầy đủ quy định pháp luật Việt Nam năm 2026, bao gồm:

✅ **Ngày phép năm** - Tính toán theo luật (12/14/16 ngày + thâm niên)  
✅ **Lịch lễ Tết 2026** - 11 ngày hưởng nguyên lương  
✅ **Ngày công chi tiết** - Phân loại 1.0, 0.5, 0.0 ngày  
✅ **OT Overtime** - Chỉ tính từ yêu cầu phê duyệt chính thức  
✅ **Tháng 3/2026** - 31 ngày, ~21-22 ngày làm việc  

---

## 🔧 HƯỚNG DẪN CÀI ĐẶT

### **Bước 1: Chạy SQL schema**

```bash
# Mở MySQL Workbench hoặc phpmyadmin
# Import file: database/schema_2026_update.sql

# Hoặc chạy qua CLI:
mysql -u root -p dl_final < database/schema_2026_update.sql
```

Các bảng sẽ được tạo:
- `employee_leaves` - Quản lý phép năm
- `leave_requests` - Yêu cầu xin phép
- `holidays_2026` - Lịch lễ Tết 2026
- `attendance_day_types` - Phân loại ngày
- `attendance_monthly_summary` - Cache tóm tắt công

### **Bước 2: Cập nhật thông tin nhân viên**

Thêm 3 cột vào table `nguoidung` (nếu chưa có):
- `job_type` - Loại công việc (basic/hazardous/special)
- `seniority_years` - Năm thâm niên
- `start_date` - Ngày bắt đầu

SQL tự động thêm trong schema_2026_update.sql.

### **Bước 3: Cập nhật thông tin phép cho nhân viên**

Insert dữ liệu vào bảng `employee_leaves`:

```sql
INSERT INTO employee_leaves (maND, job_type, seniority_years, start_date, annual_leave_total)
SELECT
    maND,
    COALESCE(job_type, 'basic') as job_type,
    COALESCE(seniority_years, 0) as seniority_years,
    COALESCE(start_date, CURDATE()) as start_date,
    12 -- Default 12 days
FROM nguoidung
WHERE trangThai = 1
ON DUPLICATE KEY UPDATE
    job_type = VALUES(job_type),
    seniority_years = VALUES(seniority_years),
    start_date = VALUES(start_date);
```

---

## 📊 CÔNG THỨC TÍNH NGÀY PHÉP

### **Ngày phép cơ bản (Base Leave)**

```
Công việc bình thường:           12 ngày/năm
Nặng nhọc/độc hại/nguy hiểm:    14 ngày/năm
Đặc biệt nặng nhọc/độc hại:     16 ngày/năm
```

### **Thâm niên bonus**

```
Mỗi 5 năm thâm niên: +1 ngày phép

Ví dụ:
- 5 năm:  12 + 1 = 13 ngày
- 10 năm: 12 + 2 = 14 ngày
- 15 năm: 12 + 3 = 15 ngày
```

### **Năm thứ nhất (Pro-rata)**

```
Công thức: (số tháng làm / 12) × ngày phép cơ bản

Ví dụ:
- Làm 6 tháng: (6/12) × 12 = 6 ngày
- Làm 9 tháng: (9/12) × 12 = 9 ngày
```

### **Phép còn lại**

```
Phép còn lại = Phép cơ bản + Thâm niên bonus - Phép đã dùng

Ví dụ:
- Phép cơ bản: 12 ngày
- Thâm niên (5 năm): +1 ngày
- Đã dùng: 3 ngày
- Còn lại: 12 + 1 - 3 = 10 ngày
```

---

## 🗓️ LỊCH LỄ TẾT 2026

**Tổng: 11 ngày lễ hưởng nguyên lương** (không trừ vào phép)

| Ngày | Tên | Số ngày |
|------|-----|--------|
| 01/01 | Tết Dương lịch | 1 |
| 21-25/02 | Tết Nguyên đán | 5 |
| 18/04 | Giỗ Tổ Hùng Vương | 1 |
| 30/04 | Ngày Chiến thắng | 1 |
| 01/05 | Quốc tế Lao động | 1 |
| 02-03/09 | Quốc khánh | 2 |

**Tháng 3/2026 đặc biệt:**
- Không có ngày lễ lớn
- 31 ngày tổng cộng
- 8-9 ngày cuối tuần (T7 + CN)
- ~21-22 ngày làm việc

---

## 📈 TÍNH NGÀY CÔNG CHI TIẾT

### **Phân loại ngày**

| Loại | Giá trị | Ý nghĩa |
|------|--------|--------|
| 1.0 | 80-100% giờ | Ngày làm việc đầy đủ |
| 0.5 | 40-79% giờ | Nửa ngày (sáng/chiều) |
| 0.0 | Phép/Lễ/CN | Không tính công |

### **Công thức tính**

```
TỔNG CÔNG THÁNG = ∑(ngày làm việc từng ngày)

Ngày i = 
  - 1.0 nếu: ngày làm việc + có chấm công + ≥80% giờ
  - 0.5 nếu: ngày làm việc + có chấm công + 40-79% giờ
  - 0.0 nếu: ngày lễ OR phép OR cuối tuần OR vắng mặt
```

### **Ví dụ tháng 3/2026**

```
01/03 (Thứ 7) = 0.0  (Cuối tuần)
02/03 (Chủ Nhật) = 0.0  (Cuối tuần)
03/03 (Thứ 2) = 1.0  (Chấm công đầy đủ 8h)
04/03 (Thứ 3) = 0.5  (Chấm công sáng, nghỉ chiều)
05/03 (Thứ 4) = 0.0  (Xin phép cả ngày)
...
31/03 (Thứ 4) = 1.0  (Chấm công)

TỔNG CÔNG = ~20.5 ngày
(trong ~21-22 ngày làm việc tiêu chuẩn)
```

---

## 🎯 OT (OVERTIME) TÍNH TOÁN

**Quy tắc:**
- Chỉ tính OT từ yêu cầu phê duyệt chính thức
- OT = (work_minutes - 480 phút) / 60

**Ví dụ:**
```
Giờ làm: 08:00 - 18:30 = 600 phút
OT = (600 - 480) / 60 = 2 giờ
```

---

## 📱 API ENDPOINTS

### **1. Lấy dữ liệu tính công chi tiết**

```http
GET /index.php?page=hr-api-payroll-detail&month=2026-03
```

**Response:**
```json
{
  "success": true,
  "month_key": "2026-03",
  "data": [
    {
      "maND": 1,
      "hoTen": "Nguyễn Văn A",
      "phongBan": "IT",
      "daily_breakdown": {
        "2026-03-01": {"day_type": "weekend", "work_value": 0.0},
        "2026-03-03": {"day_type": "working", "work_value": 1.0, "work_hours": 8}
      },
      "work_days": 20.5,
      "work_hours": 164,
      "overtime_hours": 4.5,
      "leave_days_used": 1,
      "holiday_days": 0,
      "absent_days": 0,
      "standard_work_days": 21,
      "leave_info": {
        "total_leave": 13,
        "used_leaves": 4,
        "remaining_leaves": 9
      }
    }
  ]
}
```

### **2. Lấy thông tin lễ tháng**

```http
GET /index.php?page=hr-api-holidays&month=2026-03
```

**Response:**
```json
{
  "success": true,
  "data": {
    "month": "2026-03",
    "holidays": [],
    "count": 0,
    "working_days": 21
  }
}
```

---

## 🔍 HELPER CLASSES

### **HolidayCalculator**

```php
// Kiểm tra ngày lễ
HolidayCalculator::isHoliday('2026-02-21');  // true

// Kiểm tra cuối tuần
HolidayCalculator::isWeekend('2026-03-01');  // true

// Tính ngày làm việc tháng
HolidayCalculator::getWorkingDaysCountInMonth('2026-03');  // 21

// Lấy lịch lễ tháng
HolidayCalculator::getHolidaysInMonth('2026-02');  // ['2026-02-21', ...]
```

### **LeaveCalculator**

```php
// Lấy phép cơ bản
LeaveCalculator::getBaseLeaveByJobType('basic');  // 12

// Tính thâm niên bonus
LeaveCalculator::calculateSeniorityBonus(5);  // 1

// Tính tổng phép
LeaveCalculator::calculateAnnualLeave('basic', 5, 12);  // 13

// Tính phép còn lại
LeaveCalculator::calculateRemainingLeave('basic', 5, 3, 12);  // 10

// Kiểm tra xin phép
LeaveCalculator::validateLeaveRequest(2, 10);  // ['approved' => true]
```

### **AttendanceCalculator**

```php
// Tính công chi tiết tháng
$result = AttendanceCalculator::calculateMonthlyAttendance('2026-03', $data);

// Tính công 1 ngày
$day = AttendanceCalculator::calculateDailyAttendance('2026-03-03', $checkInOut);

// So sánh với tiêu chuẩn
$compare = AttendanceCalculator::compareWithStandard(20.5, 21);
```

---

## 📝 HƯỚNG DẪN QUẢN LÝ PHÉP

### **Thêm phép cho nhân viên**

```sql
INSERT INTO leave_requests (maND, leave_date, leave_type, is_half_day, reason, status)
VALUES (1, '2026-03-05', 'annual', 0, 'Xin phép cá nhân', 'approved');
```

### **Cập nhật thâm niên**

```sql
UPDATE employee_leaves
SET seniority_years = 5
WHERE maND = 1;
```

### **Cập nhật loại công việc**

```sql
UPDATE employee_leaves
SET job_type = 'hazardous'  -- 14 ngày phép
WHERE maND = 1;
```

---

## ⚠️ LƯU Ý QUAN TRỌNG

1. **Tính toán Pro-rata:** Nếu nhân viên làm việc < 12 tháng, tính theo tỷ lệ
2. **Nửa ngày:** Chỉ tính 0.5 nếu thực tế làm 40-79% giờ tiêu chuẩn (480 phút)
3. **OT chỉ từ phê duyệt:** Không tính OT từ chấm công thường
4. **Lễ không trừ phép:** Ngày lễ hưởng nguyên lương, không trừ vào quota phép
5. **Vắng mặt không phép:** Nếu không có chấm công + không xin phép = vắng mặt (0 công)

---

## 🐛 TROUBLESHOOTING

### **Q: Sao ngày công không hiển thị chi tiết?**
A: Kiểm tra:
1. Đã chạy SQL schema chưa?
2. Có dữ liệu chấm công trong `attendance_logs` không?
3. Có dữ liệu phép trong `leave_requests` không?

### **Q: Sao phép năm không tính đúng?**
A: Kiểm tra:
1. Bảng `employee_leaves` đã được populate?
2. `job_type` và `seniority_years` có đúng không?
3. `annual_leave_used` đã cập nhật chưa?

### **Q: Sao OT không tính?**
A: OT chỉ tính từ `attendance_corrections` với `status = 'approved'` và reason chứa "OT".

---

## 📞 LIÊN HỆ HỖ TRỢ

Nếu gặp vấn đề, kiểm tra:
1. Log files: `app/logs/`
2. Database structure: `SHOW TABLES; DESCRIBE employee_leaves;`
3. Sample data: Kiểm tra xem các bảng đã có dữ liệu chưa

---

**Last Updated:** 2026-04-30  
**Version:** 2.0.0 (Updated 2026)
