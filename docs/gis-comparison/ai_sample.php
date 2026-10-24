<?php

// Live generation demonstration, not a test suite. No operational records are written.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$ai = $app->make(App\Services\AiComparisonService::class);
$results = [];
foreach (['deepseek', 'gptmini', 'llama'] as $model) {
    $result = $ai->generate($model, 'forecast_explanation');
    $results[] = $result;
    echo $result['name'].' | '.$result['status'].' | '.($result['elapsed_ms'] ?? 'n/a')." ms\n";
    flush();
}
file_put_contents(__DIR__.'/ai-sample-results.json', json_encode([
    'context' => $ai->context(), 'messages' => $ai->messages('forecast_explanation'), 'results' => $results,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo "Saved ai-sample-results.json; API key and hidden reasoning are excluded.\n";
