<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_playlist', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_playlist', 'order')) {
                $table->unsignedInteger('order')->default(0)->after('playlist_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('asset_playlist', function (Blueprint $table) {
            if (Schema::hasColumn('asset_playlist', 'order')) {
                $table->dropColumn('order');
            }
        });
    }
};
