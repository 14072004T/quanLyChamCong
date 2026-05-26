$directory = "d:\xampp\htdocs\quanLyChamCong"
$files = Get-ChildItem -Path $directory -Filter *.php -Recurse

# Create an ordered dictionary to guarantee longer strings are replaced first if necessary
# For PHP files, we need to match the strings exactly as they might appear in queries, arrays, or object properties.
# Since we just used exact replacements without backticks (`) in PHP, we need to be careful with word boundaries
# to avoid replacing substrings.

$replacements = [ordered]@{
    # Tables
    "attendance_corrections" = "suaChamCong"
    "attendance_daily_summary" = "tongHopNgayCong"
    "attendance_employee_shift" = "caNhanVien"
    "attendance_monthly_approval" = "duyetCongThang"
    "attendance_logs" = "lichSuChamCong"
    "attendance_shifts" = "caLamViec"
    "attendance_wifi" = "wifiChamCong"
    "don_nghi_phep" = "donNghiPhep"
    "leave_requests" = "yeuCauNghiPhep"
    "ot_requests" = "yeuCauTangCa"
    "shift_change_requests" = "yeuCauDoiCa"
    "system_settings" = "caiDatHeThong"

    # Columns - Longest first
    "proposed_checkin" = "gioVaoDeXuat"
    "proposed_checkout" = "gioRaDeXuat"
    "manager_approver_id" = "maNguoiDuyetQL"
    "current_shift_name" = "tenCaHienTai"
    "requested_shift_name" = "tenCaMoi"
    "current_shift_id" = "maCaHienTai"
    "requested_shift_id" = "maCaMoi"
    "overtime_minutes" = "phutTangCa"
    "attendance_date" = "ngayChamCong"
    "evidence_image" = "anhMinhChung"
    "evidence_file" = "tepMinhChung"
    "work_minutes" = "phutLamViec"
    "late_minutes" = "phutDiTre"
    "hr_sender_id" = "maNguoiGuiNS"
    "device_info" = "thongTinThietBi"
    "setting_value" = "giaTri"
    "setting_key" = "tenCaiDat"
    "manager_note" = "ghiChuQL"
    "request_date" = "ngayYeuCau"
    "is_half_day" = "laNuaNgay"
    "description" = "moTa"
    "approved_by" = "nguoiDuyet"
    "approved_at" = "ngayDuyet"
    "created_at" = "ngayTao"
    "updated_at" = "ngayCapNhat"
    "updated_by" = "nguoiCapNhat"
    "leave_type" = "loaiNghiPhep"
    "start_time" = "gioBatDau"
    "leave_date" = "ngayNghi"
    "department" = "phongBan"
    "work_date" = "ngayLamViec"
    "wifi_name" = "tenWifi"
    "month_key" = "thangNam"
    "ip_range" = "daiIP"
    "password" = "matKhau"
    "location" = "viTri"
    "first_in" = "gioVaoDau"
    "last_out" = "gioRaCuoi"
    "end_time" = "gioKetThuc"
    "is_active" = "hoatDong"
    "from_date" = "tuNgay"
    "old_time" = "gioCu"
    "new_time" = "gioMoi"
    "shift_id" = "maCa"
    "hr_note" = "ghiChuNS"
    "to_date" = "denNgay"
    "gateway" = "congMacDinh"
    "ot_date" = "ngayTangCa"
    "user_id" = "maND"
    "reason" = "lyDo"
    "status" = "trangThai"
    "action" = "hanhDong"
    "method" = "phuongThuc"
    "hours" = "soGio"
    "note" = "ghiChu"
}

foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $originalContent = $content
    
    foreach ($key in $replacements.Keys) {
        $val = $replacements[$key]
        
        # We need to replace exactly whole words.
        # This regex ensures we don't accidentally replace a substring
        # e.g., don't replace 'note' inside 'hr_note'
        $regex = "\b" + [regex]::Escape($key) + "\b"
        $content = [regex]::Replace($content, $regex, $val)
    }
    
    if ($content -cne $originalContent) {
        [System.IO.File]::WriteAllText($file.FullName, $content, (New-Object System.Text.UTF8Encoding $false))
        Write-Host "Updated: $($file.FullName)"
    }
}
Write-Host "PHP update complete!"
