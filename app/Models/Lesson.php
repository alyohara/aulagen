<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Lesson extends Model
{
    use HasFactory;
    protected $fillable = [
        'course_id',
        'module_id',
        'title',
        'slug',
        'position',
        'status',
        'content',
        'summary',
        'objectives',
        'sources',
        'related_document_ids',
        'is_ai_generated',
        'version',
        'generated_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'sources' => 'array',
            'related_document_ids' => 'array',
            'is_ai_generated' => 'boolean',
            'generated_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $lesson) {
            if (blank($lesson->slug)) {
                $lesson->slug = Str::slug($lesson->title);
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

    public function chunks(): HasMany
    {
        return $this->hasMany(ContentChunk::class);
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published;
    }

    public function isReadableByStudents(): bool
    {
        return in_array($this->status, [ContentStatus::Approved, ContentStatus::Published], true);
    }
}
