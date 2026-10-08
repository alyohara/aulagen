<?php

namespace App\Services;

class ChunkService
{
    protected int $targetLength;

    protected int $maxLength;

    protected int $minLength;

    public function __construct(int $target = 900, int $overlap = 160, int $min = 120)
    {
        $this->targetLength = $target;
        $this->minLength = $min;
        $this->maxLength = $target + $overlap;
    }

    /**
     * @param  array<int, array{text: string, page: ?int, section: ?string}>  $blocks
     * @return array<int, array{content: string, page_from: ?int, page_to: ?int, section: ?string, chunk_index: int}>
     */
    public function chunk(array $blocks): array
    {
        $raw = [];

        foreach ($blocks as $block) {
            $text = trim((string) ($block['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            foreach ($this->split($text) as $piece) {
                $raw[] = [
                    'content' => $this->clean($piece),
                    'page' => $block['page'] ?? null,
                    'section' => $block['section'] ?? null,
                ];
            }
        }

        // Fusiona fragmentos demasiado cortos con el anterior (misma página).
        $merged = [];
        foreach ($raw as $item) {
            $previous = end($merged);

            if ($previous !== false
                && mb_strlen($item['content']) < $this->minLength
                && $previous['page'] === $item['page']
                && mb_strlen($previous['content']) + mb_strlen($item['content']) <= $this->maxLength) {
                $previous['content'] = trim($previous['content']."\n\n".$item['content']);
                $merged[count($merged) - 1] = $previous;

                continue;
            }

            $merged[] = $item;
        }

        $chunks = [];
        foreach ($merged as $index => $item) {
            if (mb_strlen($item['content']) < 40) {
                continue;
            }

            $chunks[] = [
                'content' => $item['content'],
                'page_from' => $item['page'],
                'page_to' => $item['page'],
                'section' => $item['section'],
                'chunk_index' => count($chunks),
            ];
        }

        return $chunks;
    }

    /** @return array<int, string> */
    protected function split(string $text): array
    {
        if (mb_strlen($text) <= $this->maxLength) {
            return [$text];
        }

        $sentences = preg_split('/(?<=[.!?;:])\s+|\n{2,}/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [$text];
        $pieces = [];
        $current = '';

        foreach ($sentences as $sentence) {
            if (mb_strlen($sentence) > $this->maxLength) {
                if ($current !== '') {
                    $pieces[] = $current;
                    $current = '';
                }
                $pieces[] = $sentence;

                continue;
            }

            if (mb_strlen($current) + mb_strlen($sentence) + 1 > $this->targetLength && $current !== '') {
                $pieces[] = $current;
                $overlap = mb_substr($current, -min($this->targetLength - $this->maxLength + 200, mb_strlen($current)));
                $current = $this->tailSentence($overlap).' '.$sentence;
            } else {
                $current = trim($current.' '.$sentence);
            }
        }

        if (trim($current) !== '') {
            $pieces[] = $current;
        }

        return $pieces === [] ? [$text] : $pieces;
    }

    protected function tailSentence(string $text): string
    {
        $position = strpos($text, '. ');

        if ($position !== false && $position > 40) {
            return substr($text, $position + 2);
        }

        return $text;
    }

    public function clean(string $text): string
    {
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\s*\n\s*/', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
