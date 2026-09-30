<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Real school exams belong to a term (first/mid/final) — a teacher sets
     * this when authoring the exam, and it's used to organize both the exam
     * list and the grading queue. Nullable so existing exams (and a teacher
     * who hasn't decided yet) aren't forced to pick one immediately.
     */
    public function up(): void
    {
        Schema::table('subject_exams', function (Blueprint $table) {
            $table->enum('term', ['first_term', 'mid_term', 'final_term'])->nullable()->after('subject_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_exams', function (Blueprint $table) {
            $table->dropColumn('term');
        });
    }
};
