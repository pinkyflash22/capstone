<?php

namespace App\Services;

use App\Models\ProgressInvestigationReport;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ProgressInvestigationPirExporter
{
    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const TEMPLATE_PATH = 'app/private/templates/progress-investigation-template-edited.docx';

    public function __construct(private readonly DocxProofImageAppender $proofImageAppender)
    {
    }

    public function buildDocx(ProgressInvestigationReport $report): string
    {
        $templatePath = storage_path(self::TEMPLATE_PATH);

        if (! is_file($templatePath)) {
            throw new RuntimeException('The PIR Word template could not be found.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'pir_docx_');
        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create a temporary file for the PIR export.');
        }

        $docxPath = $temporaryPath . '.docx';
        @unlink($docxPath);

        if (! copy($templatePath, $docxPath)) {
            @unlink($temporaryPath);
            throw new RuntimeException('Unable to copy the PIR template.');
        }

        @unlink($temporaryPath);

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            @unlink($docxPath);
            throw new RuntimeException('Unable to open the copied PIR template.');
        }

        $documentEntryName = $this->resolveZipEntryName($zip, 'word/document.xml');
        $documentXml = $documentEntryName ? $zip->getFromName($documentEntryName) : false;
        if ($documentXml === false) {
            $zip->close();
            @unlink($docxPath);
            throw new RuntimeException('The PIR template is missing its main document XML.');
        }

        if (! $zip->addFromString($documentEntryName, $this->populateDocumentXml($documentXml, $this->buildDocumentData($report))) || ! $zip->close()) {
            @unlink($docxPath);
            throw new RuntimeException('Unable to save the generated PIR Word document.');
        }

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            @unlink($docxPath);
            throw new RuntimeException('Unable to reopen the generated PIR Word document.');
        }

        try {
            $report->loadMissing('proofImages');
            $this->proofImageAppender->append($zip, $report->proofImages);
        } catch (\Throwable $exception) {
            $zip->close();
            @unlink($docxPath);
            throw $exception;
        }

        if (! $zip->close()) {
            @unlink($docxPath);
            throw new RuntimeException('Unable to finalize the generated PIR Word document.');
        }

        return $docxPath;
    }

    public function buildFilename(ProgressInvestigationReport $report, string $extension): string
    {
        $location = Str::slug((string) ($report->spotInvestigation?->incident?->marker?->title ?? 'incident-location'));

        return sprintf(
            'progress-investigation-report-%s-%s.%s',
            $report->progress_investigation_report_id,
            $location ?: 'incident-location',
            ltrim($extension, '.')
        );
    }

    public function buildDocumentData(ProgressInvestigationReport $report): array
    {
        $station = $report->investigator?->station;
        $stationBarangay = $station?->address?->barangay;
        $stationCity = $stationBarangay?->city;
        $stationProvince = $stationCity?->province;

        $spot = $report->spotInvestigation;
        $incident = $spot?->incident;
        $incidentBarangay = $incident?->barangay;
        $incidentCity = $incidentBarangay?->city;
        $incidentProvince = $incidentCity?->province;

        $placeOfOccurrence = $spot?->place_of_occurrence ?: collect([
            $incident?->marker?->title,
            $incidentBarangay?->barangay_name,
            $incidentCity?->city_name,
            $incidentProvince?->province_name,
        ])->filter()->join(', ');

        $facts = $this->sanitizeNarrativeBlock((string) ($report->facts_of_case ?? ''));
        if ($facts === '') {
            $facts = $this->buildFallbackFactsNarrative($report, $placeOfOccurrence);
        }

        $disposition = $this->sanitizeNarrativeBlock((string) ($report->disposition ?? ''));
        if ($disposition === '') {
            $disposition = 'Follow-up investigation findings were documented by the assigned Fire Arson Investigator for further evaluation and appropriate disposition by the concerned office.';
        }

        $regionalOfficeLine = $stationProvince?->province_name
            ? Str::upper($stationProvince->province_name)
            : 'PANGASINAN';

        $districtOfficeLine = $stationCity?->city_name
            ? Str::upper($stationCity->city_name)
            : 'URBIZTONDO';

        $municipalOfficeLine = Str::upper((string) ($station?->station_name ?: 'URBIZTONDO FIRE STATION'));

        $letterheadLine = collect([
            $municipalOfficeLine,
            collect([$districtOfficeLine, $regionalOfficeLine])->filter()->join(', '),
        ])->filter()->implode("\n");

        return [
            'memorandum_for' => $report->memorandum_for ?: 'Respective head of office',
            'subject' => 'Progress Investigation Report (PIR)',
            'subject_variant' => 'Progress Investigation Report (PIR)',
            'report_completed_display' => $this->formatBfpLongDate($report->report_completed_at ?: $report->created_at),
            'mission_order_number' => $report->mission_order_number ?: 'Not specified',
            'mission_order_date_display' => $this->formatBfpLongDate($report->mission_order_date),
            'office_letterhead_line' => $letterheadLine,
            'facts_of_case' => $facts,
            'disposition' => $disposition,
            'investigator_name' => $this->formatOfficerDisplay($report->investigator) ?: 'Assigned Fire Arson Investigator',
            'investigator_role' => 'Fire Arson Investigator',
            'noted_by_name' => $this->formatOfficerDisplay($report->notedBy) ?: 'Not assigned',
            'noted_by_role' => 'Chief, Intelligence and Investigation Unit',
            'approved_by_name' => $this->formatOfficerDisplay($report->approvedBy) ?: 'Not assigned',
            'approved_by_role' => 'Head of Office',
        ];
    }

    private function populateDocumentXml(string $documentXml, array $data): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        if (! $dom->loadXML($documentXml)) {
            throw new RuntimeException('Unable to parse the PIR template XML.');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::WORD_NS);

        $paragraphs = iterator_to_array($xpath->query('//w:p') ?: []);
        $paragraphEntries = [];
        foreach ($paragraphs as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }

            $paragraphEntries[] = [
                'paragraph' => $paragraph,
                'text' => $this->paragraphText($paragraph, $xpath),
            ];
        }
        $state = null;
        $factsParagraphs = $this->splitNarrativeParagraphs((string) $data['facts_of_case']);
        $factsParagraphIndex = 0;
        $dispositionParagraphs = $this->splitNarrativeParagraphs((string) $data['disposition']);
        $dispositionParagraphIndex = 0;

        foreach ($paragraphEntries as $entry) {
            $paragraph = $entry['paragraph'];
            $text = $entry['text'];

            if (! $this->canUseParagraph($paragraph)) {
                continue;
            }

            if ($text === '' && $state === null) {
                continue;
            }

            if (preg_match('/^FOR\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'FOR', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['memorandum_for'], 'bold' => true],
                ], ['tab_stops' => [1700, 2400]]);
                continue;
            }

            if (str_contains($text, 'Regional/Provincial/District/City/Municipal') && str_contains($text, 'Letterhead')) {
                $this->setParagraphText($paragraph, $data['office_letterhead_line'], ['size' => 22]);
                continue;
            }

            if (preg_match('/^SUBJECT\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'SUBJECT', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['subject'], 'bold' => true],
                ], ['tab_stops' => [1700, 2400]]);
                continue;
            }

            if (str_contains($text, '1st , 2nd Progress Investigation Report')) {
                $this->setParagraphText($paragraph, '');
                continue;
            }

            if (preg_match('/^DATE\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'DATE', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['report_completed_display'], 'bold' => true],
                ], ['tab_stops' => [1700, 2400]]);
                continue;
            }

            if (str_contains($text, 'Mission Order Nr.')) {
                $missionLine = sprintf(
                    'Mission Order Nr. %s dated %s',
                    $data['mission_order_number'],
                    $data['mission_order_date_display']
                );
                $this->setParagraphText($paragraph, $missionLine);
                continue;
            }

            if (str_contains($text, 'FACTS OF THE CASE')) {
                $state = 'facts';
                continue;
            }

            if (str_contains($text, 'DISPOSITION')) {
                $state = 'disposition';
                continue;
            }

            if (str_contains($text, 'Name and signature of the FAI')) {
                $state = null;
                $this->setParagraphSegments($paragraph, [
                    ['text' => $data['investigator_name'], 'bold' => true],
                    ['break' => true],
                    ['text' => $data['investigator_role']],
                ], ['align' => 'center']);
                continue;
            }

            if (preg_match('/\bMarco Reyes Santos\b/i', $text)) {
                $state = null;
                $this->setParagraphSegments($paragraph, [
                    ['text' => $data['investigator_name'], 'bold' => true],
                ]);
                continue;
            }

            if (preg_match('/^\s*Fire Arson Investigator\s*$/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['tab' => true],
                    ['tab' => true],
                    ['tab' => true],
                    ['tab' => true],
                    ['tab' => true],
                    ['tab' => true],
                    ['tab' => true],
                    ['tab' => true],
                    ['text' => $data['investigator_role']],
                ], ['tab_stops' => [900, 1800, 2700, 3600, 4500, 5400, 6300, 7200]]);
                continue;
            }

            if (
                str_contains($text, 'Name and signature of the Chief of the')
                || preg_match('/\bLuis Garcia Morales\b/i', $text)
            ) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => $data['noted_by_name'], 'bold' => true],
                    ['break' => true],
                    ['text' => $data['noted_by_role']],
                ], ['align' => 'center']);
                continue;
            }

            if (
                str_contains($text, 'Name and Signature of the Head of Office')
                || preg_match('/\bCarlos Dela Cruz\b/i', $text)
            ) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => $data['approved_by_name'], 'bold' => true],
                    ['break' => true],
                    ['text' => $data['approved_by_role']],
                ], ['align' => 'center']);
                continue;
            }

            if ($state === 'facts') {
                if (
                    str_contains($text, 'This section includes')
                    || str_contains($text, 'Overview of the facts')
                    || str_contains($text, 'The relevant statements')
                    || str_contains($text, 'Affected structures')
                    || str_contains($text, 'Results from the Fire Laboratory Service')
                    || str_contains($text, 'Other relevant matters established')
                ) {
                    $this->clearParagraph($paragraph);
                    continue;
                }

                if (str_contains($text, 'DISPOSITION')) {
                    $state = 'disposition';
                    continue;
                }

                $nextParagraph = $factsParagraphs[$factsParagraphIndex] ?? null;
                if ($nextParagraph !== null) {
                    $this->setParagraphText($paragraph, $nextParagraph, ['first_line' => 720, 'align' => 'both']);
                    $factsParagraphIndex++;
                } else {
                    $this->clearParagraph($paragraph);
                }
                continue;
            }

            if ($state === 'disposition') {
                if (
                    str_contains($text, 'This section should contain')
                    || str_contains($text, 'The disposition and assessment')
                    || str_contains($text, 'May also contain whether the case will be turned over')
                    || str_contains($text, 'Approved for Submission:')
                    || str_contains($text, 'Noted By:')
                    || str_contains($text, 'Intelligence and Investigation Unit')
                    || str_contains($text, 'Fire Arson Investigator')
                ) {
                    $this->clearParagraph($paragraph);
                    continue;
                }

                $nextParagraph = $dispositionParagraphs[$dispositionParagraphIndex] ?? null;
                if ($nextParagraph !== null) {
                    $this->setParagraphText($paragraph, $nextParagraph, ['first_line' => 720, 'align' => 'both']);
                    $dispositionParagraphIndex++;
                } else {
                    $this->clearParagraph($paragraph);
                }
                continue;
            }

            if (
                preg_match('/^[\p{P}\p{S}\s]{1,12}$/u', $text)
            ) {
                $this->clearParagraph($paragraph);
            }
        }

        return $dom->saveXML() ?: $documentXml;
    }

    private function buildFallbackFactsNarrative(ProgressInvestigationReport $report, string $placeOfOccurrence): string
    {
        $spot = $report->spotInvestigation;

        $segments = collect([
            $placeOfOccurrence !== '' ? 'Follow-up investigation was conducted concerning the fire incident at ' . $placeOfOccurrence . '.' : 'Follow-up investigation was conducted concerning the reported fire incident.',
            $report->fireOrigin?->origin ? 'The fire origin was identified as ' . $report->fireOrigin->origin . '.' : null,
            $report->fire_cause ? 'The fire cause established during the investigation was ' . trim($report->fire_cause) . '.' : null,
            $report->responsible_arson ? 'The responsible person or arson subject noted in the report was ' . trim($report->responsible_arson) . '.' : null,
            $spot?->details_of_investigation ? 'Reference was made to the preceding spot investigation findings and other follow-up verification conducted by the assigned Fire Arson Investigator.' : null,
        ])->filter();

        return $segments->implode(' ');
    }

    private function paragraphText(DOMElement $paragraph, DOMXPath $xpath): string
    {
        $parts = $this->collectWordTextNodes($paragraph);

        return trim(preg_replace('/\s+/', ' ', implode('', $parts)) ?? '');
    }

    private function collectWordTextNodes(DOMElement $element): array
    {
        $parts = [];

        try {
            foreach ($element->childNodes as $child) {
                if (! $child instanceof \DOMNode) {
                    continue;
                }

                if ($child instanceof DOMElement && $child->namespaceURI === self::WORD_NS && $child->localName === 't') {
                    $parts[] = $child->textContent;
                    continue;
                }

                if ($child instanceof DOMElement && $child->hasChildNodes()) {
                    array_push($parts, ...$this->collectWordTextNodes($child));
                }
            }
        } catch (\Throwable) {
            return $parts;
        }

        return $parts;
    }

    private function clearParagraph(DOMElement $paragraph): void
    {
        if (! $this->canUseParagraph($paragraph)) {
            return;
        }

        $children = [];
        try {
            foreach ($paragraph->childNodes as $child) {
                $children[] = $child;
            }
        } catch (\Throwable) {
            return;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMElement && $child->localName === 'pPr') {
                continue;
            }

            try {
                $paragraph->removeChild($child);
            } catch (\Throwable) {
                return;
            }
        }
    }

    private function setParagraphText(DOMElement $paragraph, string $text, array $options = []): void
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [''];
        $segments = [];

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $segments[] = ['break' => true];
            }
            $segments[] = ['text' => rtrim($line)];
        }

        $this->setParagraphSegments($paragraph, $segments, $options);
    }

    private function setParagraphSegments(DOMElement $paragraph, array $segments, array $options = []): void
    {
        if (! $this->canUseParagraph($paragraph)) {
            return;
        }

        $this->clearParagraph($paragraph);

        try {
            $document = $paragraph->ownerDocument;
        } catch (\Throwable) {
            return;
        }

        if (! $document) {
            return;
        }

        $paragraphProperties = null;
        foreach ($paragraph->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === 'pPr') {
                $paragraphProperties = $child;
                break;
            }
        }

        if (! $paragraphProperties) {
            $paragraphProperties = $document->createElementNS(self::WORD_NS, 'w:pPr');
            if ($paragraph->firstChild) {
                $paragraph->insertBefore($paragraphProperties, $paragraph->firstChild);
            } else {
                $paragraph->appendChild($paragraphProperties);
            }
        }

        if (array_key_exists('align', $options)) {
            foreach (iterator_to_array($paragraphProperties->childNodes) as $child) {
                if ($child instanceof DOMElement && $child->localName === 'jc') {
                    $paragraphProperties->removeChild($child);
                }
            }

            $alignmentNode = $document->createElementNS(self::WORD_NS, 'w:jc');
            $alignmentNode->setAttributeNS(self::WORD_NS, 'w:val', $options['align']);
            $paragraphProperties->appendChild($alignmentNode);
        }

        if (array_key_exists('tab_stops', $options)) {
            foreach (iterator_to_array($paragraphProperties->childNodes) as $child) {
                if ($child instanceof DOMElement && $child->localName === 'tabs') {
                    $paragraphProperties->removeChild($child);
                }
            }

            if (! empty($options['tab_stops'])) {
                $tabsNode = $document->createElementNS(self::WORD_NS, 'w:tabs');

                foreach ($options['tab_stops'] as $position) {
                    $tabNode = $document->createElementNS(self::WORD_NS, 'w:tab');
                    $tabNode->setAttributeNS(self::WORD_NS, 'w:val', 'left');
                    $tabNode->setAttributeNS(self::WORD_NS, 'w:pos', (string) $position);
                    $tabsNode->appendChild($tabNode);
                }

                $paragraphProperties->appendChild($tabsNode);
            }
        }

        if (array_key_exists('first_line', $options)) {
            foreach (iterator_to_array($paragraphProperties->childNodes) as $child) {
                if ($child instanceof DOMElement && $child->localName === 'ind') {
                    $paragraphProperties->removeChild($child);
                }
            }

            $indentNode = $document->createElementNS(self::WORD_NS, 'w:ind');
            $indentNode->setAttributeNS(self::WORD_NS, 'w:firstLine', (string) $options['first_line']);
            $paragraphProperties->appendChild($indentNode);
        }

        foreach ($segments as $segment) {
            $run = $document->createElementNS(self::WORD_NS, 'w:r');
            $runProperties = $document->createElementNS(self::WORD_NS, 'w:rPr');
            $fonts = $document->createElementNS(self::WORD_NS, 'w:rFonts');
            $fonts->setAttributeNS(self::WORD_NS, 'w:ascii', 'Arial');
            $fonts->setAttributeNS(self::WORD_NS, 'w:hAnsi', 'Arial');
            $fonts->setAttributeNS(self::WORD_NS, 'w:cs', 'Arial');
            $runProperties->appendChild($fonts);

            if (! empty($segment['bold'])) {
                $runProperties->appendChild($document->createElementNS(self::WORD_NS, 'w:b'));
            } else {
                $boldOff = $document->createElementNS(self::WORD_NS, 'w:b');
                $boldOff->setAttributeNS(self::WORD_NS, 'w:val', '0');
                $runProperties->appendChild($boldOff);
            }

            if (! empty($segment['size']) || ! empty($options['size'])) {
                $fontSize = (string) ($segment['size'] ?? $options['size']);
                $sizeNode = $document->createElementNS(self::WORD_NS, 'w:sz');
                $sizeNode->setAttributeNS(self::WORD_NS, 'w:val', $fontSize);
                $runProperties->appendChild($sizeNode);

                $sizeCsNode = $document->createElementNS(self::WORD_NS, 'w:szCs');
                $sizeCsNode->setAttributeNS(self::WORD_NS, 'w:val', $fontSize);
                $runProperties->appendChild($sizeCsNode);
            }

            $run->appendChild($runProperties);

            if (! empty($segment['tab'])) {
                $run->appendChild($document->createElementNS(self::WORD_NS, 'w:tab'));
            }

            if (! empty($segment['break'])) {
                $run->appendChild($document->createElementNS(self::WORD_NS, 'w:br'));
            }

            if (array_key_exists('text', $segment)) {
                $textNode = $document->createElementNS(self::WORD_NS, 'w:t');
                $textValue = (string) $segment['text'];
                if (preg_match('/^\s|\s$/', $textValue)) {
                    $textNode->setAttribute('xml:space', 'preserve');
                }
                $textNode->nodeValue = $textValue;
                $run->appendChild($textNode);
            }

            $paragraph->appendChild($run);
        }
    }

    private function canUseParagraph(DOMElement $paragraph): bool
    {
        try {
            return $paragraph->ownerDocument instanceof DOMDocument;
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolveZipEntryName(ZipArchive $zip, string $expectedPath): ?string
    {
        if ($zip->locateName($expectedPath) !== false) {
            return $expectedPath;
        }

        $normalizedExpected = str_replace('\\', '/', $expectedPath);

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);
            if (! is_string($entryName)) {
                continue;
            }

            if (str_replace('\\', '/', $entryName) === $normalizedExpected) {
                return $entryName;
            }
        }

        return null;
    }

    private function formatBfpLongDate(Carbon|string|null $value): string
    {
        if (! $value) {
            return 'Not specified';
        }

        return Carbon::parse($value)->format('d F Y');
    }

    private function formatOfficerDisplay($officer): string
    {
        if (! $officer) {
            return '';
        }

        $rank = $this->normalizeOfficerRank((string) ($officer->rank ?? ''));
        $name = trim((string) ($officer->user?->full_name ?? ''));

        if ($name === '') {
            $name = trim(($officer->user?->first_name ?? '') . ' ' . ($officer->user?->last_name ?? ''));
        }

        return trim(collect([$rank !== '' ? Str::upper($rank) : null, $name])->filter()->join(' '));
    }

    private function normalizeOfficerRank(string $rank): string
    {
        $rank = trim($rank);
        if ($rank === '') {
            return '';
        }

        $normalized = Str::upper(preg_replace('/\s+/', ' ', $rank) ?? $rank);

        return match ($normalized) {
            'FIRE OFFICER 1' => 'FO1',
            'FIRE OFFICER 2' => 'FO2',
            'FIRE OFFICER 3' => 'FO3',
            'SENIOR FIRE OFFICER 1' => 'SFO1',
            'SENIOR FIRE OFFICER 2' => 'SFO2',
            'SENIOR FIRE OFFICER 3' => 'SFO3',
            'SENIOR FIRE OFFICER 4' => 'SFO4',
            'FIRE INSPECTOR' => 'FINSP',
            'FIRE SENIOR INSPECTOR' => 'FSINSP',
            'FIRE CHIEF INSPECTOR' => 'FCINSP',
            default => $rank,
        };
    }

    private function sanitizeNarrativeBlock(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        $lines = preg_split('/\n/', $text) ?: [];
        $normalizedLines = [];

        foreach ($lines as $line) {
            $line = preg_replace('/^\s*[a-d]\)\s*$/i', '', $line) ?? $line;
            $line = preg_replace('/^\s*[-]+\s*$/u', '', $line) ?? $line;
            $line = preg_replace('/^\s*[a-d]\)\s*/i', '', $line) ?? $line;
            $line = preg_replace('/^\s*[-]+\s*/u', '', $line) ?? $line;
            $line = preg_replace('/[ \t]+/u', ' ', $line) ?? $line;
            $normalizedLines[] = trim($line);
        }

        $text = implode("\n", $normalizedLines);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function splitNarrativeParagraphs(string $text): array
    {
        $text = $this->sanitizeNarrativeBlock($text);
        if ($text === '') {
            return [''];
        }

        $paragraphs = preg_split('/\n\s*\n/', $text) ?: [$text];
        $paragraphs = array_map(function (string $paragraph): string {
            $paragraph = preg_replace("/\n+/", ' ', $paragraph) ?? $paragraph;

            return trim($paragraph);
        }, $paragraphs);

        $paragraphs = array_values(array_filter($paragraphs, fn (string $paragraph) => $paragraph !== ''));

        return $paragraphs !== [] ? $paragraphs : [''];
    }
}
