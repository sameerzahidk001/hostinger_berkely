<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\SiteSettings;
use App\Models\Widget;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class SiteSettingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            static $shared = null;
            if ($shared === null) {
                $settings = SiteSettings::query()->first();
                $menus = Menu::with('children')->whereNull('parent_id')->orderBy('menu_order')->get();
                $headerGroup = trim((string) optional($settings)->header_menu);
                $hasVerify = $menus->contains(function ($menu) {
                    $blob = strtolower(trim((string) ($menu->name ?? '')) . ' ' . (string) ($menu->link ?? ''));
                    foreach (($menu->children ?? []) as $child) {
                        $blob .= ' ' . strtolower(trim((string) ($child->name ?? '')) . ' ' . (string) ($child->link ?? ''));
                    }

                    return str_contains($blob, 'ecert-verify')
                        || str_contains($blob, 'verify-certificate')
                        || str_contains($blob, 'verify certificate');
                });
                if ($headerGroup !== '' && ! $hasVerify) {
                    $verify = new Menu([
                        'name' => 'Verify Certificate',
                        'link' => '/ecert-verify',
                        'menu_group' => $headerGroup,
                        'parent_id' => null,
                        'menu_order' => 999,
                        'is_active' => 1,
                    ]);
                    $verify->setRelation('children', collect());
                    $menus = $menus->push($verify)->values();
                }
                $shared = [
                    'settings' => $settings,
                    'widgets' => Widget::all(),
                    'menus' => $menus,
                ];
            }
            foreach ($shared as $key => $value) {
                if (! $view->offsetExists($key)) {
                    $view->with($key, $value);
                }
            }
        });
    }
}
