$ErrorActionPreference = "Stop"
$root = "C:\laragon\www\berkely"
$mysql = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$php = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
$db = "u739774248_dbf3zedfyyctp"
$sqlSrcDefault = Join-Path $root "u739774248_dbf3zedfyyctp.sql"
$sqlSrcDownloads = "C:\Users\HP\Downloads\u739774248_dbf3zedfyyctp.sql"
if (Test-Path $sqlSrcDownloads) {
  $sqlSrc = $sqlSrcDownloads
} else {
  $sqlSrc = $sqlSrcDefault
}

Write-Host "==> Using SQL dump: $sqlSrc"

Write-Host "==> Recreating database..."
& $mysql -u root -e "DROP DATABASE IF EXISTS ``$db``; CREATE DATABASE ``$db`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

Write-Host "==> Importing SQL (this may take a minute)..."
cmd /c "`"$mysql`" -u root --default-character-set=utf8mb4 --force $db < `"$sqlSrc`""

Write-Host "==> Creating missing tables + seeding site settings..."
$extraSql = Join-Path $root "database\setup-missing-tables.sql"
& $mysql -u root $db -e "source $($extraSql -replace '\\','/')"

Write-Host "==> Running pending Laravel migrations (local-only)..."
Set-Location $root
# The production dump already includes core tables like users, subjects, etc.
# Only run migrations that are safe to apply on top of the dump.
@(
  'database/migrations/2026_06_15_000001_create_currency_rates_table.php',
  'database/migrations/2026_06_15_000002_add_audit_columns_to_content_tables.php',
  'database/migrations/2026_06_16_000001_add_referrer_to_page_views_table.php',
  'database/migrations/2026_06_16_000002_add_profile_fields_to_users_table.php',
  'database/migrations/2026_06_16_000003_add_module_to_permissions_table.php',
  'database/migrations/2026_06_16_000004_add_audit_columns_to_pages_seo_table.php',
  'database/migrations/2026_06_16_000005_add_source_to_payments_table.php'
) | ForEach-Object {
  & $php artisan migrate --path=$_ --force 2>$null
}
& $php artisan db:seed --class=StudentPortalPermissionSeeder --force 2>$null
& $php artisan db:seed --class=FooterSettingsSeeder --force 2>$null

Write-Host "==> Removing tables not on production (keep 58 tables)..."
& $mysql -u root $db -e "DROP TABLE IF EXISTS accountants, librarians, homepage_sections, client_images, home_settings;"

Write-Host "==> Laravel cache + storage..."
& $php artisan config:clear
& $php artisan cache:clear
& $php artisan storage:link --force 2>$null

$tableCount = & $mysql -u root $db -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$db';"
Write-Host "==> Done. Tables in database: $tableCount"

Write-Host "==> Fixing UTF-8 text encoding in database..."
$fixSql = Join-Path $root "database\fix-encoding.sql"
& $mysql -u root --default-character-set=utf8mb4 $db -e "source $($fixSql -replace '\\','/')"

Write-Host "==> Rewriting production image/menu URLs for local..."
$urlsSql = Join-Path $root "database\fix-local-urls.sql"
& $mysql -u root --default-character-set=utf8mb4 $db -e "source $($urlsSql -replace '\\','/')"

Write-Host "==> Syncing production CSS/assets (matches eduberkeley.com)..."
$buildDir = Join-Path $root "public\build\assets"
New-Item -ItemType Directory -Force -Path $buildDir | Out-Null
$prodBase = "https://eduberkeley.com/public"
@(
  @{ Url = "$prodBase/build/manifest.json"; Out = Join-Path $root "public\build\manifest.json" },
  @{ Url = "$prodBase/build/assets/app-f9ffd100.css"; Out = Join-Path $buildDir "app-f9ffd100.css" },
  @{ Url = "$prodBase/build/assets/trad-ghothic-f069cc1a.ttf"; Out = Join-Path $buildDir "trad-ghothic-f069cc1a.ttf" },
  @{ Url = "$prodBase/build/assets/CanelaDeck-Regular-Trial-bfd4525f.otf"; Out = Join-Path $buildDir "CanelaDeck-Regular-Trial-bfd4525f.otf" },
  @{ Url = "$prodBase/frontend/output.css"; Out = Join-Path $root "public\frontend\output.css" }
) | ForEach-Object {
  try { Invoke-WebRequest -Uri $_.Url -OutFile $_.Out -TimeoutSec 60 } catch {}
}
$outputCss = Join-Path $root "public\frontend\output.css"
if (Test-Path $outputCss) {
  $css = Get-Content $outputCss -Raw
  $css = $css -replace '\.\./fonts/CanelaDeckFamily/', '../frontend/fonts/CanelaDeckFamily/'
  $css = $css -replace '\.\./fonts/trad-ghothic\.ttf', '../frontend/fonts/Trade_Gothic/Trade-Gothic.woff2'
  $css = $css -replace '\.\./frontend/fonts/trad-ghothic\.ttf', '../frontend/fonts/Trade_Gothic/Trade-Gothic.woff2'
  [System.IO.File]::WriteAllText($outputCss, $css, (New-Object System.Text.UTF8Encoding $false))
}
$appCss = Join-Path $buildDir "app-f9ffd100.css"
if (Test-Path $appCss) {
  $css = Get-Content $appCss -Raw
  $css = $css -replace 'http://localhost:8000/', '/'
  $css = $css -replace 'url\(/build/assets/trad-ghothic-f069cc1a\.ttf\) format\("truetype"\)', 'url(/frontend/fonts/Trade_Gothic/Trade-Gothic.woff2) format("woff2"),url(/frontend/fonts/Trade_Gothic/Trade-Gothic-Regular.otf) format("opentype")'
  [System.IO.File]::WriteAllText($appCss, $css, (New-Object System.Text.UTF8Encoding $false))
}

Write-Host "==> Installing Trade Gothic font family..."
$tradeGothicSrc = Join-Path $env:USERPROFILE "Downloads\Trade_Gothic\Trade_Gothic"
if (-not (Test-Path $tradeGothicSrc)) {
  $tradeGothicSrc = Join-Path $root "resources\fonts\Trade_Gothic"
}
if (Test-Path $tradeGothicSrc) {
  @(
    (Join-Path $root "public\frontend\fonts\Trade_Gothic"),
    (Join-Path $root "resources\fonts\Trade_Gothic"),
    (Join-Path $root "public\fonts\Trade_Gothic")
  ) | ForEach-Object {
    New-Item -ItemType Directory -Force -Path $_ | Out-Null
    Copy-Item (Join-Path $tradeGothicSrc "*") $_ -Force -ErrorAction SilentlyContinue
  }
}

Write-Host "==> Downloading Canela + site assets..."
$canelaDir = Join-Path $root "public\frontend\fonts\CanelaDeckFamily"
New-Item -ItemType Directory -Force -Path $canelaDir | Out-Null
$canelaPath = Join-Path $canelaDir "CanelaDeck-Regular-Trial.otf"
try {
  Invoke-WebRequest -Uri "https://eduberkeley.com/public/frontend/fonts/CanelaDeckFamily/CanelaDeck-Regular-Trial.otf" -OutFile $canelaPath -TimeoutSec 30
  $pubCanelaDir = Join-Path $root "public\fonts\CanelaDeckFamily"
  New-Item -ItemType Directory -Force -Path $pubCanelaDir | Out-Null
  Copy-Item $canelaPath (Join-Path $pubCanelaDir "CanelaDeck-Regular-Trial.otf") -Force
} catch {}
$srcFonts = Join-Path $root "resources\fonts"
$pubFonts = Join-Path $root "public\fonts"
@('CanelaDeckFamily','CanelaFamily','CanelaCondensedFamily','CanelaTextFamily') | ForEach-Object {
  $from = Join-Path $srcFonts $_
  if (Test-Path $from) { Copy-Item $from (Join-Path $pubFonts $_) -Recurse -Force }
}

$imgDest = Join-Path $root "public\images"
@(
  'logo_1749981087.png','white_logo_1749981087.png','favicon_1749981087.jpg','header_search_image_1742033681.svg',
  'facebook_icon_1739438429.svg','twitter_icon_1739438429.svg','instagram_icon_1739438429.svg',
  'linkedin_icon_1739438429.svg','youtube_icon_1739438429.svg','tiktok_icon_1739438429.svg','whatsapp_icon_1739439335.svg'
) | ForEach-Object {
  try { Invoke-WebRequest -Uri "https://eduberkeley.com/public/images/$_" -OutFile (Join-Path $imgDest $_) -TimeoutSec 30 } catch {}
}
& $mysql -u root $db -e "UPDATE site_settings SET logo='logo_1749981087.png', white_logo='white_logo_1749981087.png', favicon='favicon_1749981087.jpg', mobile_logo='logo_1749981087.png', header_logo='logo', footer_bg='#0e0e0e', footer_text_color='#8996a0', footer_title_bg='#ffffff', footer_logo='white_logo', copyright_message='Copyright © 2026 Berkeley School of Business, Arts & Sciences | UKPRN: 10101119' WHERE id=1;"

Write-Host "==> Starting Laragon MySQL + Apache..."
$apache = Get-ChildItem "C:\laragon\bin\apache" -Directory | Sort-Object Name -Descending | Select-Object -First 1
if (-not (Get-Process mysqld -ErrorAction SilentlyContinue)) {
  Start-Process -FilePath "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\laragon\bin\mysql\mysql-8.4.3-winx64\my.ini" -WindowStyle Hidden
  Start-Sleep -Seconds 3
}
if (-not (Get-Process httpd -ErrorAction SilentlyContinue)) {
  Start-Process -FilePath (Join-Path $apache.FullName "bin\httpd.exe") -WindowStyle Hidden
  Start-Sleep -Seconds 2
}

Write-Host ""
Write-Host "Open: http://berkely.test  (or http://localhost/berkely/public)"
Write-Host "Add to hosts if needed: 127.0.0.1 berkely.test"
