<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subject_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('subject_exam_sections')->cascadeOnDelete();
            $table->enum('type', ['multiple_choice', 'short_answer', 'fill_in_blank']);
            $table->enum('answer_language', ['english', 'nepali'])->default('english');
            $table->text('prompt');
            $table->json('options')->nullable();
            $table->text('correct_answer');
            $table->unsignedInteger('points')->default(1);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_exam_questions');
    }
};
