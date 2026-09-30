<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Support\ComputesAttendanceSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    use ComputesAttendanceSummary;

    /**
     * Every class this teacher can take attendance for — every distinct
     * class among the subjects they teach.
     */
    public function classes(Request $request): JsonResponse
    {
        $classes = $request->user()->subjectsTaught()
            ->with('schoolClass:id,name,level')
            ->get()
            ->pluck('schoolClass')
            ->filter()
            ->unique('id')
            ->sortBy('level')
            ->values();

        return response()->json([
            'classes' => $classes->map(fn (SchoolClass $class) => [
                'id' => $class->id,
                'name' => $class->name,
                'student_count' => $class->students()->count(),
            ]),
        ]);
    }

    /**
     * One class's roster with each student's attendance status for one date
     * — null for a student not yet marked that day. Used both to load the
     * marking form and to review a past day.
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
        $this->authorizeTeacherOwnsClass($request, $class);

        return response()->json([
            'date' => $request->input('date'),
            'students' => $this->classAttendanceForDate($class, $request->input('date')),
        ]);
    }

    /**
     * Mark (or correct) a whole class's attendance for one date in one go.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', 'exists:users,id'],
            'records.*.status' => ['required', Rule::in([Attendance::STATUS_PRESENT, Attendance::STATUS_ABSENT])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $class = SchoolClass::findOrFail($request->integer('class_id'));
        $this->authorizeTeacherOwnsClass($request, $class);

        $studentIds = $class->students()->pluck('id');
        $date = $request->input('date');

        foreach ($request->input('records') as $record) {
            abort_unless($studentIds->contains($record['student_id']), 422, 'One of these students is not in that class.');

            Attendance::updateOrCreate(
                ['student_id' => $record['student_id'], 'date' => $date],
                ['class_id' => $class->id, 'status' => $record['status'], 'marked_by' => $request->user()->id]
            );
        }

        return response()->json([
            'date' => $date,
            'students' => $this->classAttendanceForDate($class, $date),
        ]);
    }

    /**
     * Every date this class has attendance recorded for — used to highlight
     * "taught" days on the marking calendar.
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
        $this->authorizeTeacherOwnsClass($request, $class);

        return response()->json(['dates' => $this->classRecordedDates($class)]);
    }

    /**
     * The class-wide attendance report — every student's present/absent
     * totals across every date recorded, instead of one date at a time.
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
        $this->authorizeTeacherOwnsClass($request, $class);

        return response()->json($this->classAttendanceSummary($class));
    }

    /**
     * Only a teacher assigned to at least one subject in this class may
     * mark or view its attendance.
     */
    protected function authorizeTeacherOwnsClass(Request $request, SchoolClass $class): void
    {
        abort_unless(
            $class->subjects()->whereHas('teachers', fn ($q) => $q->where('users.id', $request->user()->id))->exists(),
            403,
            'You are not assigned to teach this class.'
        );
    }
}
