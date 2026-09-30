<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class ClassSubjectLessonSeeder extends Seeder
{
    /**
     * Subjects taught in every class, 5 through 10.
     *
     * @var array<int, string>
     */
    protected array $subjects = [
        'English',
        'Nepali',
        'Mathematics',
        'Science',
        'Social Studies',
        'Computer Science',
        'Health, Population and Environment',
        'Moral Education',
    ];

    /**
     * Seed classes 5-10, each with the standard 8 subjects, each with a
     * handful of lessons.
     */
    public function run(): void
    {
        for ($level = 5; $level <= 10; $level++) {
            $class = SchoolClass::firstOrCreate(
                ['level' => $level],
                ['name' => "Class {$level}"]
            );

            foreach ($this->subjects as $subjectName) {
                $subject = Subject::firstOrCreate(
                    ['class_id' => $class->id, 'name' => $subjectName],
                );

                if ($subject->lessons()->exists()) {
                    continue;
                }

                $lessonCount = random_int(2, 5);

                for ($position = 1; $position <= $lessonCount; $position++) {
                    Lesson::create([
                        'subject_id' => $subject->id,
                        'title' => "Lesson {$position}: {$subjectName} for {$class->name}",
                        'description' => "Introductory material for lesson {$position} of {$subjectName}.",
                        'position' => $position,
                    ]);
                }
            }
        }
    }
}
