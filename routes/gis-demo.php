<?php

use App\Http\Controllers\GisDemoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('gis-demo')->name('gis-demo.')->group(function () {
    Route::get('/', [GisDemoController::class, 'index'])->name('index');
    Route::get('/ai', [\App\Http\Controllers\AiDemoController::class, 'index'])->name('ai');
    Route::get('/ai/catalog', [\App\Http\Controllers\AiDemoController::class, 'catalog'])->middleware('throttle:12,1')->name('ai.catalog');
    Route::post('/ai/generate', [\App\Http\Controllers\AiDemoController::class, 'generate'])->middleware('throttle:6,1')->name('ai.generate');
    Route::get('/weather', [\App\Http\Controllers\WeatherDemoController::class, 'index'])->name('weather');
    Route::get('/weather/data/{provider}', [\App\Http\Controllers\WeatherDemoController::class, 'data'])
        ->where('provider', 'openmeteo|weatherapi|visualcrossing')->middleware('throttle:12,1')->name('weather.data');
    Route::get('/mapping/{library?}', [GisDemoController::class, 'mapping'])
        ->where('library', 'leaflet|maplibre|openlayers')->name('mapping');
    Route::get('/current', [GisDemoController::class, 'index'])->defaults('provider', 'geoapify')->name('current');
    Route::get('/alternative', [GisDemoController::class, 'index'])->defaults('provider', 'osrm')->name('alternative');
    Route::get('/openrouteservice', [GisDemoController::class, 'index'])->defaults('provider', 'openrouteservice')->name('openrouteservice');
    Route::post('/openrouteservice-route', [GisDemoController::class, 'openRouteServiceRoute'])
        ->middleware('throttle:10,1')->name('openrouteservice-route');
    Route::post('/osrm-route', [GisDemoController::class, 'osrmRoute'])
        ->middleware('throttle:10,1')->name('osrm-route');
});
