<?php

namespace App\Http\Controllers\Teacher;

use App\Actions\Courses\CreateCourse;
use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Courses/Create');
    }

    public function store(Request $request, CreateCourse $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'institution' => ['nullable', 'string', 'max:180'],
            'career' => ['nullable', 'string', 'max:180'],
            'course_year' => ['nullable', 'string', 'max:80'],
            'duration' => ['nullable', 'string', 'max:120'],
            'modality' => ['nullable', 'string', 'max:120'],
            'objectives' => ['nullable', 'string'],
            'program' => ['nullable', 'string'],
            'collaborators' => ['nullable', 'string', 'max:500'],
            'bibliography_principal' => ['nullable', 'string'],
            'bibliography_complementary' => ['nullable', 'string'],
            'generate_structure' => ['nullable', 'boolean'],
        ]);

        $course = $action->handle($request->user(), $data);

        return redirect()
            ->route('courses.documents.index', $course)
            ->with('success', 'Materia creada. Cargá el material para que la IA analice la estructura.');
    }

    public function overview(Course $course): Response
    {
        $this->authorize('view', $course);

        $course->load(['settings', 'owner', 'collaborators']);

        $modules = $course->modules()->withCount('lessons')->get();
        $documents = $course->documents()->get();

        return Inertia::render('Courses/Overview', [
            'course' => $this->transform($course),
            'stats' => [
                'modules' => $modules->count(),
                'units' => $modules->where('type', 'unit')->count(),
                'lessons' => (int) $modules->sum('lessons_count'),
                'documents' => $documents->count(),
                'documents_ready' => $documents->where('status', 'ready')->count(),
                'activities' => $course->activities()->count(),
                'bibliography' => $course->bibliography()->count(),
                'pending_lessons' => $course->lessons()->whereIn('status', ['draft', 'generated', 'review'])->count(),
            ],
            'recentDocuments' => $documents->take(6)->map(fn ($doc) => [
                'id' => $doc->id,
                'name' => $doc->original_name,
                'status' => $doc->status->value,
                'type' => $doc->type->value,
            ]),
            'proposal' => $course->meta['structure_proposal'] ?? null,
            'provider' => app(\App\AI\AIManager::class)->provider()->label(),
        ]);
    }

    public function edit(Course $course): Response
    {
        $this->authorize('view', $course);

        return Inertia::render('Courses/Edit', [
            'course' => $this->transform($course->load(['settings', 'owner', 'collaborators'])),
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'institution' => ['nullable', 'string', 'max:180'],
            'career' => ['nullable', 'string', 'max:180'],
            'course_year' => ['nullable', 'string', 'max:80'],
            'duration' => ['nullable', 'string', 'max:120'],
            'modality' => ['nullable', 'string', 'max:120'],
            'objectives' => ['nullable', 'string'],
            'program' => ['nullable', 'string'],
            'collaborators' => ['nullable', 'string', 'max:500'],
            'bibliography_principal' => ['nullable', 'string'],
            'bibliography_complementary' => ['nullable', 'string'],
        ]);

        $course->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'institution' => $data['institution'] ?? null,
            'career' => $data['career'] ?? null,
            'course_year' => $data['course_year'] ?? null,
            'duration' => $data['duration'] ?? null,
            'modality' => $data['modality'] ?? null,
            'objectives' => $data['objectives'] ?? null,
            'program' => $data['program'] ?? null,
            'meta' => array_merge($course->meta ?? [], [
                'collaborators' => $data['collaborators'] ?? null,
                'bibliography_principal' => $data['bibliography_principal'] ?? null,
                'bibliography_complementary' => $data['bibliography_complementary'] ?? null,
            ]),
        ]);

        return back()->with('success', 'Materia actualizada.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);

        $course->delete();

        return redirect()->route('dashboard')->with('success', 'Materia eliminada.');
    }

    public function publish(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('publish', $course);

        $publishedLessons = $course->lessons()->where('status', 'approved')->count();

        $course->forceFill([
            'status' => CourseStatus::Published,
            'published_at' => $course->published_at ?? now(),
        ])->save();

        $course->lessons()->where('status', 'approved')->update(['status' => 'published']);

        $message = $publishedLessons > 0
            ? 'Materia publicada con '.$publishedLessons.' lecciones aprobadas.'
            : 'Materia publicada. Aprobá lecciones para que los alumnos puedan verlas.';

        return back()->with('success', $message);
    }

    public function unpublish(Course $course): RedirectResponse
    {
        $this->authorize('publish', $course);

        $course->forceFill(['status' => CourseStatus::Draft])->save();
        $course->lessons()->where('status', 'published')->update(['status' => 'approved']);

        return back()->with('success', 'Materia despublicada. El aula deja de ser visible para alumnos.');
    }

    public function settings(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('manageSettings', $course);

        $data = $request->validate([
            'ai_assistant_enabled' => ['boolean'],
            'allow_external_knowledge' => ['boolean'],
            'show_sources' => ['boolean'],
            'show_progress' => ['boolean'],
            'allow_downloads' => ['boolean'],
            'enable_search' => ['boolean'],
            'auto_generate_resources' => ['boolean'],
        ]);

        $course->ensureSettings()->fill($data)->save();

        return back()->with('success', 'Configuración guardada.');
    }

    public function preview(Course $course): RedirectResponse
    {
        $this->authorize('view', $course);

        return redirect()->route('aula.home', [$course, 'preview' => 1]);
    }

    public function transform(Course $course): array
    {
        return [
            'id' => $course->id,
            'name' => $course->name,
            'slug' => $course->slug,
            'url' => route('aula.home', $course),
            'description' => $course->description,
            'institution' => $course->institution,
            'career' => $course->career,
            'course_year' => $course->course_year,
            'duration' => $course->duration,
            'modality' => $course->modality,
            'objectives' => $course->objectives,
            'program' => $course->program,
            'status' => $course->status->value,
            'status_label' => $course->status->label(),
            'published_at' => $course->published_at?->toIso8601String(),
            'created_at' => $course->created_at?->toDateString(),
            'owner' => $course->owner ? ['id' => $course->owner->id, 'name' => $course->owner->name] : null,
            'collaborators' => $course->meta['collaborators'] ?? null,
            'bibliography_principal' => $course->meta['bibliography_principal'] ?? null,
            'bibliography_complementary' => $course->meta['bibliography_complementary'] ?? null,
            'settings' => $course->settings ? [
                'ai_assistant_enabled' => $course->settings->ai_assistant_enabled,
                'allow_external_knowledge' => $course->settings->allow_external_knowledge,
                'show_sources' => $course->settings->show_sources,
                'show_progress' => $course->settings->show_progress,
                'allow_downloads' => $course->settings->allow_downloads,
                'enable_search' => $course->settings->enable_search,
                'auto_generate_resources' => $course->settings->auto_generate_resources,
            ] : null,
            'structure_generated_at' => $course->structure_generated_at?->toIso8601String(),
            'glossary' => $course->meta['glossary'] ?? [],
            'has_proposal' => filled($course->meta['structure_proposal'] ?? null),
        ];
    }
}
