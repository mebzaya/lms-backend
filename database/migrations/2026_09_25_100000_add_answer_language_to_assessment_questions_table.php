<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Corrects an earlier design: whether an answer is expected in English or
     * Nepali Unicode varies per question within a reading assessment (a
     * teacher may mix them), not for the assessment as a whole. Moved here
     * from `assessments.answer_language`, which stays on the assessment only
     * for writing (a single essay has no separate questions to vary by).
     */
    public function up(): void
    {
        Schema::table('assessment_questions', function (Blueprint $table) {
            $table->enum('answer_language', ['english', 'nepali'])->default('english')->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_questions', function (Blueprint $table) {
            $table->dropColumn('answer_language');
        });
    }
};
