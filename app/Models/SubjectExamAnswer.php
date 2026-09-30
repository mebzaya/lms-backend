<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'attempt_id', 'question_id', 'response_text', 'is_correct', 'points_awarded',
    'feedback', 'word_count', 'graded_by', 'graded_at',
])]
class SubjectExamAnswer extends Model
{
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(SubjectExamAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SubjectExamQuestion::class, 'question_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'points_awarded' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }
}
