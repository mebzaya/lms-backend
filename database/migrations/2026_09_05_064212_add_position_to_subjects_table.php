<?php

use App\Models\Subject;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('name');
        });

        Subject::orderBy('name')->get(['id', 'class_id'])
            ->groupBy('class_id')
            ->each(function ($subjectsInClass) {
                $subjectsInClass->values()->each(function (Subject $subject, int $index) {
                    $subject->update(['position' => $index + 1]);
                });
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
