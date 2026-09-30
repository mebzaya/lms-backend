<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUsersSeeder extends Seeder
{
    /**
     * Demo password shared by every seeded account below.
     */
    protected const DEMO_PASSWORD = 'password123';

    /**
     * Seed a handful of teacher, student, and parent accounts, plus a few
     * sample class/subject assignments so the admin UI has something to show.
     */
    public function run(): void
    {
        $teachers = collect([
            ['name' => 'Anita Sharma', 'email' => 'anita.teacher@lms.test'],
            ['name' => 'Bikash Thapa', 'email' => 'bikash.teacher@lms.test'],
            ['name' => 'Deepa Gurung', 'email' => 'deepa.teacher@lms.test'],
        ])->map(fn (array $t) => User::firstOrCreate(
            ['email' => $t['email']],
            ['name' => $t['name'], 'password' => self::DEMO_PASSWORD, 'role' => User::ROLE_TEACHER]
        ));

        $students = collect([
            ['name' => 'Sita Koirala', 'email' => 'sita.student@lms.test', 'level' => 5],
            ['name' => 'Ram Bahadur', 'email' => 'ram.student@lms.test', 'level' => 6],
            ['name' => 'Gita Rai', 'email' => 'gita.student@lms.test', 'level' => 7],
            ['name' => 'Hari Adhikari', 'email' => 'hari.student@lms.test', 'level' => 8],
            ['name' => 'Kamala Shrestha', 'email' => 'kamala.student@lms.test', 'level' => 9],
            ['name' => 'Nabin Basnet', 'email' => 'nabin.student@lms.test', 'level' => 10],
        ])->map(function (array $s) {
            $class = SchoolClass::where('level', $s['level'])->first();

            return User::firstOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'password' => self::DEMO_PASSWORD,
                    'role' => User::ROLE_STUDENT,
                    'class_id' => $class?->id,
                ]
            );
        });

        $parents = collect([
            ['name' => 'Shyam Koirala', 'email' => 'shyam.parent@lms.test'],
            ['name' => 'Radha Rai', 'email' => 'radha.parent@lms.test'],
        ])->map(fn (array $p) => User::firstOrCreate(
            ['email' => $p['email']],
            ['name' => $p['name'], 'password' => self::DEMO_PASSWORD, 'role' => User::ROLE_PARENT]
        ));

        // Link a couple of parents to a couple of students.
        $parents[0]->children()->syncWithoutDetaching([$students[0]->id]);
        $parents[1]->children()->syncWithoutDetaching([$students[2]->id]);

        // Assign each teacher a subject across a couple of classes.
        $subjectAssignments = [
            ['teacher' => $teachers[0], 'subject' => 'Mathematics', 'levels' => [5, 6]],
            ['teacher' => $teachers[1], 'subject' => 'Science', 'levels' => [7, 8]],
            ['teacher' => $teachers[2], 'subject' => 'English', 'levels' => [9, 10]],
        ];

        foreach ($subjectAssignments as $assignment) {
            $subjectIds = Subject::where('name', $assignment['subject'])
                ->whereIn('class_id', SchoolClass::whereIn('level', $assignment['levels'])->pluck('id'))
                ->pluck('id');

            $assignment['teacher']->subjectsTaught()->syncWithoutDetaching($subjectIds);
        }
    }
}
