<?php

namespace App\Services;

use App\Models\ContentChunk;
use App\Models\Course;
use App\Support\Database;
use Illuminate\Support\Collection;

class VectorSearchService
{
    public function __construct(protected EmbeddingService $embeddings)
    {
    }

    /**
     * Búsqueda semántica por similitud de coseno.
     *
     * @return Collection<int, array{chunk: ContentChunk, score: float}>
     */
    public function similar(Course $course, string $query, int $topK = 6, array $filters = []): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        $vector = $this->embeddings->embed($query);
        $vectorSql = EmbeddingService::toDb($vector);

        if (Database::usesVector()) {
            $queryBuilder = ContentChunk::query()
                ->where('course_id', $course->id)
                ->whereNotNull('embedding')
                ->selectRaw('content_chunks.*, 1 - (embedding <=> ?::vector) AS score', [$vectorSql]);

            foreach ($filters as $column => $value) {
                $queryBuilder->where($column, $value);
            }

            $rows = $queryBuilder
                ->orderByDesc('score')
                ->limit($topK)
                ->get();

            return $rows->map(fn (ContentChunk $chunk) => [
                'chunk' => $chunk,
                'score' => (float) ($chunk->score ?? 0),
            ]);
        }

        // Fallback sin pgvector: similitud de coseno calculada en PHP.
        $queryBuilder = ContentChunk::query()->where('course_id', $course->id);

        foreach ($filters as $column => $value) {
            $queryBuilder->where($column, $value);
        }

        return $queryBuilder->limit(3000)
            ->get()
            ->map(function (ContentChunk $chunk) use ($vector) {
                $other = EmbeddingService::fromDb($chunk->embedding);

                return ['chunk' => $chunk, 'score' => $other === null ? 0.0 : self::cosine($vector, $other)];
            })
            ->filter(fn ($row) => $row['score'] > 0.001)
            ->sortByDesc('score')
            ->take($topK)
            ->values();
    }

    /** @param array<int, float> $a @param array<int, float> $b */
    public static function cosine(array $a, array $b): float
    {
        $length = min(count($a), count($b));

        if ($length === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA <= 0 || $normB <= 0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
