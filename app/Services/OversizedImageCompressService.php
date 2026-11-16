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
    public function run(int $budgetSeconds = 12, int $maxFiles = 12, string $onlyPrefix = ''): array
    {
        $deadline = microtime(true) + max(3, $budgetSeconds);
        $statePath = storage_path('app/oversized-compress-state.json');
        $state = $this->readState($statePath);
        $skipped = is_array($state['skipped'] ?? null) ? $state['skipped'] : [];
        $onlyPrefix = trim(str_replace('\\', '/', $onlyPrefix), '/');

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

        // Prefix-only runs always start from the top of that folder.
        $cursor = $onlyPrefix !== '' ? '' : ($state['cursor'] ?? '');
        $queue = $this->oversizedQueue($cursor, $skipped, $onlyPrefix);
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
                $skipped[$rel] = $tries;
                if ($tries >= 6) {
                    $result['failed']++;
                }
            } else {
                $tries = (int) ($skipped[$rel] ?? 0) + 1;
                $skipped[$rel] = $tries;
                $result['failed']++;
            }

            $state['cursor'] = $rel;
        }

        $state['skipped'] = $skipped;
        $left = $this->oversizedQueue($state['cursor'] ?? '', $skipped, $onlyPrefix);
        // Remaining = every file still over budget, including stubborn retries.
        $allOver = $this->oversizedQueue('', [], $onlyPrefix);
        $retryable = $this->oversizedQueue('', $skipped, $onlyPrefix);
        $result['remaining'] = count($allOver);
        $result['skipped'] = count(array_filter($skipped, fn ($n) => (int) $n >= 2));
        $result['complete'] = $allOver === [];

        if ($result['complete']) {
            if ($onlyPrefix === '') {
                $state['cursor'] = '';
                $state['skipped'] = [];
                $state['complete_at'] = now()->toIso8601String();
            }
        } elseif ($left === [] || $retryable === []) {
            // Wrap cursor; clear soft skips so stubborn files get another harder pass.
            $state['cursor'] = '';
            if ($retryable === [] && $allOver !== []) {
                $state['skipped'] = [];
            }
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
    private function oversizedQueue(string $afterRel, array $skipped, string $onlyPrefix = ''): array
    {
        // Full Media tree: library, profiles (Admin tab), courses, and legacy admin/courses.
        $dirs = ['images', 'admin/courses'];
        if ($onlyPrefix !== '') {
            $dirs = [$onlyPrefix];
        }
        $out = [];
        foreach ($dirs as $dir) {
            $abs = public_path($dir);
            if (! is_dir($abs)) {
                continue;
            }
            $this->collectOversized($abs, $dir, $skipped, $out);
        }
        $out = array_values(array_unique($out));
        // Library first (File Manager / SEO), then profiles, then course art.
        usort($out, static function (string $a, string $b): int {
            $prio = static function (string $r): int {
                // Banners / small public folders first (what File Manager users open).
                if (str_starts_with($r, 'images/banners/')) {
                    return 0;
                }
                if (str_starts_with($r, 'images/clients/')) {
                    return 1;
                }
                if (str_starts_with($r, 'images/library/')) {
                    return 2;
                }
                if (str_starts_with($r, 'images/profiles/')) {
                    return 3;
                }
                if (str_starts_with($r, 'admin/courses/') || str_starts_with($r, 'images/courses/')) {
                    return 4;
                }

                return 5;
            };
            $pa = $prio($a);
            $pb = $prio($b);
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }

            return $a <=> $b;
        });
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
        // Cursor file already under budget (removed from queue) — restart from top
        // instead of returning an empty list forever.
        if ($filtered === [] && $out !== [] && ! in_array($afterRel, $out, true)) {
            return $out;
        }

        return $filtered;
    }

    /**
     * @param  array<string, int>  $skipped
     * @param  array<int, string>  $out
     */
    private function collectOversized(string $absDir, string $relPrefix, array $skipped, array &$out): void
    {
        foreach (scandir($absDir) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $full = $absDir . DIRECTORY_SEPARATOR . $name;
            $rel = str_replace('\\', '/', $relPrefix . '/' . $name);
            if (is_dir($full)) {
                // Skip huge non-image document trees if present.
                if (in_array(strtolower($name), ['documents', 'pdf', 'files', 'vendor', 'node_modules'], true)) {
                    continue;
                }
                $this->collectOversized($full, $rel, $skipped, $out);
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
            // Soft-skip only briefly so nuclear retries still run.
            if ((int) ($skipped[$rel] ?? 0) >= 12) {
                continue;
            }
            $out[] = $rel;
        }
    }

    private function compressPath(string $path): bool
    {
        @ini_set('memory_limit', '512M');
        clearstatcache(true, $path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Prefer an existing small WebP sibling as the source (File Manager still shows PNG/JPG).
        $webpSibling = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
        $img = false;
        if (is_string($webpSibling) && is_file($webpSibling) && (@filesize($webpSibling) ?: 0) > 100
            && function_exists('imagecreatefromwebp')) {
            $img = @imagecreatefromwebp($webpSibling);
        }
        // imagecreatefromstring handles JPEG-bytes-saved-as-.png from prior nuclear passes.
        if (! $img) {
            $raw = @file_get_contents($path);
            if (is_string($raw) && $raw !== '') {
                $img = @imagecreatefromstring($raw);
            }
        }
        if (! $img) {
            $img = match ($ext) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($path),
                'png' => @imagecreatefrompng($path),
                'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
                default => false,
            };
        }
        if (! $img) {
            return false;
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $ok = false;
        $best = null;
        $bestSize = PHP_INT_MAX;

        // Keep shrinking until the ORIGINAL extension file is under budget (not only WebP).
        $widths = [];
        foreach ([1.0, 0.75, 0.55, 0.4, 0.3, 0.22, 0.16, 0.12, 0.08, 0.05] as $scale) {
            $widths[] = max(32, (int) round($w * $scale));
        }
        $widths = array_values(array_unique($widths));

        foreach ($widths as $nw) {
            $nh = max(1, (int) round($h * ($nw / max(1, $w))));
            $canvas = imagecreatetruecolor($nw, $nh);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $nw, $nh, $white);
            imagecopyresampled($canvas, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

            $tmp = $path . '.tmp';
            if (in_array($ext, ['jpg', 'jpeg'], true)) {
                foreach ([70, 55, 40, 28] as $q) {
                    @imagejpeg($canvas, $tmp, $q);
                    $sz = (int) (@filesize($tmp) ?: 0);
                    if ($sz > 0 && $sz <= $this->maxBytes) {
                        @unlink($path);
                        @rename($tmp, $path);
                        $ok = true;
                        break 2;
                    }
                    if ($sz > 0 && $sz < $bestSize) {
                        if ($best) {
                            @unlink($best);
                        }
                        $best = $path . '.best';
                        @copy($tmp, $best);
                        $bestSize = $sz;
                    }
                }
                @unlink($tmp);
            } elseif ($ext === 'webp' && function_exists('imagewebp')) {
                @imagewebp($canvas, $tmp, 55);
                $sz = (int) (@filesize($tmp) ?: 0);
                if ($sz > 0 && $sz <= $this->maxBytes) {
                    @unlink($path);
                    @rename($tmp, $path);
                    $ok = true;
                    imagedestroy($canvas);
                    break;
                }
                @unlink($tmp);
            } else {
                // PNG photos stay huge as PNG — try palette, then JPEG-into-.png (browsers sniff).
                foreach ([64, 32, 16] as $colors) {
                    $pal = imagecreatetruecolor($nw, $nh);
                    imagecopy($pal, $canvas, 0, 0, 0, 0, $nw, $nh);
                    imagetruecolortopalette($pal, false, $colors);
                    @imagepng($pal, $tmp, 9);
                    imagedestroy($pal);
                    $sz = (int) (@filesize($tmp) ?: 0);
                    if ($sz > 0 && $sz <= $this->maxBytes) {
                        @unlink($path);
                        @rename($tmp, $path);
                        $ok = true;
                        imagedestroy($canvas);
                        break 2;
                    }
                    if ($sz > 0 && $sz < $bestSize) {
                        if ($best) {
                            @unlink($best);
                        }
                        $best = $path . '.best';
                        @copy($tmp, $best);
                        $bestSize = $sz;
                    }
                    @unlink($tmp);
                }
                foreach ([60, 45, 30, 20] as $q) {
                    @imagejpeg($canvas, $tmp, $q);
                    $sz = (int) (@filesize($tmp) ?: 0);
                    if ($sz > 0 && $sz <= $this->maxBytes) {
                        @unlink($path);
                        @rename($tmp, $path);
                        $ok = true;
                        imagedestroy($canvas);
                        break 2;
                    }
                    if ($sz > 0 && $sz < $bestSize) {
                        if ($best) {
                            @unlink($best);
                        }
                        $best = $path . '.best';
                        @copy($tmp, $best);
                        $bestSize = $sz;
                    }
                    @unlink($tmp);
                }
            }
            imagedestroy($canvas);
            if ($ok) {
                break;
            }
        }

        // Last resort: if a small WebP sibling exists, copy it over the original path
        // so File Manager size drops (site already prefers .webp via optimized tags).
        if (! $ok) {
            $webpSibling = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
            if (is_string($webpSibling) && is_file($webpSibling)) {
                $wsz = (int) (@filesize($webpSibling) ?: 0);
                if ($wsz > 0 && $wsz <= $this->maxBytes) {
                    if (@copy($webpSibling, $path)) {
                        $ok = true;
                    }
                }
            }
        }

        // Only keep a "best" candidate when it actually beats the current file size.
        if (! $ok && $best && is_file($best)) {
            $bestSz = (int) (@filesize($best) ?: 0);
            $curSz = (int) (@filesize($path) ?: 0);
            if ($bestSz > 0 && ($curSz === 0 || $bestSz < $curSz)) {
                @unlink($path);
                @rename($best, $path);
            } else {
                @unlink($best);
            }
            $ok = ((@filesize($path) ?: 0) <= $this->maxBytes);
        } elseif ($best && is_file($best)) {
            @unlink($best);
        }

        // Nuclear: force a tiny JPEG into the original path (MIME sniffing still displays).
        if (! $ok && $img) {
            foreach ([800, 600, 400, 280, 160] as $nw) {
                $nh = max(1, (int) round($h * ($nw / max(1, $w))));
                $canvas = imagecreatetruecolor($nw, $nh);
                $white = imagecolorallocate($canvas, 255, 255, 255);
                imagefilledrectangle($canvas, 0, 0, $nw, $nh, $white);
                imagecopyresampled($canvas, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                $tmp = $path . '.nuke';
                foreach ([50, 35, 22] as $q) {
                    @imagejpeg($canvas, $tmp, $q);
                    $sz = (int) (@filesize($tmp) ?: 0);
                    if ($sz > 0 && $sz <= $this->maxBytes) {
                        @unlink($path);
                        @rename($tmp, $path);
                        $ok = true;
                        break 2;
                    }
                }
                @unlink($tmp);
                imagedestroy($canvas);
                if ($ok) {
                    break;
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
