$directory = "d:\xampp\htdocs\quanLyChamCong"
$files = Get-ChildItem -Path $directory -Filter *.php -Recurse

foreach ($file in $files) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $originalContent = $content
    
    # Fix HTML form attributes
    $content = $content -replace 'phuongThuc="POST"', 'method="POST"'
    $content = $content -replace 'phuongThuc="GET"', 'method="GET"'
    $content = $content -replace 'phuongThuc=''POST''', 'method=''POST'''
    $content = $content -replace 'phuongThuc=''GET''', 'method=''GET'''
    
    $content = $content -replace 'hanhDong="', 'action="'
    $content = $content -replace "hanhDong='", "action='"
    
    # Fix input types
    $content = $content -replace 'type="matKhau"', 'type="password"'
    $content = $content -replace "type='matKhau'", "type='password'"
    
    # Fix autocomplete
    $content = $content -replace 'autocomplete="current-matKhau"', 'autocomplete="current-password"'
    $content = $content -replace "autocomplete='current-matKhau'", "autocomplete='current-password'"

    # Check for $_SERVER['REQUEST_METHOD'] being accidentally renamed in index.php or api.php or routers if needed.
    # We saw in api.php: $phuongThuc = $_SERVER['REQUEST_METHOD']; this is okay because the variable is consistent.
    # In index.php: if ($_SERVER['REQUEST_METHOD'] === 'POST') - wait, let me check if $_SERVER['REQUEST_METHOD'] was renamed to $_SERVER['REQUEST_phuongThuc']?
    $content = $content -replace "\`$_SERVER\['REQUEST_phuongThuc'\]", "`$_SERVER['REQUEST_METHOD']"

    if ($content -cne $originalContent) {
        [System.IO.File]::WriteAllText($file.FullName, $content, (New-Object System.Text.UTF8Encoding $false))
        Write-Host "Fixed HTML attributes in: $($file.FullName)"
    }
}
Write-Host "HTML fix complete!"
