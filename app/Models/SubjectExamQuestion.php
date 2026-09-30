<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'section_id', 'type', 'answer_language', 'prompt', 'options', 'correct_answer', 'points',
    'min_word_count', 'max_word_count', 'position',
])]
class SubjectExamQuestion extends Model
{
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_SHORT_ANSWER = 'short_answer';

    public const TYPE_FILL_IN_BLANK = 'fill_in_blank';

    public const TYPE_TRUE_FALSE = 'true_false';

    /**
     * A manually-graded written answer of any length — the question type a
     * writing section's questions are forced to. Unlike the other types it
     * has no correct_answer/options; a teacher grades it by hand from the
     * exam grading queue.
     */
    public const TYPE_WRITTEN_RESPONSE = 'written_response';

    public const ANSWER_LANGUAGE_ENGLISH = 'english';

    public const ANSWER_LANGUAGE_NEPALI = 'nepali';

    public function section(): BelongsTo
    {
        return $this->belongsTo(SubjectExamSection::class, 'section_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }
}
