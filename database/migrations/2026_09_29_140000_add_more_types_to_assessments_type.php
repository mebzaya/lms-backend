<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Mirrors subject_exam_sections: adds 'fill_in_blank' and 'true_false' as
     * standalone assessment types, alongside the existing 'reading' (mixed
     * question types under one passage) and 'writing' (a single essay). A
     * teacher can now build a lesson assessment that's entirely
     * fill-in-the-blank or entirely true/false statements, with no passage
     * required.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE assessments DROP CONSTRAINT assessments_type_check');
        DB::statement(
            "ALTER TABLE assessments ADD CONSTRAINT assessments_type_check ".
            "CHECK (type IN ('reading', 'writing', 'fill_in_blank', 'true_false'))"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE assessments DROP CONSTRAINT assessments_type_check');
        DB::statement(
            "ALTER TABLE assessments ADD CONSTRAINT assessments_type_check ".
            "CHECK (type IN ('reading', 'writing'))"
        );
    }
};
