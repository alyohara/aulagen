<?php

namespace App\Services\Processors;

use App\Enums\DocumentType;
use App\Models\Document;

class ExtractorRegistry
{
    /** @var array<int, Extractor> */
    protected array $extractors = [];

    public function __construct()
    {
        $this->extractors = [
            new PdfExtractor(),
            new DocxExtractor(),
            new PptxExtractor(),
            new XlsxExtractor(),
            new TextExtractor(),
        ];
    }

    public function for(DocumentType $type): ?Extractor
    {
        foreach ($this->extractors as $extractor) {
            if ($extractor->supports($type)) {
                return $extractor;
            }
        }

        return null;
    }

    public function extract(Document $document, string $absolutePath): ExtractionResult
    {
        $extractor = $this->for($document->type);

        if ($extractor === null) {
            return new ExtractionResult(text: (string) $document->extracted_text);
        }

        return $extractor->extract($document, $absolutePath);
    }
}
