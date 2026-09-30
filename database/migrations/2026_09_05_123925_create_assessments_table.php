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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['reading', 'writing']);
            $table->enum('mode', ['practice', 'exam']);
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->text('passage')->nullable();
            $table->text('prompt')->nullable();
            $table->unsignedInteger('min_word_count')->nullable();
            $table->unsignedInteger('max_word_count')->nullable();
            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->unsignedInteger('max_attempts')->nullable();
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
        Schema::dropIfExists('assessments');
    }
};
