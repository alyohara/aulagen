<?php

namespace App\Services\Processors;

use App\Enums\DocumentType;
use App\Models\Document;

class TextExtractor implements Extractor
{
    public function supports(DocumentType $type): bool
    {
        return in_array($type, [DocumentType::Txt, DocumentType::Markdown, DocumentType::Text, DocumentType::Image], true);
    }

    public function extract(Document $document, string $absolutePath): ExtractionResult
    {
        $raw = @file_get_contents($absolutePath);

        if ($raw === false) {
            throw new \RuntimeException('No se pudo leer el archivo de texto.');
        }

        if (! mb_check_encoding($raw, 'UTF-8')) {
            $raw = @mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1') ?: $raw;
        }

        return $this->fromText($raw);
    }

    public function fromText(string $raw, ?int $page = null): ExtractionResult
    {
        $lines = preg_split('/\r?\n/', $raw) ?: [];
        $blocks = [];
        $headings = [];
        $section = null;
        $buffer = [];

        $flush = function () use (&$buffer, &$blocks, $page, &$section) {
            $text = trim(implode("\n", $buffer));
            $buffer = [];

            if ($text !== '') {
                $blocks[] = ['text' => $text, 'page' => $page, 'section' => $section];
            }
        };

        foreach ($lines as $line) {
            $trimmed = rtrim($line);

            if (preg_match('/^(#{1,6})\s+(.*)$/', $trimmed, $m)) {
                $flush();
                $level = strlen($m[1]);
                $text = trim($m[2]);
                $headings[] = ['level' => $level, 'text' => $text];
                $section = $text;
                $blocks[] = ['text' => $text, 'page' => $page, 'section' => $section];

                continue;
            }

            if (trim($trimmed) === '') {
                $flush();

                continue;
            }

            $buffer[] = $trimmed;
        }

        $flush();

        if ($blocks === []) {
            $chunks = str_split(trim($raw) ?: '', 4000);
            foreach ($chunks as $chunk) {
                $blocks[] = ['text' => $chunk, 'page' => $page, 'section' => null];
            }
        }

        return new ExtractionResult(
            text: trim($raw),
            blocks: $blocks,
            headings: array_slice($headings, 0, 300),
            pages: null,
            meta: ['parser' => 'text'],
        );
    }
}
