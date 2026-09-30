<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separate from published_at (which controls whether students can take the
 * exam), this controls whether the class-wide ranking/results are visible
 * to students — an admin publishes results only once every subject's
 * papers are fully graded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->timestamp('results_published_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('results_published_at');
        });
    }
};
