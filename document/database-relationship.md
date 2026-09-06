# So do quan he co so du lieu

Sơ đồ dưới đây được dựng theo `dl_final.sql` hiện tại.

- Đường liền: khóa ngoại đã được khai báo trong database.
- Đường nét đứt: quan hệ logic được thể hiện qua tên cột hoặc code, nhưng chưa có khóa ngoại trong dump.
- `PK`: khóa chính, `FK`: khóa ngoại, `UQ`: khóa duy nhất.

```mermaid
erDiagram
    TAIKHOAN ||--o{ NGUOIDUNG : "maTK"
    NGUOIDUNG ||--o| NHANVIEN : "maND"
    NGUOIDUNG ||--o| NHANSU : "maND"
    NGUOIDUNG ||--o| QUANLY : "maND"
    NGUOIDUNG ||--o| KYTHUAT : "maND"

    NGUOIDUNG ||--o{ CAIDATHETHONG : "nguoiCapNhat"
    NGUOIDUNG ||--o{ CANHANVIEN : "maND"
    CALAMVIEC ||--o{ CANHANVIEN : "maCa"
    NGUOIDUNG ||--o| FACE_PROFILE : "maND"
    NGUOIDUNG ||--o{ LICHSUCHAMCONG : "maND"
    NGUOIDUNG ||--o{ SUACHAMCONG : "maND"
    NGUOIDUNG ||--o{ TONGHOPNGAYCONG : "maND"

    NGUOIDUNG ||--o{ DUYETCONGTHANG : "maNguoiGuiNS"
    NGUOIDUNG ||--o{ DUYETCONGTHANG : "maNguoiDuyetQL"

    NGUOIDUNG ||..o{ DONNGHIPHEP : "maND nguoi xin"
    NGUOIDUNG ||..o{ DONNGHIPHEP : "nguoiDuyet"
    NGUOIDUNG ||..o{ DUYETCONGNHANVIEN : "maND"
    NGUOIDUNG ||..o{ DUYETCONGNHANVIEN : "maNguoiGuiNS"
    WIFICHAMCONG }o..o{ LICHSUCHAMCONG : "tenWifi text"

    TAIKHOAN {
        int maTK PK
        varchar tenDangNhap UQ
        varchar matKhau
        enum trangThai
        datetime lanDangNhapCuoi
    }

    NGUOIDUNG {
        int maND PK
        int maTK FK
        varchar hoTen
        varchar email
        varchar soDienThoai
        enum chucVu
        varchar phongBan
        tinyint trangThai
    }

    NHANVIEN {
        int maND PK, FK
    }

    NHANSU {
        int maND PK, FK
    }

    QUANLY {
        int maND PK, FK
    }

    KYTHUAT {
        int maND PK, FK
    }

    CAIDATHETHONG {
        int id PK
        varchar tenCaiDat UQ
        text giaTri
        int nguoiCapNhat FK
        datetime ngayCapNhat
    }

    CALAMVIEC {
        int id PK
        varchar tenCa
        time gioBatDau
        time gioKetThuc
        tinyint hoatDong
    }

    CANHANVIEN {
        int id PK
        int maND FK
        int maCa FK
        date hieuLucTu
        date hieuLucDen
    }

    FACE_PROFILE {
        int id PK
        int maND FK, UQ
        text embedding
        datetime ngayTao
        datetime ngayCapNhat
    }

    LICHSUCHAMCONG {
        int id PK
        int maND FK
        enum hanhDong
        enum phuongThuc
        varchar tenWifi
        varchar thongTinThietBi
        varchar ghiChu
        datetime ngayTao
        varchar anhMinhChung
    }

    TONGHOPNGAYCONG {
        int id PK
        int maND FK
        date ngayLamViec
        datetime gioVaoDau
        datetime gioRaCuoi
        int phutLamViec
        int phutTangCa
        int phutDiTre
        enum trangThai
    }

    SUACHAMCONG {
        int id PK
        int maND FK
        date ngayChamCong
        datetime gioCu
        datetime gioMoi
        datetime gioVaoDeXuat
        datetime gioRaDeXuat
        text lyDo
        enum trangThai
        varchar ghiChuNS
    }

    DONNGHIPHEP {
        int id PK
        int maND
        date tuNgay
        date denNgay
        text lyDo
        enum trangThai
        varchar loaiNghiPhep
        varchar tepMinhChung
        int nguoiDuyet
        datetime ngayDuyet
    }

    DUYETCONGNHANVIEN {
        int id PK
        char thangNam
        int maND
        int maNguoiGuiNS
        enum trangThai
        datetime ngayGui
        datetime ngayDuyet
        varchar ghiChu
        UQ thangNam_maND
    }

    DUYETCONGTHANG {
        int id PK
        char thangNam
        int maNguoiGuiNS FK
        int maNguoiDuyetQL FK
        varchar phongBan
        enum trangThai
        datetime ngayGui
        datetime ngayDuyet
        UQ thangNam_phongBan
    }

    WIFICHAMCONG {
        int id PK
        varchar tenWifi UQ
        tinyint hoatDong
        varchar daiIP
        varchar congMacDinh
        varchar ssid
        varchar matKhau
        varchar viTri
    }
```

## Quan he chinh

| Bang con | Cot lien ket | Bang cha | Trang thai |
| --- | --- | --- | --- |
| `nguoidung` | `maTK` | `taikhoan.maTK` | FK that |
| `caidathethong` | `nguoiCapNhat` | `nguoidung.maND` | FK that, nullable |
| `canhanvien` | `maND`, `maCa` | `nguoidung.maND`, `calamviec.id` | FK that |
| `face_profile` | `maND` | `nguoidung.maND` | FK that, 1-1 theo UQ |
| `lichsuchamcong` | `maND` | `nguoidung.maND` | FK that |
| `suachamcong` | `maND` | `nguoidung.maND` | FK that |
| `tonghopngaycong` | `maND` | `nguoidung.maND` | FK that |
| `duyetcongthang` | `maNguoiGuiNS`, `maNguoiDuyetQL` | `nguoidung.maND` | FK that |
| `nhanvien`, `nhansu`, `quanly`, `kythuat` | `maND` | `nguoidung.maND` | FK that, bang vai tro 1-1 |
| `donnghiphep` | `maND`, `nguoiDuyet` | `nguoidung.maND` | Logic, chua co FK |
| `duyetcongnhanvien` | `maND`, `maNguoiGuiNS` | `nguoidung.maND` | Logic, chua co FK |
| `lichsuchamcong` | `tenWifi` | `wifichamcong.tenWifi` | Logic text, chua co FK |

## Diem can luu y

1. `donnghiphep` va `duyetcongnhanvien` dang co cac cot tham chieu den `nguoidung`, nhung `dl_final.sql` chua khai bao FK cho cac cot nay.
2. `nguoidung.maTK` co index thuong, khong phai `UNIQUE`, nen rang buoc thuc te cua database la mot tai khoan co the gan cho nhieu nguoi dung. Neu muc tieu la 1-1, can them unique index cho `nguoidung.maTK`.
3. `wifichamcong` khong co FK den `lichsuchamcong`; bang cham cong chi luu ten Wi-Fi dang text.
4. Cac bang `employee_leaves` va `tablet_face_scans` duoc code tham chieu/tao boi ung dung nhung khong xuat hien trong `dl_final.sql`, nen khong dua vao ERD chinh nay.
