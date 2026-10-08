<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Models\User;
use App\Services\ContentGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateLessonContent implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $lessonId, public ?int $userId = null)
    {
    }

    public function handle(ContentGenerator $generator): void
    {
        $lesson = Lesson::find($this->lessonId);

        if ($lesson === null) {
            return;
        }

        $generator->generateLesson($lesson, $this->userId ? User::find($this->userId) : null);
    }
}
