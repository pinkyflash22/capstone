<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$result = DB::table('historical_fire_incidents')
    ->leftJoin('barangays', 'historical_fire_incidents.barangay_id', '=', 'barangays.barangay_id')
    ->selectRaw('historical_fire_incidents.barangay_id, barangays.barangay_name, COUNT(*) as rows, SUM(COALESCE(historical_fire_incidents.incident_count,1)) as incidents')
    ->groupBy('historical_fire_incidents.barangay_id', 'barangays.barangay_name')
    ->orderByDesc('rows')
    ->get();
echo json_encode($result, JSON_PRETTY_PRINT);
