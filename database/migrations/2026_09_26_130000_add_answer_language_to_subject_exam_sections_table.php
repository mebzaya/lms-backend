<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mirrors assessments.answer_language: only meaningful for a writing
     * section's single essay (a reading section's questions each carry
     * their own answer_language instead, since a teacher may mix English
     * and Nepali questions within one passage).
     */
    public function up(): void
    {
        Schema::table('subject_exam_sections', function (Blueprint $table) {
            $table->enum('answer_language', ['english', 'nepali'])->default('english')->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_exam_sections', function (Blueprint $table) {
            $table->dropColumn('answer_language');
        });
    }
};
