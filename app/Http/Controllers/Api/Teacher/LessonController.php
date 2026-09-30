<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LessonController extends Controller
{
    /**
     * Add a new lesson to a subject this teacher teaches — appended to the
     * end of that subject's lesson list. Unlike an admin, a teacher can only
     * create within a subject they're assigned to, and can't set a custom
     * position (always appended).
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subject = Subject::findOrFail($request->integer('subject_id'));

        abort_unless(
            $subject->teachers->contains('id', $request->user()->id),
            403,
            'You are not assigned to teach this subject.'
        );

        $lesson = Lesson::create([
            'subject_id' => $subject->id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'position' => (int) Lesson::where('subject_id', $subject->id)->max('position') + 1,
        ]);

        return response()->json(['lesson' => $lesson->load('subject:id,name,class_id')], 201);
    }

    /**
     * A single lesson's full detail, for the teacher's content editor to
     * load — the dashboard payload only carries a summary.
     */
    public function show(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorizeTeacherOwnsLesson($request, $lesson);

        return response()->json(['lesson' => $lesson->load('subject:id,name,class_id')]);
    }

    /**
     * Rename a lesson / edit its description. Teachers may not move a lesson
     * between subjects or reorder it — that stays an admin-only structural
     * change, so only title/description are accepted here.
     */
    public function update(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorizeTeacherOwnsLesson($request, $lesson);

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $lesson->update($validator->validated());

        return response()->json(['lesson' => $lesson->fresh('subject:id,name,class_id')]);
    }

    /**
     * Start a lesson, making it visible to students. Independent of any
     * other lesson this teacher has live — a teacher can teach several
     * classes at once (e.g. one left running from an earlier period), and
     * starting one lesson must never end another.
     */
    public function start(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorizeTeacherOwnsLesson($request, $lesson);

        $lesson->is_active = true;
        $lesson->started_at = now();
        $lesson->ended_at = null;
        $lesson->save();

        return response()->json(['lesson' => $lesson->fresh()]);
    }

    /**
     * End a lesson.
     */
    public function end(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorizeTeacherOwnsLesson($request, $lesson);

        $lesson->is_active = false;
        $lesson->ended_at = now();
        $lesson->save();

        return response()->json(['lesson' => $lesson->fresh()]);
    }

    /**
     * Save the lesson's Markdown textbook content as a draft. Saving never
     * releases it — a teacher may keep editing after release too, and those
     * edits stay invisible to students until released again.
     */
    public function updateContent(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorizeTeacherOwnsLesson($request, $lesson);

        $validator = Validator::make($request->all(), [
            'content' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $lesson->content = $validator->validated()['content'] ?? null;
        $lesson->save();

        return response()->json(['lesson' => $lesson->fresh()]);
    }

    /**
     * Release the lesson's current content to students.
     */
    public function releaseContent(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorizeTeacherOwnsLesson($request, $lesson);

        abort_if(blank($lesson->content), 422, 'Write some content before releasing it.');

        $lesson->content_released_at = now();
        $lesson->save();

        return response()->json(['lesson' => $lesson->fresh()]);
    }

    /**
     * Pull released content back to draft — e.g. the teacher spots a mistake
     * and wants it off students' screens while they fix it.
     */
    public function unreleaseContent(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorizeTeacherOwnsLesson($request, $lesson);

        $lesson->content_released_at = null;
        $lesson->save();

        return response()->json(['lesson' => $lesson->fresh()]);
    }

    /**
     * Only a teacher assigned to a lesson's subject may manage it.
     */
    protected function authorizeTeacherOwnsLesson(Request $request, Lesson $lesson): void
    {
        abort_unless(
            $lesson->subject->teachers->contains('id', $request->user()->id),
            403,
            'You are not assigned to teach this subject.'
        );
    }
}
