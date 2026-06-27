<?php

namespace App\Services;

use App\Models\SpotInvestigationReport;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class SpotInvestigationSirExporter
{
    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const TEMPLATE_PATH = 'app/private/templates/sir-template-follow.docx';
    private const POWERSHELL_PATH = 'C:\\WINDOWS\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';

    public function buildDocx(SpotInvestigationReport $report): string
    {
        $templatePath = storage_path(self::TEMPLATE_PATH);

        if (! is_file($templatePath)) {
            throw new RuntimeException('The SIR Word template could not be found.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'sir_docx_');
        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create a temporary file for the SIR export.');
        }

        $docxPath = $temporaryPath . '.docx';
        @unlink($docxPath);

        if (! copy($templatePath, $docxPath)) {
            @unlink($temporaryPath);
            throw new RuntimeException('Unable to copy the SIR template.');
        }

        @unlink($temporaryPath);

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            @unlink($docxPath);
            throw new RuntimeException('Unable to open the copied SIR template.');
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if ($documentXml === false) {
            $zip->close();
            @unlink($docxPath);
            throw new RuntimeException('The SIR template is missing its main document XML.');
        }

        $zip->addFromString('word/document.xml', $this->populateDocumentXml($documentXml, $this->buildDocumentData($report)));
        $zip->close();

        return $docxPath;
    }

    public function buildPdfFromWord(SpotInvestigationReport $report): string
    {
        $docxPath = $this->buildDocx($report);

        try {
            return $this->convertDocxToPdfUsingWord($docxPath);
        } finally {
            if (is_file($docxPath)) {
                @unlink($docxPath);
            }
        }
    }

    public function buildFilename(SpotInvestigationReport $report, string $extension): string
    {
        $location = Str::slug((string) ($report->incident?->marker?->title ?? 'incident-location'));

        return sprintf(
            'spot-investigation-report-%s-%s.%s',
            $report->spot_investigation_report_id,
            $location ?: 'incident-location',
            ltrim($extension, '.')
        );
    }

    public function buildDocumentData(SpotInvestigationReport $report): array
    {
        $station = $report->investigator?->station;
        $stationBarangay = $station?->address?->barangay;
        $stationCity = $stationBarangay?->city;
        $stationProvince = $stationCity?->province;

        $incidentBarangay = $report->incident?->barangay;
        $incidentCity = $incidentBarangay?->city;
        $incidentProvince = $incidentCity?->province;
        $incidentContext = $report->incident?->context;

        $investigatorName = $this->formatOfficerDisplay($report->investigator);
        $notedByName = $this->formatOfficerDisplay($report->notedBy);
        $approvedByName = $this->formatOfficerDisplay($report->approvedBy);

        $markerTitle = $report->incident?->marker?->title;
        $placeOfOccurrence = $report->place_of_occurrence ?: collect([
            $markerTitle,
            $incidentBarangay?->barangay_name,
            $incidentCity?->city_name,
            $incidentProvince?->province_name,
        ])->filter()->join(', ');

        $weatherContext = collect($report->weather_context ?? []);
        $weatherSummary = collect([
            $weatherContext->get('condition') ?? $incidentContext?->weather_condition,
            $incidentContext?->temperature !== null ? ('Temp: ' . number_format((float) $incidentContext->temperature, 1) . ' C') : null,
            $incidentContext?->humidity !== null ? ('Humidity: ' . number_format((float) $incidentContext->humidity, 1) . '%') : null,
            $incidentContext?->wind_speed !== null ? ('Wind: ' . number_format((float) $incidentContext->wind_speed, 1) . ' km/h') : null,
            $incidentContext?->wind_direction ? ('Direction: ' . $incidentContext->wind_direction) : null,
        ])->filter()->join(' | ');

        $details = $this->sanitizeNarrativeBlock((string) ($report->details_of_investigation ?? ''));
        if ($details === '') {
            $details = $this->buildFallbackNarrative($report, $placeOfOccurrence, $weatherSummary);
        }

        $disposition = $this->sanitizeNarrativeBlock((string) ($report->disposition ?? ''));
        if ($disposition === '') {
            $disposition = 'Initial spot investigation completed and documented by the assigned Fire Arson Investigator. Further verification and escalation to the next higher office will depend on follow-up findings.';
        }

        $occupancyLabel = $report->occupancy ? Str::headline($report->occupancy) : 'Not specified';
        $involvedSummary = collect([
            $occupancyLabel !== '' ? $occupancyLabel : null,
            $report->involved_structure,
        ])->filter()->join(' / ');

        $stationAddressLine = collect([
            $station?->address?->street,
            $stationBarangay?->barangay_name,
            $stationCity?->city_name,
            $stationProvince?->province_name,
        ])->filter()->join(', ');

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
            'report_number' => $report->spot_investigation_report_id,
            'station_name' => $station?->station_name ?? 'Bureau of Fire Protection',
            'station_code' => $station?->station_code,
            'station_city' => $stationCity?->city_name,
            'station_province' => $stationProvince?->province_name,
            'station_barangay' => $stationBarangay?->barangay_name,
            'memorandum_for' => $report->memorandum_for ?: 'Respective head of office',
            'subject' => 'Spot Investigation Report (SIR)',
            'regional_office_line' => $regionalOfficeLine,
            'district_office_line' => $districtOfficeLine,
            'municipal_office_line' => $municipalOfficeLine,
            'office_letterhead_line' => $letterheadLine,
            'station_address_line' => $stationAddressLine ?: 'Station address not specified',
            'report_completed_at' => $report->report_completed_at,
            'report_completed_display' => $this->formatBfpLongDate($report->report_completed_at),
            'dtpo_display' => $this->buildDtpoDisplay($report, $placeOfOccurrence),
            'place_of_occurrence' => $placeOfOccurrence ?: 'Not specified',
            'involved_display' => $involvedSummary ?: 'Not specified',
            'establishment_name' => $report->establishment_name ?: 'Not specified',
            'owner' => $report->owner ?: 'Not specified',
            'occupant' => $report->occupant ?: 'Not specified',
            'dead' => (int) ($report->dead ?? 0),
            'injured' => (int) ($report->injured ?? 0),
            'dead_display' => $this->formatCountForReport((int) ($report->dead ?? 0)),
            'injured_display' => $this->formatCountForReport((int) ($report->injured ?? 0)),
            'fatality_details' => $report->fatality_details ?: 'No additional fatality details provided.',
            'estimated_damage_display' => $this->formatDamageWords($report->estimated_damage),
            'estimated_area_display' => $report->estimated_area !== null ? number_format((float) $report->estimated_area, 2) . ' sq m' : 'Not specified',
            'affected_establishments_count' => $report->affected_establishments_count,
            'fire_started_display' => $this->formatBfpTimeThenDate($report->fire_started),
            'fire_out_display' => $this->formatBfpTimeThenDate($report->fire_out),
            'highest_alarm_level' => $report->highest_alarm_level ?: 'Not specified',
            'weather_summary' => $weatherSummary ?: 'Not specified',
            'details_of_investigation' => $details,
            'disposition' => $disposition,
            'investigator_name' => $investigatorName !== '' ? $investigatorName : 'Assigned Fire Arson Investigator',
            'investigator_role' => 'Fire Arson Investigator',
            'noted_by_name' => $notedByName !== '' ? $notedByName : 'Not assigned',
            'noted_by_role' => 'Chief, Intelligence and Investigation Unit',
            'approved_by_name' => $approvedByName !== '' ? $approvedByName : 'Not assigned',
            'approved_by_role' => 'Acting Municipal Fire Marshal',
        ];
    }

    private function populateDocumentXml(string $documentXml, array $data): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        if (! $dom->loadXML($documentXml)) {
            throw new RuntimeException('Unable to parse the SIR template XML.');
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
        $detailsParagraphs = $this->splitNarrativeParagraphs((string) $data['details_of_investigation']);
        $detailsParagraphIndex = 0;
        $dispositionParagraphs = $this->splitNarrativeParagraphs((string) $data['disposition']);
        $dispositionParagraphIndex = 0;
        $detailsSpacerPending = false;
        $dispositionSpacerPending = false;

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
                ], [
                    'tab_stops' => [1700, 2400],
                ]);
                continue;
            }

            if (str_contains($text, 'Regional/Provincial/District/City/Municipal') && str_contains($text, 'Letterhead')) {
                $this->setParagraphText($paragraph, $data['office_letterhead_line'], [
                    'size' => 22,
                ]);
                continue;
            }

            if (preg_match('/^DATE\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'DATE', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['report_completed_display'], 'bold' => true],
                ], [
                    'tab_stops' => [1700, 2400],
                ]);
                continue;
            }

            if (preg_match('/^DTPO\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'DTPO', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['dtpo_display'], 'bold' => true],
                ], [
                    'tab_stops' => [3600, 4300, 5000],
                ]);
                continue;
            }

            if (preg_match('/^INVOLVED\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'INVOLVED', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['involved_display'], 'bold' => true],
                ], [
                    'tab_stops' => [3600, 4300, 5000],
                ]);
                continue;
            }

            if (preg_match('/^NAME OF ESTABLISHMENT\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'NAME OF ESTABLISHMENT', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['establishment_name'], 'bold' => true],
                ], [
                    'tab_stops' => [3600, 4300, 5000],
                ]);
                continue;
            }

            if (preg_match('/^OWNER\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'OWNER', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['owner'], 'bold' => true],
                ], [
                    'tab_stops' => [3600, 4300, 5000],
                ]);
                continue;
            }

            if (preg_match('/^OCCUPANT\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'OCCUPANT', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['occupant'], 'bold' => true],
                ], [
                    'tab_stops' => [3600, 4300, 5000],
                ]);
                continue;
            }

            if (str_contains($text, 'Fatality')) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'CASUALTY', 'bold' => true],
                    ['tab' => true],
                    ['text' => 'Fatality', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => (string) $data['dead_display'], 'bold' => true],
                ], [
                    'tab_stops' => [1800, 3600, 4300],
                ]);
                continue;
            }

            if (preg_match('/^Injured\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['tab' => true],
                    ['text' => 'Injured', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => (string) $data['injured_display'], 'bold' => true],
                ], [
                    'tab_stops' => [1800, 3600, 4300],
                ]);
                continue;
            }

            if (preg_match('/^ESTIMATED DAMAGE\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'ESTIMATED DAMAGE', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['estimated_damage_display'], 'bold' => true],
                ], [
                    'tab_stops' => [3600, 4300, 5000],
                ]);
                continue;
            }

            if (preg_match('/^TIME FIRE STARTED\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'TIME FIRE STARTED', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['fire_started_display'], 'bold' => true],
                ], [
                    'tab_stops' => [3600, 4300, 5000],
                ]);
                continue;
            }

            if (preg_match('/^TIME OF FIRE OUT\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'TIME OF FIRE OUT', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['fire_out_display'], 'bold' => true],
                ], [
                    'tab_stops' => [3600, 4300, 5000],
                ]);
                continue;
            }

            if (preg_match('/^ALARM\b/i', $text)) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'ALARM', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['highest_alarm_level'], 'bold' => true],
                ], [
                    'tab_stops' => [3600, 4300, 5000],
                ]);
                continue;
            }

            if (str_contains($text, 'DETAILS OF INVESTIGATION')) {
                $state = 'details';
                $detailsInjected = false;
                $detailsSpacerPending = true;
                continue;
            }

            if (str_contains($text, 'DISPOSITION')) {
                $state = 'disposition';
                $dispositionInjected = false;
                $dispositionSpacerPending = true;
                continue;
            }

            if (str_contains($text, 'Name and signature of the FAI') || str_contains($text, 'F01 Marco Reyes Santos')) {
                $state = null;
                $this->setParagraphSegments($paragraph, [
                    ['text' => $data['investigator_name'], 'bold' => true],
                    ['break' => true],
                    ['text' => $data['investigator_role']],
                ], [
                    'align' => 'right',
                ]);
                continue;
            }

            if (preg_match('/^Fire Arson Investigator$/i', $text)) {
                $this->clearParagraph($paragraph);
                continue;
            }

            if (str_contains($text, 'SUBJECT') && str_contains($text, 'Spot Investigation Report')) {
                $this->setParagraphSegments($paragraph, [
                    ['text' => 'SUBJECT', 'bold' => true],
                    ['tab' => true],
                    ['text' => ':', 'bold' => true],
                    ['tab' => true],
                    ['text' => $data['subject'], 'bold' => true],
                ], [
                    'tab_stops' => [1700, 2400],
                ]);
                continue;
            }

            if (str_contains($text, 'Name and signature of the Chief') || str_contains($text, 'F01 Luis Garcia Morales') || str_contains($text, 'Chief, Intelligence and Investigation Unit')) {
                $state = null;
                $this->setParagraphSegments($paragraph, [
                    ['text' => $data['noted_by_name'], 'bold' => true],
                    ['break' => true],
                    ['text' => $data['noted_by_role']],
                ], [
                    'align' => 'left',
                ]);
                continue;
            }

            if (str_contains($text, 'Name and Signature of the Head of Office') || str_contains($text, 'SF04 TERESA C PASCUA') || str_contains($text, 'Acting Municipal Fire Marshal')) {
                $state = null;
                $this->setParagraphSegments($paragraph, [
                    ['text' => $data['approved_by_name'], 'bold' => true],
                    ['break' => true],
                    ['text' => $data['approved_by_role']],
                ], [
                    'align' => 'left',
                ]);
                continue;
            }

            if ($state === 'details') {
                if ($detailsSpacerPending) {
                    $detailsSpacerPending = false;
                    continue;
                }

                if (isset($detailsParagraphs[$detailsParagraphIndex])) {
                    $this->setParagraphText($paragraph, $detailsParagraphs[$detailsParagraphIndex], [
                        'align' => 'both',
                        'first_line' => 720,
                    ]);
                    $detailsParagraphIndex++;
                } else {
                    $this->clearParagraph($paragraph);
                }

                continue;
            }

            if ($state === 'disposition') {
                if ($dispositionSpacerPending) {
                    $dispositionSpacerPending = false;
                    continue;
                }

                if (isset($dispositionParagraphs[$dispositionParagraphIndex])) {
                    $this->setParagraphText($paragraph, $dispositionParagraphs[$dispositionParagraphIndex], [
                        'align' => 'both',
                        'first_line' => 720,
                    ]);
                    $dispositionParagraphIndex++;
                } else {
                    $this->clearParagraph($paragraph);
                }

                continue;
            }

            if (
                preg_match('/^[\p{P}\p{S}\s]{1,8}$/u', $text)
                || preg_match('/^\s*[a-d]\)\s*$/i', $text)
                || preg_match('/^approved for submission:?$/i', $text)
                || str_contains($text, 'This section should contain')
                || str_contains($text, 'Number of establishments')
                || str_contains($text, 'Estimated area in square meters')
                || str_contains($text, 'Location of fatalities')
                || str_contains($text, 'Weather condition')
                || str_contains($text, 'Other initial information')
                || str_contains($text, 'The disposition and assessment')
                || str_contains($text, 'May also contain whether the case will be turned over')
                || str_contains($text, 'Intelligence and Investigation Unit or Chief of the next higher office')
                || str_contains($text, 'i.e. C')
            ) {
                $this->clearParagraph($paragraph);
                continue;
            }
        }

        return $dom->saveXML() ?: $documentXml;
    }

    private function buildDtpoDisplay(SpotInvestigationReport $report, string $placeOfOccurrence): string
    {
        if (! $report->fire_started) {
            return $placeOfOccurrence ?: 'Not specified';
        }

        $timestamp = Carbon::parse($report->fire_started);

        return trim(sprintf(
            '%s %s %s, %s',
            $timestamp->format('d'),
            $timestamp->format('Hi') . 'H',
            $timestamp->format('F Y'),
            $placeOfOccurrence ?: 'Not specified'
        ));
    }

    private function buildFallbackNarrative(SpotInvestigationReport $report, string $placeOfOccurrence, string $weatherSummary): string
    {
        $segments = new Collection([
            'Spot investigation was conducted for Incident #' . $report->incident_id . '.',
            $placeOfOccurrence !== '' ? 'Place of occurrence: ' . $placeOfOccurrence . '.' : null,
            $report->affected_establishments_count ? 'Affected establishments: ' . $report->affected_establishments_count . '.' : null,
            $report->estimated_area !== null ? 'Estimated affected area: ' . number_format((float) $report->estimated_area, 2) . ' square meters.' : null,
            $report->estimated_damage !== null ? 'Estimated damage: ' . $this->formatMoney($report->estimated_damage) . '.' : null,
            $weatherSummary !== '' ? 'Weather condition during response: ' . $weatherSummary . '.' : null,
            ((int) $report->dead > 0 || (int) $report->injured > 0)
                ? 'Casualties recorded: ' . (int) $report->dead . ' fatality/fatalities and ' . (int) $report->injured . ' injured.'
                : 'No casualties were recorded during the initial spot investigation.',
        ]);

        return $segments->filter()->implode(' ');
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

        if (! empty($options['align'])) {
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

    private function formatDate(Carbon|string|null $value): string
    {
        if (! $value) {
            return 'Not specified';
        }

        return Carbon::parse($value)->format('F j, Y');
    }

    private function formatTime(Carbon|string|null $value): string
    {
        if (! $value) {
            return 'Not specified';
        }

        return Carbon::parse($value)->format('g:i A');
    }

    private function formatBfpLongDate(Carbon|string|null $value): string
    {
        if (! $value) {
            return 'Not specified';
        }

        return Carbon::parse($value)->format('d F Y');
    }

    private function formatBfpTime(Carbon|string|null $value): string
    {
        if (! $value) {
            return 'Not specified';
        }

        return Carbon::parse($value)->format('Hi') . 'H';
    }

    private function formatBfpDateTime(Carbon|string|null $value): string
    {
        if (! $value) {
            return 'Not specified';
        }

        $timestamp = Carbon::parse($value);

        return $timestamp->format('d F Y') . ', ' . $timestamp->format('Hi') . 'H';
    }

    private function formatBfpTimeThenDate(Carbon|string|null $value): string
    {
        if (! $value) {
            return 'Not specified';
        }

        $timestamp = Carbon::parse($value);

        return $timestamp->format('Hi') . 'H ' . $timestamp->format('d F Y');
    }

    private function formatDateTime(Carbon|string|null $value): string
    {
        if (! $value) {
            return 'Not specified';
        }

        return Carbon::parse($value)->format('F j, Y g:i A');
    }

    private function formatMoney(float|int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return 'Php 0.00';
        }

        return 'Php ' . number_format((float) $value, 2);
    }

    private function formatDamageWords(float|int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return 'Zero Pesos (Php 0)';
        }

        $amount = (int) round((float) $value);
        if ($amount <= 0) {
            return 'Zero Pesos (Php 0)';
        }

        return $this->numberToWords($amount) . ' Pesos (Php ' . number_format($amount) . ')';
    }

    private function formatCountForReport(int $count): string
    {
        if ($count <= 0) {
            return 'Negative';
        }

        return $this->numberToWords($count) . ' (' . $count . ')';
    }

    private function numberToWords(int $number): string
    {
        $ones = [
            0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
        ];
        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety',
        ];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            $ten = intdiv($number, 10);
            $remainder = $number % 10;

            return $tens[$ten] . ($remainder ? '-' . $ones[$remainder] : '');
        }

        if ($number < 1000) {
            $hundreds = intdiv($number, 100);
            $remainder = $number % 100;

            return $ones[$hundreds] . ' Hundred' . ($remainder ? ' ' . $this->numberToWords($remainder) : '');
        }

        if ($number < 1000000) {
            $thousands = intdiv($number, 1000);
            $remainder = $number % 1000;

            return $this->numberToWords($thousands) . ' Thousand' . ($remainder ? ' ' . $this->numberToWords($remainder) : '');
        }

        $millions = intdiv($number, 1000000);
        $remainder = $number % 1000000;

        return $this->numberToWords($millions) . ' Million' . ($remainder ? ' ' . $this->numberToWords($remainder) : '');
    }

    private function formatOfficerDisplay($officer): string
    {
        if (! $officer) {
            return '';
        }

        $rank = $this->normalizeOfficerRank((string) ($officer->rank ?? ''));
        $name = trim((string) ($officer->user?->full_name ?? ''));

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

    private function convertDocxToPdfUsingWord(string $docxPath): string
    {
        if (! is_file($docxPath)) {
            throw new RuntimeException('The generated SIR Word file could not be found for PDF conversion.');
        }

        if (! is_file(self::POWERSHELL_PATH)) {
            throw new RuntimeException('PowerShell is not available for Word-to-PDF conversion.');
        }

        $pdfPath = preg_replace('/\.docx$/i', '.pdf', $docxPath);
        if (! is_string($pdfPath) || $pdfPath === $docxPath) {
            $pdfPath = $docxPath . '.pdf';
        }

        @unlink($pdfPath);

        $script = <<<'PS'
$docxPath = '__DOCX_PATH__'
$pdfPath = '__PDF_PATH__'
$word = $null
$document = $null

try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $document = $word.Documents.Open($docxPath, $false, $true)
    $wdExportFormatPDF = 17
    $document.ExportAsFixedFormat($pdfPath, $wdExportFormatPDF)
}
finally {
    if ($document -ne $null) {
        $document.Close([ref]$false) | Out-Null
        [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($document)
    }

    if ($word -ne $null) {
        $word.Quit() | Out-Null
        [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($word)
    }

    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}

if (-not (Test-Path -LiteralPath $pdfPath)) {
    throw 'PDF file was not created.'
}
PS;

        $script = str_replace(
            ['__DOCX_PATH__', '__PDF_PATH__'],
            [$this->escapeForPowerShellSingleQuotes($docxPath), $this->escapeForPowerShellSingleQuotes($pdfPath)],
            $script
        );

        $encodedScript = base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8'));

        $result = Process::timeout(120)->run(sprintf(
            '"%s" -NoProfile -NonInteractive -ExecutionPolicy Bypass -EncodedCommand %s',
            self::POWERSHELL_PATH,
            $encodedScript
        ));

        if ($result->failed() || ! is_file($pdfPath)) {
            @unlink($pdfPath);

            throw new RuntimeException('Unable to convert the SIR Word file to PDF through Microsoft Word. ' . trim($result->errorOutput() ?: $result->output()));
        }

        return $pdfPath;
    }

    private function escapeForPowerShellSingleQuotes(string $value): string
    {
        return str_replace("'", "''", $value);
    }

    private function cleanupSectionPlaceholderParagraphs(array $paragraphs, DOMXPath $xpath, string $startMarker, string $endMarker): void
    {
        $withinSection = false;

        foreach ($paragraphs as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }

            $text = $this->paragraphText($paragraph, $xpath);

            if ($text !== '' && str_contains($text, $startMarker)) {
                $withinSection = true;
                continue;
            }

            if ($withinSection && $text !== '' && str_contains($text, $endMarker)) {
                return;
            }

            if (! $withinSection) {
                continue;
            }

            if (
                preg_match('/^[\p{P}\p{S}\s]{1,12}$/u', $text)
                || preg_match('/^\s*[a-d]\)\s*$/i', $text)
            ) {
                $this->clearParagraph($paragraph);
            }
        }
    }
}
