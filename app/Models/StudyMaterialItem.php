<?php

namespace App\Models;

use App\Services\ZohoLmsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\File;

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
        'size',
        'external_url',
        'sort_order',
        'allow_download',
    ];

    protected $casts = [
        'allow_download' => 'boolean',
    ];

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
        if ($this->isExternal() && !preg_match('/\.(pdf|docx?|pptx?|xlsx?|csv|txt|zip|rtf)$/', $name)) {
            return 'video';
        }

        return 'file';
    }

    public function portalPosterUrl(): ?string
    {
        if ($this->portalKind() !== 'video' || !$this->external_url) {
            return null;
        }

        $id = app(ZohoLmsService::class)->workDriveResourceIdFromUrl($this->external_url);

        return $id ? 'https://previewengine.zohoexternal.com/thumbnail/WD/' . $id . '?size=l' : null;
    }
}
