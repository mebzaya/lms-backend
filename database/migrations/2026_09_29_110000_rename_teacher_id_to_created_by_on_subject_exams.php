<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Exams are now scheduled by an admin, not authored by a teacher — the
 * teacher's job is adding sections/questions to an exam an admin already
 * created. Renamed in place (not doctrine/dbal renameColumn, to match this
 * project's established raw-SQL pattern for schema tweaks) so the existing
 * foreign key to users survives untouched; only the column's name and
 * meaning change, from "which teacher authored this" to "who created this
 * (admin or, for older exams, a teacher)".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE subject_exams RENAME COLUMN teacher_id TO created_by');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE subject_exams RENAME COLUMN created_by TO teacher_id');
    }
};
