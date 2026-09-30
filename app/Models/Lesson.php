<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subject_id', 'title', 'description', 'position'])]
class Lesson extends Model
{
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class)->orderBy('position');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'content_released_at' => 'datetime',
        ];
    }
}
