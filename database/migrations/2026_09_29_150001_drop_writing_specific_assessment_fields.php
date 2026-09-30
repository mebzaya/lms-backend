<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors 2026_09_28_100001_drop_writing_specific_section_fields.php: now
 * that a writing assessment's questions each carry their own prompt, points,
 * word-count limits and answer_language, the assessment itself no longer
 * needs any of these — every assessment type is now just {title,
 * instructions, optional shared prompt, reading-only passage} plus its list
 * of questions. Confirmed zero real writing assessments have ever had an
 * attempt, so nothing is lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['answer_language', 'min_word_count', 'max_word_count', 'max_points']);
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->enum('answer_language', ['english', 'nepali'])->default('english')->after('type');
            $table->unsignedInteger('min_word_count')->nullable();
            $table->unsignedInteger('max_word_count')->nullable();
            $table->unsignedInteger('max_points')->nullable();
        });
    }
};
