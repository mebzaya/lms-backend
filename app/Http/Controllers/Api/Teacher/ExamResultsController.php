<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\SubjectExamAttempt;
use App\Models\User;
use App\Support\ComputesExamRankings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamResultsController extends Controller
{
    use ComputesExamRankings;

    /**
     * The whole class's ranking for one exam — same as the admin's view,
     * available to any teacher who teaches at least one subject in that
     * exam's class, so they can see how the class did once it's ended,
     * without needing to go through an admin.
     */
    public function index(Request $request, Exam $exam): JsonResponse
    {
        $this->authorizeTeacherOwnsExamClass($request, $exam);

        $ranked = $this->rank($this->classScores($exam));

        return response()->json([
            'exam' => ['id' => $exam->id, 'title' => $exam->title],
            'max_total' => $this->maxTotal($exam),
            'results_published_at' => $exam->results_published_at,
            'students' => $ranked->map(fn ($s) => [
                'id' => $s['student']->id,
                'name' => $s['student']->name,
                'total_score' => $s['total'],
                'rank' => $s['rank'],
            ])->values(),
        ]);
    }

    /**
     * One student's per-subject breakdown for this exam.
     */
    public function show(Request $request, Exam $exam, User $student): JsonResponse
    {
        $this->authorizeTeacherOwnsExamClass($request, $exam);

        abort_unless($student->class_id === $exam->class_id, 404);

        $subjectExams = $exam->subjectExams()
            ->whereHas('sections')
            ->with('subject:id,name')
            ->get();

        $attempts = SubjectExamAttempt::where('student_id', $student->id)
            ->whereIn('exam_id', $subjectExams->pluck('id'))
            ->get()
            ->keyBy('exam_id');

        $subjects = $subjectExams->map(function ($subjectExam) use ($attempts) {
            $attempt = $attempts->get($subjectExam->id);
            $maxScore = $this->subjectMaxPoints($subjectExam);

            return [
                'subject' => $subjectExam->subject->name,
                'score' => $attempt && $attempt->status === SubjectExamAttempt::STATUS_GRADED
                    ? (float) $attempt->score
                    : null,
                'max_score' => $maxScore,
                'graded_at' => $attempt?->graded_at,
            ];
        })->sortBy('subject')->values();

        return response()->json([
            'exam' => ['id' => $exam->id, 'title' => $exam->title],
            'student' => ['id' => $student->id, 'name' => $student->name],
            'subjects' => $subjects,
        ]);
    }

    /**
     * Only a teacher assigned to at least one subject in this exam's class
     * may view its results.
     */
    protected function authorizeTeacherOwnsExamClass(Request $request, Exam $exam): void
    {
        abort_unless(
            $exam->subjectExams()->whereHas('subject.teachers', fn ($q) => $q->where('users.id', $request->user()->id))->exists(),
            403,
            'You are not assigned to teach any subject in this class.'
        );
    }
}
