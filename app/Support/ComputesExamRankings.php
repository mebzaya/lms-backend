<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\SubjectExamAttempt;
use App\Models\User;

/**
 * Class-wide exam ranking/scoring, shared by the student-facing results view
 * (its own class, only once published) and the admin-facing one (any exam,
 * for oversight — see Api\Student\ExamResultsController and
 * Api\Admin\ExamResultsController).
 */
trait ComputesExamRankings
{
    /**
     * @return \Illuminate\Support\Collection<int, array{student: User, total: float}>
     */
    protected function classScores(Exam $exam)
    {
        $subjectExams = $exam->subjectExams()->whereHas('sections')->get();

        $attemptsByStudent = SubjectExamAttempt::whereIn('exam_id', $subjectExams->pluck('id'))
            ->where('status', SubjectExamAttempt::STATUS_GRADED)
            ->get()
            ->groupBy('student_id');

        return $exam->schoolClass->students->map(fn (User $student) => [
            'student' => $student,
            'total' => (float) ($attemptsByStudent->get($student->id, collect())->sum('score')),
        ]);
    }

    protected function maxTotal(Exam $exam): float
    {
        return (float) $exam->subjectExams()->whereHas('sections')->get()
            ->sum(fn ($subjectExam) => $this->subjectMaxPoints($subjectExam));
    }

    protected function subjectMaxPoints($subjectExam): float
    {
        return (float) $subjectExam->sections()->with('questions')->get()
            ->flatMap(fn ($section) => $section->questions)
            ->sum('points');
    }

    /**
     * Competition ranking: equal totals share a rank, and the next distinct
     * total's rank is the count of students strictly above it, plus one
     * (e.g. 90, 90, 80 -> ranks 1, 1, 3).
     */
    protected function rank($scores)
    {
        $sorted = $scores->sortByDesc('total')->values();

        return $sorted->map(function ($entry, $index) use ($sorted) {
            $rank = $sorted->take($index)->filter(fn ($other) => $other['total'] > $entry['total'])->count() + 1;

            return [...$entry, 'rank' => $rank];
        });
    }
}
