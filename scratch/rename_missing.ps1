$directory = "d:\xampp\htdocs\quanLyChamCong"
$files = Get-ChildItem -Path $directory -Filter *.php -Recurse

$replacements = [ordered]@{
    "submitted_at" = "ngayGui"
    "effective_from" = "hieuLucTu"
    "effective_to" = "hieuLucDen"
    "shift_name" = "tenCa"
    "employee_note" = "ghiChuNV"
}

foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $originalContent = $content
    
    foreach ($key in $replacements.Keys) {
        $val = $replacements[$key]
        $regex = "\b" + [regex]::Escape($key) + "\b"
        $content = [regex]::Replace($content, $regex, $val)
    }
    
    if ($content -cne $originalContent) {
        [System.IO.File]::WriteAllText($file.FullName, $content, (New-Object System.Text.UTF8Encoding $false))
        Write-Host "Updated missing columns in: $($file.FullName)"
    }
}
Write-Host "Missing columns update complete!"
