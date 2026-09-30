<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    /**
     * Full detail for one assessment — passage/questions (never including
     * correct answers) for reading, or the prompt for writing — plus this
     * student's attempt history summary. Only visible once the parent
     * lesson has been opened by the teacher, and only to students in that
     * lesson's class.
     */
    public function show(Request $request, Assessment $assessment): JsonResponse
    {
        $user = $request->user();
        $lesson = $assessment->lesson()->with('subject')->firstOrFail();

        abort_unless($lesson->started_at !== null, 404);
        abort_unless($user->class_id === $lesson->subject->class_id, 403);

        $attempts = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('student_id', $user->id)
            ->with('answers')
            ->orderByDesc('attempt_number')
            ->get();

        $latestAttempt = $attempts->first();

        // Only reveal the correct answer once this attempt has actually been
        // submitted — never while still in_progress, or a student could
        // reload the page mid-attempt and read the answers straight off it.
        $reviewUnlocked = $latestAttempt !== null && $latestAttempt->status !== AssessmentAttempt::STATUS_IN_PROGRESS;

        $payload = [
            'id' => $assessment->id,
            'title' => $assessment->title,
            'type' => $assessment->type,
            'mode' => $assessment->mode,
            'instructions' => $assessment->instructions,
            'time_limit_minutes' => $assessment->time_limit_minutes,
            'max_attempts' => $assessment->max_attempts,
            'attempts_used' => $attempts->count(),
            'latest_attempt' => $latestAttempt ? [
                ...$latestAttempt->only(['id', 'status', 'score', 'max_score', 'submitted_at', 'graded_at', 'feedback']),
                'answers' => $latestAttempt->answers,
            ] : null,
        ];

        // Reading shows a passage; every other type shows an optional
        // shared prompt instead — all then list questions (reading allows
        // any auto-graded type mixed together; fill_in_blank/true_false/
        // writing force every question to that one type, enforced when the
        // teacher adds each question).
        if ($assessment->type === Assessment::TYPE_READING) {
            $payload['passage'] = $assessment->passage;
        } else {
            $payload['prompt'] = $assessment->prompt;
        }

        $payload['questions'] = $assessment->questions->map(fn ($question) => [
            'id' => $question->id,
            'type' => $question->type,
            'answer_language' => $question->answer_language,
            'prompt' => $question->prompt,
            'options' => $question->options,
            'points' => $question->points,
            'min_word_count' => $question->min_word_count,
            'max_word_count' => $question->max_word_count,
            // Fill-in-the-blank is displayed with a word bank (like a
            // textbook cloze exercise), so the keyword itself is shown —
            // never the correct_answer for multiple_choice/short_answer.
            'answer_hint' => $question->type === 'fill_in_blank'
                ? explode('|', $question->correct_answer)[0]
                : null,
            'correct_answer' => $reviewUnlocked && $question->type !== 'written_response'
                ? $this->displayCorrectAnswer($question)
                : null,
        ]);

        return response()->json(['assessment' => $payload]);
    }

    /**
     * A readable form of a question's stored correct_answer for review:
     * multiple_choice/true_false stay as the option key (the frontend
     * resolves it to a label via the question's own options); fill_in_blank
     * collapses each blank's "east|East" variant list down to its first
     * (most canonical) spelling and joins the blanks with ", ".
     */
    protected function displayCorrectAnswer($question): string
    {
        if ($question->type === 'fill_in_blank') {
            return collect(explode(';', $question->correct_answer))
                ->map(fn ($slot) => explode('|', $slot)[0])
                ->implode(', ');
        }

        return $question->correct_answer;
    }
}
