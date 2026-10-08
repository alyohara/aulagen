<?php

namespace App\Services;

use App\AI\AIManager;
use App\Enums\ContentStatus;
use App\Enums\GenerationType;
use App\Models\AiGeneration;
use App\Models\Course;
use App\Models\Document;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CurriculumGenerator
{
    public function __construct(protected AIManager $ai)
    {
    }

    /**
     * Propone una estructura (unidades / temas) a partir del material cargado.
     *
     * @return array{modules: array<int, array{title: string, type: string, summary: string, lessons: array<int, array{title: string, summary: string, documents: array<int, string>}>}>}
     */
    public function propose(Course $course, ?User $user = null): array
    {
        $documents = $course->documents()
            ->where('status', 'ready')
            ->whereNotNull('extracted_text')
            ->orderBy('id')
            ->get();

        if ($documents->isEmpty()) {
            $documents = $course->documents()->orderBy('id')->limit(50)->get();
        }

        $docsContext = $documents->map(function (Document $document) {
            $meta = $document->meta ?? [];
            $headings = collect($meta['headings'] ?? [])
                ->take(30)
                ->map(fn ($h) => is_array($h) ? ['level' => (int) ($h['level'] ?? 2), 'text' => (string) ($h['text'] ?? '')] : ['level' => 2, 'text' => (string) $h])
                ->values()
                ->all();

            return [
                'name' => $document->original_name,
                'title' => preg_replace('/\.[a-z0-9]+$/i', '', $document->original_name),
                'headings' => $headings,
                'excerpt' => mb_substr((string) $document->extracted_text, 0, 1500),
                'words' => str_word_count((string) $document->extracted_text),
            ];
        })->values()->all();

        $system = 'Sos un experto en diseño curricular universitario. '
            .'Recibís la lista de documentos cargados por un docente (con sus encabezados y un extracto de su contenido) '
            .'y debés proponer la estructura navegable de la materia. '
            ."Respondé SOLO con JSON válido, sin texto adicional, con esta forma exacta:\n"
            .'{"modules":[{"title":"Unidad 1 - ...","type":"unit","summary":"...","lessons":[{"title":"1.1 ...","summary":"...","documents":["archivo.pdf"]}]}]}\n'
            ."- type debe ser uno de: unit, section, activities, bibliography, complementary.\n"
            .'- Usá type=section para la presentación, activities para trabajos prácticos, bibliography para bibliografía y complementary para material extra.\n'
            .'- Los títulos deben ser claros y en español.\n'
            .'- NO inventes contenidos académicos que no estén en los documentos: solo organizá lo que hay.\n'
            .'- Incluí entre 3 y 10 unidades y entre 2 y 6 temas por unidad cuando el material lo permita.';

        $prompt = "Materia: {$course->name}\n"
            .($course->description ? "Descripción: {$course->description}\n" : '')
            .($course->objectives ? "Objetivos: {$course->objectives}\n" : '')
            .($course->program ? "Programa: {$course->program}\n" : '')
            ."\nDocumentos (".count($docsContext)."):\n"
            .json_encode($docsContext, JSON_UNESCAPED_UNICODE);

        $started = microtime(true);
        $provider = $this->ai->provider();
        $error = null;

        try {
            $result = $provider->complete($system, $prompt, [
                'task' => 'structure',
                'temperature' => 0.2,
                'json' => true,
                'context' => ['documents' => $docsContext, 'bibliography' => $course->bibliography()->count() > 0],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Fallo '.$provider->name().' en estructura: '.$e->getMessage());
            $error = $e->getMessage();
            $provider = $this->ai->local();
            $result = $provider->complete($system, $prompt, [
                'task' => 'structure',
                'context' => ['documents' => $docsContext, 'bibliography' => $course->bibliography()->count() > 0],
            ]);
        }

        $proposal = $this->normalize($result->json());

        AiGeneration::create([
            'course_id' => $course->id,
            'user_id' => $user?->id,
            'type' => GenerationType::Structure->value,
            'provider' => $provider->name(),
            'model' => $provider->model(),
            'prompt' => mb_substr($prompt, 0, 8000),
            'response' => mb_substr($result->text, 0, 30000),
            'duration_ms' => (int) ((microtime(true) - $started) * 1000),
            'status' => $error === null ? 'success' : 'fallback',
            'error' => $error,
            'meta' => ['documents' => count($docsContext), 'modules' => count($proposal['modules'])],
        ]);

        $course->forceFill([
            'structure_generated_at' => now(),
            'meta' => array_merge($course->meta ?? [], ['structure_proposal' => $proposal]),
        ])->save();

        return $proposal;
    }

    /**
     * Aplica una propuesta aceptada por el docente: crea módulos y lecciones.
     */
    public function apply(Course $course, array $proposal): void
    {
        $position = 0;

        foreach ($proposal['modules'] ?? [] as $moduleData) {
            $title = trim((string) ($moduleData['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $type = in_array($moduleData['type'] ?? '', ['unit', 'section', 'activities', 'bibliography', 'complementary'], true)
                ? $moduleData['type']
                : 'unit';

            $module = $course->modules()->updateOrCreate(
                ['slug' => Str::slug($title), 'type' => $type],
                [
                    'title' => $title,
                    'position' => $position,
                    'summary' => $moduleData['summary'] ?? null,
                    'status' => ContentStatus::Draft->value,
                    'is_ai_generated' => true,
                ]
            );

            $lessonPosition = 0;
            foreach ($moduleData['lessons'] ?? [] as $lessonData) {
                $lessonTitle = trim((string) ($lessonData['title'] ?? ''));
                if ($lessonTitle === '') {
                    continue;
                }

                $lesson = $module->lessons()
                    ->where('slug', Str::slug($lessonTitle))
                    ->first();

                if ($lesson === null) {
                    $module->lessons()->create([
                        'course_id' => $course->id,
                        'title' => $lessonTitle,
                        'slug' => Str::slug($lessonTitle),
                        'position' => $lessonPosition,
                        'status' => ContentStatus::Generated->value,
                        'summary' => $lessonData['summary'] ?? null,
                        'is_ai_generated' => true,
                        'generated_at' => now(),
                        'related_document_ids' => $this->documentIds($course, $lessonData['documents'] ?? []),
                    ]);
                    $lessonPosition++;
                }
            }
        }

        $course->modules()->orderBy('position')->orderBy('id')->get()
            ->values()
            ->each(function (Module $module, $index) {
                $module->update(['position' => $index]);

                $module->lessons()->orderBy('position')->orderBy('id')->get()
                    ->values()
                    ->each(fn (Lesson $lesson, $lessonIndex) => $lesson->update(['position' => $lessonIndex]));
            });

        $course->forceFill(['meta' => array_merge($course->meta ?? [], ['structure_proposal' => null])])->save();
    }

    public function normalize(array $proposal): array
    {
        $modules = [];

        foreach (array_slice($proposal['modules'] ?? [], 0, 30) as $index => $module) {
            $title = trim((string) ($module['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $lessons = [];
            foreach (array_slice($module['lessons'] ?? [], 0, 20) as $lesson) {
                $lessonTitle = trim((string) ($lesson['title'] ?? ''));
                if ($lessonTitle === '') {
                    continue;
                }

                $lessons[] = [
                    'title' => $lessonTitle,
                    'summary' => trim((string) ($lesson['summary'] ?? '')),
                    'documents' => array_values(array_filter(array_map('strval', $lesson['documents'] ?? []))),
                ];
            }

            $modules[] = [
                'title' => $title,
                'type' => in_array($module['type'] ?? '', ['unit', 'section', 'activities', 'bibliography', 'complementary'], true)
                    ? $module['type']
                    : 'unit',
                'summary' => trim((string) ($module['summary'] ?? '')),
                'lessons' => $lessons,
                'position' => $index,
            ];
        }

        return ['modules' => $modules];
    }

    /** @param array<int, string> $names */
    protected function documentIds(Course $course, array $names): array
    {
        if ($names === []) {
            return [];
        }

        return $course->documents()
            ->whereIn('original_name', $names)
            ->pluck('id')
            ->all();
    }
}
