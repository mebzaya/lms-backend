<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LessonController extends Controller
{
    /**
     * List lessons, optionally filtered by subject, ordered by position.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Lesson::query()
            ->with('subject:id,name,class_id')
            ->orderBy('position');

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->integer('subject_id'));
        }

        return response()->json(['lessons' => $query->get()]);
    }

    /**
     * Create a lesson under a subject. Position defaults to the next slot.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['position'] ??= (int) Lesson::where('subject_id', $data['subject_id'])->max('position') + 1;

        $lesson = Lesson::create($data);

        return response()->json(['lesson' => $lesson->load('subject:id,name,class_id')], 201);
    }

    /**
     * Update a lesson.
     */
    public function update(Request $request, Lesson $lesson): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => ['sometimes', 'required', 'integer', 'exists:subjects,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $lesson->update($validator->validated());

        return response()->json(['lesson' => $lesson->fresh(['subject:id,name,class_id'])]);
    }

    /**
     * Delete a lesson.
     */
    public function destroy(Lesson $lesson): JsonResponse
    {
        $lesson->delete();

        return response()->json(['message' => 'Lesson deleted.']);
    }
}
