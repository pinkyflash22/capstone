<?php
require __DIR__ . '/../../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2))->safeLoad();
try {
    $pdo = new PDO('pgsql:host=' . $_ENV['DB_HOST'] . ';port=' . (int) $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';sslmode=require;connect_timeout=20', $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('BEGIN TRANSACTION READ ONLY');
    $columns = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_schema='public' AND table_name='historical_fire_incidents' AND column_name IN ('response_time_minutes','response_time_source')")->fetchAll(PDO::FETCH_COLUMN);
    $migrations = $pdo->query("SELECT migration FROM migrations WHERE migration = '2026_10_18_000001_add_response_time_to_historical_fire_incidents_table'")->fetchAll(PDO::FETCH_COLUMN);
    $pdo->rollBack();
    echo json_encode(['historical_response_columns'=>$columns,'applied_response_migrations'=>$migrations,'database_writes'=>false],JSON_PRETTY_PRINT).PHP_EOL;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR,'Schema check failed ('.get_class($e).', code '.$e->getCode().').'.PHP_EOL);
    exit(1);
}
