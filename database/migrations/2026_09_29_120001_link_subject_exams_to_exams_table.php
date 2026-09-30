<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Exams stop being authored per-subject — an admin now schedules one exam
 * for a whole class, and every subject in it gets a subject_exams row (a
 * "paper") under that exam for its teacher(s) to fill in. So subject_exams
 * loses its own title/instructions/schedule/authorship (those move up to
 * the new exams table) and gains exam_id instead.
 *
 * Backfill: every subject_exams row that exists today becomes its own
 * single-subject exams row, carrying over its title/instructions/schedule
 * unchanged — so no student-facing exam, question, answer, or score
 * changes as a result of this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_exams', function (Blueprint $table) {
            $table->foreignId('exam_id')->nullable()->after('id')->constrained('exams')->cascadeOnDelete();
        });

        foreach (DB::table('subject_exams')->get() as $row) {
            $subject = DB::table('subjects')->find($row->subject_id);

            $examId = DB::table('exams')->insertGetId([
                'class_id' => $subject->class_id,
                'created_by' => $row->created_by,
                'title' => $row->title,
                'instructions' => $row->instructions,
                'starts_at' => $row->starts_at,
                'ends_at' => $row->ends_at,
                'published_at' => $row->published_at,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);

            DB::table('subject_exams')->where('id', $row->id)->update(['exam_id' => $examId]);
        }

        // Raw SQL, not ->change() — this project has no doctrine/dbal.
        DB::statement('ALTER TABLE subject_exams ALTER COLUMN exam_id SET NOT NULL');

        Schema::table('subject_exams', function (Blueprint $table) {
            $table->unique(['exam_id', 'subject_id']);
            $table->dropColumn(['created_by', 'title', 'instructions', 'starts_at', 'ends_at', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('subject_exams', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('instructions')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('published_at')->nullable();
        });

        foreach (DB::table('subject_exams')->get() as $row) {
            $exam = DB::table('exams')->find($row->exam_id);

            DB::table('subject_exams')->where('id', $row->id)->update([
                'created_by' => $exam->created_by,
                'title' => $exam->title,
                'instructions' => $exam->instructions,
                'starts_at' => $exam->starts_at,
                'ends_at' => $exam->ends_at,
                'published_at' => $exam->published_at,
            ]);
        }

        Schema::table('subject_exams', function (Blueprint $table) {
            $table->dropUnique(['exam_id', 'subject_id']);
            $table->dropConstrainedForeignId('exam_id');
        });
    }
};
