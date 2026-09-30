<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GradingController extends Controller
{
    /**
     * List every written-response answer (graded and still-pending) across
     * every lesson this teacher teaches, oldest first, so a teacher can see
     * both what still needs grading and what they've already graded.
     */
    public function index(Request $request): JsonResponse
    {
        $answers = AssessmentAnswer::query()
            ->whereHas('question', fn ($q) => $q->where('type', AssessmentQuestion::TYPE_WRITTEN_RESPONSE))
            ->whereHas('attempt', fn ($query) => $query->whereIn('status', [
                AssessmentAttempt::STATUS_SUBMITTED, AssessmentAttempt::STATUS_GRADED,
            ]))
            ->whereHas('question.assessment.lesson.subject.teachers', fn ($q) => $q->where('users.id', $request->user()->id))
            ->with([
                'attempt.student:id,name',
                'question:id,assessment_id,prompt,points',
                'question.assessment:id,lesson_id,title,mode',
                'question.assessment.lesson:id,title,subject_id',
                'question.assessment.lesson.subject:id,name,class_id',
                'question.assessment.lesson.subject.schoolClass:id,name',
            ])
            ->orderBy('created_at')
            ->get();

        return response()->json(['answers' => $answers]);
    }

    /**
     * Grade one written-response answer. Once every written_response
     * question on this attempt has been graded, the whole attempt's status
     * flips to graded and its score is finalized (auto-graded points + all
     * manually-graded points, summed).
     */
    public function grade(Request $request, AssessmentAnswer $answer): JsonResponse
    {
        $assessment = $answer->question->assessment;

        abort_unless(
            $assessment->lesson->subject->teachers->contains('id', $request->user()->id),
            403,
            'You are not assigned to teach this subject.'
        );

        abort_unless(
            $answer->question->type === AssessmentQuestion::TYPE_WRITTEN_RESPONSE,
            422,
            'Only written-response answers are graded this way.'
        );

        $validator = Validator::make($request->all(), [
            'points_awarded' => ['required', 'numeric', 'min:0', 'max:'.$answer->question->points],
            'feedback' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $answer->update([
            'points_awarded' => $request->input('points_awarded'),
            'feedback' => $request->input('feedback'),
            'graded_by' => $request->user()->id,
            'graded_at' => now(),
        ]);

        $this->finalizeAttemptIfFullyGraded($answer->attempt);

        return response()->json(['answer' => $answer->fresh()]);
    }

    /**
     * An attempt is fully graded once every one of its answers has a
     * points_awarded value. Auto-gradable questions always get one at
     * submit time, so a null here only ever means a written_response
     * question still awaiting a teacher's grade.
     */
    protected function finalizeAttemptIfFullyGraded(AssessmentAttempt $attempt): void
    {
        $stillUngraded = $attempt->answers()->whereNull('points_awarded')->exists();

        if ($stillUngraded) {
            return;
        }

        $attempt->update([
            'status' => AssessmentAttempt::STATUS_GRADED,
            'score' => (float) $attempt->answers()->sum('points_awarded'),
            'graded_at' => now(),
        ]);
    }
}
