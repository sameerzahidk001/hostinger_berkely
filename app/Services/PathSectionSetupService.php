<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Page;
use App\Models\SitePathBranding;
use Illuminate\Support\Facades\Schema;

class PathSectionSetupService
{
    /**
     * Idempotent setup for Journal + Events path hubs (pages, menus, logos, branding).
     * All rows are normal admin-editable CMS/Menu/Path Branding records.
     *
     * @return array{journal: array<string, mixed>, events: array<string, mixed>}
     */
    public function ensureDefaults(): array
    {
        $this->migrateJournalPathToShortUrl();

        return [
            'journal' => $this->ensureSection([
                'name' => 'Academic Journal',
                'prefix' => 'journal',
                'menu_group' => 'journal',
                'logo_file' => 'path-brand-journal.svg',
                'logo_label' => 'Academic Journal',
                'logo_color' => '#0B3A6E',
                // CellPress-style public nav: Journals / Publish / News & events / About (+ Contact)
                'children' => [
                    ['name' => 'Journals', 'url' => 'journals'],
                    ['name' => 'Publish', 'url' => 'publish'],
                    ['name' => 'News & events', 'url' => 'news-events'],
                    ['name' => 'About', 'url' => 'about'],
                    ['name' => 'For Authors', 'url' => 'for-authors'],
                    ['name' => 'Contact', 'url' => 'contact'],
                ],
            ]),
            'events' => $this->ensureSection([
                'name' => 'Events',
                'prefix' => 'events',
                'menu_group' => 'events',
                'logo_file' => 'path-brand-events.svg',
                'logo_label' => 'Events',
                'logo_color' => '#7A1F2B',
                'children' => [
                    ['name' => 'About', 'url' => 'about'],
                    ['name' => 'Upcoming', 'url' => 'upcoming'],
                    ['name' => 'Contact', 'url' => 'contact'],
                ],
            ]),
        ];
    }

    /**
     * Rename legacy /academic-journal hub + branding to /journal (idempotent).
     */
    public function migrateJournalPathToShortUrl(): void
    {
        try {
            if (Schema::hasTable('pages')) {
                $oldHub = Page::query()->whereNull('parent_id')->where('url', 'academic-journal')->first();
                $newHub = Page::query()->whereNull('parent_id')->where('url', 'journal')->first();
                if ($oldHub && ! $newHub) {
                    $oldHub->url = 'journal';
                    $oldHub->save();
                }
            }

            if (SitePathBranding::ensureTable()) {
                $oldBrand = SitePathBranding::query()->where('path_prefix', 'academic-journal')->first();
                $newBrand = SitePathBranding::query()->where('path_prefix', 'journal')->first();
                if ($oldBrand && ! $newBrand) {
                    $oldBrand->path_prefix = 'journal';
                    $oldBrand->home_url = '/journal';
                    if (($oldBrand->menu_group ?? '') === 'academic-journal') {
                        $oldBrand->menu_group = 'journal';
                    }
                    $oldBrand->save();
                } elseif ($oldBrand && $newBrand && (int) $oldBrand->id !== (int) $newBrand->id) {
                    $oldBrand->is_active = false;
                    $oldBrand->save();
                }
            }

            if (Schema::hasTable('menus')) {
                Menu::query()
                    ->where('menu_group', 'academic-journal')
                    ->update(['menu_group' => 'journal']);

                Menu::query()
                    ->where('link', 'like', '%/academic-journal%')
                    ->get()
                    ->each(function (Menu $menu) {
                        $menu->link = str_replace('/academic-journal', '/journal', (string) $menu->link);
                        $menu->save();
                    });
            }
        } catch (\Throwable $e) {
            // Keep setup resilient on partial schemas.
        }
    }

    /**
     * @param  array{
     *   name: string,
     *   prefix: string,
     *   menu_group: string,
     *   logo_file: string,
     *   logo_label: string,
     *   logo_color: string,
     *   children: list<array{name: string, url: string}>
     * }  $config
     * @return array<string, mixed>
     */
    private function ensureSection(array $config): array
    {
        $hub = $this->ensureHubPage($config['name'], $config['prefix']);
        $childPages = [];
        foreach ($config['children'] as $child) {
            $childPages[$child['url']] = $this->ensureChildPage($hub, $child['name'], $child['url']);
        }

        $this->ensureDummyLogo($config['logo_file'], $config['logo_label'], $config['logo_color']);
        $this->ensureMenuGroup($config['menu_group'], $config['name'], $config['prefix'], $config['children']);
        $branding = $this->ensureBranding($config);

        return [
            'hub_page_id' => $hub?->id,
            'child_page_ids' => collect($childPages)->map->id->filter()->values()->all(),
            'branding_id' => $branding?->id,
            'prefix' => $config['prefix'],
            'menu_group' => $config['menu_group'],
        ];
    }

    private function ensureHubPage(string $name, string $url): ?Page
    {
        if (! Schema::hasTable('pages')) {
            return null;
        }

        $page = Page::query()
            ->whereNull('parent_id')
            ->where('url', $url)
            ->first();

        if ($page) {
            if (function_exists('assign_column_if_exists')) {
                assign_column_if_exists($page, 'status', 1);
                if ($page->isDirty()) {
                    $page->save();
                }
            }

            return $page;
        }

        try {
            $page = new Page();
            $page->page_name = $name;
            $page->url = $url;
            $page->parent_id = null;
            $page->save();
            if (function_exists('assign_column_if_exists')) {
                assign_column_if_exists($page, 'status', 1);
                if ($page->isDirty()) {
                    $page->save();
                }
            }

            return $page->fresh() ?: $page;
        } catch (\Throwable $e) {
            return Page::query()->whereNull('parent_id')->where('url', $url)->first();
        }
    }

    private function ensureChildPage(?Page $hub, string $name, string $url): ?Page
    {
        if (! $hub || ! Schema::hasTable('pages')) {
            return null;
        }

        $page = Page::query()
            ->where('parent_id', $hub->id)
            ->where('url', $url)
            ->first();

        if ($page) {
            if (function_exists('assign_column_if_exists')) {
                assign_column_if_exists($page, 'status', 1);
                if ($page->isDirty()) {
                    $page->save();
                }
            }

            return $page;
        }

        try {
            $page = new Page();
            $page->page_name = $name;
            $page->url = $url;
            $page->parent_id = $hub->id;
            $page->save();
            if (function_exists('assign_column_if_exists')) {
                assign_column_if_exists($page, 'status', 1);
                if ($page->isDirty()) {
                    $page->save();
                }
            }

            return $page->fresh() ?: $page;
        } catch (\Throwable $e) {
            return Page::query()->where('parent_id', $hub->id)->where('url', $url)->first();
        }
    }

    /**
     * @param  list<array{name: string, url: string}>  $children
     */
    private function ensureMenuGroup(string $group, string $hubName, string $prefix, array $children): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        $hubLink = url('/' . $prefix);
        $home = Menu::query()
            ->where('menu_group', $group)
            ->whereNull('parent_id')
            ->where('name', 'Home')
            ->first();

        if (! $home) {
            $home = new Menu([
                'name' => 'Home',
                'link' => $hubLink,
                'parent_id' => null,
                'menu_order' => 1,
                'is_active' => 1,
                'menu_group' => $group,
            ]);
            $home->save();
        } else {
            $home->link = $hubLink;
            $home->is_active = 1;
            $home->save();
        }

        $order = 2;
        foreach ($children as $child) {
            $link = url('/' . $prefix . '/' . $child['url']);
            $item = Menu::query()
                ->where('menu_group', $group)
                ->whereNull('parent_id')
                ->where(function ($q) use ($child, $link) {
                    $q->where('name', $child['name'])->orWhere('link', $link);
                })
                ->first();

            if (! $item) {
                $item = new Menu([
                    'name' => $child['name'],
                    'link' => $link,
                    'parent_id' => null,
                    'menu_order' => $order,
                    'is_active' => 1,
                    'menu_group' => $group,
                ]);
                $item->save();
            } else {
                $item->name = $child['name'];
                $item->link = $link;
                $item->menu_order = $order;
                $item->is_active = 1;
                $item->save();
            }
            $order++;
        }

        // Login / Register entries for this path group
        foreach ([
            ['name' => 'Login', 'url' => 'login', 'order' => 90],
            ['name' => 'Register', 'url' => 'register', 'order' => 91],
        ] as $authItem) {
            $link = url('/' . $prefix . '/' . $authItem['url']);
            $item = Menu::query()
                ->where('menu_group', $group)
                ->whereNull('parent_id')
                ->where('name', $authItem['name'])
                ->first();

            if (! $item) {
                $item = new Menu([
                    'name' => $authItem['name'],
                    'link' => $link,
                    'parent_id' => null,
                    'menu_order' => $authItem['order'],
                    'is_active' => 1,
                    'menu_group' => $group,
                ]);
                $item->save();
            } else {
                $item->link = $link;
                $item->is_active = 1;
                $item->menu_order = $authItem['order'];
                $item->save();
            }
        }

        // Enquire / Enquirer is school-site only — never show on journal/events path menus.
        if (in_array($group, ['journal', 'events', 'academic-journal'], true)) {
            Menu::query()
                ->where('menu_group', $group)
                ->where(function ($q) {
                    $q->whereRaw('LOWER(name) = ?', ['enquire'])
                        ->orWhereRaw('LOWER(name) = ?', ['enquirer']);
                })
                ->update(['is_active' => 0]);
        }

        unset($hubName);
    }

    /**
     * @param  array{
     *   name: string,
     *   prefix: string,
     *   menu_group: string,
     *   logo_file: string
     * }  $config
     */
    private function ensureBranding(array $config): ?SitePathBranding
    {
        if (! SitePathBranding::ensureTable()) {
            return null;
        }

        $prefix = SitePathBranding::normalizePrefix($config['prefix']);
        $row = SitePathBranding::query()->where('path_prefix', $prefix)->first();

        $defaultCopyright = $prefix === 'journal'
            ? 'Copyright © ' . date('Y') . ' Berkeley Press | Academic Journal'
            : ('Copyright © ' . date('Y') . ' ' . ($config['name'] ?? 'EduBerkeley'));

        if (! $row) {
            $row = SitePathBranding::create([
                'name' => $config['name'],
                'path_prefix' => $prefix,
                'logo' => $config['logo_file'],
                'menu_group' => $config['menu_group'],
                'home_url' => '/' . $prefix,
                'is_active' => true,
                'custom_footer' => true,
                'footer_menu_group' => $config['menu_group'],
                'footer_copyright' => $defaultCopyright,
            ]);

            return $row;
        }

        // Ensure button always wires the section menu + home; keep an existing custom logo.
        $row->menu_group = $config['menu_group'];
        $row->home_url = '/' . $prefix;
        $row->is_active = true;
        if (! filled($row->logo)) {
            $row->logo = $config['logo_file'];
        }
        if (! filled($row->name)) {
            $row->name = $config['name'];
        }
        // Path sites must not fall back to the school footer widgets / WhatsApp strip.
        if (Schema::hasColumn('site_path_brandings', 'custom_footer')) {
            $row->custom_footer = true;
        }
        if (Schema::hasColumn('site_path_brandings', 'footer_menu_group') && ! filled($row->footer_menu_group)) {
            $row->footer_menu_group = $config['menu_group'];
        }
        if (Schema::hasColumn('site_path_brandings', 'footer_copyright') && ! filled($row->footer_copyright)) {
            $row->footer_copyright = $defaultCopyright;
        }
        $row->save();

        return $row;
    }

    private function ensureDummyLogo(string $fileName, string $label, string $color): void
    {
        $path = public_path('images/' . $fileName);
        if (is_file($path)) {
            return;
        }

        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $initial = htmlspecialchars(mb_substr($label, 0, 1), ENT_QUOTES, 'UTF-8');
        $safeColor = preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : '#0B3A6E';
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="420" height="96" viewBox="0 0 420 96" role="img" aria-label="{$safeLabel}">
  <rect width="420" height="96" fill="#ffffff"/>
  <rect x="8" y="18" width="60" height="60" rx="8" fill="{$safeColor}"/>
  <text x="38" y="56" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="22" font-weight="700" fill="#ffffff">{$initial}</text>
  <text x="84" y="58" font-family="Arial, Helvetica, sans-serif" font-size="28" font-weight="700" fill="{$safeColor}">{$safeLabel}</text>
</svg>
SVG;

        @file_put_contents($path, $svg);
    }
}
