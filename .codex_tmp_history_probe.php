<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$result = [
    'teams' => DB::table('teams')->count(),
    'station_officers' => DB::table('station_officers')->count(),
    'station_barangay_coverage' => DB::table('station_barangay_coverage')->count(),
    'barangays' => DB::table('barangays')->count(),
    'historical_data_by_year' => DB::table('historical_data')->selectRaw('year, COUNT(*) as rows, SUM(COALESCE(incident_count,0)) as incidents')->groupBy('year')->orderBy('year')->get(),
    'historical_incidents_by_year' => DB::table('historical_fire_incidents')->selectRaw('EXTRACT(YEAR FROM incident_date) as year, COUNT(*) as rows, SUM(COALESCE(incident_count,1)) as incidents')->groupByRaw('EXTRACT(YEAR FROM incident_date)')->orderByRaw('EXTRACT(YEAR FROM incident_date)')->get(),
    'historical_incidents_by_month' => DB::table('historical_fire_incidents')->selectRaw('EXTRACT(YEAR FROM incident_date) as year, EXTRACT(MONTH FROM incident_date) as month, COUNT(*) as rows, SUM(COALESCE(incident_count,1)) as incidents')->groupByRaw('EXTRACT(YEAR FROM incident_date), EXTRACT(MONTH FROM incident_date)')->orderByRaw('EXTRACT(YEAR FROM incident_date), EXTRACT(MONTH FROM incident_date)')->get(),
];
echo json_encode($result, JSON_PRETTY_PRINT);
