<?php

namespace App\Http\Controllers\Admin;

use App\AI\AIManager;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Support\AiSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiSettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/AiSettings', [
            'form' => AiSettings::formPayload(),
            'provider' => $this->providerInfo(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'in:auto,ollama,gemini,openai,custom,local'],
            'auto_primary' => ['required', 'string', 'in:ollama,gemini,openai,custom,local'],
            'fallback' => ['required', 'string', 'in:ollama,gemini,openai,custom,local'],
            'enabled' => ['boolean'],
            'rate_limit' => ['integer', 'min:1', 'max:120'],
            'rag_top_k' => ['integer', 'min:1', 'max:30'],
            'embedding_dim' => ['integer', 'min:128', 'max:4096'],
            'providers.ollama.url' => ['nullable', 'string', 'max:300'],
            'providers.ollama.model' => ['nullable', 'string', 'max:120'],
            'providers.ollama.embedding_model' => ['nullable', 'string', 'max:120'],
            'providers.gemini.key' => ['nullable', 'string', 'max:300'],
            'providers.gemini.model' => ['nullable', 'string', 'max:120'],
            'providers.gemini.embedding_model' => ['nullable', 'string', 'max:120'],
            'providers.openai.key' => ['nullable', 'string', 'max:300'],
            'providers.openai.url' => ['nullable', 'string', 'max:300'],
            'providers.openai.model' => ['nullable', 'string', 'max:120'],
            'providers.openai.embedding_model' => ['nullable', 'string', 'max:120'],
            'providers.custom.label' => ['nullable', 'string', 'max:120'],
            'providers.custom.key' => ['nullable', 'string', 'max:300'],
            'providers.custom.url' => ['nullable', 'string', 'max:300'],
            'providers.custom.model' => ['nullable', 'string', 'max:120'],
            'providers.custom.embedding_model' => ['nullable', 'string', 'max:120'],
        ]);

        $data['enabled'] = (bool) ($data['enabled'] ?? true);
        $data['providers'] = $this->mergeSecrets((array) $data['providers']);

        AiSettings::save($data);
        app(AIManager::class)->clearCache();

        return redirect()->route('admin.ai.index')->with('success', 'Configuración de IA actualizada.');
    }

    public function test(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'in:ollama,gemini,openai,custom,local'],
            'config' => ['nullable', 'array'],
        ]);

        $config = array_filter((array) ($data['config'] ?? []), fn ($v) => $v !== null && $v !== '');

        $key = (string) ($config['key'] ?? '');
        if ($key === '' || str_starts_with($key, '••••')) {
            unset($config['key']);
        }

        $previous = (array) config('ai.providers.'.$data['provider'], []);
        $path = 'ai.providers.'.$data['provider'];
        config([$path => array_merge($previous, $config)]);

        $provider = app(AIManager::class)->make($data['provider']);

        if (! $provider->available()) {
            return response()->json([
                'ok' => false,
                'message' => 'El proveedor no está disponible: revisá la URL y la clave.',
            ]);
        }

        try {
            $start = microtime(true);
            $result = $provider->complete(
                'Sos un asistente de prueba. Respondé únicamente con la palabra OK.',
                'Ping de conexión.',
            );

            return response()->json([
                'ok' => true,
                'provider' => $provider->name(),
                'label' => $provider->label(),
                'model' => $result->model,
                'duration_ms' => max(0, (int) ((microtime(true) - $start) * 1000)),
                'reply' => mb_substr($result->text, 0, 120),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al conectar: '.mb_substr($e->getMessage(), 0, 300),
            ]);
        }
    }

    /**
     * Conserva la clave guardada cuando el form envía vacío o la máscara.
     */
    private function mergeSecrets(array $providers): array
    {
        foreach (['gemini', 'openai', 'custom'] as $name) {
            $key = (string) ($providers[$name]['key'] ?? '');

            if ($key === '' || str_starts_with($key, '••••')) {
                $stored = (array) SystemSetting::getValue('ai', []);
                $providers[$name]['key'] = $stored['providers'][$name]['key'] ?? null;
            }
        }

        return $providers;
    }

    private function providerInfo(): array
    {
        $provider = app(AIManager::class)->provider();

        return [
            'active' => $provider->name(),
            'label' => $provider->label(),
            'model' => $provider->model(),
            'configured' => config('ai.provider'),
            'rate_limit' => config('ai.rate_limit'),
        ];
    }
}
