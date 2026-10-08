<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Course;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        $correct = fake()->randomElement(['Opción A', 'Opción B', 'Opción C']);

        return [
            'activity_id' => Activity::factory(),
            'course_id' => Course::factory(),
            'position' => fake()->numberBetween(1, 20),
            'type' => 'multiple_choice',
            'prompt' => fake()->sentence(8).'?',
            'options' => ['Opción A', 'Opción B', 'Opción C'],
            'correct_answer' => $correct,
            'explanation' => fake()->sentence(10),
            'points' => 1,
        ];
    }
}
