<?php

$dir = $argv[1] ?? 'public/images/courses';
if (! is_dir($dir)) {
    fwrite(STDERR, "Missing dir\n");
    exit(1);
}

foreach (glob(rtrim($dir, '/\\') . '/*.{png,jpg,jpeg}', GLOB_BRACE) ?: [] as $path) {
    $stem = pathinfo($path, PATHINFO_FILENAME);
    if (preg_match('/-(thumb|card|banner)$/i', $stem)) {
        continue;
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $img = match ($ext) {
        'png' => @imagecreatefrompng($path),
        'jpg', 'jpeg' => @imagecreatefromjpeg($path),
        default => false,
    };
    if (! $img) {
        echo "skip load $path\n";
        continue;
    }

    // Flatten alpha onto white — Windows GD often fails imagewebp() on alpha PNGs.
    $w = imagesx($img);
    $h = imagesy($img);
    $flat = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($flat, 255, 255, 255);
    imagefilledrectangle($flat, 0, 0, $w, $h, $white);
    imagecopy($flat, $img, 0, 0, 0, 0, $w, $h);
    imagedestroy($img);

    $webp = dirname($path) . DIRECTORY_SEPARATOR . $stem . '.webp';
    $ok = false;
    foreach ([78, 70, 60, 50, 40] as $q) {
        if (@imagewebp($flat, $webp, $q) && is_file($webp) && filesize($webp) > 100) {
            $ok = true;
            if (filesize($webp) <= 190 * 1024) {
                break;
            }
        } else {
            @unlink($webp);
        }
    }
    imagedestroy($flat);
    echo ($ok ? 'ok ' : 'fail ') . basename($webp) . ' ' . (@filesize($webp) ?: 0) . PHP_EOL;
}
