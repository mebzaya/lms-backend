<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the subject_exam_questions/subject_exam_answers generalization:
 * a writing assessment used to be a single essay prompt living directly on
 * assessments, graded once per attempt. Real lessons need several
 * independently-graded written questions under one assessment (e.g. two
 * short-answer questions plus one longer response), so writing assessments
 * now hold a list of 'written_response' questions — same shape as every
 * other assessment type. This adds what those questions need (word-count
 * limits, a nullable correct_answer since there's no single right answer)
 * and what grading them needs (feedback/word_count/grader on the answer
 * itself, since manual grading now happens per-question, not per-attempt).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_questions', function (Blueprint $table) {
            $table->unsignedInteger('min_word_count')->nullable()->after('points');
            $table->unsignedInteger('max_word_count')->nullable()->after('min_word_count');
        });

        DB::statement('ALTER TABLE assessment_questions ALTER COLUMN correct_answer DROP NOT NULL');

        DB::statement('ALTER TABLE assessment_questions DROP CONSTRAINT assessment_questions_type_check');
        DB::statement(
            "ALTER TABLE assessment_questions ADD CONSTRAINT assessment_questions_type_check ".
            "CHECK (type IN ('multiple_choice', 'short_answer', 'fill_in_blank', 'true_false', 'written_response'))"
        );

        Schema::table('assessment_answers', function (Blueprint $table) {
            $table->text('feedback')->nullable()->after('points_awarded');
            $table->unsignedInteger('word_count')->nullable()->after('feedback');
            $table->foreignId('graded_by')->nullable()->after('word_count')->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable()->after('graded_by');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('graded_by');
            $table->dropColumn(['feedback', 'word_count', 'graded_at']);
        });

        DB::statement('ALTER TABLE assessment_questions DROP CONSTRAINT assessment_questions_type_check');
        DB::statement(
            "ALTER TABLE assessment_questions ADD CONSTRAINT assessment_questions_type_check ".
            "CHECK (type IN ('multiple_choice', 'short_answer', 'fill_in_blank', 'true_false'))"
        );

        DB::statement('ALTER TABLE assessment_questions ALTER COLUMN correct_answer SET NOT NULL');

        Schema::table('assessment_questions', function (Blueprint $table) {
            $table->dropColumn(['min_word_count', 'max_word_count']);
        });
    }
};
