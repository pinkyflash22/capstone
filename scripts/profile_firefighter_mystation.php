<?php

putenv('LOG_CHANNEL=stderr');
$_ENV['LOG_CHANNEL'] = 'stderr';
$_SERVER['LOG_CHANNEL'] = 'stderr';

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config([
    'cache.default' => 'array',
    'session.driver' => 'array',
    'logging.default' => 'stderr',
    'logging.channels.stderr' => [
        'driver' => 'monolog',
        'handler' => Monolog\Handler\StreamHandler::class,
        'with' => ['stream' => 'php://stderr'],
    ],
]);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$user = App\Models\User::find(4);

if (! $user) {
    fwrite(STDERR, "User 4 not found.\n");
    exit(1);
}

Illuminate\Support\Facades\Auth::login($user);

function runProfile(Illuminate\Contracts\Http\Kernel $kernel, string $uri): array
{
    $queries = [];

    Illuminate\Support\Facades\DB::listen(function ($query) use (&$queries) {
        $sql = preg_replace('/\s+/', ' ', $query->sql);
        $queries[] = [
            'sql' => $sql,
            'time_ms' => $query->time,
        ];
    });

    $request = Illuminate\Http\Request::create($uri, 'GET');
    $request->headers->set('Accept', 'application/json,text/html');
    $request->setLaravelSession(app('session')->driver());

    $start = microtime(true);
    $response = $kernel->handle($request);
    $elapsedMs = (microtime(true) - $start) * 1000;
    $content = (string) $response->getContent();
    $kernel->terminate($request, $response);

    $duplicates = [];
    foreach ($queries as $query) {
        $duplicates[$query['sql']] = ($duplicates[$query['sql']] ?? 0) + 1;
    }

    $duplicates = array_filter($duplicates, fn ($count) => $count > 1);
    arsort($duplicates);

    $slowest = null;
    foreach ($queries as $query) {
        if ($slowest === null || $query['time_ms'] > $slowest['time_ms']) {
            $slowest = $query;
        }
    }

    return [
        'uri' => $uri,
        'status' => $response->getStatusCode(),
        'elapsed_ms' => round($elapsedMs, 2),
        'bytes' => strlen($content),
        'query_count' => count($queries),
        'slowest_query' => $slowest,
        'duplicates' => $duplicates,
        'queries' => $queries,
    ];
}

$results = [
    'my_station_shell_first' => runProfile($kernel, '/firefighter/my-station'),
    'my_station_shell_second' => runProfile($kernel, '/firefighter/my-station'),
    'my_station_overview_first' => runProfile($kernel, '/firefighter/api/my-station/overview'),
    'my_station_personnel_first' => runProfile($kernel, '/firefighter/api/my-station/personnel'),
    'my_station_equipment_first' => runProfile($kernel, '/firefighter/api/my-station/equipment'),
    'my_station_reports_spot_first' => runProfile($kernel, '/firefighter/api/my-station/reports?tab=spot'),
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
