<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * The authenticated teacher's subjects, each with its class and lessons
     * (including each lesson's live-session state).
     */
    public function index(Request $request): JsonResponse
    {
        $subjects = $request->user()->subjectsTaught()
            ->with(['schoolClass:id,name,level', 'lessons'])
            ->get();

        return response()->json([
            'subjects' => $subjects->map(fn ($subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
                'class' => $subject->schoolClass ? [
                    'id' => $subject->schoolClass->id,
                    'name' => $subject->schoolClass->name,
                ] : null,
                'lessons' => $subject->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'position' => $lesson->position,
                    'is_active' => $lesson->is_active,
                    'started_at' => $lesson->started_at,
                    'ended_at' => $lesson->ended_at,
                    // Just the status, not the markdown body itself — this is
                    // a lightweight summary list; the editor fetches the full
                    // lesson (including content) separately.
                    'has_content' => filled($lesson->content),
                    'content_released_at' => $lesson->content_released_at,
                ]),
            ]),
        ]);
    }
}
