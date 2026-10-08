<?php

namespace App\Models;

use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'slug',
        'name',
        'description',
        'institution',
        'career',
        'course_year',
        'duration',
        'modality',
        'objectives',
        'program',
        'status',
        'primary_color',
        'published_at',
        'structure_generated_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => CourseStatus::class,
            'published_at' => 'datetime',
            'structure_generated_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $course) {
            if (blank($course->slug)) {
                $course->slug = static::uniqueSlug($course->name);
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: Str::lower(Str::random(8));
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_teachers')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function settings(): HasOne
    {
        return $this->hasOne(CourseSetting::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('position')->orderBy('id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position')->orderBy('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->orderBy('position')->orderBy('id');
    }

    public function bibliography(): HasMany
    {
        return $this->hasMany(Bibliography::class)->orderBy('position')->orderBy('id');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(ContentChunk::class);
    }

    public function studentProgress(): HasMany
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AiGeneration::class)->latest();
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    public function ensureSettings(): CourseSetting
    {
        return $this->settings()->firstOrCreate([]);
    }

    public function units()
    {
        return $this->modules()->where('type', \App\Enums\ModuleType::Unit->value);
    }

    public function scopePublished($query)
    {
        return $query->where('status', CourseStatus::Published->value);
    }
}
