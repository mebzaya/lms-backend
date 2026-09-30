<?php

use App\Models\SchoolClass;
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
        Schema::table('classes', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('level');
        });

        SchoolClass::orderBy('level')->get(['id'])->each(function (SchoolClass $class, int $index) {
            $class->update(['position' => $index + 1]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
