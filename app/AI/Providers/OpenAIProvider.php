<?php

namespace App\AI\Providers;

use App\AI\Contracts\AIResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider extends AbstractProvider
{
    public function __construct(protected array $config = [])
    {
    }

    public function name(): string
    {
        return 'openai';
    }

    public function label(): string
    {
        return 'OpenAI';
    }

    public function model(): string
    {
        return (string) ($this->config['model'] ?? 'gpt-4o-mini');
    }

    public function available(): bool
    {
        return filled($this->config['key'] ?? null);
    }

    public function complete(string $system, string $user, array $options = []): AIResult
    {
        $start = microtime(true);

        $response = Http::timeout($this->timeout)
            ->withToken((string) $this->config['key'])
            ->post(rtrim((string) ($this->config['url'] ?? 'https://api.openai.com/v1'), '/').'/chat/completions', [
                'model' => $this->model(),
                'temperature' => $options['temperature'] ?? 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('OpenAI respondió '.$response->status().': '.$response->body());
        }

        return $this->result(
            text: (string) $response->json('choices.0.message.content', ''),
            model: $this->model(),
            in: (int) $response->json('usage.prompt_tokens', 0),
            out: (int) $response->json('usage.completion_tokens', 0),
            start: $start,
        );
    }

    protected function embedOne(string $text): array
    {
        $response = Http::timeout(30)
            ->withToken((string) $this->config['key'])
            ->post(rtrim((string) ($this->config['url'] ?? 'https://api.openai.com/v1'), '/').'/embeddings', [
                'model' => (string) ($this->config['embedding_model'] ?? 'text-embedding-3-small'),
                'input' => $text,
            ]);

        if ($response->failed()) {
            Log::warning('OpenAI embeddings fallaron: '.$response->body());

            return $this->normalize((new LocalProvider())->embed($text));
        }

        return $this->normalize($response->json('data.0.embedding') ?? []);
    }
}
