<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$hd = Illuminate\Support\Facades\DB::table('historical_data')->selectRaw('MIN(year) as min_year, MAX(year) as max_year')->first();
$hfi = Illuminate\Support\Facades\DB::table('historical_fire_incidents')->selectRaw('MIN(incident_date) as min_date, MAX(incident_date) as max_date')->first();
echo 'historical_data_years=' . ($hd->min_year ?? 'null') . ' to ' . ($hd->max_year ?? 'null') . PHP_EOL;
echo 'historical_fire_incidents_dates=' . ($hfi->min_date ?? 'null') . ' to ' . ($hfi->max_date ?? 'null') . PHP_EOL;
