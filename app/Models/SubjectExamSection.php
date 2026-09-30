<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['exam_id', 'type', 'title', 'instructions', 'passage', 'prompt', 'position'])]
class SubjectExamSection extends Model
{
    public const TYPE_READING = 'reading';

    public const TYPE_WRITING = 'writing';

    public const TYPE_FILL_IN_BLANK = 'fill_in_blank';

    public const TYPE_TRUE_FALSE = 'true_false';

    public function exam(): BelongsTo
    {
        return $this->belongsTo(SubjectExam::class, 'exam_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SubjectExamQuestion::class, 'section_id')->orderBy('position');
    }

    /**
     * Every section type restricts what question type(s) it accepts.
     * Reading allows any of the four auto-graded types mixed under one
     * passage; fill_in_blank/true_false/writing each force every question
     * in them to that one type (writing's questions are always
     * written_response — manually graded, no single correct answer).
     */
    public function forcedQuestionType(): ?string
    {
        return match ($this->type) {
            self::TYPE_FILL_IN_BLANK, self::TYPE_TRUE_FALSE => $this->type,
            self::TYPE_WRITING => SubjectExamQuestion::TYPE_WRITTEN_RESPONSE,
            default => null,
        };
    }
}
