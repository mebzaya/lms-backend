<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A class-wide exam event (e.g. "First-Term" for Class 7) scheduled by an
 * admin. It covers every subject in the class at once — each subject gets
 * its own SubjectExam ("paper") under this, which is where that subject's
 * teacher(s) add sections/questions.
 */
#[Fillable([
    'class_id', 'created_by', 'title', 'instructions', 'starts_at', 'ends_at', 'published_at',
    'results_published_at',
])]
class Exam extends Model
{
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function subjectExams(): HasMany
    {
        return $this->hasMany(SubjectExam::class);
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'results_published_at' => 'datetime',
        ];
    }
}
