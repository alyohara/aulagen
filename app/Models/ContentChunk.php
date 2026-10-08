<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentChunk extends Model
{
    protected $table = 'content_chunks';

    protected $fillable = [
        'course_id',
        'document_id',
        'lesson_id',
        'module_id',
        'source_type',
        'source_label',
        'section',
        'chunk_index',
        'page_from',
        'page_to',
        'content',
        'embedding',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function referenceLabel(): string
    {
        $label = $this->source_label;

        if ($this->page_from) {
            $label .= ', página '.$this->page_from.($this->page_to && $this->page_to !== $this->page_from ? '-'.$this->page_to : '');
        } elseif ($this->section) {
            $label .= ', sección "'.$this->section.'"';
        }

        return $label;
    }
}
