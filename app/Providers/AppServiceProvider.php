<?php

namespace App\Providers;

use App\Models\BarangayPredictionChecklist;
use App\Models\Incident;
use App\Observers\IncidentObserver;
use App\Observers\BarangayPredictionChecklistObserver;
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
        BarangayPredictionChecklist::observe(BarangayPredictionChecklistObserver::class);
        Incident::observe(IncidentObserver::class);

        if (app()->environment('production')) {
            $appUrl = rtrim((string) config('app.url'), '/');

            if ($appUrl !== '') {
                URL::forceRootUrl($appUrl);
                URL::forceScheme((string) parse_url($appUrl, PHP_URL_SCHEME) ?: 'https');
            }
        } elseif (! $this->app->runningInConsole()) {
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
}
