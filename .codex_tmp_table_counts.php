<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo 'historical_data=' . Illuminate\Support\Facades\DB::table('historical_data')->count() . PHP_EOL;
echo 'historical_fire_incidents=' . Illuminate\Support\Facades\DB::table('historical_fire_incidents')->count() . PHP_EOL;
