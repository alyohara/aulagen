<?php

namespace App\Jobs;

use App\Models\Course;
use App\Models\User;
use App\Services\CurriculumGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateCourseStructure implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $courseId, public ?int $userId = null)
    {
    }

    public function handle(CurriculumGenerator $generator): void
    {
        $course = Course::find($this->courseId);

        if ($course === null) {
            return;
        }

        $generator->propose($course, $this->userId ? User::find($this->userId) : null);
    }
}
