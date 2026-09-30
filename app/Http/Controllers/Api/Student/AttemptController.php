<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use App\Support\GradesReadingAnswers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttemptController extends Controller
{
    use GradesReadingAnswers;

    /**
     * Start a new attempt at an assessment, if this student has attempts
     * remaining and the parent lesson is currently visible to them.
     */
    public function store(Request $request, Assessment $assessment): JsonResponse
    {
        $user = $request->user();
        $lesson = $assessment->lesson()->with('subject')->firstOrFail();

        abort_unless($lesson->started_at !== null, 404);
        abort_unless($user->class_id === $lesson->subject->class_id, 403);

        $attemptsUsed = AssessmentAttempt::where('assessment_id', $assessment->id)
            ->where('student_id', $user->id)
            ->count();

        if ($assessment->max_attempts !== null && $attemptsUsed >= $assessment->max_attempts) {
            return response()->json([
                'message' => 'You have used all your attempts for this assessment.',
            ], 422);
        }

        $maxScore = $assessment->questions()->sum('points');

        $attempt = AssessmentAttempt::create([
            'assessment_id' => $assessment->id,
            'student_id' => $user->id,
            'attempt_number' => $attemptsUsed + 1,
            'status' => AssessmentAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
            'max_score' => $maxScore,
        ]);

        return response()->json(['attempt' => $attempt], 201);
    }

    /**
     * Submit an in-progress attempt's answers. Auto-gradable questions
     * (multiple_choice/short_answer/fill_in_blank/true_false) are scored
     * immediately; written_response questions (writing assessments) are
     * recorded and left for a teacher to grade — the attempt is fully
     * "graded" only once every written_response answer has a grade, see
     * Api\Teacher\GradingController.
     */
    public function submit(Request $request, AssessmentAttempt $attempt): JsonResponse
    {
        $user = $request->user();

        abort_unless($attempt->student_id === $user->id, 403);
        abort_unless(
            $attempt->status === AssessmentAttempt::STATUS_IN_PROGRESS,
            422,
            'This attempt has already been submitted.'
        );

        $validator = Validator::make($request->all(), [
            'answers' => ['present', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'exists:assessment_questions,id'],
            'answers.*.response_text' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $questions = $attempt->assessment->questions->keyBy('id');
        $totalPoints = 0;
        $hasPendingGrading = false;

        foreach ($request->input('answers', []) as $answerInput) {
            $question = $questions->get($answerInput['question_id']);

            if (! $question) {
                continue;
            }

            $responseText = $answerInput['response_text'] ?? '';

            if ($question->type === AssessmentQuestion::TYPE_WRITTEN_RESPONSE) {
                $hasPendingGrading = true;

                AssessmentAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'response_text' => $responseText,
                    'word_count' => str_word_count($responseText),
                ]);

                continue;
            }

            $isCorrect = $this->isAnswerCorrect($question->type, $responseText, $question->correct_answer);
            $pointsAwarded = $isCorrect ? $question->points : 0;
            $totalPoints += $pointsAwarded;

            AssessmentAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'response_text' => $responseText,
                'is_correct' => $isCorrect,
                'points_awarded' => $pointsAwarded,
            ]);
        }

        $attempt->update([
            'status' => $hasPendingGrading ? AssessmentAttempt::STATUS_SUBMITTED : AssessmentAttempt::STATUS_GRADED,
            'submitted_at' => now(),
            'score' => $hasPendingGrading ? null : $totalPoints,
            'graded_at' => $hasPendingGrading ? null : now(),
        ]);

        return response()->json(['attempt' => $attempt->fresh(['answers'])]);
    }
}
