<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Document;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AulaTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(): Course
    {
        $course = Course::factory()->published()->create();

        $module = Module::factory()->create([
            'course_id' => $course->id,
            'position' => 1,
        ]);

        Lesson::factory()->create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'position' => 1,
            'status' => 'published',
        ]);

        return $course->fresh();
    }

    public function test_guest_can_view_published_course_home(): void
    {
        $course = $this->makeCourse();

        $response = $this->get(route('aula.home', $course));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Aula/Home')
            ->where('course.slug', $course->slug)
            ->where('isPreview', false));
    }

    public function test_draft_course_returns_404_for_guests(): void
    {
        $course = Course::factory()->create();

        $this->get(route('aula.home', $course))->assertNotFound();
    }

    public function test_owner_teacher_can_preview_draft_course(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($course->owner)->get(route('aula.home', $course))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('isPreview', true));
    }

    public function test_lesson_from_another_course_returns_404(): void
    {
        $course = $this->makeCourse();
        $other = $this->makeCourse();

        $module = $other->modules()->first();
        $lesson = $other->lessons()->first();

        $this->get(route('aula.lesson', [$course, $module, $lesson]))->assertNotFound();
    }

    public function test_draft_lesson_is_not_accessible_for_students(): void
    {
        $course = $this->makeCourse();
        $module = $course->modules()->first();

        $draft = Lesson::factory()->draft()->create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'position' => 2,
        ]);

        $this->get(route('aula.lesson', [$course, $module, $draft]))->assertNotFound();

        $this->actingAs($course->owner)->get(route('aula.lesson', [$course, $module, $draft]).'?preview=1')
            ->assertOk();
    }

    public function test_activities_page_hides_draft_activities_from_guests(): void
    {
        $course = $this->makeCourse();

        Activity::factory()->create(['course_id' => $course->id, 'position' => 1]);
        Activity::factory()->draft()->create(['course_id' => $course->id, 'position' => 2]);

        $this->get(route('aula.activities', $course))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Aula/Activities')
                ->has('activities', 1));

        $this->actingAs($course->owner)->get(route('aula.activities', $course).'?preview=1')
            ->assertInertia(fn (Assert $page) => $page->has('activities', 2));
    }

    public function test_quiz_answers_are_corrected_with_score(): void
    {
        $course = $this->makeCourse();

        $activity = Activity::factory()->create(['course_id' => $course->id]);
        $question = Question::factory()->create([
            'activity_id' => $activity->id,
            'course_id' => $course->id,
            'prompt' => '¿Cuál es la complejidad de la búsqueda binaria?',
            'options' => ['O(1)', 'O(log n)', 'O(n)'],
            'correct_answer' => 'O(log n)',
            'explanation' => 'Cada comparación descarta la mitad.',
        ]);

        $wrong = Question::factory()->create([
            'activity_id' => $activity->id,
            'course_id' => $course->id,
            'correct_answer' => 'Verdadero',
        ]);

        $response = $this->postJson(route('aula.check', [$course, $activity]), [
            'answers' => [
                (string) $question->id => 'O(log n)',
                (string) $wrong->id => 'Falso',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('score', 1)
            ->assertJsonPath('total', 2)
            ->assertJsonPath("results.{$question->id}.correct", true)
            ->assertJsonPath("results.{$wrong->id}.correct", false);
    }

    public function test_draft_course_quiz_cannot_be_checked_by_guests(): void
    {
        $course = Course::factory()->create();
        $activity = Activity::factory()->create(['course_id' => $course->id]);

        $this->postJson(route('aula.check', [$course, $activity]), ['answers' => []])
            ->assertNotFound();
    }

    public function test_supporting_pages_render(): void
    {
        $course = $this->makeCourse();

        $this->get(route('aula.bibliography', $course))->assertOk();
        $this->get(route('aula.glossary', $course))->assertOk();
        $this->get(route('aula.complementary', $course))->assertOk();
        $this->get(route('aula.search', $course).'?q=algoritmo')->assertOk();
    }

    public function test_search_can_be_disabled_by_course_settings(): void
    {
        $course = $this->makeCourse();
        $settings = $course->ensureSettings();
        $settings->update(['enable_search' => false]);

        $this->get(route('aula.search', $course))->assertForbidden();
    }

    public function test_ai_assistant_can_be_disabled(): void
    {
        $course = $this->makeCourse();
        $course->ensureSettings()->update(['ai_assistant_enabled' => false]);

        $this->postJson(route('aula.ask', $course), ['question' => '¿Qué es Big-O?'])
            ->assertForbidden()
            ->assertJsonStructure(['answer', 'sources']);
    }

    public function test_download_is_blocked_when_disabled(): void
    {
        Storage::fake('public');
        $course = $this->makeCourse();
        $document = Document::factory()->create(['course_id' => $course->id]);
        Storage::disk('public')->put($document->path, 'contenido del pdf');

        $course->ensureSettings()->update(['allow_downloads' => false]);

        $this->get(route('aula.download', [$course, $document]))->assertForbidden();

        $this->actingAs($course->owner)->get(route('aula.download', [$course, $document]).'?preview=1')
            ->assertOk();
    }

    public function test_progress_endpoint_stores_student_progress(): void
    {
        $course = $this->makeCourse();
        $lesson = $course->lessons()->first();
        $student = \App\Models\User::factory()->student()->create();

        $response = $this->actingAs($student)->postJson(route('aula.progress', $course), [
            'lesson_id' => $lesson->id,
            'completed' => true,
        ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('student_progress', [
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_progress_endpoint_rejects_draft_courses(): void
    {
        $course = Course::factory()->create();

        $this->postJson(route('aula.progress', $course), ['lesson_id' => 1])
            ->assertNotFound();
    }
}
