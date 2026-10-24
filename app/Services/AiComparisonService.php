<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/** Text-assistance comparison only; never trains, forecasts incident counts or writes records. */
class AiComparisonService
{
    public const MODELS = [
        'deepseek' => ['name' => 'DeepSeek-R1-Distill-Llama-70B', 'id' => 'deepseek/deepseek-r1-distill-llama-70b'],
        'gptmini' => ['name' => 'GPT-4.1 Mini', 'id' => 'openai/gpt-4.1-mini'],
        'llama' => ['name' => 'Llama 3.3 70B Instruct', 'id' => 'meta-llama/llama-3.3-70b-instruct'],
        'qwen' => ['name' => 'Qwen2.5-72B-Instruct', 'id' => 'qwen/qwen-2.5-72b-instruct'],
        'phi' => ['name' => 'Phi-4', 'id' => 'microsoft/phi-4'],
        'mistral' => ['name' => 'Mistral Large', 'id' => 'mistralai/mistral-large'],
        'claude' => ['name' => 'Claude 3.5 Sonnet', 'id' => 'anthropic/claude-3.5-sonnet'],
    ];

    private ?array $cachedCatalog = null;

    public function context(): array
    {
        $weatherFile = base_path('docs/gis-comparison/weather-sample-results.json');
        $incidentFile = base_path('capstone-ai/historical_incident_response_times.json');
        $weather = is_file($weatherFile) ? json_decode(file_get_contents($weatherFile), true) : [];
        $incidents = is_file($incidentFile) ? json_decode(file_get_contents($incidentFile), true) : [];
        $forecast = collect(is_array($weather) ? $weather : [])->first(fn ($row) => ($row['provider'] ?? '') === 'openmeteo' && ($row['mode'] ?? '') === 'forecast' && ($row['success'] ?? false));
        return [
            'location' => 'Urbiztondo, Pangasinan',
            'purpose' => 'Documentation demonstration using saved input snapshots, not live operational advice.',
            'weather_snapshot' => $forecast ? [
                'provider' => 'Open-Meteo', 'fetched_at' => $forecast['fetched_at'],
                'requested_location' => $forecast['requested_location'],
                'days' => $forecast['days'],
                'note' => 'Weather for the seeded sample station coordinates, not incident-site measurements. Historical snapshot: may be outdated.',
            ] : null,
            'historical_records' => array_map(fn ($row) => [
                'date' => $row['incident_date'], 'barangay' => $row['barangay'],
                'cause' => $row['fire_cause'], 'source_notes' => $row['source_notes'],
            ], is_array($incidents['incidents'] ?? null) ? $incidents['incidents'] : []),
            'numerical_incident_forecast' => null,
            'limitations' => [
                'Small, incomplete historical record set; some dates use the first day of the month as a placeholder.',
                'No validated fire-incident probability or future incident-count prediction is supplied.',
                'Weather is one factor; this input does not establish a causal weather/fire relationship.',
            ],
        ];
    }

    public function messages(string $task): array
    {
        $instruction = $task === 'mitigation'
            ? 'Draft a short mitigation checklist grounded in the supplied factors.'
            : 'Explain how the supplied weather and historical context could inform a forecast briefing, and identify the missing evidence needed for numerical forecasting.';
        return [
            ['role' => 'system', 'content' => 'You assist a fire-risk documentation demonstration. Use only supplied facts. Do not invent probabilities, future incident counts, resources or verified locations. Weather alone does not establish fire risk. Give a concise rationale, not hidden reasoning. Return only a JSON object with keys summary (string), risk_factors (array of up to 3 strings), checklist (array of exactly 3 strings), limitations (array of at least 2 strings), numerical_forecast (null). Keep the entire answer under 180 words.'],
            ['role' => 'user', 'content' => $instruction."\nEvidence snapshot:\n".json_encode($this->context(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)],
        ];
    }

    public function catalog(): array
    {
        if ($this->cachedCatalog !== null) {
            return $this->cachedCatalog;
        }
        $listed = null;
        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(15)->get('https://openrouter.ai/api/v1/models');
            if ($response->successful() && is_array($response->json('data'))) {
                $listed = array_column($response->json('data'), 'id');
            }
        } catch (\Throwable $exception) {
            // Availability unknown: never infer that a model is retired from a network error.
        }
        return $this->cachedCatalog = [
            'checked_at' => now()->toIso8601String(),
            'key_configured' => trim((string) config('services.openrouter.api_key')) !== '',
            'models' => collect(self::MODELS)->map(fn ($model) => array_merge($model, [
                'availability' => $listed === null ? 'unknown' : (in_array($model['id'], $listed, true) ? 'listed' : 'not_listed'),
            ]))->all(),
        ];
    }

    public function generate(string $alias, string $task): array
    {
        $model = self::MODELS[$alias];
        $messages = $this->messages($task);
        $result = ['success' => false, 'requested_model' => $model['id'], 'name' => $model['name'],
            'task' => $task, 'prompt_sha256' => hash('sha256', json_encode($messages, JSON_THROW_ON_ERROR)),
            'requested_settings' => ['temperature' => 0.2, 'max_tokens' => 900], 'created_at' => now()->toIso8601String()];
        $key = trim((string) config('services.openrouter.api_key'));
        if ($key === '') {
            return array_merge($result, ['status' => 'missing_key', 'message' => 'OpenRouter key is not configured.']);
        }
        $catalog = $this->catalog();
        if ($catalog['models'][$alias]['availability'] === 'not_listed') {
            return array_merge($result, ['status' => 'not_listed', 'message' => 'This exact model ID is not in the current OpenRouter catalog. No replacement was requested.']);
        }
        $started = microtime(true);
        try {
            // One exact model, no cross-model fallback list, no retries or web/tools.
            $response = Http::withToken($key)->acceptJson()->connectTimeout(8)->timeout(90)
                ->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model['id'], 'messages' => $messages,
                    'temperature' => 0.2, 'max_tokens' => 900, 'stream' => false,
                ]);
            $result['elapsed_ms'] = round((microtime(true) - $started) * 1000);
            $result['http_status'] = $response->status();
            if (! $response->successful()) {
                return array_merge($result, ['status' => 'unavailable', 'message' => match ($response->status()) {
                    401, 403 => 'Access denied. Check the OpenRouter key and account/provider permissions.',
                    402 => 'OpenRouter requires sufficient credits for this request.',
                    404 => 'No serving endpoint was available for this exact model.',
                    429 => 'Rate limit reached. Try again later.',
                    default => 'The requested model could not complete this request.',
                }]);
            }
            $payload = $response->json();
            $content = data_get($payload, 'choices.0.message.content');
            $result['returned_model'] = $payload['model'] ?? null;
            $result['serving_provider'] = $payload['provider'] ?? null;
            $result['finish_reason'] = data_get($payload, 'choices.0.finish_reason');
            $result['usage'] = collect($payload['usage'] ?? [])->only(['prompt_tokens', 'completion_tokens', 'total_tokens', 'cost'])->all();
            if (! is_string($content) || trim($content) === '') {
                return array_merge($result, ['status' => 'empty', 'message' => 'No final answer was returned within this request. Reasoning-only content is not displayed.']);
            }
            $result['output'] = $content;
            $decoded = json_decode(trim($content), true);
            $format = is_array($decoded) && is_string($decoded['summary'] ?? null)
                && $this->stringList($decoded['risk_factors'] ?? null, 0, 3)
                && $this->stringList($decoded['checklist'] ?? null, 3, 3)
                && $this->stringList($decoded['limitations'] ?? null, 2, 8)
                && array_key_exists('numerical_forecast', $decoded) && $decoded['numerical_forecast'] === null;
            return array_merge($result, ['success' => true, 'status' => 'received',
                'message' => 'Final answer received; human review is required.', 'format_compliant' => $format,
                'word_count' => str_word_count(is_array($decoded) ? implode(' ', array_merge(
                    [is_string($decoded['summary'] ?? null) ? $decoded['summary'] : ''],
                    array_filter(is_array($decoded['risk_factors'] ?? null) ? $decoded['risk_factors'] : [], 'is_string'),
                    array_filter(is_array($decoded['checklist'] ?? null) ? $decoded['checklist'] : [], 'is_string'),
                    array_filter(is_array($decoded['limitations'] ?? null) ? $decoded['limitations'] : [], 'is_string')
                )) : $content), 'structured_output' => $format ? $decoded : null]);
        } catch (\Throwable $exception) {
            // Never expose request exceptions or authenticated headers.
            return array_merge($result, ['status' => 'unavailable', 'message' => 'Model connection failed or response could not be processed.']);
        }
    }

    private function stringList(mixed $value, int $minimum, int $maximum): bool
    {
        return is_array($value) && array_is_list($value) && count($value) >= $minimum && count($value) <= $maximum
            && count(array_filter($value, fn ($item) => is_string($item) && trim($item) !== '')) === count($value);
    }
}
