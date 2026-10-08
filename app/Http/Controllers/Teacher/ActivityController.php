<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateActivity;
use App\Models\Activity;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityController extends Controller
{
    public function index(Course $course)
    {
        $this->authorize('view', $course);

        $activities = $course->activities()->withCount('questions')->with('module')->orderBy('position')->get();

        return [
            'activities' => $activities->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'title' => $activity->title,
                'slug' => $activity->slug,
                'type' => $activity->type->value,
                'type_label' => $activity->type->label(),
                'status' => $activity->status,
                'instructions' => $activity->instructions,
                'questions_count' => $activity->questions_count,
                'cards_count' => count($activity->cards()),
                'is_ai_generated' => $activity->is_ai_generated,
                'module' => $activity->module?->title,
                'created_at' => $activity->created_at?->format('d/m/Y'),
            ]),
            'modules' => $course->modules()->where('type', 'unit')->orderBy('position')->get()->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
            ]),
        ];
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'string', 'in:quiz,autoeval,flashcards,exam,practice,assignment'],
            'instructions' => ['nullable', 'string'],
            'module_id' => ['nullable', 'integer'],
        ]);

        Activity::create([
            'course_id' => $course->id,
            'module_id' => $data['module_id'] ?? null,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(4)),
            'type' => ActivityType::from($data['type']),
            'instructions' => $data['instructions'] ?? null,
            'status' => 'draft',
            'position' => ((int) $course->activities()->max('position')) + 1,
        ]);

        return back()->with('success', 'Actividad creada.');
    }

    public function update(Request $request, Course $course, Activity $activity): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($activity->course_id === $course->id, 404);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:180'],
            'instructions' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:draft,review,approved,published'],
        ]);

        $activity->fill(array_filter($data, fn ($v) => $v !== null))->save();

        return back()->with('success', 'Actividad actualizada.');
    }

    public function destroy(Course $course, Activity $activity): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($activity->course_id === $course->id, 404);

        $activity->delete();

        return back()->with('success', 'Actividad eliminada.');
    }

    public function generate(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'type' => ['required', 'string', 'in:quiz,autoeval,flashcards,exam'],
            'module_id' => ['nullable', 'integer'],
            'lesson_id' => ['nullable', 'integer'],
        ]);

        if ($course->documents()->where('status', 'ready')->doesntExist() && $course->chunks()->doesntExist()) {
            return back()->with('warning', 'Cargá y procesá material antes de generar actividades.');
        }

        GenerateActivity::dispatch(
            $course->id,
            $data['type'],
            $data['module_id'] ?? null,
            $data['lesson_id'] ?? null,
            $request->user()->id,
        );

        return back()->with('success', 'Generando la actividad con IA en segundo plano.');
    }

    public function updateQuestion(Request $request, Course $course, Activity $activity, $questionId): RedirectResponse
    {
        $this->authorize('update', $course);

        abort_unless($activity->course_id === $course->id, 404);

        $question = $activity->questions()->findOrFail($questionId);

        $data = $request->validate([
            'prompt' => ['required', 'string'],
            'options' => ['nullable', 'array'],
            'correct_answer' => ['nullable', 'string'],
            'explanation' => ['nullable', 'string'],
        ]);

        $question->fill($data)->save();

        return back()->with('success', 'Pregunta actualizada.');
    }
}
