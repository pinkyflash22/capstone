<?php

putenv('LOG_CHANNEL=stderr');
$_ENV['LOG_CHANNEL'] = 'stderr';
$_SERVER['LOG_CHANNEL'] = 'stderr';

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

config([
    'cache.default' => 'array',
    'session.driver' => 'array',
]);

$user = App\Models\User::query()->find(4);

if (! $user) {
    fwrite(STDERR, "Unable to find user 4.\n");
    exit(1);
}

Illuminate\Support\Facades\Auth::login($user);

$request = Illuminate\Http\Request::create('/firefighter/my-station', 'GET');
$request->setLaravelSession(app('session')->driver());

$response = $kernel->handle($request);
$html = (string) $response->getContent();

preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $scripts);
preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $html, $styles);
preg_match_all('/<div[^>]*class="[^"]*report-modal[^"]*"[^>]*>/i', $html, $modals);

$scriptBodies = $scripts[1] ?? [];
$styleBodies = $styles[1] ?? [];

echo json_encode([
    'html_bytes' => strlen($html),
    'inline_script_tags' => count($scripts[0] ?? []),
    'inline_script_bytes' => array_sum(array_map('strlen', $scriptBodies)),
    'inline_style_tags' => count($styles[0] ?? []),
    'inline_style_bytes' => array_sum(array_map('strlen', $styleBodies)),
    'modal_div_count' => count($modals[0] ?? []),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
