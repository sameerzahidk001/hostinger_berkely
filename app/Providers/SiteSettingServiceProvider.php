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
