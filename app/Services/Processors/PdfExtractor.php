<?php

namespace App\Services\Processors;

use App\Enums\DocumentType;
use App\Models\Document;
use Smalot\PdfParser\Parser;

class PdfExtractor implements Extractor
{
    public function supports(DocumentType $type): bool
    {
        return $type === DocumentType::Pdf;
    }

    public function extract(Document $document, string $absolutePath): ExtractionResult
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($absolutePath);
        $pages = $pdf->getPages();

        $blocks = [];
        $headings = [];
        $full = [];

        foreach ($pages as $index => $page) {
            $text = trim($page->getText());
            $pageNumber = $index + 1;

            if ($text === '') {
                continue;
            }

            $blocks[] = ['text' => $text, 'page' => $pageNumber, 'section' => null];
            $full[] = $text;

            foreach (preg_split('/\r?\n/', $text) ?: [] as $line) {
                $line = trim($line);

                if ($line === '' || mb_strlen($line) > 90 || mb_strlen($line) < 4) {
                    continue;
                }

                $looksLikeHeading = preg_match('/^(\d+(\.\d+)*)[\s\-\).]+[A-ZÁÉÍÓÚÑ]/u', $line) === 1
                    || (mb_strtoupper(mb_substr($line, 0, 1)) === mb_substr($line, 0, 1)
                        && ! str_contains($line, '.')
                        && substr_count($line, ' ') < 8
                        && preg_match('/[a-záéíóúñ]{3,}/u', $line) === 1);

                if ($looksLikeHeading) {
                    $level = substr_count($line, '.') > 0 ? min(3, substr_count($line, '.') + 1) : 1;
                    $headings[] = ['level' => $level, 'text' => $line];
                }
            }
        }

        return new ExtractionResult(
            text: trim(implode("\n\n", $full)),
            blocks: $blocks,
            headings: array_slice($headings, 0, 200),
            pages: count($pages),
            meta: ['parser' => 'smalot/pdfparser'],
        );
    }
}
