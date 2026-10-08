<?php

namespace App\AI\Providers;

use App\AI\Contracts\AIResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaProvider extends AbstractProvider
{
    public function __construct(protected array $config = [])
    {
        $this->timeout = (int) ($config['timeout'] ?? 120);
    }

    public function name(): string
    {
        return 'ollama';
    }

    public function label(): string
    {
        return 'Ollama (IA local)';
    }

    public function model(): string
    {
        return (string) ($this->config['model'] ?? 'llama3.2');
    }

    public function available(): bool
    {
        try {
            return Http::timeout(2)
                ->get(rtrim((string) $this->url(), '/').'/api/tags')
                ->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function complete(string $system, string $user, array $options = []): AIResult
    {
        $start = microtime(true);

        $response = Http::timeout($this->timeout)->post(rtrim($this->url(), '/').'/api/chat', [
            'model' => $this->model(),
            'stream' => false,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'options' => [
                'temperature' => $options['temperature'] ?? 0.2,
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Ollama respondió '.$response->status().': '.$response->body());
        }

        $json = $response->json();

        return $this->result(
            text: (string) ($json['message']['content'] ?? ''),
            model: $this->model(),
            in: (int) ($json['prompt_eval_count'] ?? 0),
            out: (int) ($json['eval_count'] ?? 0),
            start: $start,
        );
    }

    protected function embedOne(string $text): array
    {
        $response = Http::timeout($this->timeout)->post(rtrim($this->url(), '/').'/api/embeddings', [
            'model' => $this->embedModel(),
            'prompt' => $text,
        ]);

        if ($response->failed()) {
            Log::warning('Ollama embeddings fallaron: '.$response->body());

            return $this->normalize((new LocalProvider())->embed($text));
        }

        return $this->normalize($response->json('embedding') ?? []);
    }

    protected function embedModel(): string
    {
        return (string) ($this->config['embedding_model'] ?? 'nomic-embed-text');
    }

    protected function url(): string
    {
        return (string) ($this->config['url'] ?? env('OLLAMA_HOST', 'http://127.0.0.1:11434'));
    }
}
