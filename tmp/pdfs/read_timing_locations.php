<?php
require __DIR__.'/../../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__,2))->safeLoad();
try {
    $pdo=new PDO('pgsql:host='.$_ENV['DB_HOST'].';port='.(int)$_ENV['DB_PORT'].';dbname='.$_ENV['DB_DATABASE'].';sslmode=require;connect_timeout=5',$_ENV['DB_USERNAME'],$_ENV['DB_PASSWORD'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('BEGIN TRANSACTION READ ONLY');
    $pdo->exec("SET LOCAL statement_timeout='10s'");
    $stations=$pdo->query('SELECT s.station_id,s.station_name,m.marker_id,m.title,m.description,m.lat,m.lng FROM stations s JOIN markers m ON m.marker_id=s.marker_id ORDER BY s.station_id')->fetchAll(PDO::FETCH_ASSOC);
    $halls=$pdo->query('SELECT b.barangay_id,b.barangay_name,h.hall_name,m.lat,m.lng FROM barangay_halls h JOIN barangays b ON b.barangay_id=h.barangay_id JOIN markers m ON m.marker_id=h.marker_id ORDER BY b.barangay_name')->fetchAll(PDO::FETCH_ASSOC);
    $pdo->rollBack();
    file_put_contents(__DIR__.'/timing_locations.json',json_encode(compact('stations','halls'),JSON_PRETTY_PRINT));
    echo json_encode(['stations'=>$stations,'hall_count'=>count($halls)],JSON_PRETTY_PRINT);
} catch(Throwable $e) { if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack(); fwrite(STDERR,'Read-only location lookup failed.'.PHP_EOL); exit(1); }
