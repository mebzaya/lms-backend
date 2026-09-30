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
        Schema::create('subject_exam_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('subject_exams')->cascadeOnDelete();
            $table->enum('type', ['reading', 'writing']);
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->text('passage')->nullable();
            $table->text('prompt')->nullable();
            $table->unsignedInteger('min_word_count')->nullable();
            $table->unsignedInteger('max_word_count')->nullable();
            $table->unsignedInteger('max_points')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_exam_sections');
    }
};
