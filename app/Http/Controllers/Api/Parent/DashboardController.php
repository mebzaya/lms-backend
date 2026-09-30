<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Exam;
use App\Models\SubjectExamAttempt;
use App\Models\User;
use App\Support\ComputesAttendanceSummary;
use App\Support\ComputesExamRankings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ComputesExamRankings;
    use ComputesAttendanceSummary;

    /**
     * The authenticated parent's linked children, each with their class, a
     * practice-activity count, and — the thing a parent actually opens this
     * page to check — whether any exam results have been published for
     * their class yet, so that's visible at a glance instead of only being
     * discoverable by clicking into a child that turns out to have nothing
     * published (exam scores themselves live behind childScores()).
     */
    public function index(Request $request): JsonResponse
    {
        $children = $request->user()->children()->with('enrolledClass:id,name,level')->get();

        return response()->json([
            'children' => $children->map(fn ($child) => [
                'id' => $child->id,
                'name' => $child->name,
                'class' => $child->enrolledClass ? [
                    'id' => $child->enrolledClass->id,
                    'name' => $child->enrolledClass->name,
                ] : null,
                'published_results_count' => $child->class_id
                    ? Exam::where('class_id', $child->class_id)->whereNotNull('results_published_at')->count()
                    : 0,
                'practice_attempts' => AssessmentAttempt::where('student_id', $child->id)
                    ->whereHas('assessment', fn ($query) => $query->where('mode', Assessment::MODE_PRACTICE))
                    ->count(),
            ]),
        ]);
    }

    /**
     * A linked child's graded exam scores (the official record — practice
     * scores are intentionally excluded here), with a per-subject average.
     * Merges both graded exam sources: per-lesson exams (AssessmentAttempt)
     * and subject-wide exams (SubjectExamAttempt) — a parent shouldn't need
     * to know these are two different underlying systems.
     */
    public function childScores(Request $request, User $child): JsonResponse
    {
        abort_unless($request->user()->children->contains('id', $child->id), 403);

        $lessonAttempts = AssessmentAttempt::where('student_id', $child->id)
            ->where('status', AssessmentAttempt::STATUS_GRADED)
            ->whereHas('assessment', fn ($query) => $query->where('mode', Assessment::MODE_EXAM))
            ->with([
                'assessment:id,title,type,lesson_id',
                'assessment.lesson:id,title,subject_id',
                'assessment.lesson.subject:id,name,class_id',
            ])
            ->get()
            ->map(fn ($attempt) => [
                'id' => 'assessment-'.$attempt->id,
                'subject' => $attempt->assessment->lesson->subject->name,
                'lesson' => $attempt->assessment->lesson->title,
                'title' => $attempt->assessment->title,
                'type' => $attempt->assessment->type,
                'score' => (float) $attempt->score,
                'max_score' => (float) $attempt->max_score,
                'graded_at' => $attempt->graded_at,
            ]);

        $subjectExamAttempts = SubjectExamAttempt::where('student_id', $child->id)
            ->where('status', SubjectExamAttempt::STATUS_GRADED)
            ->with(['exam:id,exam_id,subject_id', 'exam.exam:id,title', 'exam.subject:id,name,class_id'])
            ->get()
            ->map(fn ($attempt) => [
                'id' => 'subject-exam-'.$attempt->id,
                'subject' => $attempt->exam->subject->name,
                'lesson' => null,
                'title' => $attempt->exam->title,
                'type' => 'subject_exam',
                'score' => (float) $attempt->score,
                'max_score' => (float) $attempt->max_score,
                'graded_at' => $attempt->graded_at,
            ]);

        $attempts = $lessonAttempts->concat($subjectExamAttempts)
            ->sortByDesc('graded_at')
            ->values();

        $subjectAverages = $attempts
            ->groupBy('subject')
            ->map(fn ($group) => round(
                $group->avg(fn ($attempt) => $attempt['max_score'] > 0 ? ($attempt['score'] / $attempt['max_score']) * 100 : 0),
                1
            ));

        return response()->json([
            'child' => ['id' => $child->id, 'name' => $child->name],
            'attempts' => $attempts,
            'subject_averages' => $subjectAverages,
        ]);
    }

    /**
     * Every exam for this child's class whose results have been published,
     * each with the child's own rank/score — same shape (and same report-card
     * presentation) as the student's own results page, so a parent sees
     * exactly what their child sees.
     */
    public function childClassResults(Request $request, User $child): JsonResponse
    {
        abort_unless($request->user()->children->contains('id', $child->id), 403);

        $exams = Exam::where('class_id', $child->class_id)
            ->whereNotNull('results_published_at')
            ->orderByDesc('results_published_at')
            ->get();

        $summaries = $exams->map(function (Exam $exam) use ($child) {
            $ranked = $this->rank($this->classScores($exam));
            $mine = $ranked->first(fn ($s) => $s['student']->id === $child->id);

            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'results_published_at' => $exam->results_published_at,
                'your_rank' => $mine['rank'] ?? null,
                'your_score' => $mine['total'] ?? null,
                'max_total' => $this->maxTotal($exam),
                'student_count' => $ranked->count(),
            ];
        });

        return response()->json(['exams' => $summaries]);
    }

    /**
     * The child's per-subject breakdown for one published exam — the same
     * report card a student can view of any classmate, scoped here to a
     * parent's own linked child instead.
     */
    public function childExamResult(Request $request, User $child, Exam $exam): JsonResponse
    {
        abort_unless($request->user()->children->contains('id', $child->id), 403);
        abort_unless($exam->class_id === $child->class_id, 404);
        abort_unless($exam->results_published_at !== null, 404);

        $subjectExams = $exam->subjectExams()
            ->whereHas('sections')
            ->with('subject:id,name')
            ->get();

        $attempts = SubjectExamAttempt::where('student_id', $child->id)
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
            'student' => ['id' => $child->id, 'name' => $child->name],
            'subjects' => $subjects,
        ]);
    }

    /**
     * A linked child's attendance history and summary — same shape as the
     * student's own view.
     */
    public function childAttendance(Request $request, User $child): JsonResponse
    {
        abort_unless($request->user()->children->contains('id', $child->id), 403);

        return response()->json($this->studentAttendanceSummary($child));
    }
}
