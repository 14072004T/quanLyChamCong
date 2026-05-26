$file = "d:\xampp\htdocs\quanLyChamCong\dl_final.sql"
$content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)

# ==========================================
# TABLE NAME REPLACEMENTS
# ==========================================
$content = $content.Replace('`attendance_corrections`', '`suaChamCong`')
$content = $content.Replace('`attendance_daily_summary`', '`tongHopNgayCong`')
$content = $content.Replace('`attendance_employee_shift`', '`caNhanVien`')
$content = $content.Replace('`attendance_logs`', '`lichSuChamCong`')
$content = $content.Replace('`attendance_monthly_approval`', '`duyetCongThang`')
$content = $content.Replace('`attendance_shifts`', '`caLamViec`')
$content = $content.Replace('`attendance_wifi`', '`wifiChamCong`')
$content = $content.Replace('`don_nghi_phep`', '`donNghiPhep`')
$content = $content.Replace('`leave_requests`', '`yeuCauNghiPhep`')
$content = $content.Replace('`ot_requests`', '`yeuCauTangCa`')
$content = $content.Replace('`shift_change_requests`', '`yeuCauDoiCa`')
$content = $content.Replace('`system_settings`', '`caiDatHeThong`')
# nguoidung, taikhoan - da la tieng Viet, giu nguyen

# ==========================================
# COLUMN NAME REPLACEMENTS
# (Longer names first to avoid substring issues)
# ==========================================

# -- Cot rieng cua suaChamCong --
$content = $content.Replace('`attendance_date`', '`ngayChamCong`')
$content = $content.Replace('`evidence_image`', '`anhMinhChung`')
$content = $content.Replace('`evidence_file`', '`tepMinhChung`')
$content = $content.Replace('`proposed_checkin`', '`gioVaoDeXuat`')
$content = $content.Replace('`proposed_checkout`', '`gioRaDeXuat`')
$content = $content.Replace('`old_time`', '`gioCu`')
$content = $content.Replace('`new_time`', '`gioMoi`')
$content = $content.Replace('`hr_note`', '`ghiChuNS`')

# -- Cot rieng cua tongHopNgayCong --
$content = $content.Replace('`overtime_minutes`', '`phutTangCa`')
$content = $content.Replace('`work_minutes`', '`phutLamViec`')
$content = $content.Replace('`late_minutes`', '`phutDiTre`')
$content = $content.Replace('`work_date`', '`ngayLamViec`')
$content = $content.Replace('`first_in`', '`gioVaoDau`')
$content = $content.Replace('`last_out`', '`gioRaCuoi`')

# -- Cot rieng cua caNhanVien --
$content = $content.Replace('`effective_from`', '`hieuLucTu`')
$content = $content.Replace('`effective_to`', '`hieuLucDen`')

# -- Cot rieng cua lichSuChamCong --
$content = $content.Replace('`device_info`', '`thongTinThietBi`')
$content = $content.Replace('`wifi_name`', '`tenWifi`')

# -- Cot rieng cua duyetCongThang --
$content = $content.Replace('`month_key`', '`thangNam`')
$content = $content.Replace('`hr_sender_id`', '`maNguoiGuiNS`')
$content = $content.Replace('`submitted_at`', '`ngayGui`')

# -- Cot rieng cua caLamViec --
$content = $content.Replace('`shift_name`', '`tenCa`')

# -- Cot rieng cua wifiChamCong --
$content = $content.Replace('`ip_range`', '`daiIP`')
$content = $content.Replace('`gateway`', '`congMacDinh`')
$content = $content.Replace('`password`', '`matKhau`')
$content = $content.Replace('`location`', '`viTri`')

# -- Cot rieng cua yeuCauDoiCa --
$content = $content.Replace('`current_shift_id`', '`maCaHienTai`')
$content = $content.Replace('`requested_shift_id`', '`maCaMoi`')
$content = $content.Replace('`current_shift_name`', '`tenCaHienTai`')
$content = $content.Replace('`requested_shift_name`', '`tenCaMoi`')
$content = $content.Replace('`request_date`', '`ngayYeuCau`')

# -- Cot rieng cua caiDatHeThong --
$content = $content.Replace('`setting_key`', '`tenCaiDat`')
$content = $content.Replace('`setting_value`', '`giaTri`')
$content = $content.Replace('`updated_by`', '`nguoiCapNhat`')

# -- Cot rieng cua donNghiPhep --
$content = $content.Replace('`user_id`', '`maND`')
$content = $content.Replace('`approved_by`', '`nguoiDuyet`')

# -- Cot rieng cua yeuCauNghiPhep --
$content = $content.Replace('`leave_date`', '`ngayNghi`')
$content = $content.Replace('`is_half_day`', '`laNuaNgay`')

# -- Cot rieng cua yeuCauTangCa --
$content = $content.Replace('`ot_date`', '`ngayTangCa`')
$content = $content.Replace('`hours`', '`soGio`')

# -- Cot chung (dung trong nhieu bang) --
$content = $content.Replace('`manager_approver_id`', '`maNguoiDuyetQL`')
$content = $content.Replace('`manager_note`', '`ghiChuQL`')
$content = $content.Replace('`approved_at`', '`ngayDuyet`')
$content = $content.Replace('`leave_type`', '`loaiNghiPhep`')
$content = $content.Replace('`from_date`', '`tuNgay`')
$content = $content.Replace('`to_date`', '`denNgay`')
$content = $content.Replace('`shift_id`', '`maCa`')
$content = $content.Replace('`start_time`', '`gioBatDau`')
$content = $content.Replace('`end_time`', '`gioKetThuc`')
$content = $content.Replace('`is_active`', '`hoatDong`')
$content = $content.Replace('`description`', '`moTa`')
$content = $content.Replace('`reason`', '`lyDo`')
$content = $content.Replace('`status`', '`trangThai`')
$content = $content.Replace('`created_at`', '`ngayTao`')
$content = $content.Replace('`updated_at`', '`ngayCapNhat`')
$content = $content.Replace('`department`', '`phongBan`')
$content = $content.Replace('`action`', '`hanhDong`')
$content = $content.Replace('`method`', '`phuongThuc`')
$content = $content.Replace('`note`', '`ghiChu`')

# Ghi file
[System.IO.File]::WriteAllText($file, $content, (New-Object System.Text.UTF8Encoding $false))
Write-Host "Hoan tat! Da doi ten tat ca bang va cot sang tieng Viet khong dau."
