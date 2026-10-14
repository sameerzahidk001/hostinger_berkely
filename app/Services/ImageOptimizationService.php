<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageOptimizationService
{
    /** @var array<string, int> */
    private array $variantWidths = [
        'thumb' => 400,
        'card' => 800,
        'banner' => 1600,
    ];

    public function optimizeUploadedFile(UploadedFile $file, string $destinationPath, ?string $preferredName = null): array
    {
        $originalName = $preferredName ?: $file->getClientOriginalName();
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $slug = Str::slug($base) ?: 'image';
        $fileName = $slug . '-' . time() . '.' . $ext;

        if (! is_dir($destinationPath)) {
            @mkdir($destinationPath, 0755, true);
        }

        $target = rtrim($destinationPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
        $file->move($destinationPath, $fileName);

        $variants = [];
        if ($this->gdAvailable() && $this->isRasterImage($ext)) {
            $this->compressInPlace($target, $ext);
            $variants = $this->createVariants($target, $destinationPath, $slug, $ext);
        }

        return [
            'filename' => $fileName,
            'path' => $target,
            'variants' => $variants,
        ];
    }

    public function optimizeExistingPath(string $absolutePath): array
    {
        if (! is_file($absolutePath) || ! $this->gdAvailable()) {
            return [];
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (! $this->isRasterImage($ext)) {
            return [];
        }

        $this->compressInPlace($absolutePath, $ext);
        $dir = dirname($absolutePath);
        $slug = pathinfo($absolutePath, PATHINFO_FILENAME);

        return $this->createVariants($absolutePath, $dir, $slug, $ext);
    }

    public function webpUrl(?string $publicPath): ?string
    {
        $publicPath = ltrim((string) $publicPath, '/');
        if ($publicPath === '') {
            return null;
        }

        $webpRel = preg_replace('/\.(jpe?g|png)$/i', '.webp', $publicPath);
        if (! $webpRel || $webpRel === $publicPath) {
            return null;
        }

        $abs = public_path($webpRel);
        if (! is_file($abs)) {
            return null;
        }

        return asset($webpRel);
    }

    private function compressInPlace(string $path, string $ext): void
    {
        try {
            $image = $this->loadImage($path, $ext);
            if (! $image) {
                return;
            }

            $this->saveImage($image, $path, $ext, 82);
            $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
            if ($webpPath && function_exists('imagewebp')) {
                @imagewebp($image, $webpPath, 80);
            }
            imagedestroy($image);
        } catch (\Throwable $e) {
            Log::warning('Image compress failed: ' . $e->getMessage());
        }
    }

    /**
     * @return array<string, string> keyed by variant name => absolute path
     */
    private function createVariants(string $sourcePath, string $dir, string $slug, string $ext): array
    {
        $out = [];
        $source = $this->loadImage($sourcePath, $ext);
        if (! $source) {
            return $out;
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);

        foreach ($this->variantWidths as $name => $maxW) {
            if ($srcW <= $maxW) {
                continue;
            }

            $newW = $maxW;
            $newH = (int) max(1, round($srcH * ($newW / $srcW)));
            $canvas = imagecreatetruecolor($newW, $newH);
            if ($ext === 'png') {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
            }
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

            $variantName = $slug . '-' . $name . '.' . $ext;
            $variantPath = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $variantName;
            $this->saveImage($canvas, $variantPath, $ext, 80);

            if (function_exists('imagewebp')) {
                @imagewebp($canvas, preg_replace('/\.(jpe?g|png)$/i', '.webp', $variantPath), 78);
            }

            imagedestroy($canvas);
            $out[$name] = $variantPath;
        }

        imagedestroy($source);

        return $out;
    }

    private function loadImage(string $path, string $ext)
    {
        return match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'gif' => function_exists('imagecreatefromgif') ? @imagecreatefromgif($path) : false,
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };
    }

    private function saveImage($image, string $path, string $ext, int $quality): void
    {
        match ($ext) {
            'jpg', 'jpeg' => imagejpeg($image, $path, $quality),
            'png' => imagepng($image, $path, (int) max(0, min(9, round((100 - $quality) / 10)))),
            'webp' => function_exists('imagewebp') ? imagewebp($image, $path, $quality) : null,
            default => null,
        };
    }

    private function isRasterImage(string $ext): bool
    {
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    private function gdAvailable(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatetruecolor');
    }
}
