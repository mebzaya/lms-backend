<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\SubjectExamAttempt;
use App\Models\User;
use App\Support\ComputesExamRankings;
use Illuminate\Http\JsonResponse;

class ExamResultsController extends Controller
{
    use ComputesExamRankings;

    /**
     * The whole class's ranking for one exam — same ranking students see
     * once published, but visible to an admin regardless of publish state,
     * so they can review it before deciding to publish (or re-check it
     * after).
     */
    public function index(Exam $exam): JsonResponse
    {
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
    public function show(Exam $exam, User $student): JsonResponse
    {
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
