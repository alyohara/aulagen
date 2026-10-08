<?php

namespace App\Http\Controllers\Aula;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Document;
use App\Models\Lesson;
use App\Models\Module;
use App\Services\RAGService;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class AulaController extends Controller
{
    public function home(Request $request, Course $course): Response
    {
        [$isPreview, $user] = $this->guard($request, $course);

        $lessons = $this->visibleLessons($course, $isPreview);
        $progress = $this->progressMap($request, $course);

        return Inertia::render('Aula/Home', [
            ...$this->sharedProps($course, $isPreview),
            'stats' => [
                'modules' => $this->visibleModules($course, $isPreview)->count(),
                'lessons' => $lessons->count(),
                'activities' => $course->activities()->where('status', '!=', 'draft')->count(),
                'documents' => $course->documents()->where('status', 'ready')->count(),
            ],
            'objectives' => $course->objectives,
            'program' => $course->program,
            'description' => $course->description,
            'continue' => $this->continueLesson($lessons, $progress),
            'progress' => $progress,
            'bibliography' => $course->bibliography->take(5)->map(fn ($b) => $b->formatted()),
        ]);
    }

    public function module(Request $request, Course $course, Module $module): Response
    {
        [$isPreview] = $this->guard($request, $course);

        abort_unless($module->course_id === $course->id, 404);

        $lessons = $this->visibleLessons($course, $isPreview)->where('module_id', $module->id)->values();

        return Inertia::render('Aula/Module', [
            ...$this->sharedProps($course, $isPreview),
            'module' => [
                'id' => $module->id,
                'title' => $module->title,
                'slug' => $module->slug,
                'summary' => $module->summary,
                'type' => $module->type,
            ],
            'lessons' => $lessons->map(fn (Lesson $lesson) => $this->lessonCard($lesson)),
            'progress' => $this->progressMap($request, $course),
            'documents' => $course->documents()
                ->where('module_id', $module->id)
                ->where('status', 'ready')
                ->get()
                ->map(fn ($doc) => [
                    'id' => $doc->id,
                    'name' => $doc->original_name,
                    'type' => $doc->type->value,
                    'size' => $doc->humanSize(),
                    ...$this->documentLink($course, $doc),
                ]),
        ]);
    }

    public function lesson(Request $request, Course $course, Module $module, Lesson $lesson): Response
    {
        [$isPreview] = $this->guard($request, $course);

        abort_unless($module->course_id === $course->id && $lesson->module_id === $module->id, 404);
        abort_unless($isPreview || $lesson->isReadableByStudents(), 404);

        $lessons = $this->visibleLessons($course, $isPreview)->values();
        $index = $lessons->search(fn (Lesson $item) => $item->id === $lesson->id);

        $related = $lesson->related_document_ids ?: [];

        return Inertia::render('Aula/Lesson', [
            ...$this->sharedProps($course, $isPreview),
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'content' => $lesson->content,
                'summary' => $lesson->summary,
                'status' => $lesson->status->value,
                'status_label' => $lesson->status->label(),
                'sources' => $lesson->sources ?? [],
                'module' => ['id' => $module->id, 'title' => $module->title, 'slug' => $module->slug],
                'generated_at' => $lesson->generated_at?->format('d/m/Y H:i'),
            ],
            'prev' => $index !== false && $index > 0 ? $this->lessonLink($course, $lessons[$index - 1]) : null,
            'next' => $index !== false && $index < $lessons->count() - 1 ? $this->lessonLink($course, $lessons[$index + 1]) : null,
            'relatedDocuments' => $course->documents()->whereIn('id', $related)->get()->map(fn ($doc) => [
                'id' => $doc->id,
                'name' => $doc->original_name,
                ...$this->documentLink($course, $doc),
            ]),
            'progress' => $this->progressMap($request, $course),
        ]);
    }

    public function activities(Request $request, Course $course): Response
    {
        [$isPreview] = $this->guard($request, $course);

        $activities = $course->activities()
            ->with(['questions', 'module'])
            ->when(! $isPreview, fn ($q) => $q->where('status', '!=', 'draft'))
            ->orderBy('position')
            ->get();

        return Inertia::render('Aula/Activities', [
            ...$this->sharedProps($course, $isPreview),
            'activities' => $activities->map(fn ($activity) => [
                'id' => $activity->id,
                'title' => $activity->title,
                'slug' => $activity->slug,
                'type' => $activity->type->value,
                'type_label' => $activity->type->label(),
                'instructions' => $activity->instructions,
                'module' => $activity->module?->title,
                'questions' => $activity->questions->map(fn ($q) => [
                    'id' => $q->id,
                    'type' => $q->type,
                    'prompt' => $q->prompt,
                    'options' => $q->options,
                ]),
                'cards' => $activity->cards(),
            ]),
            'progress' => $this->progressMap($request, $course),
        ]);
    }

    public function checkAnswers(Request $request, Course $course, $activityId): JsonResponse
    {
        [$isPreview] = $this->guard($request, $course);

        $activity = $course->activities()->with('questions')->findOrFail($activityId);

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string', 'max:500'],
        ]);

        $results = [];

        foreach ($activity->questions as $question) {
            $chosen = $data['answers'][(string) $question->id] ?? null;
            $correct = $chosen !== null && $chosen === $question->correct_answer;

            $results[(string) $question->id] = [
                'correct' => $correct,
                'correct_answer' => $question->correct_answer,
                'explanation' => $question->explanation,
            ];
        }

        return response()->json([
            'results' => $results,
            'score' => count(array_filter($results, fn ($r) => $r['correct'])),
            'total' => count($results),
        ]);
    }

    public function bibliography(Request $request, Course $course): Response
    {
        [$isPreview] = $this->guard($request, $course);

        $entries = $course->bibliography()->get();

        $metaPrincipal = $course->meta['bibliography_principal'] ?? null;
        $metaComplementary = $course->meta['bibliography_complementary'] ?? null;

        return Inertia::render('Aula/Bibliography', [
            ...$this->sharedProps($course, $isPreview),
            'principal' => $entries->where('kind', 'principal')->values(),
            'complementary' => $entries->where('kind', 'complementaria')->values(),
            'rawPrincipal' => $metaPrincipal,
            'rawComplementary' => $metaComplementary,
            'progress' => $this->progressMap($request, $course),
        ]);
    }

    public function glossary(Request $request, Course $course): Response
    {
        [$isPreview] = $this->guard($request, $course);

        return Inertia::render('Aula/Glossary', [
            ...$this->sharedProps($course, $isPreview),
            'terms' => collect($course->meta['glossary'] ?? [])->sortBy('term')->values(),
            'progress' => $this->progressMap($request, $course),
        ]);
    }

    public function complementary(Request $request, Course $course): Response
    {
        [$isPreview] = $this->guard($request, $course);

        $documents = $course->documents()
            ->where('status', 'ready')
            ->get()
            ->filter(fn ($doc) => in_array($doc->type->value, ['pdf', 'docx', 'pptx', 'txt', 'markdown', 'image', 'video', 'link', 'text'], true))
            ->groupBy(fn ($doc) => $doc->module?->title ?? 'Material general');

        return Inertia::render('Aula/Complementary', [
            ...$this->sharedProps($course, $isPreview),
            'groups' => $documents->map(fn ($group, $title) => [
                'title' => $title,
                'documents' => $group->map(fn ($doc) => [
                    'id' => $doc->id,
                    'name' => $doc->original_name,
                    'type' => $doc->type->value,
                    'size' => $doc->humanSize(),
                    ...$this->documentLink($course, $doc),
                ])->values(),
            ])->values(),
            'progress' => $this->progressMap($request, $course),
        ]);
    }

    public function search(Request $request, Course $course, SearchService $searchService): Response
    {
        [$isPreview] = $this->guard($request, $course);

        $settings = $course->ensureSettings();
        abort_unless($settings->enable_search || $isPreview, 403);

        $query = trim((string) $request->query('q', ''));

        $results = $query === '' ? [] : $searchService->search($course, $query, 15);

        foreach ($results as &$result) {
            $result['url'] = $this->resultUrl($course, $result);
        }

        return Inertia::render('Aula/Search', [
            ...$this->sharedProps($course, $isPreview),
            'query' => $query,
            'results' => $results,
            'progress' => $this->progressMap($request, $course),
        ]);
    }

    public function ask(Request $request, Course $course, RAGService $rag): JsonResponse
    {
        [$isPreview] = $this->guard($request, $course);

        $settings = $course->ensureSettings();

        if (! $settings->ai_assistant_enabled && ! $isPreview) {
            return response()->json([
                'answer' => 'El asistente de IA está desactivado para esta materia.',
                'sources' => [],
            ], 403);
        }

        $data = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $answer = $rag->ask($course, $data['question'], $request->user());
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'answer' => 'No pude responder en este momento. Probá de nuevo en unos segundos.',
                'sources' => [],
                'error' => true,
            ], 500);
        }

        return response()->json($answer);
    }

    public function download(Request $request, Course $course, $documentId)
    {
        [$isPreview] = $this->guard($request, $course);

        $document = $course->documents()->findOrFail($documentId);
        $settings = $course->ensureSettings();

        if (! $isPreview && ! $settings->allow_downloads) {
            abort(403, 'Las descargas están deshabilitadas para esta materia.');
        }

        if ($document->isInline() && filled($document->source_url)) {
            return redirect()->away($document->source_url);
        }

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_name,
            ['Content-Type' => $document->mime_type ?: 'application/octet-stream']
        );
    }

    public function progress(Request $request, Course $course): JsonResponse
    {
        $this->guard($request, $course);

        $data = $request->validate([
            'lesson_id' => ['required', 'integer'],
            'completed' => ['boolean'],
        ]);

        $lesson = $course->lessons()->findOrFail($data['lesson_id']);

        if ($request->user()) {
            $course->studentProgress()->updateOrCreate(
                ['user_id' => $request->user()->id, 'lesson_id' => $lesson->id],
                ['completed' => (bool) ($data['completed'] ?? true)],
            );
        }

        return response()->json(['ok' => true]);
    }

    /* --------------------------------------------------------- Helpers */

    private function documentLink(Course $course, Document $document): array
    {
        $external = in_array($document->type->value, ['link', 'video'], true) && filled($document->source_url);

        return [
            'url' => $external ? $document->source_url : route('aula.download', [$course, $document]),
            'external' => $external,
        ];
    }

    private function guard(Request $request, Course $course): array
    {
        $user = $request->user();
        $teacher = $user !== null && $user->teaches($course);

        if (! $course->isPublished() && ! $teacher) {
            abort(404);
        }

        $isPreview = $teacher && (! $course->isPublished() || $request->boolean('preview'));

        return [$isPreview, $user];
    }

    private function sharedProps(Course $course, bool $isPreview): array
    {
        $settings = $course->ensureSettings();
        $course->loadMissing(['owner', 'collaborators']);

        return [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'slug' => $course->slug,
                'institution' => $course->institution,
                'career' => $course->career,
                'course_year' => $course->course_year,
                'duration' => $course->duration,
                'modality' => $course->modality,
                'description' => $course->description,
                'owner' => $course->owner?->name,
                'collaborators' => $course->meta['collaborators'] ?? null,
                'status' => $course->status->value,
                'published_at' => $course->published_at?->format('d/m/Y'),
            ],
            'navigation' => $this->navigation($course, $isPreview),
            'settings' => [
                'ai_assistant_enabled' => $settings->ai_assistant_enabled,
                'enable_search' => $settings->enable_search,
                'show_progress' => $settings->show_progress,
                'show_sources' => $settings->show_sources,
                'allow_downloads' => $settings->allow_downloads,
                'allow_external_knowledge' => $settings->allow_external_knowledge,
            ],
            'isPreview' => $isPreview,
            'previewUrl' => URL::current(),
        ];
    }

    private function navigation(Course $course, bool $isPreview): array
    {
        return $course->modules()
            ->with(['lessons' => fn ($q) => $q->orderBy('position')->orderBy('id')])
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (Module $module) => [
                'id' => $module->id,
                'title' => $module->title,
                'slug' => $module->slug,
                'type' => $module->type,
                'lessons' => $module->lessons
                    ->filter(fn (Lesson $lesson) => $isPreview || $lesson->isReadableByStudents())
                    ->map(fn (Lesson $lesson) => [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'slug' => $lesson->slug,
                        'status' => $lesson->status->value,
                    ])
                    ->values(),
            ])
            ->all();
    }

    private function visibleModules(Course $course, bool $isPreview)
    {
        return $course->modules()->orderBy('position')->get()
            ->filter(fn (Module $module) => $isPreview || $module->lessons()
                ->whereIn('status', ['approved', 'published'])->exists())
            ->values();
    }

    private function visibleLessons(Course $course, bool $isPreview)
    {
        return $course->lessons()
            ->with('module')
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->filter(fn (Lesson $lesson) => $isPreview || $lesson->isReadableByStudents())
            ->values();
    }

    private function lessonCard(Lesson $lesson): array
    {
        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'slug' => $lesson->slug,
            'summary' => $lesson->summary,
            'status' => $lesson->status->value,
            'status_label' => $lesson->status->label(),
            'badge' => $lesson->status->badge(),
            'module' => ['id' => $lesson->module?->id, 'slug' => $lesson->module?->slug, 'title' => $lesson->module?->title],
            'url' => route('aula.lesson', [$lesson->course, $lesson->module, $lesson]),
        ];
    }

    private function lessonLink(Course $course, Lesson $lesson): array
    {
        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'url' => $lesson->module ? route('aula.lesson', [$course, $lesson->module->slug, $lesson->slug]) : route('aula.home', $course),
        ];
    }

    private function resultUrl(Course $course, array $result): string
    {
        if (($result['lesson_id'] ?? null) !== null) {
            $lesson = $course->lessons()->with('module')->find($result['lesson_id']);

            if ($lesson !== null && $lesson->module !== null) {
                return route('aula.lesson', [$course, $lesson->module->slug, $lesson->slug]);
            }
        }

        if (($result['module_id'] ?? null) !== null) {
            $module = $course->modules()->find($result['module_id']);

            if ($module !== null) {
                return route('aula.module', [$course, $module->slug]);
            }
        }

        return route('aula.home', $course);
    }

    private function progressMap(Request $request, Course $course): array
    {
        if ($request->user()) {
            return $course->studentProgress()
                ->where('user_id', $request->user()->id)
                ->where('completed', true)
                ->pluck('lesson_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return [];
    }

    private function continueLesson($lessons, array $progress)
    {
        foreach ($lessons as $lesson) {
            if (! in_array($lesson->id, $progress, true)) {
                return $this->lessonCard($lesson);
            }
        }

        return $lessons->isNotEmpty() ? $this->lessonCard($lessons->last()) : null;
    }
}
