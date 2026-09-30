<?php

namespace App\Http\Controllers\Api\Student;

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
     * Every exam for this student's class whose results have been
     * published, each with this student's own rank/score — this is what
     * the student's Results page lists, so they know which exams to open
     * for the full class ranking.
     */
    public function published(Request $request): JsonResponse
    {
        $user = $request->user();

        $exams = Exam::where('class_id', $user->class_id)
            ->whereNotNull('results_published_at')
            ->orderByDesc('results_published_at')
            ->get();

        $summaries = $exams->map(function (Exam $exam) use ($user) {
            $ranked = $this->rank($this->classScores($exam));
            $mine = $ranked->first(fn ($s) => $s['student']->id === $user->id);

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
     * The whole class's ranking for one exam — every student's total score
     * across every subject that actually has content, sorted and ranked
     * (ties share a rank, competition-style: 1, 1, 3). Only visible once an
     * admin has published this exam's results.
     */
    public function index(Request $request, Exam $exam): JsonResponse
    {
        $user = $request->user();
        abort_unless($exam->class_id === $user->class_id, 403);
        abort_unless($exam->results_published_at !== null, 404);

        $scores = $this->classScores($exam);

        $ranked = $this->rank($scores);

        return response()->json([
            'exam' => ['id' => $exam->id, 'title' => $exam->title],
            'max_total' => $this->maxTotal($exam),
            'students' => $ranked->map(fn ($s) => [
                'id' => $s['student']->id,
                'name' => $s['student']->name,
                'total_score' => $s['total'],
                'rank' => $s['rank'],
                'is_you' => $s['student']->id === $user->id,
            ])->values(),
        ]);
    }

    /**
     * One classmate's per-subject breakdown for this exam — same gating as
     * index(), plus the requested student must actually be in this exam's
     * class.
     */
    public function show(Request $request, Exam $exam, User $student): JsonResponse
    {
        $user = $request->user();
        abort_unless($exam->class_id === $user->class_id, 403);
        abort_unless($exam->results_published_at !== null, 404);
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

}
