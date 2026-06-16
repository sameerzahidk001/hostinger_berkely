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
        $settings = SiteSettings::first();
        $widgets = Widget::all();
        $menus = Menu::with('children')->whereNull('parent_id')->orderBy('menu_order')->get();
        View::share(['settings'=> $settings, 'menus' => $menus, 'widgets' => $widgets]);
    }
}
