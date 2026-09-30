<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    /**
     * List all classes, ordered by their display position, with their subject,
     * student, and (distinct) teacher counts — all read-only, inferred figures
     * to help an admin see class load at a glance.
     */
    public function index(): JsonResponse
    {
        $classes = SchoolClass::withCount(['subjects', 'students'])
            ->orderBy('position')
            ->get()
            ->each(fn (SchoolClass $class) => $class->teachers_count = $this->teachersCount($class));

        return response()->json(['classes' => $classes]);
    }

    /**
     * How many distinct teachers teach at least one subject in this class.
     */
    private function teachersCount(SchoolClass $class): int
    {
        return User::where('role', User::ROLE_TEACHER)
            ->whereHas('subjectsTaught', fn ($q) => $q->where('subjects.class_id', $class->id))
            ->count();
    }

    /**
     * Create a class. Position defaults to the end of the list.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer', 'min:1', 'max:255', 'unique:classes,level'],
            'position' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['position'] ??= (int) SchoolClass::max('position') + 1;

        $class = SchoolClass::create($data);

        return response()->json(['class' => $class->loadCount('subjects')], 201);
    }

    /**
     * Update a class.
     */
    public function update(Request $request, SchoolClass $class): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'level' => ['sometimes', 'required', 'integer', 'min:1', 'max:255', Rule::unique('classes', 'level')->ignore($class->id)],
            'position' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $class->update($validator->validated());

        return response()->json(['class' => $class->fresh()->loadCount('subjects')]);
    }

    /**
     * Delete a class (and its subjects and lessons, via cascade).
     */
    public function destroy(SchoolClass $class): JsonResponse
    {
        $class->delete();

        return response()->json(['message' => 'Class deleted.']);
    }
}
