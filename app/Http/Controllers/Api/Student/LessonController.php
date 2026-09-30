<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    /**
     * A lesson's released textbook content. Never returns draft content —
     * only visible once the teacher has explicitly released it, and only to
     * students enrolled in that lesson's class.
     */
    public function show(Request $request, Lesson $lesson): JsonResponse
    {
        $user = $request->user();
        $subject = $lesson->subject()->with('schoolClass:id,name,level')->firstOrFail();

        abort_unless($user->class_id === $subject->class_id, 403);
        abort_unless($lesson->content_released_at !== null, 404);

        return response()->json([
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'content' => $lesson->content,
                'content_released_at' => $lesson->content_released_at,
                'subject' => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'class' => $subject->schoolClass,
                ],
            ],
        ]);
    }
}
