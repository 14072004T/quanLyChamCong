$file = "d:\xampp\htdocs\quanLyChamCong\app\models\ChamCongModel.php"
$lines = [System.IO.File]::ReadAllLines($file, [System.Text.Encoding]::UTF8)

# Replace 'duyetCongThang' with 'duyetCongNhanVien' for lines > 2900
for ($i = 2900; $i -lt $lines.Length; $i++) {
    $lines[$i] = $lines[$i] -replace 'duyetCongThang', 'duyetCongNhanVien'
}

# Add table creation logic around line 117
$newLines = @()
$inserted = $false
foreach ($line in $lines) {
    if ($line -match 'CREATE TABLE IF NOT EXISTS yeuCauNghiPhep' -and -not $inserted) {
        $newLines += '        $this->conn->query("'
        $newLines += '            CREATE TABLE IF NOT EXISTS duyetCongNhanVien ('
        $newLines += '                id INT AUTO_INCREMENT PRIMARY KEY,'
        $newLines += '                thangNam CHAR(7) NOT NULL,'
        $newLines += '                maND INT NOT NULL,'
        $newLines += '                maNguoiGuiNS INT NOT NULL,'
        $newLines += '                trangThai ENUM(''draft'',''submitted'',''approved'',''rejected'') NOT NULL DEFAULT ''draft'','
        $newLines += '                ngayGui DATETIME DEFAULT NULL,'
        $newLines += '                ngayDuyet DATETIME DEFAULT NULL,'
        $newLines += '                ghiChu VARCHAR(255) DEFAULT NULL,'
        $newLines += '                ngayTao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
        $newLines += '                ngayCapNhat DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,'
        $newLines += '                UNIQUE KEY uk_month_user (thangNam, maND)'
        $newLines += '            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        $newLines += '        ");'
        $newLines += ''
        $inserted = $true
    }
    $newLines += $line
}

[System.IO.File]::WriteAllLines($file, $newLines, (New-Object System.Text.UTF8Encoding $false))
Write-Host "Fixed employee timesheet table!"
