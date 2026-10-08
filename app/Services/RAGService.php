<?php

namespace App\Services;

use App\AI\AIManager;
use App\Enums\GenerationType;
use App\Models\AiGeneration;
use App\Models\ContentChunk;
use App\Models\Course;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RAGService
{
    public function __construct(
        protected AIManager $ai,
        protected VectorSearchService $vectors,
        protected EmbeddingService $embeddings,
    ) {}

    /**
     * Responde una pregunta usando exclusivamente el material de la materia (RAG).
     *
     * @return array{answer: string, sources: array<int, string>, provider: string, chunks: int}
     */
    public function ask(Course $course, string $question, ?User $user = null): array
    {
        $settings = $course->ensureSettings();

        if (! $settings->ai_assistant_enabled) {
            return [
                'answer' => 'El asistente de IA está desactivado para esta materia.',
                'sources' => [],
                'provider' => 'disabled',
                'chunks' => 0,
            ];
        }

        $question = trim($question);
        $topK = (int) config('ai.rag_top_k', 6);

        $matches = $this->vectors->similar($course, $question, $topK);

        if ($matches->isEmpty()) {
            // Fallback léxico: si no hay embeddings útiles, buscamos por palabras clave.
            $term = '%'.addslashes($question).'%';
            $matches = $course->chunks()
                ->where('content', 'like', $term)
                ->limit($topK)
                ->get()
                ->map(fn (ContentChunk $chunk) => ['chunk' => $chunk, 'score' => 0.4])
                ->values();
        }

        $chunks = $matches->map(fn ($row) => $row['chunk'])->values();

        $context = $chunks->map(fn (ContentChunk $chunk) => [
            'content' => $chunk->content,
            'reference' => $chunk->referenceLabel(),
            'module' => $chunk->module?->title,
            'source_type' => $chunk->source_type,
        ])->all();

        $system = $this->systemPrompt($course, $settings);

        $userPrompt = "Pregunta del alumno: {$question}\n\nContexto del material:\n"
            .$this->formatContext($context);

        $started = microtime(true);
        $provider = $this->ai->provider();
        $error = null;
        $answer = '';

        try {
            $result = $provider->complete($system, $userPrompt, [
                'task' => 'assistant',
                'temperature' => 0.1,
                'context' => ['question' => $question, 'chunks' => $context],
            ]);
            $answer = $result->text;
        } catch (\Throwable $e) {
            Log::warning('Fallo proveedor de IA ('.$provider->name().'): '.$e->getMessage());
            $error = $e->getMessage();

            $result = $this->ai->local()->complete($system, $userPrompt, [
                'task' => 'assistant',
                'context' => ['question' => $question, 'chunks' => $context],
            ]);
            $answer = $result->text;
            $provider = $this->ai->local();
        }

        $sources = $this->extractSources($answer);

        AiGeneration::create([
            'course_id' => $course->id,
            'user_id' => $user?->id,
            'type' => GenerationType::Assistant->value,
            'provider' => $provider->name(),
            'model' => $provider->model(),
            'prompt' => mb_substr($question, 0, 4000),
            'response' => mb_substr($answer, 0, 20000),
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            'status' => $error === null ? 'success' : 'fallback',
            'error' => $error,
            'meta' => ['chunks' => count($context)],
        ]);

        if (! $settings->show_sources) {
            $sources = [];
        }

        return [
            'answer' => $answer,
            'sources' => $sources,
            'provider' => $provider->label(),
            'chunks' => count($context),
        ];
    }

    protected function systemPrompt(Course $course, $settings): string
    {
        $base = "Sos el asistente de la materia \"{$course->name}\""
            .($course->institution ? " de {$course->institution}" : '').".\n"
            ."Respondé siempre en español, con un tono académico y claro.\n"
            ."Reglas estrictas:\n"
            ."- Usá EXCLUSIVAMENTE la información del contexto provisto, que proviene del material cargado por el docente.\n"
            ."- NO inventes datos, definiciones ni ejemplos que no estén en el contexto.\n"
            ."- Citá la fuente al final de cada idea: > Fuente: documento, página.\n"
            ."- Si el contexto no alcanza para responder, decí claramente que no está en el material de la materia.";

        if ($settings->allow_external_knowledge) {
            $base .= "\n- El docente autorizó complementar con conocimiento general, indicalo explícitamente cuando lo hagas.";
        }

        return $base;
    }

    protected function formatContext(array $context): string
    {
        $max = (int) config('ai.max_context_chars', 24000);
        $out = '';
        $used = 0;

        foreach ($context as $index => $item) {
            $block = '['.($index + 1).'] '.($item['module'] ? $item['module']."\n" : '')
                .$item['content']."\n> Fuente: {$item['reference']}\n";

            if ($used + strlen($block) > $max) {
                break;
            }

            $used += strlen($block);
            $out .= $block."\n";
        }

        return $out;
    }

    /** @return array<int, string> */
    protected function extractSources(string $answer): array
    {
        preg_match_all('/>\s*Fuente:\s*(.+)$/m', $answer, $matches);

        $sources = array_map(fn ($s) => trim($s), $matches[1] ?? []);

        return array_values(array_unique($sources));
    }
}
