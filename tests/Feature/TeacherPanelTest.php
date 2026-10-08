<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_students_cannot_access_teacher_panel(): void
    {
        $student = \App\Models\User::factory()->student()->create();

        $this->actingAs($student)->get('/dashboard')->assertForbidden();
    }

    public function test_teachers_can_view_dashboard(): void
    {
        $teacher = \App\Models\User::factory()->teacher()->create();

        $this->actingAs($teacher)->get('/dashboard')->assertOk();
    }

    public function test_teachers_can_create_a_course(): void
    {
        $teacher = \App\Models\User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->post('/courses', [
            'name' => 'Materia de prueba',
            'description' => 'Descripción de prueba',
        ]);

        $course = Course::where('slug', 'materia-de-prueba')->first();
        $this->assertNotNull($course);
        $response->assertRedirect(route('courses.documents.index', $course));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'owner_id' => $teacher->id,
            'status' => 'draft',
        ]);
    }

    public function test_teachers_cannot_edit_other_teachers_courses(): void
    {
        $owner = \App\Models\User::factory()->teacher()->create();
        $other = \App\Models\User::factory()->teacher()->create();
        $course = Course::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($other)->get(route('courses.overview', $course))->assertForbidden();
        $this->actingAs($other)->get(route('courses.edit', $course))->assertForbidden();
    }

    public function test_owner_can_publish_a_course(): void
    {
        $owner = \App\Models\User::factory()->teacher()->create();
        $course = Course::factory()->create(['owner_id' => $owner->id, 'status' => 'draft']);

        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'status' => 'approved',
        ]);

        $this->actingAs($owner)->post(route('courses.publish', $course))
            ->assertRedirect();

        $course->refresh();
        $this->assertTrue($course->isPublished());
        $this->assertSame('published', $course->lessons()->first()->status->value);
    }

    public function test_publishing_requires_ownership(): void
    {
        $owner = \App\Models\User::factory()->teacher()->create();
        $other = \App\Models\User::factory()->teacher()->create();
        $course = Course::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($other)->post(route('courses.publish', $course))->assertForbidden();
    }

    public function test_lesson_status_can_be_updated_by_owner(): void
    {
        $owner = \App\Models\User::factory()->teacher()->create();
        $course = Course::factory()->create(['owner_id' => $owner->id]);
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'course_id' => $course->id,
            'module_id' => $module->id,
            'status' => 'draft',
        ]);

        $this->actingAs($owner)
            ->post(route('courses.lessons.status', [$course, $lesson]), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertSame('approved', $lesson->fresh()->status->value);
    }
}
