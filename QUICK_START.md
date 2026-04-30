# 🚀 QUICK START - Hệ thống Chấm Công 2026

## ⚡ 3 BƯỚC ĐỂ KHỞI ĐỘNG

### **Bước 1: Chạy SQL (1 phút)**

```bash
# Mở MySQL, copy & paste file này:
database/schema_2026_update.sql

# Hoặc CLI:
mysql -u root -p dl_final < database/schema_2026_update.sql
```

✅ **Kết quả:** 5 bảng mới + 3 cột được thêm vào `nguoidung`

---

### **Bước 2: Thêm thông tin phép (2 phút)**

```sql
-- Thêm phép cho nhân viên (chỉnh sửa maND)
INSERT INTO employee_leaves (maND, job_type, seniority_years, start_date)
SELECT
    maND,
    'basic',           -- Loại công việc (basic/hazardous/special)
    0,                 -- Năm thâm niên (sửa nếu cần)
    DATE_SUB(CURDATE(), INTERVAL 1 YEAR)  -- Ngày bắt đầu
FROM nguoidung
WHERE trangThai = 1
ON DUPLICATE KEY UPDATE
    job_type = VALUES(job_type),
    seniority_years = VALUES(seniority_years),
    start_date = VALUES(start_date);
```

✅ **Kết quả:** Tất cả nhân viên có dữ liệu phép

---

### **Bước 3: Test API (1 phút)**

```bash
# Copy URL vào browser:
http://localhost/xampp/quanLyChamCong/index.php?page=hr-api-payroll-detail&month=2026-03
```

✅ **Kết quả:** JSON dữ liệu chi tiết tháng 3/2026

---

## 📊 BẢNG CÔNG CHI TIẾT - THÁNG 3/2026

```
Đầu vào:
- 31 ngày tháng 3
- Không có lễ lớn
- 9 ngày cuối tuần (T7 + CN)
- 22 ngày làm việc tiêu chuẩn

Đầu ra:
┌─────────┬────────┬──────┬─────┬──────┐
│ Nhân viên│ Tổng  │ Phép │ OT  │ Phép │
│         │ công  │ dùng │ (h) │ còn  │
├─────────┼────────┼──────┼─────┼──────┤
│ A. Thắng│ 20.5  │ 1    │ 2.5 │ 11.0 │
│ B. Hà   │ 21.0  │ 0    │ 0.0 │ 12.0 │
│ C. Minh │ 19.0  │ 2    │ 1.5 │ 10.0 │
└─────────┴────────┴──────┴─────┴──────┘

TỔNG:
- Tổng công: 60.5 ngày
- Tổng phép: 3 ngày
- Tổng OT: 4 giờ
```

---

## 🔧 CÔNG THỨC TÍNH NHANH

### **Ngày phép:**
```
Phép = 12 + ⌊thâm niên / 5⌋
       (+ tính pro-rata nếu < 12 tháng)

Ví dụ:
- Nhân viên mới (0 năm): 12 ngày
- 5 năm thâm niên: 12 + 1 = 13 ngày
- 10 năm thâm niên: 12 + 2 = 14 ngày
```

### **Ngày công:**
```
1.0 = Ngày làm việc đầy đủ (8h)
0.5 = Nửa ngày (4h)
0.0 = Phép/Lễ/CN/Vắng

Tổng công = Σ(1.0 hoặc 0.5) cho tất cả ngày làm
```

### **Tháng 3/2026:**
```
Công tiêu chuẩn = 22 ngày
(31 ngày - 9 cuối tuần)
```

---

## 🎯 HELPER CLASSES - USE

```php
// 1️⃣ HOLIDAY CALCULATOR
require_once 'app/helpers/HolidayCalculator.php';

HolidayCalculator::isHoliday('2026-02-21');      // true (Tết)
HolidayCalculator::isWeekend('2026-03-01');      // true (CN)
HolidayCalculator::getWorkingDaysCountInMonth('2026-03');  // 22

// 2️⃣ LEAVE CALCULATOR
require_once 'app/helpers/LeaveCalculator.php';

LeaveCalculator::getBaseLeaveByJobType('basic');  // 12
LeaveCalculator::calculateAnnualLeave('basic', 5, 12);  // 13
LeaveCalculator::calculateRemainingLeave('basic', 5, 3, 12);  // 10

// 3️⃣ ATTENDANCE CALCULATOR
require_once 'app/helpers/AttendanceCalculator.php';

$result = AttendanceCalculator::calculateMonthlyAttendance('2026-03', $data);
echo $result['totals']['total_work_days'];  // 20.5

// 4️⃣ MODEL
$model = new ChamCongModel();
$data = $model->getMonthlyAttendanceDetailNew('2026-03');

foreach ($data as $emp) {
    echo $emp['hoTen'] . ": " . $emp['work_days'] . " công\n";
}
```

---

## 📱 API ENDPOINTS

### **1. Dữ liệu chi tiết tháng**
```
GET /index.php?page=hr-api-payroll-detail&month=2026-03

Response:
{
  "success": true,
  "month_key": "2026-03",
  "data": [
    {
      "maND": 1,
      "hoTen": "Nguyễn Văn A",
      "work_days": 20.5,
      "overtime_hours": 2.5,
      "leave_info": {
        "total_leave": 12,
        "remaining_leaves": 11
      }
    }
  ]
}
```

### **2. Thông tin lễ tháng**
```
GET /index.php?page=hr-api-holidays&month=2026-03

Response:
{
  "success": true,
  "data": {
    "month": "2026-03",
    "holidays": [],
    "count": 0,
    "working_days": 22
  }
}
```

---

## 🗓️ LỊCH LỄ TẾT 2026

| Sự kiện | Ngày | Số ngày |
|---------|------|--------|
| Tết Dương lịch | 01/01 | 1 |
| Tết Nguyên đán | 21-25/02 | 5 |
| Giỗ Tổ Hùng Vương | 18/04 | 1 |
| Ngày Chiến thắng | 30/04 | 1 |
| Quốc tế Lao động | 01/05 | 1 |
| Quốc khánh | 02-03/09 | 2 |
| **TỔNG** | | **11 ngày** |

---

## ✅ CHECKLIST CÀI ĐẶT

- [ ] Chạy SQL schema
- [ ] Kiểm tra 5 bảng mới tạo
- [ ] Populate employee_leaves
- [ ] Test API payroll-detail
- [ ] Test API holidays
- [ ] Kiểm tra dữ liệu tháng 3/2026
- [ ] Training team sử dụng

---

## 🐛 QUICK FIX

**Q: API không trả về dữ liệu?**
```php
// Kiểm tra:
1. Schema đã chạy?
2. Employee_leaves đã có dữ liệu?
3. Attendance_logs đã có chấm công?
4. URL đúng không? (...page=hr-api-payroll-detail)
```

**Q: Phép không tính đúng?**
```sql
-- Kiểm tra:
SELECT * FROM employee_leaves WHERE maND = 1;
SELECT * FROM leave_requests WHERE maND = 1;
-- Phải có dữ liệu!
```

**Q: Tháng 3 sao 22 ngày?**
```
Tháng 3/2026: 31 ngày
- Thứ 7: 7, 14, 21, 28 (4 ngày)
- CN: 1, 8, 15, 22, 29 (5 ngày)
- Lễ: 0 ngày
= 31 - 9 = 22 ngày làm việc
```

---

## 📖 TÀI LIỆU THAM KHẢO

📄 **Đọc thêm:**
- `INSTALLATION_2026.md` - Hướng dẫn chi tiết
- `FIX_REPORT_2026.md` - Báo cáo công việc
- `app/helpers/HolidayCalculator.php` - Mã nguồn lịch lễ
- `app/helpers/LeaveCalculator.php` - Mã nguồn tính phép
- `app/helpers/AttendanceCalculator.php` - Mã nguồn tính công
- `database/schema_2026_update.sql` - SQL schema

---

## 🎉 HOÀN TẤT!

✅ Hệ thống đã sẵn sàng!

Tiếp theo:
1. Hiển thị dữ liệu trên UI
2. Training người dùng
3. Deploy production

---

**Phiên bản:** 2.0.0 (2026)  
**Cập nhật:** 2026-04-30  
**Status:** ✅ READY TO USE
