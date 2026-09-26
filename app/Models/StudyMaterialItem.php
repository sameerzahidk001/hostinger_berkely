<?php

namespace App\Models;

use App\Services\ZohoLmsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class StudyMaterialItem extends Model
{
    protected $fillable = [
        'folder_id',
        'parent_id',
        'type',
        'name',
        'disk_path',
        'original_name',
        'mime',
        'icon_type',
        'size',
        'external_url',
        'sort_order',
        'allow_download',
    ];

    protected $casts = [
        'allow_download' => 'boolean',
    ];

    public static function ensureIconTypeColumn(): bool
    {
        if (! Schema::hasTable('study_material_items')) {
            return false;
        }
        if (Schema::hasColumn('study_material_items', 'icon_type')) {
            return true;
        }

        Schema::table('study_material_items', function (Blueprint $table) {
            $table->string('icon_type', 32)->nullable()->after('mime');
        });

        return Schema::hasColumn('study_material_items', 'icon_type');
    }

    public static function iconTypeOptions(): array
    {
        return [
            'auto' => 'Auto-detect from file',
            'pdf' => 'PDF',
            'word' => 'Word',
            'excel' => 'Excel',
            'ppt' => 'PowerPoint',
            'video' => 'Video',
            'audio' => 'Audio',
            'image' => 'Image',
            'zip' => 'ZIP / Archive',
            'file' => 'Generic file',
        ];
    }

    public static function normalizeIconType(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));
        if ($value === '' || $value === 'auto') {
            return null;
        }

        return array_key_exists($value, self::iconTypeOptions()) ? $value : null;
    }

    public static function guessIconTypeFromName(?string $name, ?string $mime = null): ?string
    {
        $mime = strtolower((string) $mime);
        $name = strtolower(trim((string) $name));

        if (str_starts_with($mime, 'video/') || preg_match('/\.(mp4|webm|ogg|mov|m4v|avi)(\s|$)/', $name)) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/') || preg_match('/\.(mp3|wav|m4a|aac|flac)(\s|$)/', $name)) {
            return 'audio';
        }
        if (str_contains($mime, 'pdf') || str_ends_with($name, '.pdf')) {
            return 'pdf';
        }
        if (str_contains($mime, 'word') || preg_match('/\.(docx?|rtf)(\s|$)/', $name)) {
            return 'word';
        }
        if (str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet') || preg_match('/\.(xlsx?|csv)(\s|$)/', $name)) {
            return 'excel';
        }
        if (str_contains($mime, 'powerpoint') || str_contains($mime, 'presentation') || preg_match('/\.(pptx?)(\s|$)/', $name)) {
            return 'ppt';
        }
        if (str_starts_with($mime, 'image/') || preg_match('/\.(png|jpe?g|gif|webp|bmp|svg)(\s|$)/', $name)) {
            return 'image';
        }
        if (str_contains($mime, 'zip') || preg_match('/\.(zip|rar|7z|tar|gz)(\s|$)/', $name)) {
            return 'zip';
        }

        return null;
    }

    public function resolvedIconType(): string
    {
        $chosen = self::normalizeIconType($this->icon_type);
        if ($chosen) {
            return $chosen;
        }

        return self::guessIconTypeFromName(
            $this->name . ' ' . ($this->original_name ?? ''),
            $this->mime
        ) ?: 'file';
    }

    public function iconFaClass(): string
    {
        return match ($this->resolvedIconType()) {
            'pdf' => 'fa-file-pdf-o text-danger',
            'word' => 'fa-file-word-o text-primary',
            'excel' => 'fa-file-excel-o text-success',
            'ppt' => 'fa-file-powerpoint-o text-warning',
            'video' => 'fa-file-video-o text-info',
            'audio' => 'fa-file-audio-o text-info',
            'image' => 'fa-file-image-o text-success',
            'zip' => 'fa-file-archive-o text-muted',
            default => $this->isExternal() ? 'fa-cloud text-info' : 'fa-file-o text-muted',
        };
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(StudyMaterialFolder::class, 'folder_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    /**
     * Natural sort: "1, 2, 10" instead of lexicographic "1, 10, 2".
     * Honors sort_order first when set.
     */
    public static function naturalSort($items)
    {
        $collection = $items instanceof \Illuminate\Support\Collection
            ? $items
            : collect($items);

        return $collection->sort(function ($a, $b) {
            $order = ((int) ($a->sort_order ?? 0)) <=> ((int) ($b->sort_order ?? 0));
            if ($order !== 0) {
                return $order;
            }

            return strnatcasecmp((string) ($a->name ?? ''), (string) ($b->name ?? ''));
        })->values();
    }

    public function isFolder(): bool
    {
        return $this->type === 'folder';
    }

    public function isExternal(): bool
    {
        return filled($this->external_url);
    }

    public function publicPath(): ?string
    {
        return $this->disk_path ? public_path($this->disk_path) : null;
    }

    public function portalPreviewUrl(): string
    {
        if ($this->portalKind() === 'video' && $this->isExternal()) {
            $id = app(ZohoLmsService::class)->workDriveResourceIdFromUrl($this->external_url);
            if ($id) {
                return 'https://workdrive.zohoexternal.com/preview/' . $id . '?hidetheme=true';
            }
        }

        return route('user.study-materials.file', ['itemId' => $this->id, 'raw' => 1]);
    }

    public function allowsDownload(): bool
    {
        return $this->type === 'file' && $this->allow_download !== false;
    }

    public function portalDownloadUrl(): string
    {
        return route('user.study-materials.file', ['itemId' => $this->id, 'download' => 1]);
    }

    public function portalKind(): string
    {
        $icon = $this->resolvedIconType();
        if (in_array($icon, ['video', 'pdf', 'image', 'audio'], true)) {
            return $icon;
        }

        $mime = strtolower((string) $this->mime);
        $name = strtolower(trim($this->name . ' ' . ($this->original_name ?? '')));

        if (str_starts_with($mime, 'video/') || preg_match('/\.(mp4|webm|ogg|mov|m4v|avi)(\s|$)/', $name)) {
            return 'video';
        }
        if (str_contains($mime, 'pdf') || str_ends_with($name, '.pdf')) {
            return 'pdf';
        }
        if (str_starts_with($mime, 'image/') || preg_match('/\.(png|jpe?g|gif|webp|bmp|svg)$/', $name)) {
            return 'image';
        }
        if (str_starts_with($mime, 'audio/') || preg_match('/\.(mp3|wav|m4a)$/', $name)) {
            return 'audio';
        }
        if ($this->isExternal() && ! preg_match('/\.(pdf|docx?|pptx?|xlsx?|csv|txt|zip|rtf)$/', $name)) {
            return 'video';
        }

        return 'file';
    }

    public function portalPosterUrl(): ?string
    {
        if ($this->portalKind() !== 'video' || ! $this->external_url) {
            return null;
        }

        $id = app(ZohoLmsService::class)->workDriveResourceIdFromUrl($this->external_url);

        return $id ? 'https://previewengine.zohoexternal.com/thumbnail/WD/' . $id . '?size=l' : null;
    }
}
