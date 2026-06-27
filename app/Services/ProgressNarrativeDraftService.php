<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProgressNarrativeDraftService
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = env('OPENROUTER_API_KEY');
    }

    public function draftProgressNarrative(array $context): string
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
                        'content' => 'You are a Bureau of Fire Protection report-writing assistant. Rewrite mixed Tagalog and English investigation notes into a clear, formal Progress Investigation Report narrative for the Facts of the Case section. Keep only facts provided by the user or linked report context, and do not invent, assume, estimate, or embellish missing facts. Return plain narrative paragraphs only with no bullets, headings, labels, markdown, or quotation marks. Prefer 2 to 3 paragraphs: first for the investigation authority and incident context, second for established facts and witness or scene findings, third for responsibility, cause, laboratory or follow-up findings when present. Separate paragraphs with one blank line. Omit any missing item instead of guessing.',
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

        Log::warning('Progress narrative draft failed for all models.', ['errors' => $errors]);

        throw new \RuntimeException('Narrative drafting is temporarily unavailable.');
    }

    private function buildPrompt(array $context): string
    {
        $lines = [
            'Create a polished Facts of the Case narrative for a fire progress investigation report.',
            'Write in formal report style, but keep the facts grounded only in the data below.',
            'Prefer exactly 2 or 3 coherent paragraphs when the details are sufficient.',
            'Paragraph 1 should cover the incident context, basis of investigation, and response background.',
            'Paragraph 2 should cover facts established during the follow-up investigation, witness information, scene findings, and affected properties when present.',
            'Paragraph 3 should cover responsible person findings, fire cause findings, laboratory results, or other follow-up assessment when present.',
            'If some fields are missing, skip them instead of guessing.',
            'If the raw notes contain Tagalog, translate and refine them into professional English.',
            'Do not create placeholder facts such as pending laboratory results, witness statements, or admissions unless the input explicitly contains them.',
            'Separate each paragraph with one blank line.',
            '',
            'Progress report context:',
            'Existing facts of the case draft: ' . $this->stringOrFallback($context['facts_of_case'] ?? null),
            'Disposition notes: ' . $this->stringOrFallback($context['disposition'] ?? null),
            'Fire cause findings: ' . $this->stringOrFallback($context['fire_cause'] ?? null),
            'Responsible arson / subject: ' . $this->stringOrFallback($context['responsible_arson'] ?? null),
            'Fire origin: ' . $this->stringOrFallback($context['fire_origin'] ?? null),
            'Mission order number: ' . $this->stringOrFallback($context['mission_order_number'] ?? null),
            'Mission order date: ' . $this->stringOrFallback($context['mission_order_date'] ?? null),
            'Report completed at: ' . $this->stringOrFallback($context['report_completed_at'] ?? null),
            '',
            'Linked spot investigation context:',
            'Incident location: ' . $this->stringOrFallback($context['incident_location'] ?? null),
            'Place of occurrence: ' . $this->stringOrFallback($context['place_of_occurrence'] ?? null),
            'Spot raw notes: ' . $this->stringOrFallback($context['spot_description'] ?? null),
            'Spot narrative: ' . $this->stringOrFallback($context['spot_details_of_investigation'] ?? null),
            'Spot disposition: ' . $this->stringOrFallback($context['spot_disposition'] ?? null),
            'Fire started: ' . $this->stringOrFallback($context['fire_started'] ?? null),
            'Fire out: ' . $this->stringOrFallback($context['fire_out'] ?? null),
            'Owner: ' . $this->stringOrFallback($context['owner'] ?? null),
            'Occupant: ' . $this->stringOrFallback($context['occupant'] ?? null),
            'Establishment name: ' . $this->stringOrFallback($context['establishment_name'] ?? null),
            'Involved structure: ' . $this->stringOrFallback($context['involved_structure'] ?? null),
            'Estimated damage: ' . $this->stringOrFallback($context['estimated_damage'] ?? null),
            'Estimated area: ' . $this->stringOrFallback($context['estimated_area'] ?? null),
            'Affected establishments: ' . $this->stringOrFallback($context['affected_establishments_count'] ?? null),
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
        $content = preg_replace('/^\s*(facts of the case|narrative)\s*:?\s*/i', '', $content) ?? $content;
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
