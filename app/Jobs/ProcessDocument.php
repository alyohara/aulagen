<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(public Document $document)
    {
    }

    public function handle(DocumentProcessor $processor): void
    {
        $processor->process($this->document);
    }

    public function failed(?\Throwable $e): void
    {
        $this->document->forceFill([
            'status' => \App\Enums\DocumentStatus::Failed,
            'error_message' => mb_substr($e?->getMessage() ?? 'Error desconocido', 0, 1000),
        ])->save();
    }
}
