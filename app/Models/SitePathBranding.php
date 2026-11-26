<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SitePathBranding extends Model
{
    protected $table = 'site_path_brandings';

    protected $fillable = [
        'name',
        'path_prefix',
        'logo',
        'menu_group',
        'home_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function ensureTable(): bool
    {
        try {
            if (Schema::hasTable('site_path_brandings')) {
                return true;
            }

            Schema::create('site_path_brandings', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('path_prefix', 120)->unique();
                $table->string('logo', 255)->nullable();
                $table->string('menu_group', 120)->nullable();
                $table->string('home_url', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            return Schema::hasTable('site_path_brandings');
        } catch (\Throwable $e) {
            return Schema::hasTable('site_path_brandings');
        }
    }

    public static function normalizePrefix(?string $prefix): string
    {
        $prefix = strtolower(trim((string) $prefix));
        $prefix = trim(str_replace('\\', '/', $prefix), '/');
        $prefix = preg_replace('#/+#', '/', $prefix) ?? $prefix;
        $parts = array_values(array_filter(explode('/', $prefix), fn ($p) => $p !== ''));
        $parts = array_map(fn ($p) => preg_replace('/[^a-z0-9\-]+/', '-', $p) ?: '', $parts);
        $parts = array_values(array_filter($parts, fn ($p) => $p !== '' && $p !== '-'));

        return implode('/', $parts);
    }

    public function matchesPath(string $path): bool
    {
        $prefix = self::normalizePrefix($this->path_prefix);
        if ($prefix === '') {
            return false;
        }

        $path = trim(str_replace('\\', '/', $path), '/');
        $path = strtolower($path);

        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }

    public function resolvedHomeUrl(): string
    {
        $custom = trim((string) $this->home_url);
        if ($custom !== '') {
            if (preg_match('#^https?://#i', $custom)) {
                return $custom;
            }

            return url('/' . trim($custom, '/'));
        }

        $prefix = self::normalizePrefix($this->path_prefix);

        return $prefix !== '' ? url('/' . $prefix) : url('/');
    }

    public function logoUrl(): ?string
    {
        $logo = trim((string) $this->logo);
        if ($logo === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $logo) || str_starts_with($logo, '/')) {
            return $logo;
        }

        return asset('images/' . ltrim($logo, '/'));
    }
}
