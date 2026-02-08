<?php

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
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'youtube_publish_url')) {
                $table->string('youtube_publish_url', 500)->nullable()->after('scheduled_publish_at');
            }
            if (!Schema::hasColumn('assets', 'soundcloud_publish_url')) {
                $table->string('soundcloud_publish_url', 500)->nullable()->after('youtube_publish_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'youtube_publish_url')) {
                $table->dropColumn('youtube_publish_url');
            }
            if (Schema::hasColumn('assets', 'soundcloud_publish_url')) {
                $table->dropColumn('soundcloud_publish_url');
            }
        });
    }
};
