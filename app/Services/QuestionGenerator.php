<?php

namespace App\Services;

use App\AI\AIManager;
use App\Enums\ActivityType;
use App\Enums\GenerationType;
use App\Models\Activity;
use App\Models\AiGeneration;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Str;

class QuestionGenerator
{
    public function __construct(protected AIManager $ai, protected VectorSearchService $vectors)
    {
    }

    /**
     * Genera una actividad (cuestionario, autoevaluación, flashcards o examen)
     * a partir del material. Siempre queda en borrador para revisión docente.
     */
    public function generate(
        Course $course,
        ActivityType $type,
        ?Module $module = null,
        ?Lesson $lesson = null,
        ?User $user = null,
    ): Activity {
        $chunks = $this->gatherChunks($course, $module, $lesson);

        $context = $chunks->map(fn ($chunk) => [
            'content' => $chunk->content,
            'reference' => $chunk->referenceLabel(),
        ])->all();

        $subject = $lesson?->title ?? $module?->title ?? $course->name;

        $task = match ($type) {
            ActivityType::Quiz => 'quiz',
            ActivityType::Autoeval => 'autoeval',
            ActivityType::Exam => 'exam',
            ActivityType::Flashcards => 'flashcards',
            default => 'quiz',
        };

        [$system, $jsonShape] = match ($task) {
            'flashcards' => [
                'Creás tarjetas de estudio (flashcards) a partir del material. Respondé SOLO JSON: {"cards":[{"front":"","back":""}]}. Máximo 20 tarjetas.',
                '{"cards": []}',
            ],
            'exam' => [
                'Elaborás preguntas de examen a partir del material, de dificultad creciente. Respondé SOLO JSON: {"questions":[{"type":"multiple","prompt":"","options":["","","",""],"correct_answer":"","explanation":"","points":4}]}. Máximo 8 preguntas. NO inventes contenido fuera del material.',
                '{"questions": []}',
            ],
            default => [
                'Creás preguntas de autoevaluación/cuestionario SOLO a partir del material provisto. Respondé SOLO JSON: {"questions":[{"type":"multiple|boolean|short","prompt":"","options":[".."]|null,"correct_answer":"","explanation":"","points":1}]}. Máximo 10 preguntas. NO inventes información que no esté en el material.',
                '{"questions": []}',
            ],
        };

        $userPrompt = 'Tema: '.$subject."\n\nMaterial:\n".json_encode($context, JSON_UNESCAPED_UNICODE);

        $started = microtime(true);
        $provider = $this->ai->provider();
        $error = null;

        try {
            $result = $provider->complete($system, $userPrompt, [
                'task' => $task,
                'temperature' => 0.3,
                'json' => true,
                'context' => ['chunks' => $context, 'title' => $subject],
            ]);
            $payload = $result->json();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            $provider = $this->ai->local();
            $result = $provider->complete($system, $userPrompt, [
                'task' => $task,
                'context' => ['chunks' => $context, 'title' => $subject],
            ]);
            $payload = $result->json();
        }

        $activity = Activity::create([
            'course_id' => $course->id,
            'module_id' => $module?->id,
            'lesson_id' => $lesson?->id,
            'title' => $this->defaultTitle($type, $subject),
            'slug' => Str::slug($this->defaultTitle($type, $subject)).'-'.Str::lower(Str::random(5)),
            'type' => $type,
            'instructions' => $this->defaultInstructions($type),
            'status' => 'draft',
            'payload' => $task === 'flashcards' ? ['cards' => array_slice($payload['cards'] ?? [], 0, 20)] : null,
            'position' => ($course->activities()->max('position') ?? 0) + 1,
            'is_ai_generated' => true,
        ]);

        foreach (array_slice($payload['questions'] ?? [], 0, 12) as $index => $question) {
            $activity->questions()->create([
                'course_id' => $course->id,
                'position' => $index,
                'type' => in_array($question['type'] ?? '', ['multiple', 'boolean', 'short', 'open'], true) ? $question['type'] : 'multiple',
                'prompt' => (string) ($question['prompt'] ?? ''),
                'options' => is_array($question['options'] ?? null) ? array_values($question['options']) : null,
                'correct_answer' => $question['correct_answer'] ?? null,
                'explanation' => $question['explanation'] ?? null,
                'points' => (int) ($question['points'] ?? 1),
            ]);
        }

        AiGeneration::create([
            'course_id' => $course->id,
            'user_id' => $user?->id,
            'type' => (match ($type) {
                ActivityType::Quiz => GenerationType::Quiz,
                ActivityType::Autoeval => GenerationType::Autoeval,
                ActivityType::Flashcards => GenerationType::Flashcards,
                ActivityType::Exam => GenerationType::Exam,
                default => GenerationType::Quiz,
            })->value,
            'provider' => $provider->name(),
            'model' => $provider->model(),
            'prompt' => mb_substr($userPrompt, 0, 6000),
            'response' => mb_substr($result->text, 0, 30000),
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            'status' => $error === null ? 'success' : 'fallback',
            'error' => $error,
            'meta' => ['activity_id' => $activity->id, 'chunks' => count($context)],
        ]);

        return $activity;
    }

    protected function gatherChunks(Course $course, ?Module $module, ?Lesson $lesson)
    {
        if ($lesson !== null) {
            $matches = $this->vectors->similar($course, $lesson->title.' '.($lesson->module?->title ?? ''), 12);

            if ($matches->isNotEmpty()) {
                return $matches->map(fn ($row) => $row['chunk'])->values();
            }
        }

        if ($module !== null) {
            $moduleChunks = $course->chunks()->where('module_id', $module->id)->latest('id')->limit(12)->get();

            if ($moduleChunks->isNotEmpty()) {
                return $moduleChunks;
            }

            $matches = $this->vectors->similar($course, $module->title, 12);
            if ($matches->isNotEmpty()) {
                return $matches->map(fn ($row) => $row['chunk'])->values();
            }
        }

        return $course->chunks()->latest('id')->limit(16)->get()->values();
    }

    protected function defaultTitle(ActivityType $type, string $subject): string
    {
        return match ($type) {
            ActivityType::Quiz => 'Cuestionario: '.$subject,
            ActivityType::Autoeval => 'Autoevaluación: '.$subject,
            ActivityType::Flashcards => 'Flashcards: '.$subject,
            ActivityType::Exam => 'Preguntas de examen: '.$subject,
            ActivityType::Practice => 'Práctica: '.$subject,
            ActivityType::Assignment => 'Trabajo práctico: '.$subject,
        };
    }

    protected function defaultInstructions(ActivityType $type): string
    {
        return match ($type) {
            ActivityType::Flashcards => 'Pensá la respuesta antes de destapar. Repasá las tarjetas hasta dominar los conceptos.',
            ActivityType::Exam => 'Respuestas sugeridas a partir del material de la materia. El docente puede editarlas.',
            default => 'Seleccioná la opción correcta. Al finalizar podrás revisar las respuestas correctas.',
        };
    }
}
