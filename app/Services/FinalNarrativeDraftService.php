<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FinalNarrativeDraftService
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('OPENROUTER_API_KEY');
    }

    public function draftFinalNarrativeSections(array $context): array
    {
        if (! $this->apiKey) {
            throw new \RuntimeException('OpenRouter API key is not configured.');
        }

        $errors = [];

        foreach ($this->getModelCandidates() as $model) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(45)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a Bureau of Fire Protection report-writing assistant. Rewrite mixed Tagalog and English investigation notes into a clear, formal Final Investigation Report. Keep only facts provided by the user or linked report context, and do not invent, assume, estimate, or embellish missing facts. Return valid JSON only with the keys facts_of_case, discussion, findings, and recommendation. Each value must be plain text paragraphs with no markdown, bullets, or headings. facts_of_case should summarize the established facts and relevant documents. discussion should explain how the origin and cause were established. findings should clearly state the nature of the fire and other findings. recommendation should contain the Fire Arson Investigator recommendation grounded only in the provided facts.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($context),
                    ],
                ],
                'temperature' => 0.2,
                'max_tokens' => 1200,
            ]);

            if (! $response->successful()) {
                $errors[] = "{$model}: HTTP {$response->status()}";
                continue;
            }

            $content = trim((string) data_get($response->json(), 'choices.0.message.content', ''));
            if ($content === '') {
                $errors[] = "{$model}: empty response";
                continue;
            }

            $parsed = $this->parseSections($content);
            if ($parsed !== null) {
                return $parsed;
            }

            $errors[] = "{$model}: unparseable response";
        }

        Log::warning('Final narrative draft failed for all models.', ['errors' => $errors]);

        throw new \RuntimeException('Narrative drafting is temporarily unavailable.');
    }

    private function buildPrompt(array $context): string
    {
        $lines = [
            'Create polished narrative sections for a Final Investigation Report.',
            'Return a JSON object with exactly these keys: facts_of_case, discussion, findings, recommendation.',
            'Use formal report language.',
            'Do not include headings, bullets, markdown, numbering, or extra keys.',
            'If the input contains Tagalog, translate and refine it into professional English.',
            'If some fields are missing, skip them instead of guessing.',
            '',
            'Final report context:',
            'Existing facts of the case: ' . $this->stringOrFallback($context['facts_of_case'] ?? null),
            'Existing discussion: ' . $this->stringOrFallback($context['discussion'] ?? null),
            'Existing findings: ' . $this->stringOrFallback($context['findings'] ?? null),
            'Existing recommendation: ' . $this->stringOrFallback($context['recommendation'] ?? null),
            'Office address: ' . $this->stringOrFallback($context['office_address'] ?? null),
            'Place of fire: ' . $this->stringOrFallback($context['exact_address'] ?? null),
            'Alarm date/time: ' . $this->stringOrFallback($context['alarm_at'] ?? null),
            'Establishment burned: ' . $this->stringOrFallback($context['establishment_burned'] ?? null),
            'Fire victims: ' . $this->stringOrFallback($context['fire_victims'] ?? null),
            'Origin of fire: ' . $this->stringOrFallback($context['origin_of_fire'] ?? null),
            'Cause of fire: ' . $this->stringOrFallback($context['cause_of_fire'] ?? null),
            'Total damage: ' . $this->stringOrFallback($context['total_damage'] ?? null),
            'Substantiating documents: ' . $this->stringOrFallback(implode(', ', (array) ($context['substantiating_documents'] ?? []))),
            '',
            'Linked progress report context:',
            'Progress fire origin: ' . $this->stringOrFallback($context['progress_fire_origin'] ?? null),
            'Progress fire cause: ' . $this->stringOrFallback($context['progress_fire_cause'] ?? null),
            'Progress facts: ' . $this->stringOrFallback($context['progress_facts_of_case'] ?? null),
            'Progress disposition: ' . $this->stringOrFallback($context['progress_disposition'] ?? null),
            'Responsible arson: ' . $this->stringOrFallback($context['responsible_arson'] ?? null),
            '',
            'Linked spot report context:',
            'Incident location: ' . $this->stringOrFallback($context['incident_location'] ?? null),
            'Place of occurrence: ' . $this->stringOrFallback($context['place_of_occurrence'] ?? null),
            'Spot notes: ' . $this->stringOrFallback($context['spot_description'] ?? null),
            'Spot narrative: ' . $this->stringOrFallback($context['spot_details_of_investigation'] ?? null),
            'Spot disposition: ' . $this->stringOrFallback($context['spot_disposition'] ?? null),
            'Fire started: ' . $this->stringOrFallback($context['fire_started'] ?? null),
            'Fire out: ' . $this->stringOrFallback($context['fire_out'] ?? null),
            'Owner: ' . $this->stringOrFallback($context['owner'] ?? null),
            'Occupant: ' . $this->stringOrFallback($context['occupant'] ?? null),
            'Establishment name: ' . $this->stringOrFallback($context['spot_establishment_name'] ?? null),
            'Occupancy: ' . $this->stringOrFallback($context['occupancy'] ?? null),
            'Estimated damage: ' . $this->stringOrFallback($context['estimated_damage'] ?? null),
            'Fatality details: ' . $this->stringOrFallback($context['fatality_details'] ?? null),
            'Dead: ' . $this->stringOrFallback($context['dead'] ?? null),
            'Injured: ' . $this->stringOrFallback($context['injured'] ?? null),
        ];

        return implode("\n", $lines);
    }

    private function parseSections(string $content): ?array
    {
        $content = trim(preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content);
        $content = trim(preg_replace('/\s*```$/', '', $content) ?? $content);

        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            return null;
        }

        $required = ['facts_of_case', 'discussion', 'findings', 'recommendation'];
        foreach ($required as $key) {
            if (! array_key_exists($key, $decoded)) {
                return null;
            }
        }

        return collect($required)->mapWithKeys(function (string $key) use ($decoded) {
            $value = preg_replace("/\r\n|\r/", "\n", trim((string) ($decoded[$key] ?? ''))) ?? '';
            $value = preg_replace("/\n{3,}/", "\n\n", $value) ?? $value;

            return [$key => trim($value)];
        })->all();
    }

    private function stringOrFallback(mixed $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : 'Not provided';
    }

    private function getModelCandidates(): array
    {
        $models = array_filter([
            env('OPENROUTER_MODEL'),
            'openai/gpt-4.1-mini',
            'openai/gpt-4o-mini',
            'deepseek/deepseek-r1-distill-llama-70b',
        ]);

        return array_values(array_unique($models));
    }
}
