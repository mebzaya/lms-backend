<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Lets a teacher declare whether a question's/essay's expected answer is
     * typed in English or Nepali Unicode (Devanagari), so the student's
     * attempt screen can default the Nepali-typing toggle accordingly.
     * Existing assessments default to 'english', matching how they already
     * behave today.
     */
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->enum('answer_language', ['english', 'nepali'])->default('english')->after('mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('answer_language');
        });
    }
};
