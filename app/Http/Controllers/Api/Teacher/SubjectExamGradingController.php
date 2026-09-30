<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\SubjectExamAnswer;
use App\Models\SubjectExamAttempt;
use App\Models\SubjectExamQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubjectExamGradingController extends Controller
{
    /**
     * List every written-response answer (graded and still-pending) across
     * every subject this teacher teaches, oldest first, so a teacher can see
     * both what still needs grading and what they've already graded.
     */
    public function index(Request $request): JsonResponse
    {
        $answers = SubjectExamAnswer::query()
            ->whereHas('question', fn ($q) => $q->where('type', SubjectExamQuestion::TYPE_WRITTEN_RESPONSE))
            ->whereHas('attempt', fn ($query) => $query->whereIn('status', [
                SubjectExamAttempt::STATUS_SUBMITTED, SubjectExamAttempt::STATUS_GRADED,
            ]))
            ->whereHas('question.section.exam.subject.teachers', fn ($q) => $q->where('users.id', $request->user()->id))
            ->with([
                'attempt.student:id,name',
                'question:id,section_id,prompt,points',
                'question.section:id,exam_id,title',
                'question.section.exam:id,exam_id,subject_id',
                'question.section.exam.exam:id,title',
                'question.section.exam.subject:id,name,class_id',
                'question.section.exam.subject.schoolClass:id,name',
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
    public function grade(Request $request, SubjectExamAnswer $answer): JsonResponse
    {
        $exam = $answer->question->section->exam;

        abort_unless(
            $exam->subject->teachers->contains('id', $request->user()->id),
            403,
            'You are not assigned to teach this subject.'
        );

        abort_unless(
            $answer->question->type === SubjectExamQuestion::TYPE_WRITTEN_RESPONSE,
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
    protected function finalizeAttemptIfFullyGraded(SubjectExamAttempt $attempt): void
    {
        $stillUngraded = $attempt->answers()->whereNull('points_awarded')->exists();

        if ($stillUngraded) {
            return;
        }

        $attempt->update([
            'status' => SubjectExamAttempt::STATUS_GRADED,
            'score' => (float) $attempt->answers()->sum('points_awarded'),
            'graded_at' => now(),
        ]);
    }
}
