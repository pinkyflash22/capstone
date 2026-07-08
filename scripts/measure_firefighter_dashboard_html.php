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

$request = Illuminate\Http\Request::create('/firefighter/dashboard', 'GET');
$request->setLaravelSession(app('session')->driver());

$response = $kernel->handle($request);
$html = (string) $response->getContent();

preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $html, $scripts);
preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $html, $styles);
preg_match_all('/<div[^>]*class="[^"]*modal[^"]*"[^>]*>/i', $html, $modals);

$scriptBodies = $scripts[1] ?? [];
$styleBodies = $styles[1] ?? [];

$inlineScriptBytes = array_sum(array_map('strlen', $scriptBodies));
$inlineStyleBytes = array_sum(array_map('strlen', $styleBodies));

$largestScriptBytes = 0;
foreach ($scriptBodies as $body) {
    $largestScriptBytes = max($largestScriptBytes, strlen($body));
}

$largestStyleBytes = 0;
foreach ($styleBodies as $body) {
    $largestStyleBytes = max($largestStyleBytes, strlen($body));
}

echo json_encode([
    'html_bytes' => strlen($html),
    'inline_script_tags' => count($scripts[0] ?? []),
    'inline_script_bytes' => $inlineScriptBytes,
    'largest_inline_script_bytes' => $largestScriptBytes,
    'inline_style_tags' => count($styles[0] ?? []),
    'inline_style_bytes' => $inlineStyleBytes,
    'largest_inline_style_bytes' => $largestStyleBytes,
    'modal_div_count' => count($modals[0] ?? []),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
