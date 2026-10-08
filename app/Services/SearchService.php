<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Str;

class SearchService
{
    public function __construct(
        protected VectorSearchService $vectors,
        protected EmbeddingService $embeddings,
    ) {}

    /**
     * Búsqueda híbrida (semántica + palabras clave) dentro de una materia.
     *
     * @return array<int, array{type: string, title: string, subtitle: ?string, snippet: string, score: float, lesson_id: ?int, module_id: ?int, source: ?string}>
     */
    public function search(Course $course, string $query, int $limit = 12): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $results = [];
        $seenLessons = [];
        $seenChunks = [];

        foreach ($this->vectors->similar($course, $query, 8) as $row) {
            $chunk = $row['chunk'];
            $lesson = $chunk->source_type === 'lesson' ? $chunk->lesson : null;

            $seenChunks[$chunk->id] = true;
            if ($lesson !== null) {
                $seenLessons[$lesson->id] = true;
            }

            $results[] = [
                'type' => $lesson !== null ? 'lesson' : 'material',
                'title' => $lesson?->title ?? $chunk->source_label,
                'subtitle' => $lesson?->module?->title ?? $chunk->module?->title,
                'snippet' => $this->excerpt($chunk->content, $query),
                'score' => 0.6 + min(0.4, max(0.0, $row['score'])),
                'lesson_id' => $lesson?->id,
                'module_id' => $chunk->module_id ?? $lesson?->module_id,
                'source' => $chunk->source_label,
            ];
        }

        foreach ($this->keywordChunks($course, $query) as $chunk) {
            if (isset($seenChunks[$chunk->id])) {
                continue;
            }

            $lesson = $chunk->source_type === 'lesson' ? $chunk->lesson : null;
            $seenChunks[$chunk->id] = true;

            if ($lesson !== null) {
                if (isset($seenLessons[$lesson->id])) {
                    continue;
                }
                $seenLessons[$lesson->id] = true;
            }

            $results[] = [
                'type' => $lesson !== null ? 'lesson' : 'material',
                'title' => $lesson?->title ?? $chunk->source_label,
                'subtitle' => $lesson?->module?->title ?? $chunk->module?->title,
                'snippet' => $this->excerpt($chunk->content, $query),
                'score' => 0.5,
                'lesson_id' => $lesson?->id,
                'module_id' => $chunk->module_id ?? $lesson?->module_id,
                'source' => $chunk->source_label,
            ];
        }

        foreach ($this->keywordLessons($course, $query) as $lesson) {
            if (isset($seenLessons[$lesson->id])) {
                continue;
            }
            $seenLessons[$lesson->id] = true;

            $results[] = [
                'type' => 'lesson',
                'title' => $lesson->title,
                'subtitle' => $lesson->module?->title,
                'snippet' => $this->excerpt((string) $lesson->content, $query),
                'score' => 0.9,
                'lesson_id' => $lesson->id,
                'module_id' => $lesson->module_id,
                'source' => null,
            ];
        }

        return collect($results)
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();
    }

    protected function keywordChunks(Course $course, string $query, int $limit = 15)
    {
        $term = '%'.addslashes($query).'%';

        return $course->chunks()
            ->where(function ($builder) use ($term) {
                $builder->where('content', 'like', $term)
                    ->orWhere('source_label', 'like', $term);
            })
            ->limit($limit)
            ->get();
    }

    protected function keywordLessons(Course $course, string $query)
    {
        $term = '%'.addslashes($query).'%';

        return Lesson::query()
            ->where('course_id', $course->id)
            ->whereIn('status', ['approved', 'published'])
            ->where(function ($builder) use ($term) {
                $builder->where('title', 'like', $term)
                    ->orWhere('summary', 'like', $term)
                    ->orWhere('content', 'like', $term);
            })
            ->with('module')
            ->limit(10)
            ->get();
    }

    protected function excerpt(string $text, string $query, int $length = 220): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');

        if ($text === '') {
            return '';
        }

        $position = stripos($text, $query);

        if ($position === false) {
            foreach (preg_split('/\s+/', $query) ?: [] as $token) {
                if (mb_strlen($token) < 3) {
                    continue;
                }
                $found = stripos($text, $token);
                if ($found !== false) {
                    $position = $found;
                    break;
                }
            }
        }

        if ($position === false) {
            return Str::limit($text, $length);
        }

        $start = max(0, $position - (int) ($length / 3));

        return ($start > 0 ? '…' : '').mb_substr($text, $start, $length).((int) $position + $length < mb_strlen($text) ? '…' : '');
    }
}
