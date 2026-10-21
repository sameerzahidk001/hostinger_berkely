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
                $shared = [
                    'settings' => SiteSettings::query()->first(),
                    'widgets' => Widget::all(),
                    'menus' => Menu::with('children')->whereNull('parent_id')->orderBy('menu_order')->get(),
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
