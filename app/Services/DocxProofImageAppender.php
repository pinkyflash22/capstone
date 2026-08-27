<?php

namespace App\Services;

use App\Models\InvestigationReportImage;
use App\Support\MediaPath;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use ZipArchive;

class DocxProofImageAppender
{
    private const REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';
    private const CONTENT_TYPES_NS = 'http://schemas.openxmlformats.org/package/2006/content-types';
    private const IMAGE_REL_TYPE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image';
    private const EMU_PER_INCH = 914400;
    private const MAX_WIDTH_EMU = 5760720; // 6.3 inches
    private const MAX_HEIGHT_EMU = 3749040; // 4.1 inches; normally fits two photos per page

    /** @param Collection<int, InvestigationReportImage> $images */
    public function append(ZipArchive $zip, Collection $images): void
    {
        if ($images->isEmpty()) {
            return;
        }

        $documentPath = $this->resolveEntry($zip, 'word/document.xml');
        $relationshipsPath = $this->resolveEntry($zip, 'word/_rels/document.xml.rels');
        $contentTypesPath = $this->resolveEntry($zip, '[Content_Types].xml');

        if (! $documentPath || ! $relationshipsPath || ! $contentTypesPath) {
            throw new RuntimeException('The Word document is missing files required to append proof images.');
        }

        $documentXml = $zip->getFromName($documentPath);
        $relationshipsXml = $zip->getFromName($relationshipsPath);
        $contentTypesXml = $zip->getFromName($contentTypesPath);

        if ($documentXml === false || $relationshipsXml === false || $contentTypesXml === false) {
            throw new RuntimeException('Unable to read the Word document package for proof images.');
        }

        $relationships = $this->loadXml($relationshipsXml, 'Word relationships');
        $relationshipRoot = $relationships->documentElement;
        $contentTypes = $this->loadXml($contentTypesXml, 'Word content types');
        $contentTypesRoot = $contentTypes->documentElement;
        $appendXml = $this->headingXml();

        foreach ($images->values() as $index => $image) {
            try {
                $bytes = Storage::disk(MediaPath::diskFor($image->file_path))
                    ->get((string) MediaPath::stripPrefix($image->file_path));
            } catch (Throwable $exception) {
                report($exception);
                $bytes = null;
            }

            if (! is_string($bytes) || $bytes === '') {
                $appendXml .= $this->unavailableImageXml($index + 1, (string) $image->file_name);
                continue;
            }

            $imageInfo = @getimagesizefromstring($bytes);
            $mime = strtolower((string) ($imageInfo['mime'] ?? ''));
            if (! in_array($mime, ['image/png', 'image/jpeg'], true)) {
                $appendXml .= $this->unavailableImageXml($index + 1, (string) $image->file_name);
                continue;
            }

            $extension = $mime === 'image/png' ? 'png' : 'jpeg';
            $contentType = $extension === 'png' ? 'image/png' : 'image/jpeg';
            $mediaName = sprintf('proof-image-%d-%d.%s', $image->investigation_report_image_id, $index + 1, $extension);
            $relationshipId = 'rIdProofImage' . $image->investigation_report_image_id . '_' . ($index + 1);

            $zip->addFromString('word/media/' . $mediaName, $bytes);
            $this->addRelationship($relationships, $relationshipRoot, $relationshipId, 'media/' . $mediaName);
            $this->ensureContentType($contentTypes, $contentTypesRoot, $extension, $contentType);

            [$widthEmu, $heightEmu] = $this->scaledDimensions($bytes);
            $appendXml .= $this->imageXml(
                $relationshipId,
                50000 + (int) $index,
                $widthEmu,
                $heightEmu,
                'Proof image ' . ($index + 1),
                (string) $image->file_name
            );
        }

        $insertAt = strripos($documentXml, '<w:sectPr');
        if ($insertAt === false) {
            $insertAt = strripos($documentXml, '</w:body>');
        }

        if ($insertAt === false) {
            throw new RuntimeException('Unable to place proof images after the Word report content.');
        }

        $updatedDocumentXml = substr($documentXml, 0, $insertAt)
            . $appendXml
            . substr($documentXml, $insertAt);

        // Refuse to write malformed document XML into an otherwise valid ZIP.
        $this->loadXml($updatedDocumentXml, 'updated Word document');

        $zip->addFromString($documentPath, $updatedDocumentXml);
        $zip->addFromString($relationshipsPath, (string) $relationships->saveXML());
        $zip->addFromString($contentTypesPath, (string) $contentTypes->saveXML());
    }

    private function headingXml(): string
    {
        return '<w:p><w:pPr><w:pageBreakBefore/><w:jc w:val="center"/></w:pPr>'
            . '<w:r><w:rPr><w:b/><w:sz w:val="28"/></w:rPr><w:t>PHOTO DOCUMENTATION</w:t></w:r></w:p>';
    }

    private function imageXml(string $relationshipId, int $drawingId, int $width, int $height, string $name, string $caption): string
    {
        $safeName = htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $safeCaption = htmlspecialchars($caption, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="120" w:after="80"/></w:pPr><w:r><w:drawing>'
            . '<wp:inline xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"'
            . ' xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"'
            . ' xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
            . ' distT="0" distB="0" distL="0" distR="0"><wp:extent cx="' . $width . '" cy="' . $height . '"/>'
            . '<wp:effectExtent l="0" t="0" r="0" b="0"/><wp:docPr id="' . $drawingId . '" name="' . $safeName . '"/>'
            . '<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr><a:graphic>'
            . '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic>'
            . '<pic:nvPicPr><pic:cNvPr id="' . $drawingId . '" name="' . $safeName . '"/><pic:cNvPicPr/></pic:nvPicPr>'
            . '<pic:blipFill><a:blip r:embed="' . $relationshipId . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            . '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $width . '" cy="' . $height . '"/></a:xfrm>'
            . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic>'
            . '</wp:inline></w:drawing></w:r></w:p>'
            . '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="160"/></w:pPr>'
            . '<w:r><w:rPr><w:i/><w:sz w:val="18"/></w:rPr><w:t>' . $safeCaption . '</w:t></w:r></w:p>';
    }

    private function unavailableImageXml(int $number, string $filename): string
    {
        $safeFilename = htmlspecialchars($filename !== '' ? $filename : 'Unnamed image', ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:before="120" w:after="160"/></w:pPr>'
            . '<w:r><w:rPr><w:i/><w:color w:val="7F1D1D"/><w:sz w:val="18"/></w:rPr>'
            . '<w:t>Proof image ' . $number . ' could not be embedded: ' . $safeFilename . '</w:t></w:r></w:p>';
    }

    private function scaledDimensions(string $bytes): array
    {
        $size = @getimagesizefromstring($bytes);
        $width = max(1, (int) ($size[0] ?? 1200));
        $height = max(1, (int) ($size[1] ?? 800));
        $widthEmu = $width * 9525;
        $heightEmu = $height * 9525;
        $scale = min(1, self::MAX_WIDTH_EMU / $widthEmu, self::MAX_HEIGHT_EMU / $heightEmu);

        return [(int) round($widthEmu * $scale), (int) round($heightEmu * $scale)];
    }

    private function addRelationship(DOMDocument $dom, ?DOMElement $root, string $id, string $target): void
    {
        if (! $root) {
            throw new RuntimeException('The Word relationships file is invalid.');
        }

        $relationship = $dom->createElementNS(self::REL_NS, 'Relationship');
        $relationship->setAttribute('Id', $id);
        $relationship->setAttribute('Type', self::IMAGE_REL_TYPE);
        $relationship->setAttribute('Target', $target);
        $root->appendChild($relationship);
    }

    private function ensureContentType(DOMDocument $dom, ?DOMElement $root, string $extension, string $contentType): void
    {
        if (! $root) {
            throw new RuntimeException('The Word content types file is invalid.');
        }

        foreach ($root->childNodes as $node) {
            if ($node instanceof DOMElement && strtolower($node->getAttribute('Extension')) === $extension) {
                return;
            }
        }

        $default = $dom->createElementNS(self::CONTENT_TYPES_NS, 'Default');
        $default->setAttribute('Extension', $extension);
        $default->setAttribute('ContentType', $contentType);
        $root->appendChild($default);
    }

    private function loadXml(string $xml, string $label): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        if (! $dom->loadXML($xml)) {
            throw new RuntimeException("Unable to parse the {$label} XML.");
        }

        return $dom;
    }

    private function resolveEntry(ZipArchive $zip, string $expected): ?string
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (is_string($name) && strtolower(str_replace('\\', '/', $name)) === strtolower($expected)) {
                return $name;
            }
        }

        return null;
    }
}
