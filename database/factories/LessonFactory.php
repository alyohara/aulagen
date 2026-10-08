<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(5, true);

        return [
            'course_id' => Course::factory(),
            'module_id' => Module::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'position' => fake()->numberBetween(1, 20),
            'status' => 'published',
            'content' => '<p>Contenido de prueba generado para el test.</p><blockquote> Fuente: Material oficial </blockquote>',
            'summary' => fake()->sentence(10),
            'sources' => ['Material oficial del docente'],
            'is_ai_generated' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }
}
