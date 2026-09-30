<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `correct_answer` was varchar(255), which is too narrow for a
     * short-answer question whose expected answer is a full sentence.
     * Widened to text (no doctrine/dbal installed, so this uses a raw
     * statement rather than Schema::table(...)->change()).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE assessment_questions ALTER COLUMN correct_answer TYPE text');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE assessment_questions ALTER COLUMN correct_answer TYPE varchar(255)');
    }
};
