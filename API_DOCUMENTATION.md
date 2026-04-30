# RESTful API Documentation - Hệ thống Quản lý Chấm Công

## Phê Duyệt Bảng Công (Manager Approval APIs)

### 1. Lấy Danh Sách Phê Duyệt (Pending & History)
**Endpoint:** `GET /index.php?page=manager-api-approvals`

**Parameters:**
- `status` (string): `submitted` | `approved` | `rejected` | `history` | `processed`
- `year` (string): Year filter (e.g., `2026`)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "month_key": "2026-04",
      "hr_sender_id": 5,
      "manager_approver_id": 3,
      "status": "approved",
      "submitted_at": "2026-04-30 10:00:00",
      "approved_at": "2026-04-30 15:30:00",
      "note": "Được duyệt",
      "hr_name": "Lê Văn Hùng",
      "approver_name": "Lê Văn Quan Ly",
      "total_employees": 11,
      "total_work_days": 220,
      "total_ot_hours": 45.5,
      "violation_rate": 2.5
    }
  ]
}
```

**Examples:**
```bash
# Lấy danh sách chờ duyệt
curl "http://localhost/QUANLYCHAMCONG/index.php?page=manager-api-approvals&status=submitted"

# Lấy lịch sử phê duyệt
curl "http://localhost/QUANLYCHAMCONG/index.php?page=manager-api-approvals&status=history"

# Lấy lịch sử năm 2026
curl "http://localhost/QUANLYCHAMCONG/index.php?page=manager-api-approvals&status=history&year=2026"
```

---

### 2. Lấy Lịch Sử Phê Duyệt (Riêng)
**Endpoint:** `GET /index.php?page=manager-api-approval-history`

**Parameters:**
- `year` (string): Year filter (e.g., `2026`) - Optional
- `limit` (integer): Max records (default: 50, max: 500) - Optional

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "month_key": "2026-04",
      "hr_sender_id": 5,
      "manager_approver_id": 3,
      "status": "approved",
      "submitted_at": "2026-04-30 10:00:00",
      "approved_at": "2026-04-30 15:30:00",
      "note": "Được duyệt",
      "hr_name": "Lê Văn Hùng",
      "approver_name": "Lê Văn Quan Ly",
      "total_employees": 11,
      "total_work_days": 220,
      "total_ot_hours": 45.5,
      "violation_rate": 2.5
    }
  ],
  "count": 25
}
```

**Examples:**
```bash
# Lấy 50 bản ghi lịch sử gần nhất
curl "http://localhost/QUANLYCHAMCONG/index.php?page=manager-api-approval-history"

# Lấy 100 bản ghi lịch sử năm 2026
curl "http://localhost/QUANLYCHAMCONG/index.php?page=manager-api-approval-history&year=2026&limit=100"

# Lấy 30 bản ghi lịch sử
curl "http://localhost/QUANLYCHAMCONG/index.php?page=manager-api-approval-history&limit=30"
```

---

### 3. Lấy Chi Tiết Phê Duyệt
**Endpoint:** `GET /index.php?page=manager-api-approval-detail`

**Parameters:**
- `approval_id` (integer): ID của bảng công cần xem chi tiết

**Response:**
```json
{
  "success": true,
  "data": {
    "approval": {
      "id": 1,
      "month_key": "2026-04",
      "hr_sender_id": 5,
      "manager_approver_id": 3,
      "status": "approved",
      "submitted_at": "2026-04-30 10:00:00",
      "approved_at": "2026-04-30 15:30:00",
      "note": "Được duyệt",
      "hr_name": "Lê Văn Hùng",
      "approver_name": "Lê Văn Quan Ly"
    },
    "summary": {
      "employees": 11,
      "total_work_days": 220,
      "total_work_hours": 1760,
      "total_overtime_hours": 45.5
    },
    "rows": [
      {
        "hoTen": "Cấm Vì",
        "phongBan": "Điều hành",
        "work_days": 20,
        "work_hours": 160,
        "overtime_hours": 0
      },
      {
        "hoTen": "Lê Văn Manh",
        "phongBan": "Điều hành",
        "work_days": 20,
        "work_hours": 160,
        "overtime_hours": 5.5
      }
    ]
  }
}
```

**Examples:**
```bash
curl "http://localhost/QUANLYCHAMCONG/index.php?page=manager-api-approval-detail&approval_id=1"
```

---

### 4. Phê Duyệt hoặc Từ Chối Bảng Công
**Endpoint:** `POST /index.php?page=manager-api-approve`

**Request Headers:**
```
Content-Type: application/x-www-form-urlencoded
X-Requested-With: XMLHttpRequest (optional but recommended)
```

**Parameters (POST):**
- `approval_id` (integer, required): ID của bảng công
- `action` (string, required): `approve` | `reject`
- `note` (string, optional): Ghi chú phê duyệt hoặc lý do từ chối

**Response (Success):**
```json
{
  "success": true,
  "message": "Đã cập nhật trạng thái bảng công"
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Dữ liệu phê duyệt không hợp lệ"
}
```

**Examples:**
```bash
# Phê duyệt bảng công
curl -X POST "http://localhost/QUANLYCHAMCONG/index.php?page=manager-api-approve" \
  -d "approval_id=1&action=approve&note=Được duyệt"

# Từ chối bảng công
curl -X POST "http://localhost/QUANLYCHAMCONG/index.php?page=manager-api-approve" \
  -d "approval_id=1&action=reject&note=Thiếu dữ liệu nhân sự"
```

---

## Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request thành công |
| 404 | Not Found - Không tìm thấy dữ liệu |
| 405 | Method Not Allowed - Phương thức HTTP không được phép |
| 422 | Unprocessable Entity - Dữ liệu không hợp lệ |
| 500 | Internal Server Error - Lỗi server |

---

## Ghi Chú

1. **Lưu Lịch Sử**: Khi phê duyệt hoặc từ chối, hệ thống tự động lưu:
   - `status`: trạng thái mới (approved/rejected)
   - `manager_approver_id`: ID của manager
   - `approved_at`: thời gian phê duyệt
   - `note`: ghi chú/lý do

2. **Sắp Xếp**: Lịch sử được sắp xếp theo `approved_at DESC`, bản ghi mới nhất ở trên

3. **Limit**: API lịch sử trả về tối đa 100 bản ghi mặc định, có thể tùy chỉnh qua parameter `limit`

4. **Authentication**: Tất cả API yêu cầu đăng nhập và role `manager`

---

## Database Table Schema

```sql
CREATE TABLE attendance_monthly_approval (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month_key CHAR(7) NOT NULL,              -- Kỳ công (YYYY-MM)
    hr_sender_id INT NOT NULL,               -- ID của HR gửi
    manager_approver_id INT DEFAULT NULL,    -- ID của Manager phê duyệt
    status ENUM('draft','submitted','approved','rejected') DEFAULT 'draft',
    submitted_at DATETIME DEFAULT NULL,      -- Thời gian HR gửi
    approved_at DATETIME DEFAULT NULL,       -- Thời gian phê duyệt
    note VARCHAR(255) DEFAULT NULL,          -- Ghi chú
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_month_key (month_key)
);
```

---

## JavaScript Examples

### Lấy danh sách chờ duyệt
```javascript
fetch('index.php?page=manager-api-approvals&status=submitted')
  .then(res => res.json())
  .then(json => {
    console.log('Pending approvals:', json.data);
  });
```

### Lấy lịch sử phê duyệt
```javascript
fetch('index.php?page=manager-api-approval-history?year=2026&limit=50')
  .then(res => res.json())
  .then(json => {
    console.log('Approval history:', json.data);
    console.log('Total records:', json.count);
  });
```

### Phê duyệt bảng công
```javascript
const formData = new FormData();
formData.append('approval_id', 1);
formData.append('action', 'approve');
formData.append('note', 'Được duyệt');

fetch('index.php?page=manager-api-approve', {
  method: 'POST',
  headers: { 'X-Requested-With': 'XMLHttpRequest' },
  body: formData
})
.then(res => res.json())
.then(json => {
  if (json.success) {
    console.log('Approval successful');
  }
});
```

---

## Tác Giả
Hệ thống Quản lý Chấm Công - v1.0.2
