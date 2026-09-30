<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\User;

/**
 * Attendance reporting shared by every role that can view it — a student's
 * own record, a parent's view of a child, and a teacher/admin's view of a
 * whole class (see Api\Student\AttendanceController,
 * Api\Parent\DashboardController, Api\Teacher\AttendanceController and
 * Api\Admin\AttendanceController).
 */
trait ComputesAttendanceSummary
{
    /**
     * One student's full attendance history plus a present/absent/percentage
     * summary.
     */
    protected function studentAttendanceSummary(User $student): array
    {
        $records = Attendance::where('student_id', $student->id)
            ->orderByDesc('date')
            ->get();

        $presentCount = $records->where('status', Attendance::STATUS_PRESENT)->count();
        $totalDays = $records->count();

        return [
            'records' => $records->map(fn (Attendance $a) => [
                'date' => $a->date->toDateString(),
                'status' => $a->status,
            ])->values(),
            'present_count' => $presentCount,
            'absent_count' => $totalDays - $presentCount,
            'total_days' => $totalDays,
            'percentage' => $totalDays > 0 ? round($presentCount / $totalDays * 100, 1) : null,
        ];
    }

    /**
     * Every distinct date this class has any attendance recorded for — used
     * to highlight "taught" days on a calendar, so a teacher/admin can see
     * at a glance which days already have a record instead of guessing and
     * clicking through one date at a time.
     */
    protected function classRecordedDates(SchoolClass $class): array
    {
        return Attendance::where('class_id', $class->id)
            ->selectRaw('DISTINCT date')
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($date) => $date->toDateString())
            ->values()
            ->all();
    }

    /**
     * Every student in a class with their attendance status for one date —
     * null for a student with no record yet that day, so the UI can tell
     * "marked absent" apart from "not marked at all".
     */
    protected function classAttendanceForDate(SchoolClass $class, string $date): array
    {
        $students = $class->students()->orderBy('name')->get(['id', 'name']);

        $recordsByStudent = Attendance::where('class_id', $class->id)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('student_id');

        return $students->map(fn (User $student) => [
            'student_id' => $student->id,
            'name' => $student->name,
            'status' => $recordsByStudent->get($student->id)?->status,
        ])->values()->all();
    }

    /**
     * Every student in a class with their attendance totals across every
     * date that's ever been recorded for that class — the class-wide report
     * a teacher/admin sees instead of drilling into one date at a time.
     */
    protected function classAttendanceSummary(SchoolClass $class): array
    {
        $students = $class->students()->orderBy('name')->get(['id', 'name']);

        $recordsByStudent = Attendance::where('class_id', $class->id)->get()->groupBy('student_id');

        $totalDaysRecorded = Attendance::where('class_id', $class->id)->distinct('date')->count('date');

        return [
            'total_days_recorded' => $totalDaysRecorded,
            'students' => $students->map(function (User $student) use ($recordsByStudent) {
                $records = $recordsByStudent->get($student->id, collect());
                $presentCount = $records->where('status', Attendance::STATUS_PRESENT)->count();
                $totalDays = $records->count();

                return [
                    'student_id' => $student->id,
                    'name' => $student->name,
                    'present_count' => $presentCount,
                    'absent_count' => $totalDays - $presentCount,
                    'total_days' => $totalDays,
                    'percentage' => $totalDays > 0 ? round($presentCount / $totalDays * 100, 1) : null,
                ];
            })->values(),
        ];
    }
}
