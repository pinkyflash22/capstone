<?php

namespace App\Services;

use App\Models\AfterActivityReport;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class AfterActivityReportExporter
{
    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const TEMPLATE_PATH = 'app/private/templates/after-activity-report-template.docx';

    public function __construct(private readonly DocxProofImageAppender $proofImageAppender)
    {
    }

    public function buildDocx(AfterActivityReport $report): string
    {
        $template = storage_path(self::TEMPLATE_PATH);
        if (! is_file($template)) {
            throw new RuntimeException('The AAR Word template is missing.');
        }

        $path = tempnam(sys_get_temp_dir(), 'aar_');
        if ($path === false) {
            throw new RuntimeException('Unable to prepare the AAR Word file.');
        }
        $docxPath = $path . '.docx';
        @unlink($path);
        if (! copy($template, $docxPath)) {
            throw new RuntimeException('Unable to copy the AAR Word template.');
        }

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            @unlink($docxPath);
            throw new RuntimeException('Unable to open the AAR Word template.');
        }

        $xml = $zip->getFromName('word/document.xml');
        if (! is_string($xml)) {
            $zip->close();
            @unlink($docxPath);
            throw new RuntimeException('The AAR template is missing its document content.');
        }

        $zip->addFromString('word/document.xml', $this->populateDocumentXml($xml, $report));
        $report->loadMissing('proofImages');
        $this->proofImageAppender->append($zip, $report->proofImages, 'ANNEX A - PICTURES');

        if (! $zip->close()) {
            @unlink($docxPath);
            throw new RuntimeException('Unable to save the AAR Word file.');
        }

        return $docxPath;
    }

    public function buildFilename(AfterActivityReport $report): string
    {
        $activity = Str::slug($report->activity_title) ?: 'activity';

        return "after-activity-report-{$report->after_activity_report_id}-{$activity}.docx";
    }

    private function populateDocumentXml(string $xml, AfterActivityReport $report): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        if (! $dom->loadXML($xml)) {
            throw new RuntimeException('Unable to read the AAR template XML.');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::WORD_NS);
        $paragraphs = iterator_to_array($xpath->query('//w:body//w:p') ?: []);
        $pendingSection = null;
        $sections = [
            '1. Reference/s:' => $report->references ?: 'None',
            '2. Background:' => $report->background ?: 'Not specified.',
            '3. Significance of the Operation/Activity/ies:' => $report->significance ?: 'Not specified.',
            '4. Details of the Operation/Activity/ies:' => collect([
                $report->activity_details,
                'Participants: ' . (count($report->participants ?? []) ? implode('; ', $report->participants) : 'None recorded'),
                'Deployed resources: ' . (count($report->deployed_resources ?? []) ? implode('; ', $report->deployed_resources) : 'None recorded'),
            ])->join("\n"),
            '5. Issues and Challenges Encountered:' => $report->issues_challenges ?: 'None',
            '6. Discussion/Assessment:' => $report->discussion_assessment ?: 'Not specified.',
            'Recommendation/s:' => $report->recommendations ?: 'None',
        ];

        foreach ($paragraphs as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }
            $text = $this->paragraphText($paragraph, $xpath);

            if ($pendingSection !== null) {
                $this->setParagraphText($paragraph, $sections[$pendingSection]);
                $pendingSection = null;
                continue;
            }

            if (array_key_exists($text, $sections)) {
                $pendingSection = $text;
                continue;
            }

            if (str_contains($text, '(Regional/Provincial/District/City/Municipal Letterhead)')) {
                $this->setParagraphText($paragraph, $this->stationLetterhead($report));
            } elseif (str_starts_with($text, 'FOR:')) {
                $this->setParagraphText($paragraph, 'FOR: ' . ($report->memorandum_for ?: 'Respective head of office'));
            } elseif (str_starts_with($text, 'SUBJECT:')) {
                $year = $report->activity_date?->format('Y') ?: now()->format('Y');
                $this->setParagraphText($paragraph, "SUBJECT: AFTER ACTIVITY REPORT ON {$year} {$report->activity_title}");
            } elseif (str_starts_with($text, 'DATE:')) {
                $date = Carbon::parse($report->report_completed_at ?: $report->created_at)->format('d F Y');
                $this->setParagraphText($paragraph, 'DATE: ' . $date);
            } elseif (str_contains($text, 'Please see Annex A')) {
                $this->setParagraphText($paragraph, $report->proofImages->isEmpty() ? 'No pictures attached.' : 'Please see Annex A for the pictures.');
            } elseif (str_contains($text, 'Operations Staff') && str_contains($text, '___')) {
                $suffix = $report->prepared_signature_hash ? ' - Electronically signed' : '';
                $this->setParagraphText($paragraph, $this->officerName($report->preparedBy) . ' - Operations Staff' . $suffix);
            } elseif ($text !== '' && str_contains($text, '___') && ! str_contains($text, 'Operations Staff')) {
                $this->setParagraphText($paragraph, $report->isApproved()
                    ? Str::upper((string) $report->approval_signer_name) . ' - Digitally approved'
                    : 'PENDING COMMAND REVIEW');
            }
        }

        return (string) $dom->saveXML();
    }

    private function stationLetterhead(AfterActivityReport $report): string
    {
        $station = $report->station;
        $address = $station?->address;

        return collect([
            Str::upper((string) ($station?->station_name ?: 'BUREAU OF FIRE PROTECTION')),
            $address?->barangay?->barangay_name,
            $address?->barangay?->city?->city_name,
            $address?->barangay?->city?->province?->province_name,
        ])->filter()->join(', ');
    }

    private function officerName($officer): string
    {
        $user = $officer?->user;
        $name = trim(collect([$user?->first_name, $user?->middle_name, $user?->last_name])->filter()->join(' '));

        return $name !== '' ? Str::upper($name) : 'NOT ASSIGNED';
    }

    private function paragraphText(DOMElement $paragraph, DOMXPath $xpath): string
    {
        $parts = [];
        foreach ($xpath->query('.//w:t', $paragraph) ?: [] as $node) {
            $parts[] = $node->textContent;
        }

        return trim((string) preg_replace('/\s+/', ' ', implode('', $parts)));
    }

    private function setParagraphText(DOMElement $paragraph, string $text): void
    {
        foreach (iterator_to_array($paragraph->childNodes) as $child) {
            if (! ($child instanceof DOMElement && $child->localName === 'pPr')) {
                $paragraph->removeChild($child);
            }
        }
        $run = $paragraph->ownerDocument->createElementNS(self::WORD_NS, 'w:r');
        $textNode = $paragraph->ownerDocument->createElementNS(self::WORD_NS, 'w:t');
        $textNode->setAttribute('xml:space', 'preserve');
        $textNode->appendChild($paragraph->ownerDocument->createTextNode($text));
        $run->appendChild($textNode);
        $paragraph->appendChild($run);
    }
}
