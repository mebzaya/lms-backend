<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Removes the standalone "term" classification added in error — what
     * was actually wanted was grouping by the exam's own title (e.g.
     * "First-Term"), which already exists as a free-text field, not a
     * separate first/mid/final-term enum.
     */
    public function up(): void
    {
        Schema::table('subject_exams', function (Blueprint $table) {
            $table->dropColumn('term');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_exams', function (Blueprint $table) {
            $table->enum('term', ['first_term', 'mid_term', 'final_term'])->nullable()->after('subject_id');
        });
    }
};
