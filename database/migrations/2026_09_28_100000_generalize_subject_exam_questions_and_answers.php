<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Writing sections used to be a single essay prompt living directly on
     * subject_exam_sections. Real exam papers need several independently-
     * graded written questions under one section heading (e.g. "5 short
     * answers @ 5 marks + 1 essay @ 5 marks"), so writing sections now hold
     * a list of 'written_response' questions — same shape as every other
     * section type. This adds what those questions need (word-count limits,
     * a nullable correct_answer since there's no single right answer) and
     * what grading them needs (feedback/word_count/grader on the answer
     * itself, since manual grading now happens per-question, not per-
     * section).
     */
    public function up(): void
    {
        Schema::table('subject_exam_questions', function (Blueprint $table) {
            $table->unsignedInteger('min_word_count')->nullable()->after('points');
            $table->unsignedInteger('max_word_count')->nullable()->after('min_word_count');
        });

        DB::statement('ALTER TABLE subject_exam_questions ALTER COLUMN correct_answer DROP NOT NULL');

        DB::statement('ALTER TABLE subject_exam_questions DROP CONSTRAINT subject_exam_questions_type_check');
        DB::statement(
            "ALTER TABLE subject_exam_questions ADD CONSTRAINT subject_exam_questions_type_check ".
            "CHECK (type IN ('multiple_choice', 'short_answer', 'fill_in_blank', 'true_false', 'written_response'))"
        );

        Schema::table('subject_exam_answers', function (Blueprint $table) {
            $table->text('feedback')->nullable()->after('points_awarded');
            $table->unsignedInteger('word_count')->nullable()->after('feedback');
            $table->foreignId('graded_by')->nullable()->after('word_count')->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable()->after('graded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_exam_answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('graded_by');
            $table->dropColumn(['feedback', 'word_count', 'graded_at']);
        });

        DB::statement('ALTER TABLE subject_exam_questions DROP CONSTRAINT subject_exam_questions_type_check');
        DB::statement(
            "ALTER TABLE subject_exam_questions ADD CONSTRAINT subject_exam_questions_type_check ".
            "CHECK (type IN ('multiple_choice', 'short_answer', 'fill_in_blank', 'true_false'))"
        );

        DB::statement("UPDATE subject_exam_questions SET correct_answer = '' WHERE correct_answer IS NULL");
        DB::statement('ALTER TABLE subject_exam_questions ALTER COLUMN correct_answer SET NOT NULL');

        Schema::table('subject_exam_questions', function (Blueprint $table) {
            $table->dropColumn(['min_word_count', 'max_word_count']);
        });
    }
};
