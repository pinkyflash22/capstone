<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$result = [
    'stations' => DB::table('stations')->select('station_id','station_name','address_id')->get(),
    'station_barangay_coverage_rows' => DB::table('station_barangay_coverage')->select('station_id','barangay_id','is_active')->get(),
];
echo json_encode($result, JSON_PRETTY_PRINT);
