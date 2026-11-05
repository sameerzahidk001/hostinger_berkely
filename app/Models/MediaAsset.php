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
    ];

    public static function ensureTable(): bool
    {
        if (Schema::hasTable('media_assets')) {
            return true;
        }

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500)->unique();
            $table->string('alt', 255)->nullable();
            $table->string('title', 255)->nullable();
            $table->text('caption')->nullable();
            $table->timestamps();
        });

        return Schema::hasTable('media_assets');
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
        foreach (['alt', 'title', 'caption'] as $key) {
            if (array_key_exists($key, $attrs)) {
                $val = $attrs[$key];
                $row->{$key} = is_string($val) ? (trim($val) !== '' ? trim($val) : null) : $val;
            }
        }
        $row->save();

        return $row;
    }
}
