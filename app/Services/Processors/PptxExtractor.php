<?php

namespace App\Services\Processors;

use App\Enums\DocumentType;
use App\Models\Document;

class PptxExtractor implements Extractor
{
    public function supports(DocumentType $type): bool
    {
        return $type === DocumentType::Pptx;
    }

    public function extract(Document $document, string $absolutePath): ExtractionResult
    {
        $zip = new \ZipArchive();

        if ($zip->open($absolutePath) !== true) {
            throw new \RuntimeException('No se pudo abrir el archivo PPTX.');
        }

        $slides = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = $stat['name'] ?? '';

            if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $name, $m)) {
                $slides[(int) $m[1]] = $name;
            }
        }

        ksort($slides);

        if ($slides === []) {
            $zip->close();
            throw new \RuntimeException('El PPTX no contiene diapositivas.');
        }

        $blocks = [];
        $headings = [];
        $lines = [];

        foreach ($slides as $number => $name) {
            $xml = $zip->getFromName($name);

            if ($xml === false) {
                continue;
            }

            $dom = new \DOMDocument();
            if (! @$dom->loadXML(preg_replace('/&(?!#?\w+;)/', '&amp;', (string) $xml))) {
                continue;
            }

            $xpath = new \DOMXPath($dom);
            $texts = [];

            foreach ($xpath->query('//*[local-name()="t"]') as $node) {
                $value = trim($node->textContent);
                if ($value !== '') {
                    $texts[] = $value;
                }
            }

            if ($texts === []) {
                continue;
            }

            $title = $texts[0];
            $body = implode("\n", array_slice($texts, 1));

            $headings[] = ['level' => 2, 'text' => 'Diapositiva '.$number.': '.$title];
            $blocks[] = ['text' => $title."\n".$body, 'page' => $number, 'section' => $title];
            $lines[] = 'Diapositiva '.$number.': '.$title."\n".$body;
        }

        $zip->close();

        return new ExtractionResult(
            text: trim(implode("\n\n", $lines)),
            blocks: $blocks,
            headings: array_slice($headings, 0, 300),
            pages: count($slides),
            meta: ['parser' => 'zip+xml', 'format' => 'pptx', 'slides' => count($slides)],
        );
    }
}
