<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Compress oversized public images in place (library + courses) so Hostinger
 * Media/File Manager files drop under ~200KB without a huge zip upload.
 */
class OversizedImageCompressService
{
    private int $maxBytes = 190 * 1024;

    /**
     * @return array{scanned:int,compressed:int,already_ok:int,failed:int,remaining:int,complete:bool}
     */
    public function run(int $budgetSeconds = 20, int $maxFiles = 40): array
    {
        $deadline = microtime(true) + max(3, $budgetSeconds);
        $statePath = storage_path('app/oversized-compress-state.json');
        $state = $this->readState($statePath);

        $result = [
            'scanned' => 0,
            'compressed' => 0,
            'already_ok' => 0,
            'failed' => 0,
            'remaining' => 0,
            'complete' => false,
        ];

        if (! extension_loaded('gd') || ! function_exists('imagecreatetruecolor')) {
            $result['failed'] = 1;
            $result['complete'] = true;

            return $result;
        }

        $queue = $this->oversizedQueue($state['cursor'] ?? '');
        $result['remaining'] = count($queue);
        $done = 0;

        foreach ($queue as $rel) {
            if ($done >= $maxFiles || microtime(true) >= $deadline) {
                break;
            }
            $result['scanned']++;
            $done++;
            $abs = public_path($rel);
            if (! is_file($abs)) {
                $state['cursor'] = $rel;
                continue;
            }
            $before = (int) (@filesize($abs) ?: 0);
            if ($before <= $this->maxBytes) {
                $result['already_ok']++;
                $state['cursor'] = $rel;
                continue;
            }

            $ok = $this->compressPath($abs);
            $after = (int) (@filesize($abs) ?: 0);
            if ($ok && $after > 0 && $after < $before) {
                $result['compressed']++;
                $this->persistCompressed($rel);
                // Mirror courses into admin/courses when present.
                if (str_starts_with($rel, 'images/courses/')) {
                    $adminRel = 'admin/courses/' . substr($rel, strlen('images/courses/'));
                    $adminAbs = public_path($adminRel);
                    $adminDir = dirname($adminAbs);
                    if (! is_dir($adminDir)) {
                        @mkdir($adminDir, 0755, true);
                    }
                    @copy($abs, $adminAbs);
                    $this->persistCompressed($adminRel);
                }
            } elseif ($after > $this->maxBytes) {
                $result['failed']++;
            } else {
                $result['already_ok']++;
            }
            $state['cursor'] = $rel;
        }

        $left = $this->oversizedQueue($state['cursor'] ?? '');
        $result['remaining'] = count($left);
        $result['complete'] = $left === [];
        if ($result['complete']) {
            $state['cursor'] = '';
            $state['complete_at'] = now()->toIso8601String();
        }
        $state['last'] = $result;
        $this->writeState($statePath, $state);

        if ($result['compressed'] > 0) {
            @unlink(storage_path('app/media-library-index.json'));
            @unlink(storage_path('app/media-library-index-v2.json'));
            @unlink(storage_path('app/media-library-index-v3.json'));
        }

        return $result;
    }

    /**
     * @return array<int, string> relative public paths
     */
    private function oversizedQueue(string $afterRel): array
    {
        $dirs = ['images/library', 'images/courses', 'admin/courses'];
        $out = [];
        foreach ($dirs as $dir) {
            $abs = public_path($dir);
            if (! is_dir($abs)) {
                continue;
            }
            foreach (File::files($abs) as $file) {
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    continue;
                }
                $stem = $file->getFilenameWithoutExtension();
                if (preg_match('/-(thumb|card|banner)$/i', $stem)) {
                    continue;
                }
                if ($file->getSize() <= $this->maxBytes) {
                    continue;
                }
                $rel = str_replace('\\', '/', $dir . '/' . $file->getFilename());
                $out[] = $rel;
            }
        }
        sort($out);
        if ($afterRel === '') {
            return $out;
        }
        $filtered = [];
        $pass = false;
        foreach ($out as $rel) {
            if (! $pass) {
                if ($rel === $afterRel) {
                    $pass = true;
                }
                continue;
            }
            $filtered[] = $rel;
        }

        // If cursor was last item or missing, restart from beginning of remaining oversized.
        return $filtered !== [] ? $filtered : $out;
    }

    private function compressPath(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $img = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
        if (! $img) {
            return false;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $scales = [1.0, 0.9, 0.8, 0.7, 0.6, 0.5, 0.42, 0.35, 0.28];
        $qualities = [82, 74, 66, 58, 50, 42, 34, 28];
        $ok = false;

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
                foreach ($qualities as $q) {
                    $tmp = $path . '.tmp';
                    if ($ext === 'webp' && function_exists('imagewebp')) {
                        @imagewebp($canvas, $tmp, $q);
                    } else {
                        @imagejpeg($canvas, $tmp, $q);
                    }
                    if (is_file($tmp) && (@filesize($tmp) ?: 0) > 0 && (@filesize($tmp) ?: 0) <= $this->maxBytes) {
                        @unlink($path);
                        @rename($tmp, $path);
                        $ok = true;
                        break 2;
                    }
                    @unlink($tmp);
                }
            } else {
                foreach ([6, 7, 8, 9] as $level) {
                    $tmp = $path . '.tmp';
                    @imagepng($canvas, $tmp, $level);
                    if (is_file($tmp) && (@filesize($tmp) ?: 0) > 0 && (@filesize($tmp) ?: 0) <= $this->maxBytes) {
                        @unlink($path);
                        @rename($tmp, $path);
                        $ok = true;
                        break 2;
                    }
                    @unlink($tmp);
                }
                // Flattened JPEG often wins for photo PNGs; keep .png URL by re-encoding palette.
                $flat = imagecreatetruecolor($nw, $nh);
                $white = imagecolorallocate($flat, 255, 255, 255);
                imagefilledrectangle($flat, 0, 0, $nw, $nh, $white);
                imagecopy($flat, $canvas, 0, 0, 0, 0, $nw, $nh);
                imagetruecolortopalette($flat, false, 256);
                @imagepng($flat, $path, 9);
                imagedestroy($flat);
                if ((@filesize($path) ?: 0) <= $this->maxBytes) {
                    $ok = true;
                    imagedestroy($canvas);
                    break;
                }
            }
            imagedestroy($canvas);
        }

        // WebP sidecar (flattened) when possible.
        if (function_exists('imagewebp')) {
            $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
            if (is_string($webp) && $webp !== $path) {
                $src = @imagecreatefrompng($path);
                if (! $src && in_array($ext, ['jpg', 'jpeg'], true)) {
                    $src = @imagecreatefromjpeg($path);
                }
                if ($src) {
                    $fw = imagesx($src);
                    $fh = imagesy($src);
                    $flat = imagecreatetruecolor($fw, $fh);
                    $white = imagecolorallocate($flat, 255, 255, 255);
                    imagefilledrectangle($flat, 0, 0, $fw, $fh, $white);
                    imagecopy($flat, $src, 0, 0, 0, 0, $fw, $fh);
                    foreach ([78, 70, 60, 50] as $q) {
                        if (@imagewebp($flat, $webp, $q) && is_file($webp) && filesize($webp) > 100) {
                            break;
                        }
                        @unlink($webp);
                    }
                    imagedestroy($flat);
                    imagedestroy($src);
                }
            }
        }

        imagedestroy($img);

        return $ok || ((@filesize($path) ?: 0) <= $this->maxBytes);
    }

    private function persistCompressed(string $rel): void
    {
        if (! function_exists('persistent_uploads_root')) {
            return;
        }
        $root = persistent_uploads_root() . DIRECTORY_SEPARATOR . 'public';
        $target = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        $dir = dirname($target);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $src = public_path($rel);
        if (is_file($src)) {
            @copy($src, $target);
        }
        $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $rel);
        if (is_string($webp) && is_file(public_path($webp))) {
            $webpTarget = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $webp);
            @copy(public_path($webp), $webpTarget);
        }
    }

    private function readState(string $path): array
    {
        if (! is_file($path)) {
            return ['cursor' => ''];
        }
        $decoded = json_decode((string) @file_get_contents($path), true);

        return is_array($decoded) ? $decoded : ['cursor' => ''];
    }

    private function writeState(string $path, array $state): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($path, json_encode($state));
    }
}
