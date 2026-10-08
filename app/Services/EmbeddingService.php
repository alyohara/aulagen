<?php

namespace App\Services;

use App\AI\AIManager;
use App\Models\ContentChunk;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    public function __construct(protected AIManager $ai)
    {
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedMany(array $texts): array
    {
        $texts = array_values(array_filter($texts, fn ($t) => trim((string) $t) !== ''));

        if ($texts === []) {
            return [];
        }

        $provider = $this->ai->provider();
        $vectors = [];

        try {
            $vectors = $provider->embedMany($texts);
        } catch (\Throwable $e) {
            Log::warning('Embeddings con '.$provider->name().' fallaron: '.$e->getMessage());
        }

        // Fallback local: nunca dejamos la búsqueda sin vectores.
        if (count($vectors) !== count($texts)) {
            $local = $this->ai->local();
            $vectors = [];

            foreach ($texts as $text) {
                try {
                    $vectors[] = $local->embed($text);
                } catch (\Throwable) {
                    $vectors[] = array_fill(0, (int) config('ai.embedding_dim', 768), 0.0);
                }
            }
        }

        return $vectors;
    }

    public function embed(string $text): array
    {
        return $this->embedMany([$text])[0] ?? array_fill(0, (int) config('ai.embedding_dim', 768), 0.0);
    }

    /**
     * @param  array<int, array{content: string, page_from: ?int, page_to: ?int, section: ?string, chunk_index: int}>  $chunks
     * @return array<int, array{content: string, page_from: ?int, page_to: ?int, section: ?string, chunk_index: int, embedding: array<int, float>}>
     */
    public function withEmbeddings(array $chunks): array
    {
        $vectors = $this->embedMany(array_column($chunks, 'content'));

        foreach ($chunks as $index => $chunk) {
            $chunks[$index]['embedding'] = $vectors[$index] ?? array_fill(0, (int) config('ai.embedding_dim', 768), 0.0);
        }

        return $chunks;
    }

    /** @param array<int, float>|null $vector */
    public static function toDb(?array $vector): ?string
    {
        if ($vector === null || $vector === []) {
            return null;
        }

        if (\App\Support\Database::usesVector()) {
            return '['.implode(',', array_map(fn ($v) => (string) (float) $v, $vector)).']';
        }

        return json_encode(array_values($vector));
    }

    /** @return array<int, float>|null */
    public static function fromDb(?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? array_map('floatval', $decoded) : null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_map('floatval', $decoded) : null;
    }
}
