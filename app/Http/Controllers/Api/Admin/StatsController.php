<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    /**
     * Return user counts by role for the admin dashboard.
     */
    public function index(): JsonResponse
    {
        $counts = User::query()
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return response()->json([
            'stats' => [
                'teachers' => (int) ($counts[User::ROLE_TEACHER] ?? 0),
                'students' => (int) ($counts[User::ROLE_STUDENT] ?? 0),
                'parents' => (int) ($counts[User::ROLE_PARENT] ?? 0),
                'admins' => (int) ($counts[User::ROLE_ADMIN] ?? 0),
            ],
        ]);
    }
}
