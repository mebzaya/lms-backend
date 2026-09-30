<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Having sections/questions isn't the same as being done — a teacher may
     * still be mid-draft. This tracks the teacher's own deliberate "submit"
     * action, so an admin only ever sees a subject as ready once its teacher
     * has actually declared it finished, rather than the moment the first
     * (possibly empty) section is added.
     */
    public function up(): void
    {
        Schema::table('subject_exams', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('subject_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_exams', function (Blueprint $table) {
            $table->dropColumn('submitted_at');
        });
    }
};
