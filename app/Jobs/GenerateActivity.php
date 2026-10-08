<?php

namespace App\Jobs;

use App\Enums\ActivityType;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use App\Services\QuestionGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateActivity implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public int $courseId,
        public string $activityType,
        public ?int $moduleId = null,
        public ?int $lessonId = null,
        public ?int $userId = null,
    ) {}

    public function handle(QuestionGenerator $generator): void
    {
        $course = Course::find($this->courseId);

        if ($course === null) {
            return;
        }

        $module = $this->moduleId ? Module::find($this->moduleId) : null;
        $lesson = $this->lessonId ? Lesson::find($this->lessonId) : null;

        $generator->generate(
            $course,
            ActivityType::from($this->activityType),
            $module,
            $lesson,
            $this->userId ? User::find($this->userId) : null,
        );
    }
}
