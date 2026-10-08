<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgress extends Model
{
    protected $table = 'student_progress';

    protected $fillable = [
        'course_id',
        'user_id',
        'lesson_id',
        'completed',
        'seconds',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
