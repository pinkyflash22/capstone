<?php

// Standalone live demonstration; no migrations, seeders, tests or database queries.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service = $app->make(App\Services\WeatherComparisonService::class);
$results = [];
foreach (['forecast', 'history'] as $mode) {
    foreach (['openmeteo', 'weatherapi', 'visualcrossing'] as $provider) {
        $result = $service->fetch($provider, $mode, '2026-02-26');
        $results[] = $result;
        echo $provider.' | '.$mode.' | '.$result['status'].' | '.count($result['days'])." daily rows\n";
    }
}
file_put_contents(__DIR__.'/weather-sample-results.json', json_encode($results, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo "Saved weather-sample-results.json; credentials are excluded.\n";
