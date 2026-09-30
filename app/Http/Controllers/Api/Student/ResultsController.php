<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\SubjectExamAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultsController extends Controller
{
    /**
     * The authenticated student's own graded exam results — merges both
     * graded exam sources: per-lesson exams (AssessmentAttempt) and
     * subject-wide exams (SubjectExamAttempt), same shape/merge as the
     * parent-facing childScores() endpoint, with a per-subject average.
     */
    public function index(Request $request): JsonResponse
    {
        $studentId = $request->user()->id;

        $lessonAttempts = AssessmentAttempt::where('student_id', $studentId)
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

        $subjectExamAttempts = SubjectExamAttempt::where('student_id', $studentId)
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
            'attempts' => $attempts,
            'subject_averages' => $subjectAverages,
        ]);
    }
}
