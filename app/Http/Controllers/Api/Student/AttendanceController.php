<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Support\ComputesAttendanceSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use ComputesAttendanceSummary;

    /**
     * The authenticated student's own attendance history and summary.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->studentAttendanceSummary($request->user()));
    }
}
