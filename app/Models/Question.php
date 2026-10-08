<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use HasFactory;
    protected $fillable = [
        'activity_id',
        'course_id',
        'position',
        'type',
        'prompt',
        'options',
        'correct_answer',
        'explanation',
        'points',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'meta' => 'array',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
