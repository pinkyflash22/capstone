<?php
require __DIR__ . '/../../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();
try {
    $pdo = new PDO('pgsql:host=' . $_ENV['DB_HOST'] . ';port=' . (int) $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';sslmode=require;connect_timeout=20', $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('BEGIN TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY');
    $schema = $pdo->query("SELECT table_name,column_name,data_type FROM information_schema.columns WHERE table_schema='public' AND (table_name IN ('historical_fire_incidents','spot_investigation_report','barangays','migrations','incidents') OR column_name ~ '(arriv|dispatch|respond)') ORDER BY table_name,ordinal_position")->fetchAll(PDO::FETCH_ASSOC);
    $rows = $pdo->query('SELECT * FROM historical_fire_incidents ORDER BY incident_date,historical_fire_incident_id')->fetchAll(PDO::FETCH_ASSOC);
    $reports = $pdo->query('SELECT spot_investigation_report_id,incident_id,fire_started,fire_out FROM spot_investigation_report ORDER BY spot_investigation_report_id')->fetchAll(PDO::FETCH_ASSOC);
    $barangays = $pdo->query('SELECT * FROM barangays')->fetchAll(PDO::FETCH_ASSOC);
    $units = $pdo->query('SELECT incident_id,dispatched_at,arrived_at FROM incident_response_units WHERE incident_id IN (SELECT incident_id FROM historical_fire_incidents WHERE incident_id IS NOT NULL) ORDER BY incident_id,dispatched_at')->fetchAll(PDO::FETCH_ASSOC);
    $migration = $pdo->query("SELECT migration,batch FROM migrations WHERE migration LIKE '%response_time%'")->fetchAll(PDO::FETCH_ASSOC);
    $meta = $pdo->query('SELECT CURRENT_TIMESTAMP AS extracted_at,current_setting(\'transaction_read_only\') AS read_only,current_setting(\'TimeZone\') AS database_timezone')->fetch(PDO::FETCH_ASSOC);
    $pdo->rollBack();
    $export = compact('schema','rows','reports','barangays','units','migration','meta');
    file_put_contents(__DIR__.'/historical_database_snapshot.json', json_encode($export, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    echo json_encode(['historical_rows'=>count($rows),'positive_incident_rows'=>count(array_filter($rows,fn($r)=>(int)($r['incident_count']??0)>0)),'spot_reports'=>count($reports),'reports_with_both_times'=>count(array_filter($reports,fn($r)=>!empty($r['fire_started'])&&!empty($r['fire_out']))),'response_units'=>$units,'response_migrations'=>$migration,'meta'=>$meta],JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR,'Read-only database export failed ('.get_class($e).', code '.$e->getCode().').'.PHP_EOL);
    exit(1);
}
