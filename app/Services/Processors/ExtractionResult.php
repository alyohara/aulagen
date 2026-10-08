<?php

namespace App\Services\Processors;

final class ExtractionResult
{
    /**
     * @param  array<int, array{text: string, page: ?int, section: ?string}>  $blocks
     * @param  array<int, array{level: int, text: string}>  $headings
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly string $text = '',
        public readonly array $blocks = [],
        public readonly array $headings = [],
        public readonly ?int $pages = null,
        public readonly array $meta = [],
    ) {}
}
