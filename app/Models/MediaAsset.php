<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MediaAsset extends Model
{
    protected $fillable = [
        'path',
        'alt',
        'title',
        'caption',
        'description',
    ];

    public static function ensureTable(): bool
    {
        if (! Schema::hasTable('media_assets')) {
            Schema::create('media_assets', function (Blueprint $table) {
                $table->id();
                $table->string('path', 500)->unique();
                $table->string('alt', 255)->nullable();
                $table->string('title', 255)->nullable();
                $table->text('caption')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('media_assets')) {
            return false;
        }

        // Additive: older live tables may lack description.
        if (! Schema::hasColumn('media_assets', 'description')) {
            Schema::table('media_assets', function (Blueprint $table) {
                $table->text('description')->nullable()->after('caption');
            });
        }

        return true;
    }

    public static function normalizePath(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        $path = str_replace(
            [
                'https://eduberkeley.com/public/',
                'http://eduberkeley.com/public/',
                'https://eduberkeley.com/',
                'http://eduberkeley.com/',
            ],
            '',
            $path
        );

        if (preg_match('#^https?://#i', $path)) {
            $parts = parse_url($path);
            $path = (string) ($parts['path'] ?? '');
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }

        return $path;
    }

    public static function forPath(?string $path): ?self
    {
        if (! static::ensureTable()) {
            return null;
        }

        $rel = static::normalizePath($path);
        if ($rel === '') {
            return null;
        }

        return static::query()->where('path', $rel)->first();
    }

    public static function upsertForPath(string $path, array $attrs): self
    {
        static::ensureTable();
        $rel = static::normalizePath($path);

        $row = static::query()->firstOrNew(['path' => $rel]);
        foreach (['alt', 'title', 'caption', 'description'] as $key) {
            if (array_key_exists($key, $attrs)) {
                $val = $attrs[$key];
                $row->{$key} = is_string($val) ? (trim($val) !== '' ? trim($val) : null) : $val;
            }
        }
        $row->save();

        return $row;
    }

    /**
     * Prefer non-empty linked-source values when media row fields are empty.
     *
     * @param  array<string, mixed>|null  $row
     * @param  array{alt?:?string,title?:?string,caption?:?string,description?:?string}  $linked
     * @return array{alt:?string,title:?string,caption:?string,description:?string}
     */
    public static function mergeDisplayMeta(?array $row = null, array $linked = []): array
    {
        $src = $row ?? [];
        $out = [
            'alt' => $src['alt'] ?? null,
            'title' => $src['title'] ?? null,
            'caption' => $src['caption'] ?? null,
            'description' => $src['description'] ?? null,
        ];

        foreach (['alt', 'title', 'caption', 'description'] as $key) {
            if (trim((string) ($out[$key] ?? '')) === '' && trim((string) ($linked[$key] ?? '')) !== '') {
                $out[$key] = trim((string) $linked[$key]);
            }
        }

        return $out;
    }
}
