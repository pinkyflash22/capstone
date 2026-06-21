<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpotNarrativeDraftService
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('OPENROUTER_API_KEY');
    }

    public function draftSpotNarrative(array $context): string
    {
        if (! $this->apiKey) {
            throw new \RuntimeException('OpenRouter API key is not configured.');
        }

        $errors = [];

        foreach ($this->getModelCandidates() as $model) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->connectTimeout(10)->timeout(35)->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a Bureau of Fire Protection report-writing assistant. Rewrite mixed Tagalog and English field notes into a clear, formal Spot Investigation Report narrative. Keep only facts provided by the user, do not invent, assume, estimate, or embellish missing facts, and do not manufacture names, identities, weather conditions, fire behavior, findings, or conclusions. Return plain narrative paragraphs only with no bullets, headings, labels, markdown, or quotation marks. Prefer exactly 3 paragraphs when enough details are available: first for incident notification and response, second for on-scene findings and fire status, third for casualties, losses, and investigative assessment. Separate paragraphs with one blank line. The narrative must be grounded in the Fire Arson Investigator checklist items provided by the user, but any checklist item with missing data must be omitted rather than guessed.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($context),
                    ],
                ],
                'temperature' => 0.2,
                'max_tokens' => 700,
            ]);

            if (! $response->successful()) {
                $errors[] = "{$model}: HTTP {$response->status()}";
                continue;
            }

            $content = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

            if ($content !== '') {
                return $this->cleanNarrative($content);
            }

            $errors[] = "{$model}: empty response";
        }

        Log::warning('Spot narrative draft failed for all models.', ['errors' => $errors]);

        throw new \RuntimeException('Narrative drafting is temporarily unavailable.');
    }

    private function buildPrompt(array $context): string
    {
        $lines = [
            'Create a polished Details of Investigation narrative for a fire spot investigation report.',
            'Write in formal report style, but keep the facts grounded only in the data below.',
            'Use exactly 3 coherent paragraphs when the details are sufficient.',
            'Paragraph 1 should cover when and where the incident was reported and the initial response.',
            'Paragraph 2 should cover the on-scene findings, extent of fire, and suppression status.',
            'Paragraph 3 should cover casualties, losses, and the investigator assessment or follow-up context.',
            'If the available facts are too limited, still separate the narrative into at least 2 short paragraphs instead of one long block.',
            'If the raw notes contain Tagalog, translate and refine them into professional English.',
            'If some fields are missing, skip them instead of guessing.',
            'Follow this investigation checklist only from the facts actually supplied by the user:',
            '1. Number of establishments and/or affected establishments.',
            '2. Estimated area in square meters and estimated amount of damage.',
            '3. Location of fatalities and initial identity details.',
            '4. Weather condition.',
            '5. Other initial information about the involved establishment and the fire incident.',
            'Do not mention a checklist item unless supporting facts are present in the input.',
            'Do not create placeholder facts such as "under investigation" or "weather was fair" unless the user explicitly said so.',
            'Separate each paragraph with one blank line.',
            '',
            'Incident context:',
            'Raw field notes / description: ' . $this->stringOrFallback($context['description'] ?? null),
            'Existing draft narrative: ' . $this->stringOrFallback($context['details_of_investigation'] ?? null),
            'Disposition notes: ' . $this->stringOrFallback($context['disposition'] ?? null),
            'Reported at: ' . $this->stringOrFallback($context['incident_reported_at'] ?? null),
            'Place of occurrence: ' . $this->stringOrFallback($context['place_of_occurrence'] ?? null),
            'Incident location label: ' . $this->stringOrFallback($context['incident_location'] ?? null),
            'Fire started: ' . $this->stringOrFallback($context['fire_started'] ?? null),
            'Fire out: ' . $this->stringOrFallback($context['fire_out'] ?? null),
            'Occupancy: ' . $this->stringOrFallback($context['occupancy'] ?? null),
            'Involved structure: ' . $this->stringOrFallback($context['involved_structure'] ?? null),
            'Establishment name: ' . $this->stringOrFallback($context['establishment_name'] ?? null),
            'Owner: ' . $this->stringOrFallback($context['owner'] ?? null),
            'Occupant: ' . $this->stringOrFallback($context['occupant'] ?? null),
            'Estimated damage: ' . $this->stringOrFallback($context['estimated_damage'] ?? null),
            'Estimated area: ' . $this->stringOrFallback($context['estimated_area'] ?? null),
            'Highest alarm level: ' . $this->stringOrFallback($context['highest_alarm_level'] ?? null),
            'Weather condition: ' . $this->stringOrFallback($context['weather_condition'] ?? null),
            'Weather summary: ' . $this->stringOrFallback($context['weather_summary'] ?? null),
            'Fatalities: ' . $this->stringOrFallback($context['dead'] ?? null),
            'Injured: ' . $this->stringOrFallback($context['injured'] ?? null),
            'Fatality details: ' . $this->stringOrFallback($context['fatality_details'] ?? null),
        ];

        return implode("\n", $lines);
    }

    private function cleanNarrative(string $content): string
    {
        $content = preg_replace('/^```[\w-]*\s*/', '', $content) ?? $content;
        $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        $content = preg_replace('/^\s*(details of investigation|narrative)\s*:?\s*/i', '', $content) ?? $content;
        $content = preg_replace("/\r\n|\r/", "\n", $content) ?? $content;
        $content = preg_replace("/\n{3,}/", "\n\n", $content) ?? $content;

        return trim($content);
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
