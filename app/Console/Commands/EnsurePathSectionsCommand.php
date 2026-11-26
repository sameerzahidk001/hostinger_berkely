<?php

namespace App\Console\Commands;

use App\Services\PathSectionSetupService;
use Illuminate\Console\Command;

class EnsurePathSectionsCommand extends Command
{
    protected $signature = 'path-sections:ensure';

    protected $description = 'Create/update Academic Journal + Events blank pages, menus, logos, and path branding (admin-editable)';

    public function handle(PathSectionSetupService $setup): int
    {
        $result = $setup->ensureDefaults();
        foreach ($result as $key => $info) {
            $this->info(sprintf(
                '%s: hub page #%s, branding #%s, prefix /%s, menu group %s',
                ucfirst($key),
                $info['hub_page_id'] ?? 'n/a',
                $info['branding_id'] ?? 'n/a',
                $info['prefix'] ?? '',
                $info['menu_group'] ?? ''
            ));
        }

        $this->info('Done. Edit via Admin → Pages, Menu, Path Branding.');

        return self::SUCCESS;
    }
}
