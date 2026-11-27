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
        'custom_footer',
        'footer_menu_group',
        'footer_copyright',
        'footer_bg',
        'footer_text_color',
        'footer_title_color',
        'footer_social',
        'footer_whatsapp',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'custom_footer' => 'boolean',
        'footer_social' => 'array',
        'footer_whatsapp' => 'array',
    ];

    public static function ensureTable(): bool
    {
        try {
            if (! Schema::hasTable('site_path_brandings')) {
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
            }

            self::ensureFooterColumns();

            return Schema::hasTable('site_path_brandings');
        } catch (\Throwable $e) {
            return Schema::hasTable('site_path_brandings');
        }
    }

    public static function ensureFooterColumns(): void
    {
        if (! Schema::hasTable('site_path_brandings')) {
            return;
        }

        $cols = [
            'custom_footer' => fn (Blueprint $t) => $t->boolean('custom_footer')->default(false),
            'footer_menu_group' => fn (Blueprint $t) => $t->string('footer_menu_group', 120)->nullable(),
            'footer_copyright' => fn (Blueprint $t) => $t->string('footer_copyright', 255)->nullable(),
            'footer_bg' => fn (Blueprint $t) => $t->string('footer_bg', 32)->nullable(),
            'footer_text_color' => fn (Blueprint $t) => $t->string('footer_text_color', 32)->nullable(),
            'footer_title_color' => fn (Blueprint $t) => $t->string('footer_title_color', 32)->nullable(),
            'footer_social' => fn (Blueprint $t) => $t->json('footer_social')->nullable(),
            'footer_whatsapp' => fn (Blueprint $t) => $t->json('footer_whatsapp')->nullable(),
        ];

        foreach ($cols as $name => $adder) {
            try {
                if (! Schema::hasColumn('site_path_brandings', $name)) {
                    Schema::table('site_path_brandings', function (Blueprint $table) use ($adder) {
                        $adder($table);
                    });
                }
            } catch (\Throwable $e) {
                // keep additive & safe on partial schemas
            }
        }
    }

    public function usesCustomFooter(): bool
    {
        return (bool) ($this->custom_footer ?? false);
    }

    /**
     * @return list<array{label:string,url:string}>
     */
    public function footerWhatsappLinks(): array
    {
        $rows = is_array($this->footer_whatsapp) ? $this->footer_whatsapp : [];
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $url = trim((string) ($row['url'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            if ($url === '') {
                continue;
            }
            $out[] = [
                'label' => $label !== '' ? $label : 'WhatsApp',
                'url' => $url,
            ];
        }

        return $out;
    }

    public function footerSocialUrl(string $platform): string
    {
        $social = is_array($this->footer_social) ? $this->footer_social : [];

        return trim((string) ($social[$platform . '_url'] ?? $social[$platform] ?? ''));
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
