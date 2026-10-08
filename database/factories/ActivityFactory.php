<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(4, true);

        return [
            'course_id' => Course::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'type' => 'quiz',
            'instructions' => fake()->sentence(8),
            'status' => 'published',
            'position' => fake()->numberBetween(1, 20),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }
}
