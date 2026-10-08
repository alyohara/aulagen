<?php

namespace App\Http\Controllers\Admin;

use App\AI\AIManager;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AiGeneration;
use App\Models\Course;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'teachers' => User::where('role', UserRole::Teacher->value)->count(),
                'courses' => Course::withTrashed()->count(),
                'published' => Course::where('status', 'published')->count(),
                'documents' => Document::count(),
                'documents_failed' => Document::where('status', 'failed')->count(),
                'generations' => AiGeneration::count(),
                'generations_errors' => AiGeneration::where('status', 'error')->count(),
            ],
            'provider' => $this->providerInfo(),
            'errors' => $this->errors(),
        ]);
    }

    public function users(): Response
    {
        return Inertia::render('Admin/Users', [
            'users' => User::withCount('ownedCourses')->latest()->limit(200)->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'is_active' => $user->is_active,
                'courses' => $user->owned_courses_count,
                'created_at' => $user->created_at?->format('d/m/Y'),
            ]),
            'roles' => collect(UserRole::cases())->map(fn ($role) => ['value' => $role->value, 'label' => $role->label()]),
            'provider' => $this->providerInfo(),
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'in:admin,teacher,student'],
            'is_active' => ['boolean'],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== UserRole::Admin->value) {
            return back()->with('warning', 'No podés quitar tu propio rol de administrador.');
        }

        $user->update([
            'role' => $data['role'],
            'is_active' => (bool) ($data['is_active'] ?? $user->is_active),
        ]);

        return back()->with('success', 'Usuario actualizado.');
    }

    public function generations(): Response
    {
        return Inertia::render('Admin/Generations', [
            'generations' => AiGeneration::with(['course', 'user'])->latest()->limit(100)->get()->map(fn (AiGeneration $generation) => [
                'id' => $generation->id,
                'type' => $generation->type,
                'provider' => $generation->provider,
                'model' => $generation->model,
                'status' => $generation->status,
                'duration_ms' => $generation->duration_ms,
                'course' => $generation->course?->name,
                'user' => $generation->user?->name,
                'error' => $generation->error,
                'created_at' => $generation->created_at?->format('d/m/Y H:i'),
            ]),
            'provider' => $this->providerInfo(),
        ]);
    }

    private function providerInfo(): array
    {
        $manager = app(AIManager::class);
        $provider = $manager->provider();

        return [
            'active' => $provider->name(),
            'label' => $provider->label(),
            'model' => $provider->model(),
            'configured' => config('ai.provider'),
            'rate_limit' => config('ai.rate_limit'),
            'embedding_dim' => config('ai.embedding_dim'),
        ];
    }

    private function errors(): array
    {
        $documentErrors = Document::where('status', 'failed')
            ->latest()->limit(10)->get()
            ->map(fn (Document $document) => [
                'source' => 'Documento',
                'subject' => $document->original_name,
                'error' => $document->error_message,
                'created_at' => $document->updated_at?->format('d/m/Y H:i'),
            ]);

        $generationErrors = AiGeneration::where('status', 'error')
            ->latest()->limit(10)->get()
            ->map(fn (AiGeneration $generation) => [
                'source' => 'IA ('.$generation->provider.')',
                'subject' => $generation->type,
                'error' => $generation->error,
                'created_at' => $generation->created_at?->format('d/m/Y H:i'),
            ]);

        return $documentErrors->concat($generationErrors)->take(20)->all();
    }
}
