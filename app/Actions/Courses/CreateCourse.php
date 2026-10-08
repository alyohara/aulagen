<?php

namespace App\Actions\Courses;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;

class CreateCourse
{
    public function handle(User $user, array $data): Course
    {
        $course = Course::create([
            'owner_id' => $user->id,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Course::uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'institution' => $data['institution'] ?? null,
            'career' => $data['career'] ?? null,
            'course_year' => $data['course_year'] ?? null,
            'duration' => $data['duration'] ?? null,
            'modality' => $data['modality'] ?? null,
            'objectives' => $data['objectives'] ?? null,
            'program' => $data['program'] ?? null,
            'status' => CourseStatus::Draft,
            'meta' => [
                'collaborators' => $data['collaborators'] ?? null,
                'bibliography_principal' => $data['bibliography_principal'] ?? null,
                'bibliography_complementary' => $data['bibliography_complementary'] ?? null,
            ],
        ]);

        $course->settings()->create([]);

        return $course;
    }
}
