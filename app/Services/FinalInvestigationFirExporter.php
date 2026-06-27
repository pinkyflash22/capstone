<?php

namespace App\Services;

use App\Models\FinalInvestigationReport;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class FinalInvestigationFirExporter
{
    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const TEMPLATE_PATH = 'app/private/templates/final-investigation-template-source-v2.docx';
    private const SUBSTANTIATING_DOCUMENT_OPTIONS = [
        'Spot Investigation Report',
        'Progress Investigation Report',
        'Affidavit of loss (itemized and duly notarized)',
        'Sworn statement of loss submitted to insurance adjusters / companies',
        'Latest complete inventory of stocks prior to the fire incident',
        'Complete inventory of salvaged items after the fire incident',
        'Complete copies of insurance policies to include co-insurances',
        'Sworn statements of witnesses',
        'Income Tax Return (ITR) for the last three (3) years',
        'Financial statements for the last three (3) years',
        'Balance sheets for the last three (3) years',
        'Mayor\'s permit and Business license',
        'Occupancy permit',
        'Department of Trade and Industry (DTI) registration',
        'Securities and Exchange Commission (SEC) registration',
        'Latest Fire Safety Inspection Certificate (FSIC)',
        'Complete list of employees',
        'Approved Floor, Building and Electrical Plans',
        'Copy of lease contract agreement',
        'Land title / tax declaration',
        'Fire Laboratory Services Report',
        'Photographs of the fire scene',
    ];

    public function buildDocx(FinalInvestigationReport $report): string
    {
        $templatePath = storage_path(self::TEMPLATE_PATH);

        if (! is_file($templatePath)) {
            throw new RuntimeException('The FIR Word template could not be found.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'fir_docx_');
        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create a temporary file for the FIR export.');
        }

        $docxPath = $temporaryPath . '.docx';
        @unlink($docxPath);

        if (! copy($templatePath, $docxPath)) {
            @unlink($temporaryPath);
            throw new RuntimeException('Unable to copy the FIR template.');
        }

        @unlink($temporaryPath);

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            @unlink($docxPath);
            throw new RuntimeException('Unable to open the copied FIR template.');
        }

        $documentEntryName = $this->resolveZipEntryName($zip, 'word/document.xml');
        $documentXml = $documentEntryName ? $zip->getFromName($documentEntryName) : false;
        if ($documentXml === false) {
            $zip->close();
            @unlink($docxPath);
            throw new RuntimeException('The FIR template is missing its main document XML.');
        }

        $zip->addFromString($documentEntryName, $this->populateDocumentXml($documentXml, $this->buildDocumentData($report)));
        $zip->close();

        return $docxPath;
    }

    public function buildFilename(FinalInvestigationReport $report, string $extension): string
    {
        $location = Str::slug((string) ($report->progressInvestigation?->spotInvestigation?->incident?->marker?->title ?? 'incident-location'));

        return sprintf(
            'final-investigation-report-%s-%s.%s',
            $report->final_investigation_report_id,
            $location ?: 'incident-location',
            ltrim($extension, '.')
        );
    }

    public function buildDocumentData(FinalInvestigationReport $report): array
    {
        $station = $report->investigator?->station;
        $stationBarangay = $station?->address?->barangay;
        $stationCity = $stationBarangay?->city;
        $stationProvince = $stationCity?->province;

        $progress = $report->progressInvestigation;
        $spot = $progress?->spotInvestigation;
        $incident = $spot?->incident;
        $incidentBarangay = $incident?->barangay;
        $incidentCity = $incidentBarangay?->city;
        $incidentProvince = $incidentCity?->province;

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

        $officeAddress = $report->office_address ?: collect([
            $station?->station_name,
            $station?->address?->street,
            $stationBarangay?->barangay_name,
            $stationCity?->city_name,
            $stationProvince?->province_name,
        ])->filter()->join(', ');

        $placeOfFire = $report->exact_address ?: $spot?->place_of_occurrence ?: collect([
            $incident?->marker?->title,
            $incidentBarangay?->barangay_name,
            $incidentCity?->city_name,
            $incidentProvince?->province_name,
        ])->filter()->join(', ');

        $facts = $this->sanitizeNarrativeBlock((string) ($report->facts_of_case ?? ''));
        if ($facts === '') {
            $facts = $this->buildFallbackFactsNarrative($report, $placeOfFire);
        }

        $discussion = $this->sanitizeNarrativeBlock((string) ($report->discussion ?? ''));
        if ($discussion === '') {
            $discussion = $this->buildFallbackDiscussionNarrative($report);
        }

        $findings = $this->sanitizeNarrativeBlock((string) ($report->findings ?? ''));
        if ($findings === '') {
            $findings = $this->buildFallbackFindingsNarrative($report);
        }

        $recommendation = $this->sanitizeNarrativeBlock((string) ($report->recommendation ?? ''));
        if ($recommendation === '') {
            $recommendation = 'This Final Investigation Report is respectfully recommended for filing, reference, and such further action as may be deemed appropriate by the concerned office.';
        }

        return [
            'memorandum_for' => $report->memorandum_for ?? 'Respective head of office',
            'subject' => 'Final Investigation Report',
            'report_completed_display' => $this->formatBfpLongDate($report->report_completed_at ?: $report->created_at),
            'office_letterhead_line' => $letterheadLine,
            'office_address' => $officeAddress ?: 'Complete address of the respective Investigation and Intelligence Office',
            'place_of_fire' => $placeOfFire ?: 'Not specified',
            'alarm_at_display' => $this->formatBfpAlarmDateTime($report->alarm_at ?: $spot?->fire_started ?: $incident?->created_at),
            'establishment_burned' => $report->establishment_burned ?: $this->deriveEstablishmentBurned($spot),
            'damage_to_property' => $this->formatMoney($report->total_damage),
            'fire_victims' => $report->fire_victims ?: $this->deriveVictimSummary($spot),
            'origin_of_fire' => $report->origin_of_fire ?: ($progress?->fireOrigin?->origin ?: 'Not specified'),
            'cause_of_fire' => $report->cause_of_fire ?: ($progress?->fire_cause ?: 'Not specified'),
            'substantiating_documents' => array_values(array_filter((array) ($report->substantiating_documents ?? []))),
            'facts_of_case' => $facts,
            'discussion' => $discussion,
            'findings' => $findings,
            'recommendation' => $recommendation,
            'signed_statement' => sprintf(
                'SIGNED this %s at the %s, Philippines.',
                $this->formatBfpLongDate($report->report_completed_at ?: $report->created_at),
                $officeAddress ?: ($station?->station_name ?: 'concerned office')
            ),
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
            throw new RuntimeException('Unable to parse the FIR template XML.');
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
        $discussionParagraphs = $this->splitNarrativeParagraphs((string) $data['discussion']);
        $discussionParagraphIndex = 0;
        $findingsParagraphs = $this->splitNarrativeParagraphs((string) $data['findings']);
        $findingsParagraphIndex = 0;
        $recommendationParagraphs = $this->splitNarrativeParagraphs((string) $data['recommendation']);
        $recommendationParagraphIndex = 0;
        $selectedSubstantiatingDocuments = array_values(array_filter((array) ($data['substantiating_documents'] ?? [])));
        $selectedSubstantiatingIndex = 0;

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

            if (str_contains($text, 'Complete address of the respective Investigation and Intelligence Office')) {
                $this->setParagraphText($paragraph, $data['office_address']);
                continue;
            }

            if (str_contains($text, 'Complete address where the fire incident occurred')) {
                $this->setParagraphText($paragraph, $data['place_of_fire']);
                continue;
            }

            if (str_contains($text, '0000H Date / Month / Year')) {
                $this->setParagraphText($paragraph, $data['alarm_at_display']);
                continue;
            }

            if (str_contains($text, 'Name of establishment gutted by fire')) {
                $this->setParagraphText($paragraph, $data['establishment_burned']);
                continue;
            }

            if (str_contains($text, 'Amount of damage in Peso')) {
                $this->setParagraphText($paragraph, $data['damage_to_property']);
                continue;
            }

            if (str_contains($text, 'State the complete name of the fire victim')) {
                $this->setParagraphText($paragraph, $data['fire_victims']);
                continue;
            }

            if (str_contains($text, '06. ORIGIN OF FIRE')) {
                $this->setParagraphText($paragraph, '06. ORIGIN OF FIRE: ' . $data['origin_of_fire']);
                continue;
            }

            if (str_contains($text, '07. CAUSE OF FIRE')) {
                $this->setParagraphText($paragraph, '07. CAUSE OF FIRE: ' . $data['cause_of_fire']);
                continue;
            }

            $normalizedSubstantiatingText = $this->normalizeSubstantiatingDocumentLabel($text);
            if (in_array($normalizedSubstantiatingText, self::SUBSTANTIATING_DOCUMENT_OPTIONS, true)) {
                $nextSelectedDocument = $selectedSubstantiatingDocuments[$selectedSubstantiatingIndex] ?? null;
                if ($nextSelectedDocument !== null) {
                    $letterPrefix = chr(65 + $selectedSubstantiatingIndex) . '. ';
                    $this->setParagraphText($paragraph, $letterPrefix . $nextSelectedDocument, ['first_line' => 360, 'align' => 'both']);
                    $selectedSubstantiatingIndex++;
                } else {
                    $this->clearParagraph($paragraph);
                }
                continue;
            }

            if (str_contains($text, 'FACTS OF THE CASE')) {
                $state = 'facts';
                continue;
            }

            if (str_contains($text, 'DISCUSSION')) {
                $state = 'discussion';
                continue;
            }

            if (str_contains($text, 'FINDINGS')) {
                $state = 'findings';
                continue;
            }

            if (str_contains($text, 'RECOMMENDATION')) {
                $state = 'recommendation';
                continue;
            }

            if (str_contains($text, 'SIGNED this')) {
                $state = null;
                $this->setParagraphText($paragraph, $data['signed_statement']);
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

            if (str_contains($text, 'Name and signature of the Chief of the')) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => $data['noted_by_name'], 'bold' => true],
                    ['break' => true],
                    ['text' => $data['noted_by_role']],
                ], ['align' => 'center']);
                continue;
            }

            if (str_contains($text, 'Name and Signature of the Head of Office')) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => $data['approved_by_name'], 'bold' => true],
                    ['break' => true],
                    ['text' => $data['approved_by_role']],
                ], ['align' => 'center']);
                continue;
            }

            if ($state === 'facts') {
                if (
                    str_contains($text, 'This section should contain')
                    || str_contains($text, 'Details of the fire incident')
                    || str_contains($text, 'Physical details of the burned premises')
                    || str_contains($text, 'Relevant matters contained in the submitted substantiating documents')
                    || str_contains($text, 'Relevant statements of witnesses')
                    || str_contains($text, 'Results from the Fire Laboratory Service')
                    || str_contains($text, 'Other relevant matters established')
                ) {
                    $this->clearParagraph($paragraph);
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

            if ($state === 'discussion') {
                if (
                    str_contains($text, 'This section should contain')
                    || str_contains($text, 'An exhaustive discussion on how the origin and cause of fire was established')
                    || str_contains($text, 'How the facts and circumstances established support the findings')
                    || str_contains($text, 'The principles and procedures adhered to by the FAI')
                    || str_contains($text, 'The discussion must be supported by the photographs')
                ) {
                    $this->clearParagraph($paragraph);
                    continue;
                }

                $nextParagraph = $discussionParagraphs[$discussionParagraphIndex] ?? null;
                if ($nextParagraph !== null) {
                    $this->setParagraphText($paragraph, $nextParagraph, ['first_line' => 720, 'align' => 'both']);
                    $discussionParagraphIndex++;
                } else {
                    $this->clearParagraph($paragraph);
                }
                continue;
            }

            if ($state === 'findings') {
                if (
                    str_contains($text, 'This section should contain')
                    || str_contains($text, 'Whether the fire was ACCIDENTAL or INTENTIONAL in nature')
                    || str_contains($text, 'Other relevant findings')
                ) {
                    $this->clearParagraph($paragraph);
                    continue;
                }

                $nextParagraph = $findingsParagraphs[$findingsParagraphIndex] ?? null;
                if ($nextParagraph !== null) {
                    $this->setParagraphText($paragraph, $nextParagraph, ['first_line' => 720, 'align' => 'both']);
                    $findingsParagraphIndex++;
                } else {
                    $this->clearParagraph($paragraph);
                }
                continue;
            }

            if ($state === 'recommendation') {
                if (
                    str_contains($text, 'This section should contain')
                    || str_contains($text, 'recommendation/s of the FAI')
                ) {
                    $this->clearParagraph($paragraph);
                    continue;
                }

                $nextParagraph = $recommendationParagraphs[$recommendationParagraphIndex] ?? null;
                if ($nextParagraph !== null) {
                    $this->setParagraphText($paragraph, $nextParagraph, ['first_line' => 720, 'align' => 'both']);
                    $recommendationParagraphIndex++;
                } else {
                    $this->clearParagraph($paragraph);
                }
                continue;
            }

            if (
                preg_match('/^[\p{P}\p{S}\s]{1,12}$/u', $text)
                || str_contains($text, 'LOGO')
            ) {
                $this->clearParagraph($paragraph);
            }
        }

        return $dom->saveXML() ?: $documentXml;
    }

    private function buildFallbackFactsNarrative(FinalInvestigationReport $report, string $placeOfFire): string
    {
        $progress = $report->progressInvestigation;
        $spot = $progress?->spotInvestigation;

        return collect([
            $placeOfFire !== '' ? 'Final investigation was conducted regarding the fire incident at ' . $placeOfFire . '.' : 'Final investigation was conducted regarding the reported fire incident.',
            $report->establishment_burned ? 'The involved establishment was recorded as ' . trim($report->establishment_burned) . '.' : null,
            $report->fire_victims ? 'Reported fire victims were noted as ' . trim($report->fire_victims) . '.' : null,
            $report->total_damage !== null ? 'The reported property damage amounted to ' . $this->formatMoney($report->total_damage) . '.' : null,
            $progress?->facts_of_case ? 'Reference was made to the linked progress investigation report and the substantiating documents submitted for evaluation.' : null,
            $spot?->details_of_investigation ? 'The scene findings documented in the preceding spot investigation report were also considered.' : null,
        ])->filter()->implode(' ');
    }

    private function buildFallbackDiscussionNarrative(FinalInvestigationReport $report): string
    {
        return collect([
            $report->origin_of_fire ? 'The origin of fire was established as ' . trim($report->origin_of_fire) . '.' : null,
            $report->cause_of_fire ? 'The cause of fire was established as ' . trim($report->cause_of_fire) . '.' : null,
            'The foregoing determination was based on the available scene findings, linked investigation reports, and substantiating documents submitted to the investigating office.',
        ])->filter()->implode(' ');
    }

    private function buildFallbackFindingsNarrative(FinalInvestigationReport $report): string
    {
        return collect([
            $report->cause_of_fire ? 'Based on the established cause, the fire was evaluated in accordance with the available investigative findings.' : null,
            $report->fire_victims ? 'The investigation also took into account the reported victim information reflected in the case records.' : null,
            'All relevant findings were documented by the assigned Fire Arson Investigator for final case disposition.',
        ])->filter()->implode(' ');
    }

    private function deriveEstablishmentBurned($spot): string
    {
        $segments = collect([
            $spot?->establishment_name,
            $spot?->occupancy ? Str::title(str_replace('_', ' ', (string) $spot->occupancy)) : null,
        ])->filter()->values();

        if ($segments->isEmpty()) {
            return 'Not specified';
        }

        return $segments->count() > 1
            ? $segments[0] . ' (' . $segments[1] . ')'
            : (string) $segments[0];
    }

    private function deriveVictimSummary($spot): string
    {
        if ($spot?->fatality_details) {
            return (string) $spot->fatality_details;
        }

        $dead = (int) ($spot?->dead ?? 0);
        $injured = (int) ($spot?->injured ?? 0);

        if ($dead === 0 && $injured === 0) {
            return 'No reported fire victims.';
        }

        return "Dead: {$dead}; Injured: {$injured}";
    }

    private function normalizeSubstantiatingDocumentLabel(string $label): string
    {
        $label = trim($label);
        $label = preg_replace('/^\[[xX\s]?\]\s*/', '', $label) ?? $label;
        $label = preg_replace('/^[A-Z]\.\s*/', '', $label) ?? $label;

        return trim($label);
    }

    private function formatMoney($value): string
    {
        return 'PHP ' . number_format((float) ($value ?? 0), 2);
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
            $segments[] = ['text' => trim($line)];
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

    private function formatBfpAlarmDateTime(Carbon|string|null $value): string
    {
        if (! $value) {
            return 'Not specified';
        }

        return Carbon::parse($value)->format('Hi\H d / F / Y');
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
            $line = preg_replace('/^\s*[a-z0-9]+\)\s*/i', '', $line) ?? $line;
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
