<?php

namespace App\Support;

use App\Models\SystemSetting;

class AiSettings
{
    public const KEY = 'ai';

    /**
     * Aplica los ajustes guardados en DB sobre config('ai.*').
     * Silencioso si la tabla aún no existe (migraciones tempranas).
     */
    public static function apply(): void
    {
        try {
            $stored = SystemSetting::getValue(self::KEY);
        } catch (\Throwable) {
            return;
        }

        if (! is_array($stored)) {
            return;
        }

        self::mergeIntoConfig($stored);
    }

    public static function mergeIntoConfig(array $stored): void
    {
        foreach ($stored as $key => $value) {
            if ($key === 'providers' && is_array($value)) {
                foreach ($value as $provider => $config) {
                    if (! is_array($config)) {
                        continue;
                    }

                    $current = (array) config("ai.providers.{$provider}", []);
                    $config = array_filter($config, fn ($v) => $v !== null && $v !== '');
                    config(["ai.providers.{$provider}" => array_merge($current, $config)]);
                }

                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            config(["ai.{$key}" => $value]);
        }
    }

    public static function save(array $data): void
    {
        SystemSetting::setValue(self::KEY, $data);
        self::mergeIntoConfig($data);
    }

    /**
     * Config efectiva para el formulario del admin (con keys enmascaradas).
     */
    public static function formPayload(): array
    {
        $mask = fn ($value) => filled($value) ? '••••'.substr((string) $value, -4) : null;

        $ollama = (array) config('ai.providers.ollama', []);
        $gemini = (array) config('ai.providers.gemini', []);
        $openai = (array) config('ai.providers.openai', []);
        $custom = (array) config('ai.providers.custom', []);

        return [
            'provider' => (string) config('ai.provider', 'auto'),
            'auto_primary' => (string) config('ai.auto_primary', 'ollama'),
            'fallback' => (string) config('ai.fallback', 'local'),
            'enabled' => (bool) config('ai.enabled', true),
            'rate_limit' => (int) config('ai.rate_limit', 10),
            'rag_top_k' => (int) config('ai.rag_top_k', 6),
            'embedding_dim' => (int) config('ai.embedding_dim', 768),
            'providers' => [
                'ollama' => [
                    'url' => (string) ($ollama['url'] ?? ''),
                    'model' => (string) ($ollama['model'] ?? ''),
                    'embedding_model' => (string) ($ollama['embedding_model'] ?? ''),
                ],
                'gemini' => [
                    'key' => null,
                    'key_masked' => $mask($gemini['key'] ?? null),
                    'model' => (string) ($gemini['model'] ?? ''),
                    'embedding_model' => (string) ($gemini['embedding_model'] ?? ''),
                ],
                'openai' => [
                    'key' => null,
                    'key_masked' => $mask($openai['key'] ?? null),
                    'url' => (string) ($openai['url'] ?? 'https://api.openai.com/v1'),
                    'model' => (string) ($openai['model'] ?? ''),
                    'embedding_model' => (string) ($openai['embedding_model'] ?? ''),
                ],
                'custom' => [
                    'label' => (string) ($custom['label'] ?? 'Endpoint compatible con OpenAI'),
                    'key' => null,
                    'key_masked' => $mask($custom['key'] ?? null),
                    'url' => (string) ($custom['url'] ?? ''),
                    'model' => (string) ($custom['model'] ?? ''),
                    'embedding_model' => (string) ($custom['embedding_model'] ?? ''),
                ],
            ],
        ];
    }
}
