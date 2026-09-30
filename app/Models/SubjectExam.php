<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One subject's "paper" within a class-wide Exam — this is what a
 * subject's teacher(s) add sections/questions to, and what students
 * actually attempt. Its title/instructions/schedule/published state all
 * live on the parent Exam now (exposed here as read-only accessors so
 * every existing caller that read $subjectExam->title etc. keeps working
 * unchanged).
 */
#[Fillable(['exam_id', 'subject_id', 'submitted_at'])]
class SubjectExam extends Model
{
    protected $appends = ['title', 'instructions', 'starts_at', 'ends_at', 'published_at'];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SubjectExamSection::class, 'exam_id')->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(SubjectExamAttempt::class, 'exam_id');
    }

    public function getTitleAttribute(): ?string
    {
        return $this->exam?->title;
    }

    public function getInstructionsAttribute(): ?string
    {
        return $this->exam?->instructions;
    }

    public function getStartsAtAttribute()
    {
        return $this->exam?->starts_at;
    }

    public function getEndsAtAttribute()
    {
        return $this->exam?->ends_at;
    }

    public function getPublishedAtAttribute()
    {
        return $this->exam?->published_at;
    }
}
