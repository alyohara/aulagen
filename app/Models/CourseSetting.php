<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseSetting extends Model
{
    protected $fillable = [
        'course_id',
        'ai_assistant_enabled',
        'allow_external_knowledge',
        'show_sources',
        'show_progress',
        'allow_downloads',
        'enable_search',
        'auto_generate_resources',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'ai_assistant_enabled' => 'boolean',
            'allow_external_knowledge' => 'boolean',
            'show_sources' => 'boolean',
            'show_progress' => 'boolean',
            'allow_downloads' => 'boolean',
            'enable_search' => 'boolean',
            'auto_generate_resources' => 'boolean',
            'features' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
