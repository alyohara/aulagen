<?php

namespace App\Services\Processors;

use App\Enums\DocumentType;
use App\Models\Document;

interface Extractor
{
    public function supports(DocumentType $type): bool;

    /** @throws \RuntimeException si el archivo no pudo procesarse */
    public function extract(Document $document, string $absolutePath): ExtractionResult;
}
