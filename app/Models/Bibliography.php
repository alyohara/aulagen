<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bibliography extends Model
{
    protected $table = 'bibliography';

    protected $fillable = [
        'course_id',
        'kind',
        'authors',
        'title',
        'edition',
        'publisher',
        'year',
        'url',
        'note',
        'position',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function formatted(): string
    {
        $parts = array_filter([
            $this->authors,
            $this->year ? '('.$this->year.')' : null,
            $this->title,
            $this->edition,
            $this->publisher,
        ]);

        return implode('. ', $parts).'.';
    }
}
