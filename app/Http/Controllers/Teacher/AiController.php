<?php

namespace App\Http\Controllers\Teacher;

use App\AI\AIManager;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\ContentGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiController extends Controller
{
    public function index(Request $request, Course $course)
    {
        $this->authorize('view', $course);

        $manager = app(AIManager::class);
        $provider = $manager->provider();

        $generations = $course->aiGenerations()->limit(60)->get()->map(fn ($generation) => [
            'id' => $generation->id,
            'type' => $generation->type,
            'type_label' => \App\Enums\GenerationType::tryFrom($generation->type)?->label() ?? $generation->type,
            'provider' => $generation->provider,
            'model' => $generation->model,
            'status' => $generation->status,
            'duration_ms' => $generation->duration_ms,
            'error' => $generation->error,
            'created_at' => $generation->created_at?->format('d/m/Y H:i'),
        ]);

        $providers = collect(['ollama', 'gemini', 'openai', 'local'])
            ->map(fn ($name) => [
                'name' => $name,
                'label' => $manager->make($name)->label(),
                'available' => $manager->make($name)->available(),
                'active' => $provider->name() === $name,
            ]);

        return Inertia::render('Courses/Ai', [
            'course' => app(CourseController::class)->transform($course->load(['settings', 'owner'])),
            'provider' => ['name' => $provider->name(), 'label' => $provider->label(), 'model' => $provider->model()],
            'providers' => $providers,
            'generations' => $generations,
            'glossary' => $course->meta['glossary'] ?? [],
            'hasProposal' => filled($course->meta['structure_proposal'] ?? null),
        ]);
    }

    public function updateGlossary(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'glossary' => ['nullable', 'array'],
            'glossary.*.term' => ['required', 'string', 'max:120'],
            'glossary.*.definition' => ['required', 'string', 'max:1000'],
        ]);

        $course->forceFill([
            'meta' => array_merge($course->meta ?? [], ['glossary' => $data['glossary'] ?? []]),
        ])->save();

        return back()->with('success', 'Glosario actualizado.');
    }

    public function generateGlossary(Request $request, Course $course, ContentGenerator $generator): RedirectResponse
    {
        $this->authorize('update', $course);

        if ($course->chunks()->doesntExist()) {
            return back()->with('warning', 'Procesá material antes de generar el glosario.');
        }

        try {
            $terms = $generator->generateGlossary($course, $request->user());
        } catch (\Throwable $e) {
            return back()->with('warning', 'No se pudo generar el glosario: '.$e->getMessage());
        }

        return back()->with('success', 'Glosario generado con '.count($terms).' términos. Podés editarlo desde la configuración.');
    }

    public function clearCache(): RedirectResponse
    {
        app(AIManager::class)->clearCache();

        return back()->with('success', 'Estado de proveedores de IA actualizado.');
    }
}
