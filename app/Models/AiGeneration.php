<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneration extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'type',
        'provider',
        'model',
        'prompt',
        'response',
        'input_tokens',
        'output_tokens',
        'duration_ms',
        'status',
        'error',
        'meta',
    ];

    protected function casts(): array
    {
        return [
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
}
