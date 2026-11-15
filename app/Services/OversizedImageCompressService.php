<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Compress oversized public images in place (library + courses).
 * Short batches only — Hostinger nginx 504s on long requests.
 */
class OversizedImageCompressService
{
    private int $maxBytes = 190 * 1024;

    /**
     * @return array{scanned:int,compressed:int,already_ok:int,failed:int,skipped:int,remaining:int,complete:bool}
     */
    public function run(int $budgetSeconds = 12, int $maxFiles = 12): array
    {
        $deadline = microtime(true) + max(3, $budgetSeconds);
        $statePath = storage_path('app/oversized-compress-state.json');
        $state = $this->readState($statePath);
        $skipped = is_array($state['skipped'] ?? null) ? $state['skipped'] : [];

        $result = [
            'scanned' => 0,
            'compressed' => 0,
            'already_ok' => 0,
            'failed' => 0,
            'skipped' => count($skipped),
            'remaining' => 0,
            'complete' => false,
        ];

        if (! extension_loaded('gd') || ! function_exists('imagecreatetruecolor')) {
            $result['failed'] = 1;
            $result['complete'] = true;

            return $result;
        }

        $queue = $this->oversizedQueue($state['cursor'] ?? '', $skipped);
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
                unset($skipped[$rel]);
                continue;
            }

            $this->compressPath($abs);
            $after = (int) (@filesize($abs) ?: 0);

            if ($after > 0 && $after <= $this->maxBytes) {
                $result['compressed']++;
                unset($skipped[$rel]);
                $this->persistCompressed($rel);
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
            } elseif ($after > 0 && $after < $before) {
                // Smaller but still over budget — count as progress, retry next full cycle once more.
                $result['compressed']++;
                $this->persistCompressed($rel);
                $tries = (int) ($skipped[$rel] ?? 0) + 1;
                if ($tries >= 2) {
                    $skipped[$rel] = $tries; // give up so the loop can finish
                    $result['failed']++;
                } else {
                    // Keep in queue for a second harder pass (do not advance as permanent skip).
                    $skipped[$rel] = $tries;
                }
            } else {
                $tries = (int) ($skipped[$rel] ?? 0) + 1;
                $skipped[$rel] = $tries;
                $result['failed']++;
            }

            $state['cursor'] = $rel;
        }

        $state['skipped'] = $skipped;
        $left = $this->oversizedQueue($state['cursor'] ?? '', $skipped);
        // Also count files still oversized that were cursor-skipped this cycle.
        $allLeft = $this->oversizedQueue('', $skipped);
        $result['remaining'] = count($allLeft);
        $result['skipped'] = count(array_filter($skipped, fn ($n) => (int) $n >= 2));
        $result['complete'] = $allLeft === [];

        if ($result['complete']) {
            $state['cursor'] = '';
            $state['skipped'] = [];
            $state['complete_at'] = now()->toIso8601String();
        } elseif ($left === []) {
            // Wrap cursor for next pass over remaining (not yet given up).
            $state['cursor'] = '';
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
     * @param  array<string, int>  $skipped
     * @return array<int, string>
     */
    private function oversizedQueue(string $afterRel, array $skipped): array
    {
        // Include both course trees: Hostinger may serve either path.
        $dirs = ['images/library', 'images/courses', 'admin/courses'];
        $out = [];
        foreach ($dirs as $dir) {
            $abs = public_path($dir);
            if (! is_dir($abs)) {
                continue;
            }
            foreach (scandir($abs) ?: [] as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $full = $abs . DIRECTORY_SEPARATOR . $name;
                if (! is_file($full)) {
                    continue;
                }
                $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    continue;
                }
                $stem = (string) pathinfo($name, PATHINFO_FILENAME);
                if (preg_match('/-(thumb|card|banner)$/i', $stem)) {
                    continue;
                }
                $size = (int) (@filesize($full) ?: 0);
                if ($size <= $this->maxBytes) {
                    continue;
                }
                $rel = str_replace('\\', '/', $dir . '/' . $name);
                if ((int) ($skipped[$rel] ?? 0) >= 2) {
                    continue;
                }
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

        return $filtered;
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
        // Fast path: fewer scales, flatten everything onto white, emit JPEG for jpg/webp
        // and compact palette PNG for .png URLs.
        $scales = [0.7, 0.5, 0.35, 0.25, 0.18, 0.12];
        $ok = false;
        $best = null;
        $bestSize = PHP_INT_MAX;

        foreach ($scales as $scale) {
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));
            $canvas = imagecreatetruecolor($nw, $nh);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $nw, $nh, $white);
            imagecopyresampled($canvas, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

            $tmp = $path . '.tmp';
            if (in_array($ext, ['jpg', 'jpeg'], true)) {
                @imagejpeg($canvas, $tmp, 55);
            } elseif ($ext === 'webp' && function_exists('imagewebp')) {
                @imagewebp($canvas, $tmp, 55);
            } else {
                $pal = imagecreatetruecolor($nw, $nh);
                imagecopy($pal, $canvas, 0, 0, 0, 0, $nw, $nh);
                imagetruecolortopalette($pal, false, 128);
                @imagepng($pal, $tmp, 9);
                imagedestroy($pal);
            }
            imagedestroy($canvas);

            $sz = (int) (@filesize($tmp) ?: 0);
            if ($sz <= 0) {
                @unlink($tmp);
                continue;
            }
            if ($sz <= $this->maxBytes) {
                @unlink($path);
                @rename($tmp, $path);
                $ok = true;
                break;
            }
            if ($sz < $bestSize) {
                if ($best) {
                    @unlink($best);
                }
                $best = $path . '.best';
                @rename($tmp, $best);
                $bestSize = $sz;
            } else {
                @unlink($tmp);
            }
        }

        if (! $ok && $best && is_file($best)) {
            @unlink($path);
            @rename($best, $path);
            $ok = ((@filesize($path) ?: 0) <= $this->maxBytes);
        } elseif ($best && is_file($best)) {
            @unlink($best);
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
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return;
        }
        $src = public_path($rel);
        if (is_file($src)) {
            @copy($src, $target);
        }
    }

    private function readState(string $path): array
    {
        if (! is_file($path)) {
            return ['cursor' => '', 'skipped' => []];
        }
        $decoded = json_decode((string) @file_get_contents($path), true);

        return is_array($decoded) ? $decoded : ['cursor' => '', 'skipped' => []];
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
