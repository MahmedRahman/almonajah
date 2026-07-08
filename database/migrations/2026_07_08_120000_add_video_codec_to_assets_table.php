<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'video_codec')) {
                $table->string('video_codec', 32)->nullable()->after('extension')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'video_codec')) {
                $table->dropIndex(['video_codec']);
                $table->dropColumn('video_codec');
            }
        });
    }
};
