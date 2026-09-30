<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * The authenticated student's class, its subjects, each subject's
     * teacher(s), and its lessons (with any attached reading/writing
     * assessments and this student's own attempt summary for each).
     */
    public function index(Request $request): JsonResponse
    {
        $studentId = $request->user()->id;

        $user = $request->user()->load([
            'enrolledClass.subjects.lessons.assessments.attempts' => fn ($query) => $query->where('student_id', $studentId),
            'enrolledClass.subjects.teachers:id,name',
        ]);

        if (! $user->enrolledClass) {
            return response()->json(['class' => null, 'subjects' => []]);
        }

        return response()->json([
            'class' => [
                'id' => $user->enrolledClass->id,
                'name' => $user->enrolledClass->name,
                'level' => $user->enrolledClass->level,
            ],
            'subjects' => $user->enrolledClass->subjects->map(fn ($subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
                'teachers' => $subject->teachers->map(fn ($teacher) => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                ]),
                'lessons' => $subject->lessons
                    // Visible only once the teacher has actually started the
                    // lesson at least once (it stays visible after they end
                    // it too) — never before, even if its content has been
                    // released, so a student's dashboard only ever shows
                    // lessons their class has actually gotten to.
                    ->filter(fn ($lesson) => $lesson->started_at !== null)
                    ->values()
                    ->map(fn ($lesson) => [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'position' => $lesson->position,
                        'is_active' => $lesson->is_active,
                        'started_at' => $lesson->started_at,
                        'ended_at' => $lesson->ended_at,
                        'content_released_at' => $lesson->content_released_at,
                        'assessments' => $lesson->assessments->map(function ($assessment) {
                            $latestAttempt = $assessment->attempts->sortByDesc('attempt_number')->first();

                            return [
                                'id' => $assessment->id,
                                'title' => $assessment->title,
                                'type' => $assessment->type,
                                'mode' => $assessment->mode,
                                'max_attempts' => $assessment->max_attempts,
                                'attempts_used' => $assessment->attempts->count(),
                                'latest_status' => $latestAttempt?->status,
                                'latest_score' => $latestAttempt?->score,
                                'max_score' => $latestAttempt?->max_score,
                            ];
                        }),
                    ]),
            ]),
        ]);
    }
}
