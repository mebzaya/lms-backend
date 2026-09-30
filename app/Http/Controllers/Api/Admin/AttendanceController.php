<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Support\ComputesAttendanceSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    use ComputesAttendanceSummary;

    /**
     * One class's roster with each student's attendance status for one date
     * — read-only oversight; admins don't mark attendance themselves.
     */
    public function show(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'date' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $class = SchoolClass::findOrFail($request->integer('class_id'));

        return response()->json([
            'date' => $request->input('date'),
            'students' => $this->classAttendanceForDate($class, $request->input('date')),
        ]);
    }

    /**
     * Every date this class has attendance recorded for — used to highlight
     * "taught" days on the calendar.
     */
    public function dates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => ['required', 'integer', 'exists:classes,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $class = SchoolClass::findOrFail($request->integer('class_id'));

        return response()->json(['dates' => $this->classRecordedDates($class)]);
    }

    /**
     * The class-wide attendance report — every student's present/absent
     * totals across every date recorded for that class.
     */
    public function summary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => ['required', 'integer', 'exists:classes,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $class = SchoolClass::findOrFail($request->integer('class_id'));

        return response()->json($this->classAttendanceSummary($class));
    }
}
