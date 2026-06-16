$ErrorActionPreference = "Stop"
$root = "C:\laragon\www\berkely"
$mysql = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$php = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
$db = "u739774248_dbf3zedfyyctp"
$apache = "C:\laragon\bin\apache\httpd-2.4.66-260223-Win64-VS18\bin\httpd.exe"
$mysqld = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe"

Write-Host "==> Starting MySQL..."
if (-not (Get-Process mysqld -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $mysqld -ArgumentList "--defaults-file=C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini" -WindowStyle Hidden
    Start-Sleep -Seconds 3
}

Write-Host "==> Starting Apache..."
if (-not (Get-Process httpd -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $apache -WindowStyle Hidden
    Start-Sleep -Seconds 2
}

Write-Host "==> Trim database to 58 tables..."
& $mysql -u root $db -e "source C:/laragon/www/berkely/database/trim-to-58-tables.sql"
$count = & $mysql -u root $db -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$db';"
Write-Host "Tables: $count"

Write-Host "==> Rebuild CSS/JS assets..."
Set-Location $root
npm run build

Write-Host "==> Clear Laravel caches..."
& $php artisan config:clear
& $php artisan view:clear
& $php artisan cache:clear

Write-Host "==> Done. Open http://berkely.test in Laragon"
