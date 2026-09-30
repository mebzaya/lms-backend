<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    /**
     * Relations to eager-load whenever a subject is returned.
     *
     * @var array<int, string>
     */
    protected const RELATIONS = [
        'schoolClass:id,name,level',
        'teachers:id,name,email',
    ];

    /**
     * List subjects, optionally filtered by class, ordered by display
     * position, with their lesson count and assigned teachers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Subject::query()
            ->with(self::RELATIONS)
            ->withCount('lessons')
            ->orderBy('position');

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->integer('class_id'));
        }

        return response()->json(['subjects' => $query->get()]);
    }

    /**
     * Create a subject under a class. Position defaults to the end of that
     * class's subject list.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('subjects', 'name')->where(fn ($query) => $query->where('class_id', $request->input('class_id'))),
            ],
            'position' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['position'] ??= (int) Subject::where('class_id', $data['class_id'])->max('position') + 1;

        $subject = Subject::create($data);

        return response()->json(['subject' => $subject->load(self::RELATIONS)->loadCount('lessons')], 201);
    }

    /**
     * Update a subject.
     */
    public function update(Request $request, Subject $subject): JsonResponse
    {
        $classId = $request->input('class_id', $subject->class_id);

        $validator = Validator::make($request->all(), [
            'class_id' => ['sometimes', 'required', 'integer', 'exists:classes,id'],
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('subjects', 'name')->where(fn ($query) => $query->where('class_id', $classId))->ignore($subject->id),
            ],
            'position' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subject->update($validator->validated());

        return response()->json(['subject' => $subject->fresh(self::RELATIONS)->loadCount('lessons')]);
    }

    /**
     * Delete a subject (and its lessons, via cascade).
     */
    public function destroy(Subject $subject): JsonResponse
    {
        $subject->delete();

        return response()->json(['message' => 'Subject deleted.']);
    }

    /**
     * Replace the set of teachers assigned to a subject.
     */
    public function syncTeachers(Request $request, Subject $subject): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'teacher_ids' => ['present', 'array'],
            'teacher_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', User::ROLE_TEACHER)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $subject->teachers()->sync($request->input('teacher_ids'));

        return response()->json([
            'subject' => $subject->fresh(self::RELATIONS)->loadCount('lessons'),
        ]);
    }
}
