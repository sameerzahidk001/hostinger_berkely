$ErrorActionPreference = "Continue"
$root = "C:\laragon\www\berkely"
$mysql = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$php = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
$db = "u739774248_dbf3zedfyyctp"
$prodBase = "https://eduberkeley.com/public"

Write-Host "==> Rewriting production URLs in database..."
& $mysql -u root --default-character-set=utf8mb4 $db -e "source C:/laragon/www/berkely/database/fix-local-urls.sql"

Write-Host "==> Syncing CSS + fonts from production..."
$buildDir = Join-Path $root "public\build\assets"
New-Item -ItemType Directory -Force -Path $buildDir | Out-Null
@(
  "$prodBase/build/manifest.json|$(Join-Path $root 'public\build\manifest.json')",
  "$prodBase/build/assets/app-f9ffd100.css|$buildDir\app-f9ffd100.css",
  "$prodBase/build/assets/trad-ghothic-f069cc1a.ttf|$buildDir\trad-ghothic-f069cc1a.ttf",
  "$prodBase/build/assets/CanelaDeck-Regular-Trial-bfd4525f.otf|$buildDir\CanelaDeck-Regular-Trial-bfd4525f.otf",
  "$prodBase/frontend/output.css|$(Join-Path $root 'public\frontend\output.css')"
) | ForEach-Object {
  $parts = $_ -split '\|', 2
  try { Invoke-WebRequest -Uri $parts[0] -OutFile $parts[1] -TimeoutSec 60 } catch {}
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

Write-Host "==> Downloading Canela font..."
$fontDirs = @(
  (Join-Path $root "public\frontend\fonts"),
  (Join-Path $root "public\fonts"),
  (Join-Path $root "resources\fonts")
)
foreach ($d in $fontDirs) { New-Item -ItemType Directory -Force -Path $d | Out-Null }
$canelaDir = Join-Path $root "public\frontend\fonts\CanelaDeckFamily"
New-Item -ItemType Directory -Force -Path $canelaDir | Out-Null
$canela = Join-Path $canelaDir "CanelaDeck-Regular-Trial.otf"
try {
  Invoke-WebRequest -Uri "$prodBase/frontend/fonts/CanelaDeckFamily/CanelaDeck-Regular-Trial.otf" -OutFile $canela -TimeoutSec 30
} catch {}

Write-Host "==> Downloading header/site images..."
$imgDest = Join-Path $root "public\images"
@(
  'logo_1749981087.png','favicon_1749981087.jpg','header_search_image_1742033681.svg',
  'facebook_icon_1739438429.svg','twitter_icon_1739438429.svg','instagram_icon_1739438429.svg',
  'linkedin_icon_1739438429.svg','youtube_icon_1739438429.svg','tiktok_icon_1739438429.svg','whatsapp_icon_1739439335.svg'
) | ForEach-Object {
  try { Invoke-WebRequest -Uri "$prodBase/images/$_" -OutFile (Join-Path $imgDest $_) -TimeoutSec 30 } catch {}
}

Write-Host "==> Clearing Laravel caches..."
Set-Location $root
& $php artisan view:clear
& $php artisan config:clear
& $php artisan cache:clear

Write-Host ""
Write-Host "Done. Hard-refresh http://berkely.test (Ctrl+Shift+R)"
