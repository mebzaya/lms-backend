<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Now that a writing section's questions each carry their own prompt,
     * points, word-count limits and answer_language (see the previous
     * migration), the section itself no longer needs any of these — every
     * section type is now just {title, instructions, optional shared
     * prompt, reading-only passage} plus its list of questions. The old
     * per-attempt-per-section essay response table is superseded by
     * per-question answers (subject_exam_answers), and was empty (no
     * writing sections had ever been created), so nothing is lost.
     */
    public function up(): void
    {
        Schema::table('subject_exam_sections', function (Blueprint $table) {
            $table->dropColumn(['answer_language', 'min_word_count', 'max_word_count', 'max_points']);
        });

        Schema::dropIfExists('subject_exam_section_responses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_exam_sections', function (Blueprint $table) {
            $table->enum('answer_language', ['english', 'nepali'])->default('english')->after('type');
            $table->unsignedInteger('min_word_count')->nullable();
            $table->unsignedInteger('max_word_count')->nullable();
            $table->unsignedInteger('max_points')->nullable();
        });

        Schema::create('subject_exam_section_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('subject_exam_attempts')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('subject_exam_sections')->cascadeOnDelete();
            $table->text('response_text')->nullable();
            $table->unsignedInteger('word_count')->nullable();
            $table->decimal('points_awarded', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
        });
    }
};
