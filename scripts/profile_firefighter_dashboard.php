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

    $listener = Illuminate\Support\Facades\DB::listen(function ($query) use (&$queries) {
        $sql = preg_replace('/\s+/', ' ', $query->sql);
        $queries[] = [
            'sql' => $sql,
            'time_ms' => $query->time,
        ];
    });

    $request = Illuminate\Http\Request::create($uri, 'GET');
    $request->headers->set('Accept', 'application/json,text/html');

    $start = microtime(true);
    $response = $kernel->handle($request);
    $elapsedMs = (microtime(true) - $start) * 1000;
    $content = (string) $response->getContent();
    $payload = null;

    if (str_contains((string) $response->headers->get('content-type'), 'application/json')) {
        $payload = json_decode($content, true);
    }

    $kernel->terminate($request, $response);

    $normalizedCounts = [];
    foreach ($queries as $query) {
        $normalizedCounts[$query['sql']] = ($normalizedCounts[$query['sql']] ?? 0) + 1;
    }

    $duplicates = array_filter($normalizedCounts, fn ($count) => $count > 1);
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
        'payload' => $payload,
    ];
}

$results = [
    'dashboard_shell_first' => runProfile($kernel, '/firefighter/dashboard'),
    'dashboard_shell_second' => runProfile($kernel, '/firefighter/dashboard'),
    'dashboard_api_first' => runProfile($kernel, '/firefighter/api/dashboard'),
    'dashboard_api_second' => runProfile($kernel, '/firefighter/api/dashboard'),
    'dashboard_posts_first' => runProfile($kernel, '/firefighter/api/dashboard/posts'),
    'dashboard_posts_second' => runProfile($kernel, '/firefighter/api/dashboard/posts'),
];

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
