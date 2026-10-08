<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Support\AiSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminAiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teachers_cannot_access_ai_settings(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get('/admin/ia')->assertForbidden();
    }

    public function test_admin_can_view_ai_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/ia')->assertOk();
    }

    public function test_admin_can_update_ai_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/admin/ia', [
            'provider' => 'gemini',
            'auto_primary' => 'ollama',
            'fallback' => 'local',
            'enabled' => true,
            'rate_limit' => 20,
            'rag_top_k' => 8,
            'embedding_dim' => 768,
            'providers' => [
                'ollama' => ['url' => 'http://ollama:11434', 'model' => 'llama3.2', 'embedding_model' => 'nomic-embed-text'],
                'gemini' => ['key' => 'secret-key-123', 'model' => 'gemini-2.0-flash', 'embedding_model' => 'text-embedding-004'],
                'openai' => ['key' => null, 'url' => 'https://api.openai.com/v1', 'model' => 'gpt-4o-mini', 'embedding_model' => 'text-embedding-3-small'],
                'custom' => ['label' => 'Mi proxy', 'key' => null, 'url' => null, 'model' => 'x', 'embedding_model' => 'y'],
            ],
        ])->assertRedirect(route('admin.ai.index'));

        $stored = SystemSetting::getValue('ai');
        $this->assertSame('gemini', $stored['provider']);
        $this->assertSame('secret-key-123', $stored['providers']['gemini']['key']);
        $this->assertSame('gemini', config('ai.provider'));
        $this->assertSame('secret-key-123', config('ai.providers.gemini.key'));
        $this->assertSame(20, config('ai.rate_limit'));
    }

    public function test_blank_api_key_keeps_the_stored_one(): void
    {
        $admin = User::factory()->admin()->create();
        SystemSetting::setValue('ai', [
            'provider' => 'gemini',
            'providers' => ['gemini' => ['key' => 'keep-me']],
        ]);

        $this->actingAs($admin)->put('/admin/ia', [
            'provider' => 'gemini',
            'auto_primary' => 'ollama',
            'fallback' => 'local',
            'enabled' => true,
            'rate_limit' => 10,
            'rag_top_k' => 6,
            'embedding_dim' => 768,
            'providers' => [
                'ollama' => [],
                'gemini' => ['key' => '', 'model' => 'gemini-2.0-flash'],
                'openai' => [],
                'custom' => [],
            ],
        ])->assertRedirect(route('admin.ai.index'));

        $this->assertSame('keep-me', SystemSetting::getValue('ai')['providers']['gemini']['key']);
    }

    public function test_masked_api_key_is_not_saved(): void
    {
        $admin = User::factory()->admin()->create();
        SystemSetting::setValue('ai', [
            'providers' => ['gemini' => ['key' => 'real-key-9999']],
        ]);

        $this->actingAs($admin)->put('/admin/ia', [
            'provider' => 'gemini',
            'auto_primary' => 'ollama',
            'fallback' => 'local',
            'enabled' => true,
            'rate_limit' => 10,
            'rag_top_k' => 6,
            'embedding_dim' => 768,
            'providers' => [
                'ollama' => [],
                'gemini' => ['key' => '••••9999'],
                'openai' => [],
                'custom' => [],
            ],
        ]);

        $this->assertSame('real-key-9999', SystemSetting::getValue('ai')['providers']['gemini']['key']);
    }

    public function test_form_masks_existing_keys(): void
    {
        $admin = User::factory()->admin()->create();
        SystemSetting::setValue('ai', [
            'providers' => ['gemini' => ['key' => 'abcd1234secret']],
        ]);
        AiSettings::apply();

        $page = $this->actingAs($admin)->get('/admin/ia');
        $page->assertOk();
        $page->assertInertia(fn ($page) => $page
            ->component('Admin/AiSettings')
            ->where('form.providers.gemini.key', null)
            ->where('form.providers.gemini.key_masked', '••••cret'));
    }

    public function test_connection_test_returns_ok_for_local_provider(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->postJson('/admin/ia/probar', [
            'provider' => 'local',
            'config' => [],
        ]);

        $response->assertOk()->assertJsonPath('ok', true);
    }

    public function test_connection_test_reports_unavailable_provider(): void
    {
        $admin = User::factory()->admin()->create();

        Http::preventStrayRequests();

        $response = $this->actingAs($admin)->postJson('/admin/ia/probar', [
            'provider' => 'gemini',
            'config' => [],
        ]);

        $response->assertOk()->assertJsonPath('ok', false);
    }

    public function test_connection_test_uses_submitted_config(): void
    {
        $admin = User::factory()->admin()->create();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'OK']]], 'finishReason' => 'STOP']],
                'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 1],
            ]),
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/ia/probar', [
            'provider' => 'gemini',
            'config' => ['key' => 'fake-key', 'model' => 'gemini-2.0-flash'],
        ]);

        $response->assertOk()->assertJsonPath('ok', true)->assertJsonPath('model', 'gemini-2.0-flash');
    }
}
