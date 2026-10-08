<?php

namespace App\Services\Processors;

use App\Enums\DocumentType;
use App\Models\Document;

/**
 * Extractor DOCX basado en ZipArchive + XML de Word.
 * Evita dependencias pesadas y permite detectar estilos de encabezado.
 */
class DocxExtractor implements Extractor
{
    public function supports(DocumentType $type): bool
    {
        return $type === DocumentType::Docx;
    }

    public function extract(Document $document, string $absolutePath): ExtractionResult
    {
        $zip = new \ZipArchive();

        if ($zip->open($absolutePath) !== true) {
            throw new \RuntimeException('No se pudo abrir el archivo DOCX.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $styles = $zip->getFromName('word/styles.xml');
        $zip->close();

        if ($xml === false) {
            throw new \RuntimeException('El archivo DOCX no contiene word/document.xml.');
        }

        $styleMap = $this->styleMap((string) $styles);

        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;

        if (! @$dom->loadXML($this->sanitizeXml((string) $xml))) {
            throw new \RuntimeException('No se pudo interpretar el XML del DOCX.');
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $blocks = [];
        $headings = [];
        $lines = [];
        $section = null;

        foreach ($xpath->query('//w:body/w:p') as $paragraph) {
            $text = trim($this->paragraphText($xpath, $paragraph));

            if ($text === '') {
                continue;
            }

            $style = '';
            $styleNode = $xpath->query('./w:pPr/w:pStyle', $paragraph)->item(0);
            if ($styleNode !== null) {
                $style = $styleNode->attributes->getNamedItem('w:val')?->nodeValue ?? '';
            }

            $level = $this->headingLevel($style, $styleMap);

            if ($level > 0) {
                $headings[] = ['level' => $level, 'text' => $text];
                $section = $text;
                $blocks[] = ['text' => $text, 'page' => null, 'section' => $section];
            } else {
                $blocks[] = ['text' => $text, 'page' => null, 'section' => $section];
            }

            $lines[] = $text;
        }

        foreach ($xpath->query('//w:body/w:tbl') as $table) {
            foreach ($xpath->query('.//w:tr', $table) as $row) {
                $cells = [];
                foreach ($xpath->query('.//w:tc', $row) as $cell) {
                    $cellText = [];
                    foreach ($xpath->query('.//w:t', $cell) as $node) {
                        $cellText[] = $node->textContent;
                    }
                    $cells[] = trim(implode(' ', $cellText));
                }
                $rowText = trim(implode(' | ', array_filter($cells)));

                if ($rowText !== '') {
                    $blocks[] = ['text' => $rowText, 'page' => null, 'section' => $section];
                    $lines[] = $rowText;
                }
            }
        }

        return new ExtractionResult(
            text: trim(implode("\n", $lines)),
            blocks: $blocks,
            headings: array_slice($headings, 0, 200),
            pages: null,
            meta: ['parser' => 'zip+xml', 'format' => 'docx'],
        );
    }

    protected function paragraphText(\DOMXPath $xpath, \DOMNode $paragraph): string
    {
        $parts = [];

        foreach ($xpath->query('.//w:t', $paragraph) as $node) {
            $parts[] = $node->textContent;
        }

        return implode('', $parts);
    }

    protected function styleMap(string $stylesXml): array
    {
        if ($stylesXml === '') {
            return [];
        }

        $dom = new \DOMDocument();
        if (! @$dom->loadXML($this->sanitizeXml($stylesXml))) {
            return [];
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $map = [];

        foreach ($xpath->query('//w:style') as $style) {
            $id = $style->attributes->getNamedItem('w:styleId')?->nodeValue ?? '';
            $nameNode = $xpath->query('./w:name', $style)->item(0);
            $name = $nameNode?->attributes->getNamedItem('w:val')?->nodeValue ?? '';

            if ($id !== '' && $name !== '') {
                $map[$id] = $name;
            }
        }

        return $map;
    }

    protected function headingLevel(string $styleId, array $styleMap): int
    {
        $name = $styleMap[$styleId] ?? $styleId;

        if (preg_match('/heading\s*(\d)/i', $name, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/t[íi]tulo\s*(\d)/iu', $name, $m)) {
            return (int) $m[1];
        }

        if (preg_match('/^(?:[Tt]ítulo|[Hh]eadline)\s*(\d)$/', $styleId, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    protected function sanitizeXml(string $xml): string
    {
        return preg_replace('/&(?!#?\w+;)/', '&amp;', $xml) ?? $xml;
    }
}
