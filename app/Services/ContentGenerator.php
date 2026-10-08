<?php

namespace App\Services;

use App\AI\AIManager;
use App\Enums\ContentStatus;
use App\Enums\GenerationType;
use App\Models\AiGeneration;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;

class ContentGenerator
{
    public function __construct(
        protected AIManager $ai,
        protected VectorSearchService $vectors,
        protected EmbeddingService $embeddings,
        protected ChunkService $chunks,
    ) {}

    /**
     * Genera el contenido markdown de una lección a partir del material de la materia.
     */
    public function generateLesson(Lesson $lesson, ?User $user = null): Lesson
    {
        $course = $lesson->course()->firstOrFail();
        $module = $lesson->module()->first();

        $query = implode(' ', array_filter([
            $lesson->title,
            $module?->title,
            $lesson->summary,
            $course->name,
        ]));

        $matches = $this->vectors->similar($course, $query, 14, ['source_type' => 'document']);

        if ($matches->count() < 3) {
            $matches = $this->vectors->similar($course, $query, 14);
        }

        $chunks = $matches->map(fn ($row) => $row['chunk'])->values();

        if ($chunks->count() < 2) {
            $chunks = $course->chunks()->latest('id')->limit(12)->get()->values();
        }

        $context = $chunks->map(fn ($chunk) => [
            'content' => $chunk->content,
            'reference' => $chunk->referenceLabel(),
            'document' => $chunk->source_label,
        ])->all();

        $system = "Redactás contenido académico para una materia universitaria/terciaria, en español, en formato Markdown.\n"
            ."Reglas estrictas:\n"
            ."- Usá EXCLUSIVAMENTE el contexto provisto, que son fragmentos del material cargado por el docente.\n"
            ."- NO inventes definiciones, datos, ejemplos ni bibliografía que no estén en el contexto.\n"
            ."- Estructurá con encabezados (##, ###), listas y párrafos cortos.\n"
            ."- Terminá cada bloque con una cita en formato: > Fuente: documento, página\n"
            ."- No incluyas el título de la lección (ya se muestra arriba).";

        $userPrompt = 'Lección: '.$lesson->title."\n"
            .($module ? 'Unidad: '.$module->title."\n" : '')
            .($lesson->summary ? 'Resumen sugerido: '.$lesson->summary."\n" : '')
            ."\nContexto del material:\n"
            .json_encode($context, JSON_UNESCAPED_UNICODE);

        $started = microtime(true);
        $provider = $this->ai->provider();
        $error = null;

        try {
            $result = $provider->complete($system, $userPrompt, [
                'task' => 'lesson_content',
                'temperature' => 0.2,
                'context' => [
                    'title' => $lesson->title,
                    'module_title' => $module?->title,
                    'intro' => $module?->summary,
                    'chunks' => $context,
                ],
            ]);
            $content = $result->text;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $provider = $this->ai->local();
            $result = $provider->complete($system, $userPrompt, [
                'task' => 'lesson_content',
                'context' => [
                    'title' => $lesson->title,
                    'module_title' => $module?->title,
                    'intro' => $module?->summary,
                    'chunks' => $context,
                ],
            ]);
            $content = $result->text;
        }

        $status = in_array($lesson->status, [ContentStatus::Approved, ContentStatus::Published], true)
            ? ContentStatus::Review
            : ContentStatus::Generated;

        $lesson->forceFill([
            'content' => $content,
            'sources' => $this->extractSources($content),
            'related_document_ids' => $chunks->pluck('document_id')->filter()->unique()->values()->all(),
            'status' => $status,
            'is_ai_generated' => true,
            'generated_at' => now(),
            'version' => $lesson->version + 1,
        ])->save();

        $this->indexLesson($lesson);

        AiGeneration::create([
            'course_id' => $course->id,
            'user_id' => $user?->id,
            'type' => GenerationType::LessonContent->value,
            'provider' => $provider->name(),
            'model' => $provider->model(),
            'prompt' => mb_substr($userPrompt, 0, 6000),
            'response' => mb_substr($content, 0, 30000),
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            'status' => $error === null ? 'success' : 'fallback',
            'error' => $error,
            'meta' => ['lesson_id' => $lesson->id, 'chunks' => count($context)],
        ]);

        return $lesson->refresh();
    }

    /**
     * Indexa el contenido de una lección para búsqueda y RAG.
     */
    public function indexLesson(Lesson $lesson): void
    {
        $lesson->chunks()->delete();

        $content = trim((string) $lesson->content);

        if ($content === '') {
            return;
        }

        $blocks = [];
        $section = null;

        foreach (preg_split('/\n(?=#{2,4}\s)/', $content) ?: [$content] as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            if (preg_match('/^(#{2,4})\s+(.*)$/', $part, $m)) {
                $section = trim($m[2]);
            }

            $blocks[] = ['text' => $part, 'page' => null, 'section' => $section];
        }

        $chunks = $this->chunks->chunk($blocks);
        $chunks = $this->embeddings->withEmbeddings($chunks);

        foreach ($chunks as $chunk) {
            $lesson->chunks()->create([
                'course_id' => $lesson->course_id,
                'module_id' => $lesson->module_id,
                'source_type' => 'lesson',
                'source_label' => $lesson->title,
                'section' => $chunk['section'],
                'chunk_index' => $chunk['chunk_index'],
                'content' => $chunk['content'],
                'embedding' => EmbeddingService::toDb($chunk['embedding'] ?? null),
            ]);
        }
    }

    /** @return array<int, array{term: string, definition: string, source: ?string}> */
    public function generateGlossary(Course $course, ?User $user = null): array
    {
        $chunks = $course->chunks()->latest('id')->limit(20)->get();

        $context = $chunks->map(fn ($chunk) => [
            'content' => $chunk->content,
            'reference' => $chunk->referenceLabel(),
        ])->all();

        $provider = $this->ai->provider();
        $started = microtime(true);

        $result = $provider->complete(
            'Extraés términos y definiciones del material de una materia. Respondé SOLO JSON: {"terms":[{"term":"","definition":"","source":""}]}',
            json_encode($context, JSON_UNESCAPED_UNICODE),
            ['task' => 'glossary', 'context' => ['chunks' => $context]]
        );

        $terms = $result->json()['terms'] ?? [];

        $course->forceFill([
            'meta' => array_merge($course->meta ?? [], ['glossary' => array_slice($terms, 0, 60)]),
        ])->save();

        AiGeneration::create([
            'course_id' => $course->id,
            'user_id' => $user?->id,
            'type' => GenerationType::Glossary->value,
            'provider' => $provider->name(),
            'model' => $provider->model(),
            'prompt' => 'Generación de glosario',
            'response' => $result->text,
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            'status' => 'success',
            'meta' => ['terms' => count($terms)],
        ]);

        return $terms;
    }

    /** @return array<int, string> */
    protected function extractSources(string $content): array
    {
        preg_match_all('/>\s*Fuente:\s*(.+)$/m', $content, $matches);

        return array_values(array_unique(array_map(fn ($s) => trim($s), $matches[1] ?? [])));
    }
}
