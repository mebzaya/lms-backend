<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations. Same CHECK-constraint situation as
     * assessment_questions.type — see that table's equivalent migration.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE subject_exam_questions DROP CONSTRAINT subject_exam_questions_type_check');
        DB::statement(
            "ALTER TABLE subject_exam_questions ADD CONSTRAINT subject_exam_questions_type_check ".
            "CHECK (type IN ('multiple_choice', 'short_answer', 'fill_in_blank', 'true_false'))"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE subject_exam_questions DROP CONSTRAINT subject_exam_questions_type_check');
        DB::statement(
            "ALTER TABLE subject_exam_questions ADD CONSTRAINT subject_exam_questions_type_check ".
            "CHECK (type IN ('multiple_choice', 'short_answer', 'fill_in_blank'))"
        );
    }
};
