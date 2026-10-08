<?php

namespace App\AI\Providers;

use App\AI\Contracts\AIResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProvider extends AbstractProvider
{
    public function __construct(protected array $config = [])
    {
    }

    public function name(): string
    {
        return 'gemini';
    }

    public function label(): string
    {
        return 'Google Gemini';
    }

    public function model(): string
    {
        return (string) ($this->config['model'] ?? 'gemini-2.0-flash');
    }

    public function available(): bool
    {
        return filled($this->config['key'] ?? null);
    }

    public function complete(string $system, string $user, array $options = []): AIResult
    {
        $start = microtime(true);

        $response = Http::timeout($this->timeout)
            ->withHeaders(['x-goog-api-key' => (string) $this->config['key']])
            ->post('https://generativelanguage.googleapis.com/v1beta/models/'.$this->model().':generateContent', [
                'systemInstruction' => ['parts' => [['text' => $system]]],
                'contents' => [['role' => 'user', 'parts' => [['text' => $user]]]],
                'generationConfig' => [
                    'temperature' => $options['temperature'] ?? 0.2,
                ],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gemini respondió '.$response->status().': '.$response->body());
        }

        $parts = $response->json('candidates.0.content.parts') ?? [];
        $text = collect($parts)->pluck('text')->implode('');

        return $this->result(
            text: $text,
            model: $this->model(),
            in: (int) ($response->json('usageMetadata.promptTokenCount') ?? 0),
            out: (int) ($response->json('usageMetadata.candidatesTokenCount') ?? 0),
            start: $start,
        );
    }

    protected function embedOne(string $text): array
    {
        $model = (string) ($this->config['embedding_model'] ?? 'text-embedding-004');

        $response = Http::timeout(30)
            ->withHeaders(['x-goog-api-key' => (string) $this->config['key']])
            ->post('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':embedContent', [
                'model' => 'models/'.$model,
                'content' => ['parts' => [['text' => $text]]],
            ]);

        if ($response->failed()) {
            Log::warning('Gemini embeddings fallaron: '.$response->body());

            return $this->normalize((new LocalProvider())->embed($text));
        }

        return $this->normalize($response->json('embedding.values') ?? []);
    }
}
