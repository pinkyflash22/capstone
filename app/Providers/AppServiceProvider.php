<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $request = request();
        $rootUrl = $request->getSchemeAndHttpHost();

        if ($rootUrl) {
            URL::forceRootUrl($rootUrl);
        }

        $forwardedProto = $request->header('X-Forwarded-Proto');

        if ($request->isSecure() || $forwardedProto === 'https') {
            URL::forceScheme('https');
        }
    }
}
