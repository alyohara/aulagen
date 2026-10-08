<?php

namespace App\AI;

use App\AI\Contracts\AIProvider;
use Illuminate\Support\Facades\Cache;

class AIManager
{
    public function __construct()
    {
    }

    public function provider(): AIProvider
    {
        $requested = config('ai.provider', 'auto');
        $cacheKey = 'ai.provider.resolved.'.$requested;

        return Cache::remember($cacheKey, 60, function () use ($requested) {
            if ($requested === 'auto') {
                $primary = $this->make((string) config('ai.auto_primary', 'ollama'));

                if ($primary->available()) {
                    return $primary;
                }

                return $this->make((string) config('ai.fallback', 'local'));
            }

            $provider = $this->make($requested);

            if (! $provider->available()) {
                $fallback = $this->make((string) config('ai.fallback', 'local'));

                return $fallback->available() ? $fallback : $provider;
            }

            return $provider;
        });
    }

    public function make(string $name): AIProvider
    {
        return match ($name) {
            'ollama' => new Providers\OllamaProvider(config('ai.providers.ollama', [])),
            'gemini' => new Providers\GeminiProvider(config('ai.providers.gemini', [])),
            'openai' => new Providers\OpenAIProvider(config('ai.providers.openai', [])),
            'custom' => new Providers\CustomProvider(config('ai.providers.custom', [])),
            default => new Providers\LocalProvider(),
        };
    }

    public function local(): AIProvider
    {
        return new Providers\LocalProvider();
    }

    public function clearCache(): void
    {
        foreach (['auto', 'ollama', 'gemini', 'openai', 'custom', 'local'] as $name) {
            Cache::forget('ai.provider.resolved.'.$name);
        }
    }
}
