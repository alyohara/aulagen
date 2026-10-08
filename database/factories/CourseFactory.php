<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory()->teacher(),
            'name' => fake()->unique()->words(4, true),
            'description' => fake()->sentence(12),
            'institution' => fake()->company(),
            'career' => fake()->randomElement(['Licenciatura en Sistemas', 'Ingeniería en Informática']),
            'course_year' => '2026',
            'status' => 'draft',
            'primary_color' => '#2563eb',
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
