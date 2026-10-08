<?php

namespace App\Models;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Activity extends Model
{
    use HasFactory;
    protected $fillable = [
        'course_id',
        'module_id',
        'lesson_id',
        'title',
        'slug',
        'type',
        'instructions',
        'status',
        'payload',
        'position',
        'is_ai_generated',
    ];

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'payload' => 'array',
            'is_ai_generated' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $activity) {
            if (blank($activity->slug)) {
                $activity->slug = Str::slug($activity->title);
            }
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position')->orderBy('id');
    }

    public function cards(): array
    {
        return $this->payload['cards'] ?? [];
    }
}
