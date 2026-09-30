<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Lets a teacher write up a lesson's textbook content in Markdown and
     * release it to students independently of the live-session state
     * (`is_active`/`started_at`/`ended_at`) — a teacher may want to publish
     * reading material before ever starting a live session, or keep working
     * on a draft after one has ended. `content_released_at` null = draft,
     * only the teacher can see it; set = visible to students.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('description');
            $table->timestamp('content_released_at')->nullable()->after('content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['content', 'content_released_at']);
        });
    }
};
