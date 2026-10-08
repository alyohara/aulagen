<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory;
    protected $fillable = [
        'course_id',
        'parent_id',
        'title',
        'slug',
        'type',
        'position',
        'summary',
        'description',
        'status',
        'is_ai_generated',
    ];

    protected function casts(): array
    {
        return [
            'is_ai_generated' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Module::class, 'parent_id')->orderBy('position');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position')->orderBy('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function isPublished(): bool
    {
        return $this->status === \App\Enums\ContentStatus::Published->value
            || $this->course?->isPublished() === true && in_array($this->status, [
                \App\Enums\ContentStatus::Approved->value,
                \App\Enums\ContentStatus::Published->value,
            ], true);
    }
}
