<?php

namespace App\AI\Providers;

use App\AI\Contracts\AIProvider;
use App\AI\Contracts\AIResult;

abstract class AbstractProvider implements AIProvider
{
    protected int $timeout = 120;

    public function supportsEmbeddings(): bool
    {
        return true;
    }

    public function embed(string $text): array
    {
        return $this->embedMany([$text])[0] ?? array_fill(0, (int) config('ai.embedding_dim', 768), 0.0);
    }

    public function embedMany(array $texts): array
    {
        $out = [];

        foreach ($texts as $text) {
            $out[] = $this->embedOne((string) $text);
        }

        return $out;
    }

    abstract protected function embedOne(string $text): array;

    protected function normalize(array $vector): array
    {
        $vector = array_map('floatval', $vector);

        if ($vector === []) {
            return array_fill(0, (int) config('ai.embedding_dim', 768), 0.0);
        }

        $norm = sqrt(array_reduce($vector, fn ($c, $v) => $c + $v * $v, 0.0));

        if ($norm > 0) {
            $vector = array_map(fn ($v) => $v / $norm, $vector);
        }

        return $vector;
    }

    protected function result(string $text, string $model, ?int $in = null, ?int $out = null, int $start = 0, array $meta = []): AIResult
    {
        return new AIResult(
            text: trim($text),
            provider: $this->name(),
            model: $model,
            inputTokens: $in ?? (int) (strlen($text) / 4),
            outputTokens: $out ?? (int) (strlen($text) / 4),
            durationMs: max(0, (int) ((microtime(true) - $start) * 1000)),
            meta: $meta,
        );
    }
}
