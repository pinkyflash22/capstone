<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$columns = Illuminate\Support\Facades\Schema::getColumnListing('historical_fire_incidents');
foreach ($columns as $column) {
    echo $column . PHP_EOL;
}
