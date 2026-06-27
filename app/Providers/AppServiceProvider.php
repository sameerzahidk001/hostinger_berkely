<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('local') && ! $this->app->runningInConsole()) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());
        }

        if ($this->app->environment('production')) {
            if (str_starts_with((string) config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }

            if (config('session.secure') === null) {
                config(['session.secure' => true]);
            }
        }
    }
}
