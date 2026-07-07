<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('playlists', function (Blueprint $table) {
            if (! Schema::hasColumn('playlists', 'is_visible')) {
                $table->boolean('is_visible')->default(true)->after('image_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('playlists', function (Blueprint $table) {
            if (Schema::hasColumn('playlists', 'is_visible')) {
                $table->dropColumn('is_visible');
            }
        });
    }
};
