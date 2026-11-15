<?php

/**
 * Compress raster images in a folder to <= 200 KB (same path/filename) and write WebP + size variants.
 * Usage: php scripts/compress-images-under-200kb.php public/images/courses
 */

$root = $argv[1] ?? null;
if (! is_string($root) || $root === '' || ! is_dir($root)) {
    fwrite(STDERR, "Usage: php scripts/compress-images-under-200kb.php <directory>\n");
    exit(1);
}
if (! extension_loaded('gd')) {
    fwrite(STDERR, "GD extension required.\n");
    exit(1);
}

// Use decimal 200KB (200000) so auditors counting 200*1000 still pass.
$maxBytes = 190 * 1024;
$allowed = ['jpg' => true, 'jpeg' => true, 'png' => true, 'webp' => true];
$files = [];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
    if (! $file->isFile()) {
        continue;
    }
    $ext = strtolower($file->getExtension());
    if (! isset($allowed[$ext])) {
        continue;
    }
    // Skip generated variants
    $stem = pathinfo($file->getFilename(), PATHINFO_FILENAME);
    if (preg_match('/-(thumb|card|banner)$/i', $stem)) {
        continue;
    }
    $files[] = $file->getPathname();
}

$changed = 0;
$ok = 0;
$fail = 0;
foreach ($files as $path) {
    $before = (int) (@filesize($path) ?: 0);
    $result = compressFile($path, $maxBytes);
    writeSidecars($path);
    $after = (int) (@filesize($path) ?: 0);
    if ($before > $maxBytes && $after <= $maxBytes) {
        echo basename($path) . "  {$before} -> {$after}\n";
        $changed++;
    } elseif ($after <= $maxBytes) {
        $ok++;
    } else {
        echo "FAIL " . basename($path) . " still {$after}\n";
        $fail++;
    }
}
echo "done changed={$changed} already_ok={$ok} fail={$fail} total=" . count($files) . PHP_EOL;
exit($fail > 0 ? 2 : 0);

function compressFile(string $path, int $maxBytes): bool
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $img = loadImage($path, $ext);
    if (! $img) {
        return false;
    }
    if ((@filesize($path) ?: 0) <= $maxBytes) {
        imagedestroy($img);
        return true;
    }

    $w = imagesx($img);
    $h = imagesy($img);
    $scales = [1.0, 0.9, 0.8, 0.7, 0.6, 0.5, 0.42, 0.35, 0.28, 0.22];
    $jpegQ = [82, 74, 66, 58, 50, 42, 35, 28];
    $pngLevels = [6, 7, 8, 9];

    foreach ($scales as $scale) {
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $canvas = imagecreatetruecolor($nw, $nh);
        if ($ext === 'png') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $nw, $nh, $transparent);
        } else {
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $nw, $nh, $white);
        }
        imagecopyresampled($canvas, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        if (in_array($ext, ['jpg', 'jpeg', 'webp'], true)) {
            foreach ($jpegQ as $q) {
                $tmp = $path . '.tmp';
                if ($ext === 'webp' && function_exists('imagewebp')) {
                    imagewebp($canvas, $tmp, $q);
                } else {
                    imagejpeg($canvas, $tmp, $q);
                }
                if (is_file($tmp) && (@filesize($tmp) ?: 0) <= $maxBytes) {
                    @unlink($path);
                    rename($tmp, $path);
                    imagedestroy($canvas);
                    imagedestroy($img);
                    return true;
                }
                @unlink($tmp);
            }
        } else {
            // PNG: try compressed PNG, then flatten to JPEG saved as .jpg sibling + replace .png with resized PNG palette
            foreach ($pngLevels as $level) {
                $tmp = $path . '.tmp';
                imagepng($canvas, $tmp, $level);
                if (is_file($tmp) && (@filesize($tmp) ?: 0) <= $maxBytes) {
                    @unlink($path);
                    rename($tmp, $path);
                    imagedestroy($canvas);
                    imagedestroy($img);
                    return true;
                }
                @unlink($tmp);
            }
            // Flattened JPEG often much smaller; keep .png URL working by writing JPEG into a .jpg
            // and also rewriting the .png as a re-encoded smaller PNG from the resized canvas.
            $jpgSibling = preg_replace('/\.png$/i', '.jpg', $path);
            foreach ($jpegQ as $q) {
                imagejpeg($canvas, $jpgSibling, $q);
                if (is_file($jpgSibling) && (@filesize($jpgSibling) ?: 0) <= $maxBytes) {
                    break;
                }
            }
            // Palette PNG last resort
            $pal = imagecreatetruecolor($nw, $nh);
            imagecopy($pal, $canvas, 0, 0, 0, 0, $nw, $nh);
            imagetruecolortopalette($pal, false, 256);
            imagepng($pal, $path, 9);
            imagedestroy($pal);
            if ((@filesize($path) ?: 0) <= $maxBytes) {
                imagedestroy($canvas);
                imagedestroy($img);
                return true;
            }
        }
        imagedestroy($canvas);
    }

    imagedestroy($img);
    return (@filesize($path) ?: 0) <= $maxBytes;
}

function writeSidecars(string $path): void
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return;
    }
    $img = loadImage($path, $ext);
    if (! $img) {
        return;
    }
    $dir = dirname($path);
    $slug = pathinfo($path, PATHINFO_FILENAME);
    $maxBytes = 190 * 1024;

    if (function_exists('imagewebp')) {
        $webpPath = $dir . DIRECTORY_SEPARATOR . $slug . '.webp';
        // Flatten alpha onto white — Windows/Hostinger GD often fails imagewebp() on alpha PNGs.
        $fw = imagesx($img);
        $fh = imagesy($img);
        $flat = imagecreatetruecolor($fw, $fh);
        $white = imagecolorallocate($flat, 255, 255, 255);
        imagefilledrectangle($flat, 0, 0, $fw, $fh, $white);
        imagecopy($flat, $img, 0, 0, 0, 0, $fw, $fh);
        foreach ([78, 70, 60, 50, 40] as $q) {
            if (@imagewebp($flat, $webpPath, $q) && is_file($webpPath) && filesize($webpPath) > 100) {
                if (filesize($webpPath) <= $maxBytes) {
                    break;
                }
            } else {
                @unlink($webpPath);
            }
        }
        imagedestroy($flat);
        if (is_file($webpPath) && filesize($webpPath) === 0) {
            @unlink($webpPath);
        }
    }

    $w = imagesx($img);
    $h = imagesy($img);
    foreach (['thumb' => 400, 'card' => 800, 'banner' => 1600] as $name => $maxW) {
        if ($w <= $maxW) {
            continue;
        }
        $nw = $maxW;
        $nh = max(1, (int) round($h * ($nw / $w)));
        $canvas = imagecreatetruecolor($nw, $nh);
        // Flatten onto white so JPEG/WebP variants stay small and valid.
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $nw, $nh, $white);
        imagecopyresampled($canvas, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $variantJpg = $dir . DIRECTORY_SEPARATOR . $slug . '-' . $name . '.jpg';
        foreach ([78, 70, 62, 54, 46] as $q) {
            imagejpeg($canvas, $variantJpg, $q);
            if ((@filesize($variantJpg) ?: 0) <= $maxBytes) {
                break;
            }
        }

        // Keep same-extension variant only when it stays under budget.
        $variant = $dir . DIRECTORY_SEPARATOR . $slug . '-' . $name . '.' . $ext;
        if (in_array($ext, ['jpg', 'jpeg'], true)) {
            @copy($variantJpg, $variant);
        } elseif ($ext === 'webp' && function_exists('imagewebp')) {
            @imagewebp($canvas, $variant, 72);
        } else {
            // Prefer JPEG bytes for srcset widths; skip huge PNG card variants.
            @unlink($variant);
        }

        if (function_exists('imagewebp')) {
            $variantWebp = $dir . DIRECTORY_SEPARATOR . $slug . '-' . $name . '.webp';
            foreach ([76, 68, 58, 48] as $q) {
                if (@imagewebp($canvas, $variantWebp, $q) && is_file($variantWebp) && filesize($variantWebp) > 0) {
                    if (filesize($variantWebp) <= $maxBytes) {
                        break;
                    }
                } else {
                    @unlink($variantWebp);
                }
            }
            if (is_file($variantWebp) && filesize($variantWebp) === 0) {
                @unlink($variantWebp);
            }
        }
        imagedestroy($canvas);
    }
    imagedestroy($img);
}

function loadImage(string $path, string $ext)
{
    return match ($ext) {
        'jpg', 'jpeg' => @imagecreatefromjpeg($path),
        'png' => @imagecreatefrompng($path),
        'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        default => false,
    };
}
