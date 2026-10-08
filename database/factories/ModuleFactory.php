<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(4, true);

        return [
            'course_id' => Course::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'type' => 'unit',
            'position' => fake()->numberBetween(1, 20),
            'summary' => fake()->sentence(10),
            'status' => 'published',
        ];
    }
}
