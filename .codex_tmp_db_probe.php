<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$result = [
    'stations' => Illuminate\Support\Facades\DB::table('stations')->count(),
    'historical_data' => Illuminate\Support\Facades\DB::table('historical_data')->count(),
    'historical_fire_incidents' => Illuminate\Support\Facades\DB::table('historical_fire_incidents')->count(),
    'incidents' => Illuminate\Support\Facades\DB::table('incidents')->count(),
];
echo json_encode($result, JSON_PRETTY_PRINT);
