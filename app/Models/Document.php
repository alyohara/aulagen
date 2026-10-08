<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;
    protected $fillable = [
        'course_id',
        'module_id',
        'uploaded_by',
        'original_name',
        'type',
        'mime_type',
        'size_bytes',
        'disk',
        'path',
        'source_url',
        'status',
        'error_message',
        'extracted_text',
        'page_count',
        'chunk_count',
        'meta',
        'processing_started_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'meta' => 'array',
            'processing_started_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(ContentChunk::class);
    }

    public function isInline(): bool
    {
        return $this->type === DocumentType::Link
            || $this->type === DocumentType::Video
            || $this->type === DocumentType::Text;
    }

    public function humanSize(): string
    {
        $bytes = (float) $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i > 0 ? 1 : 0).' '.$units[$i];
    }
}
