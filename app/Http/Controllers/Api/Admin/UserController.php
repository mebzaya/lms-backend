<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Relations to eager-load whenever a user is returned, so the admin UI
     * can show class/subject/guardian context without extra requests.
     *
     * @var array<int, string>
     */
    protected const RELATIONS = [
        'parents:id,name,email',
        'children:id,name,email',
        'enrolledClass:id,name,level',
        'subjectsTaught:id,name,class_id',
        'subjectsTaught.schoolClass:id,name,level',
    ];

    /**
     * List users, optionally filtered by role.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->with(self::RELATIONS)
            ->orderBy('name');

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        return response()->json([
            'users' => $query->paginate($request->integer('per_page', 20)),
        ]);
    }

    /**
     * Create a user account. Only admins can do this — accounts, usernames
     * and passwords for teachers/students/parents are admin-managed.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'role' => ['required', Rule::in(User::roles())],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($data['role'] !== User::ROLE_STUDENT) {
            $data['class_id'] = null;
        }

        $user = User::create($data);

        return response()->json(['user' => $user->load(self::RELATIONS)], 201);
    }

    /**
     * Update a user's name, email, role, class, and optionally their password.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'string', Password::min(8)],
            'role' => ['sometimes', 'required', Rule::in(User::roles())],
            'class_id' => ['sometimes', 'nullable', 'integer', 'exists:classes,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $role = $data['role'] ?? $user->role;
        if (array_key_exists('class_id', $data) && $role !== User::ROLE_STUDENT) {
            $data['class_id'] = null;
        }

        $user->update($data);

        return response()->json(['user' => $user->fresh(self::RELATIONS)]);
    }

    /**
     * Delete a user account.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted.',
        ]);
    }

    /**
     * Replace the set of guardians linked to a student.
     */
    public function syncParents(Request $request, User $user): JsonResponse
    {
        if (! $user->hasRole(User::ROLE_STUDENT)) {
            return response()->json([
                'message' => 'Only students can have guardians assigned.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'parent_ids' => ['present', 'array'],
            'parent_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', User::ROLE_PARENT)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->parents()->sync($request->input('parent_ids'));

        return response()->json([
            'user' => $user->fresh(self::RELATIONS),
        ]);
    }

    /**
     * Replace the set of subjects a teacher teaches.
     */
    public function syncSubjects(Request $request, User $user): JsonResponse
    {
        if (! $user->hasRole(User::ROLE_TEACHER)) {
            return response()->json([
                'message' => 'Only teachers can be assigned subjects.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'subject_ids' => ['present', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->subjectsTaught()->sync($request->input('subject_ids'));

        return response()->json([
            'user' => $user->fresh(self::RELATIONS),
        ]);
    }
}
