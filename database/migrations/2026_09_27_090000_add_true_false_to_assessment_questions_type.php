<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds 'true_false' to the allowed `type` values. Postgres has no
     * native enum here (no doctrine/dbal installed), so the original
     * `enum()` column is backed by a CHECK constraint that must be
     * dropped and recreated with the extra value.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE assessment_questions DROP CONSTRAINT assessment_questions_type_check');
        DB::statement(
            "ALTER TABLE assessment_questions ADD CONSTRAINT assessment_questions_type_check ".
            "CHECK (type IN ('multiple_choice', 'short_answer', 'fill_in_blank', 'true_false'))"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE assessment_questions DROP CONSTRAINT assessment_questions_type_check');
        DB::statement(
            "ALTER TABLE assessment_questions ADD CONSTRAINT assessment_questions_type_check ".
            "CHECK (type IN ('multiple_choice', 'short_answer', 'fill_in_blank'))"
        );
    }
};
