<?php

namespace App\AI\Contracts;

final class AIResult
{
    public function __construct(
        public readonly string $text,
        public readonly string $provider,
        public readonly string $model,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly int $durationMs = 0,
        public readonly array $meta = [],
    ) {}

    public function json(): array
    {
        $clean = trim($this->text);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
        $clean = preg_replace('/\s*```$/', '', $clean);

        $decoded = json_decode($clean, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        if (preg_match('/\{.*\}|\[.*\]/s', $clean, $match)) {
            $decoded = json_decode($match[0], true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        throw new \RuntimeException('La IA devolvió una respuesta que no es JSON válido.');
    }
}
