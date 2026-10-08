<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Services\Processors\ExtractorRegistry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentProcessor
{
    public function __construct(
        protected ExtractorRegistry $extractors,
        protected ChunkService $chunks,
        protected EmbeddingService $embeddings,
    ) {}

    public function process(Document $document): void
    {
        $document->forceFill([
            'status' => DocumentStatus::Processing,
            'processing_started_at' => now(),
            'error_message' => null,
        ])->save();

        try {
            if (in_array($document->type, [DocumentType::Link, DocumentType::Video], true)) {
                $this->processExternal($document);

                return;
            }

            $absolutePath = Storage::disk($document->disk)->path($document->path);

            if (! is_file($absolutePath)) {
                throw new \RuntimeException('El archivo ya no existe en el almacenamiento.');
            }

            $result = $this->extractors->extract($document, $absolutePath);

            $text = $result->text;

            if (trim($text) === '') {
                throw new \RuntimeException(
                    $document->type === DocumentType::Image
                        ? 'No se pudo extraer texto de la imagen (OCR no habilitado en el MVP).'
                        : 'No se pudo extraer texto del archivo.'
                );
            }

            $blocks = $result->blocks ?: [['text' => $text, 'page' => null, 'section' => null]];
            $chunks = $this->chunks->chunk($blocks);
            $chunks = $this->embeddings->withEmbeddings($chunks);

            $document->chunks()->delete();

            foreach ($chunks as $chunk) {
                $document->chunks()->create([
                    'course_id' => $document->course_id,
                    'module_id' => $document->module_id,
                    'source_type' => 'document',
                    'source_label' => $document->original_name,
                    'section' => $chunk['section'],
                    'chunk_index' => $chunk['chunk_index'],
                    'page_from' => $chunk['page_from'],
                    'page_to' => $chunk['page_to'],
                    'content' => $chunk['content'],
                    'embedding' => EmbeddingService::toDb($chunk['embedding'] ?? null),
                ]);
            }

            $document->forceFill([
                'status' => DocumentStatus::Ready,
                'extracted_text' => mb_substr($text, 0, 2000000),
                'page_count' => $result->pages,
                'chunk_count' => count($chunks),
                'meta' => array_merge($document->meta ?? [], [
                    'headings' => array_slice($result->headings, 0, 200),
                    'parser_meta' => $result->meta,
                ]),
                'processed_at' => now(),
                'error_message' => null,
            ])->save();
        } catch (\Throwable $e) {
            Log::error('Fallo al procesar documento #'.$document->id.': '.$e->getMessage());

            $document->forceFill([
                'status' => DocumentStatus::Failed,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ])->save();
        }
    }

    protected function processExternal(Document $document): void
    {
        $url = (string) $document->source_url;
        $text = $document->original_name.($url !== '' ? "\n".$url : '');

        $document->chunks()->delete();

        $document->chunks()->create([
            'course_id' => $document->course_id,
            'module_id' => $document->module_id,
            'source_type' => 'document',
            'source_label' => $document->original_name,
            'content' => $text,
            'embedding' => EmbeddingService::toDb($this->embeddings->embed($text)),
        ]);

        $document->forceFill([
            'status' => DocumentStatus::Ready,
            'extracted_text' => $text,
            'chunk_count' => 1,
            'processed_at' => now(),
            'meta' => array_merge($document->meta ?? [], ['external' => true]),
        ])->save();
    }
}
