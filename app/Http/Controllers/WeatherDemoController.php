<?php

namespace App\Http\Controllers;

use App\Services\WeatherComparisonService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class WeatherDemoController extends Controller
{
    public function index()
    {
        return response()->view('demos.weather-comparison', [
            'historyMax' => CarbonImmutable::now('Asia/Manila')->subDays(7)->toDateString(),
            'historyDefault' => '2026-02-26',
        ])->header('Cache-Control', 'no-store');
    }

    public function data(Request $request, string $provider, WeatherComparisonService $weather)
    {
        $data = $request->validate([
            'mode' => ['required', 'in:forecast,history'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:1940-01-01', 'before_or_equal:'.CarbonImmutable::now('Asia/Manila')->subDays(7)->toDateString()],
        ]);
        return response()->json($weather->fetch($provider, $data['mode'], $data['date']))
            ->header('Cache-Control', 'no-store');
    }
}
