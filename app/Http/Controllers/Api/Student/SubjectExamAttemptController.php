<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\SubjectExam;
use App\Models\SubjectExamAnswer;
use App\Models\SubjectExamAttempt;
use App\Models\SubjectExamQuestion;
use App\Models\SubjectExamSection;
use App\Support\GradesReadingAnswers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubjectExamAttemptController extends Controller
{
    use GradesReadingAnswers;

    /**
     * Start the one and only attempt a student gets at a subject exam —
     * unlike lesson practice, there's no retaking a formal exam. Only
     * allowed while the exam's scheduled window is open.
     */
    public function store(Request $request, SubjectExam $exam): JsonResponse
    {
        $user = $request->user();
        $subject = $exam->subject;

        abort_unless($exam->published_at !== null, 404);
        abort_unless($user->class_id === $subject->class_id, 403);

        $now = now();
        if ($exam->starts_at && $now->lt($exam->starts_at)) {
            return response()->json(['message' => 'This exam has not opened yet.'], 422);
        }
        if ($exam->ends_at && $now->gt($exam->ends_at)) {
            return response()->json(['message' => 'This exam has already closed.'], 422);
        }

        $existing = SubjectExamAttempt::where('exam_id', $exam->id)->where('student_id', $user->id)->first();
        if ($existing) {
            return response()->json(['message' => 'You have already attempted this exam.'], 422);
        }

        $sections = $exam->sections()->with('questions')->get();
        $maxScore = $sections->flatMap(fn (SubjectExamSection $section) => $section->questions)->sum('points');

        $attempt = SubjectExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $user->id,
            'status' => SubjectExamAttempt::STATUS_IN_PROGRESS,
            'started_at' => $now,
            'max_score' => $maxScore,
        ]);

        return response()->json(['attempt' => $attempt], 201);
    }

    /**
     * Submit every section's answers at once, in the same
     * {section_id, answers: [{question_id, response_text}]} shape for every
     * section type. Auto-gradable questions (multiple_choice/short_answer/
     * fill_in_blank/true_false) are scored immediately; written_response
     * questions are recorded and left for a teacher to grade. The attempt
     * is fully "graded" only once every written_response answer has a
     * grade — see SubjectExamGradingController.
     */
    public function submit(Request $request, SubjectExamAttempt $attempt): JsonResponse
    {
        $user = $request->user();

        abort_unless($attempt->student_id === $user->id, 403);
        abort_unless(
            $attempt->status === SubjectExamAttempt::STATUS_IN_PROGRESS,
            422,
            'This attempt has already been submitted.'
        );

        $validator = Validator::make($request->all(), [
            'sections' => ['present', 'array'],
            'sections.*.section_id' => ['required', 'integer', 'exists:subject_exam_sections,id'],
            'sections.*.answers' => ['nullable', 'array'],
            'sections.*.answers.*.question_id' => ['required_with:sections.*.answers', 'integer', 'exists:subject_exam_questions,id'],
            'sections.*.answers.*.response_text' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sections = $attempt->exam->sections()->with('questions')->get()->keyBy('id');
        $hasPendingGrading = false;
        $autoGradedPoints = 0;

        foreach ($request->input('sections', []) as $sectionInput) {
            $section = $sections->get($sectionInput['section_id']);
            if (! $section) {
                continue;
            }

            [$points, $pending] = $this->recordSectionAnswers($attempt, $section, $sectionInput['answers'] ?? []);
            $autoGradedPoints += $points;
            $hasPendingGrading = $hasPendingGrading || $pending;
        }

        $attempt->update([
            'status' => $hasPendingGrading ? SubjectExamAttempt::STATUS_SUBMITTED : SubjectExamAttempt::STATUS_GRADED,
            'submitted_at' => now(),
            'score' => $hasPendingGrading ? null : $autoGradedPoints,
            'graded_at' => $hasPendingGrading ? null : now(),
        ]);

        return response()->json(['attempt' => $attempt->fresh(['answers'])]);
    }

    /**
     * @return array{0: float, 1: bool} points auto-graded so far, and
     *                                   whether any question here is still
     *                                   awaiting manual grading
     */
    protected function recordSectionAnswers(SubjectExamAttempt $attempt, SubjectExamSection $section, array $answers): array
    {
        $questions = $section->questions->keyBy('id');
        $totalPoints = 0;
        $hasPendingGrading = false;

        foreach ($answers as $answerInput) {
            $question = $questions->get($answerInput['question_id'] ?? null);
            if (! $question) {
                continue;
            }

            $responseText = $answerInput['response_text'] ?? '';

            if ($question->type === SubjectExamQuestion::TYPE_WRITTEN_RESPONSE) {
                $hasPendingGrading = true;

                SubjectExamAnswer::create([
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

            SubjectExamAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'response_text' => $responseText,
                'is_correct' => $isCorrect,
                'points_awarded' => $pointsAwarded,
            ]);
        }

        return [$totalPoints, $hasPendingGrading];
    }
}
