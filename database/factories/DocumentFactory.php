<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'uploaded_by' => User::factory()->teacher(),
            'original_name' => fake()->unique()->word().'.pdf',
            'type' => 'pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1024, 5_000_000),
            'disk' => 'public',
            'path' => 'documents/'.fake()->unique()->uuid().'.pdf',
            'status' => 'ready',
            'chunk_count' => 0,
        ];
    }
}
