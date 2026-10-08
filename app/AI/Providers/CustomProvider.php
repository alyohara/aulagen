<?php

namespace App\AI\Providers;

use App\AI\Contracts\AIResult;

class CustomProvider extends OpenAIProvider
{
    public function name(): string
    {
        return 'custom';
    }

    public function label(): string
    {
        return (string) ($this->config['label'] ?? 'Endpoint compatible con OpenAI');
    }

    public function model(): string
    {
        return (string) ($this->config['model'] ?? 'gpt-4o-mini');
    }

    public function available(): bool
    {
        return filled($this->config['url'] ?? null);
    }

    public function complete(string $system, string $user, array $options = []): AIResult
    {
        return parent::complete($system, $user, $options);
    }
}
