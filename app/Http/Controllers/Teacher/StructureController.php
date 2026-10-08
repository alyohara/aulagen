<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ContentStatus;
use App\Enums\ModuleType;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateLessonContent;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Services\ContentGenerator;
use App\Services\CurriculumGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StructureController extends Controller
{
    public function index(Course $course): Response
    {
        $this->authorize('view', $course);

        $modules = $course->modules()->with(['lessons' => fn ($q) => $q->orderBy('position')->orderBy('id')])->orderBy('position')->orderBy('id')->get();

        return Inertia::render('Courses/Content', [
            'course' => app(CourseController::class)->transform($course->load(['settings', 'owner'])),
            'modules' => $modules->map(fn (Module $module) => [
                'id' => $module->id,
                'title' => $module->title,
                'slug' => $module->slug,
                'type' => $module->type,
                'type_label' => ModuleType::from($module->type)->label(),
                'position' => $module->position,
                'summary' => $module->summary,
                'status' => $module->status,
                'is_ai_generated' => $module->is_ai_generated,
                'lessons' => $module->lessons->map(fn (Lesson $lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'slug' => $lesson->slug,
                    'position' => $lesson->position,
                    'status' => $lesson->status->value,
                    'status_label' => $lesson->status->label(),
                    'badge' => $lesson->status->badge(),
                    'is_ai_generated' => $lesson->is_ai_generated,
                    'has_content' => filled($lesson->content),
                    'summary' => $lesson->summary,
                ]),
            ]),
            'proposal' => $course->meta['structure_proposal'] ?? null,
            'documents' => $course->documents()->with('module')->latest()->limit(100)->get()->map(fn ($doc) => [
                'id' => $doc->id,
                'name' => $doc->original_name,
                'status' => $doc->status->value,
                'module' => $doc->module?->title,
            ]),
            'provider' => app(\App\AI\AIManager::class)->provider()->label(),
            'routeNames' => [
                'modules' => 'courses.modules.store',
                'lessons' => 'courses.lessons.store',
            ],
        ]);
    }

    /* ---------------------------------------------------------- Módulos */

    public function storeModule(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'type' => ['nullable', 'string', 'in:unit,section,activities,bibliography,complementary'],
            'summary' => ['nullable', 'string'],
        ]);

        $position = ((int) $course->modules()->max('position')) + 1;

        $course->modules()->create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']),
            'type' => $data['type'] ?? ModuleType::Unit->value,
            'summary' => $data['summary'] ?? null,
            'position' => $position,
            'status' => ContentStatus::Draft->value,
            'is_ai_generated' => false,
        ]);

        return back()->with('success', 'Módulo creado.');
    }

    public function updateModule(Request $request, Course $course, Module $module): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($module->course_id === $course->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['nullable', 'string'],
        ]);

        $module->update([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']),
            'summary' => $data['summary'] ?? null,
        ]);

        return back()->with('success', 'Módulo actualizado.');
    }

    public function destroyModule(Course $course, Module $module): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($module->course_id === $course->id, 404);

        $module->delete();

        return back()->with('success', 'Módulo eliminado.');
    }

    public function reorderModules(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($data['order'] as $index => $moduleId) {
            $course->modules()->whereKey($moduleId)->update(['position' => $index]);
        }

        return back();
    }

    /* ---------------------------------------------------------- Lecciones */

    public function storeLesson(Request $request, Course $course, Module $module): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($module->course_id === $course->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['nullable', 'string'],
        ]);

        $position = ((int) $module->lessons()->max('position')) + 1;

        $module->lessons()->create([
            'course_id' => $course->id,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']),
            'summary' => $data['summary'] ?? null,
            'position' => $position,
            'status' => ContentStatus::Draft->value,
        ]);

        return back()->with('success', 'Lección creada.');
    }

    public function editLesson(Course $course, Lesson $lesson): Response
    {
        $this->authorize('view', $course);

        abort_unless($lesson->course_id === $course->id, 404);

        $lesson->load('module');

        $sources = $lesson->chunks()->limit(10)->get()->map(fn ($chunk) => [
            'label' => $chunk->referenceLabel(),
            'content' => mb_substr($chunk->content, 0, 400),
        ]);

        return Inertia::render('Lessons/Edit', [
            'course' => app(CourseController::class)->transform($course->load(['settings', 'owner'])),
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'content' => $lesson->content,
                'summary' => $lesson->summary,
                'status' => $lesson->status->value,
                'status_label' => $lesson->status->label(),
                'badge' => $lesson->status->badge(),
                'is_ai_generated' => $lesson->is_ai_generated,
                'version' => $lesson->version,
                'generated_at' => $lesson->generated_at?->format('d/m/Y H:i'),
                'sources' => $lesson->sources ?? [],
                'module' => $lesson->module ? ['id' => $lesson->module->id, 'title' => $lesson->module->title] : null,
            ],
            'sourcesIndex' => $sources,
        ]);
    }

    public function updateLesson(Request $request, Course $course, Lesson $lesson, ContentGenerator $contentGenerator): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($lesson->course_id === $course->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['nullable', 'string'],
            'summary' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,generated,review,approved,published'],
        ]);

        $contentChanged = trim((string) $lesson->content) !== trim((string) ($data['content'] ?? ''));

        $status = $data['status'] ?? ($contentChanged ? ContentStatus::Review->value : $lesson->status->value);

        if (! Gate::forUser($request->user())->allows('publish', $course) && $status === ContentStatus::Published->value) {
            $status = ContentStatus::Approved->value;
        }

        $lesson->forceFill([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']),
            'content' => $data['content'] ?? $lesson->content,
            'summary' => $data['summary'] ?? null,
            'status' => $status,
            'is_ai_generated' => false,
            'approved_at' => $status === ContentStatus::Approved->value ? ($lesson->approved_at ?? now()) : $lesson->approved_at,
        ])->save();

        if ($contentChanged) {
            $contentGenerator->indexLesson($lesson);
        }

        return redirect()
            ->route('courses.lessons.edit', [$course, $lesson])
            ->with('success', 'Lección guardada.');
    }

    public function setLessonStatus(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($lesson->course_id === $course->id, 404);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:draft,generated,review,approved,published'],
        ]);

        $status = ContentStatus::from($data['status']);

        if ($status === ContentStatus::Published && ! $course->isPublished()) {
            return back()->with('warning', 'Publicá primero la materia para que esta lección sea visible.');
        }

        $lesson->forceFill([
            'status' => $status,
            'approved_at' => $status === ContentStatus::Approved ? ($lesson->approved_at ?? now()) : $lesson->approved_at,
        ])->save();

        return back()->with('success', 'Estado actualizado a "'.$status->label().'".');
    }

    public function destroyLesson(Course $course, Lesson $lesson): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($lesson->course_id === $course->id, 404);

        $lesson->delete();

        return back()->with('success', 'Lección eliminada.');
    }

    public function reorderLessons(Request $request, Course $course, Module $module): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($module->course_id === $course->id, 404);

        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($data['order'] as $index => $lessonId) {
            $module->lessons()->whereKey($lessonId)->update(['position' => $index]);
        }

        return back();
    }

    /* ---------------------------------------------------------- IA */

    public function generateLesson(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($lesson->course_id === $course->id, 404);

        if ($course->documents()->where('status', 'ready')->doesntExist()) {
            return back()->with('warning', 'Todavía no hay material procesado. Cargá documentos primero.');
        }

        GenerateLessonContent::dispatch($lesson->id, $request->user()->id);

        return back()->with('success', 'Generando contenido de "'.$lesson->title.'" en segundo plano.');
    }

    public function generateAllContent(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $lessons = $course->lessons()
            ->where(function ($query) {
                $query->whereNull('content')
                    ->orWhere('content', '')
                    ->orWhere('status', ContentStatus::Generated->value);
            })
            ->limit(30)
            ->get();

        foreach ($lessons as $lesson) {
            GenerateLessonContent::dispatch($lesson->id, $request->user()->id);
        }

        return back()->with('success', 'Se encolaron '.count($lessons).' lecciones para generar.');
    }

    public function proposeStructure(Request $request, Course $course, CurriculumGenerator $generator): RedirectResponse
    {
        $this->authorize('update', $course);

        if ($course->documents()->doesntExist()) {
            return back()->with('warning', 'Cargá material antes de pedirle a la IA que proponga la estructura.');
        }

        $generator->propose($course, $request->user());

        return back()->with('success', 'La IA propuso una estructura. Revisala y aceptala o descartala.');
    }

    public function applyStructure(Request $request, Course $course, CurriculumGenerator $generator): RedirectResponse
    {
        $this->authorize('update', $course);

        $proposal = $request->input('proposal') ?: $course->meta['structure_proposal'] ?? null;

        if (empty($proposal['modules'])) {
            return back()->with('warning', 'No hay ninguna estructura propuesta para aplicar.');
        }

        $generator->apply($course, $generator->normalize($proposal));

        return back()->with('success', 'Estructura aplicada. Ahora generá los contenidos de cada lección.');
    }

    public function discardStructure(Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $course->forceFill([
            'meta' => array_merge($course->meta ?? [], ['structure_proposal' => null]),
        ])->save();

        return back()->with('success', 'Propuesta descartada.');
    }
}
