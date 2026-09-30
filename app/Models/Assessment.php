<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'lesson_id', 'teacher_id', 'type', 'mode', 'title', 'instructions', 'passage',
    'prompt', 'time_limit_minutes', 'max_attempts', 'position',
])]
class Assessment extends Model
{
    public const TYPE_READING = 'reading';

    public const TYPE_WRITING = 'writing';

    public const TYPE_FILL_IN_BLANK = 'fill_in_blank';

    public const TYPE_TRUE_FALSE = 'true_false';

    public const MODE_PRACTICE = 'practice';

    public const MODE_EXAM = 'exam';

    public const ANSWER_LANGUAGE_ENGLISH = 'english';

    public const ANSWER_LANGUAGE_NEPALI = 'nepali';

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(AssessmentQuestion::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    /**
     * Every assessment type restricts what question type(s) it accepts.
     * Reading allows any of the four auto-graded types mixed under one
     * passage; fill_in_blank/true_false/writing each force every question
     * in them to that one type (writing's questions are always
     * written_response — manually graded, no single correct answer).
     */
    public function forcedQuestionType(): ?string
    {
        return match ($this->type) {
            self::TYPE_FILL_IN_BLANK, self::TYPE_TRUE_FALSE => $this->type,
            self::TYPE_WRITING => AssessmentQuestion::TYPE_WRITTEN_RESPONSE,
            default => null,
        };
    }
}
