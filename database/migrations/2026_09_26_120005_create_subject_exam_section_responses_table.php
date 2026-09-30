<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per writing section per attempt — a single exam can have more
     * than one writing section, and each needs its own essay text and its
     * own teacher grade/feedback.
     */
    public function up(): void
    {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_exam_section_responses');
    }
};
