<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * تخزين ترجمات المحتوى النصي بلغات متعددة: en, fr, ur, id, ha, la
     * البنية: { "en": [{ "start", "end", "text" }, ...], "fr": [...], ... }
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'translation_segments')) {
                $table->json('translation_segments')->nullable()->after('transcription_plain');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'translation_segments')) {
                $table->dropColumn('translation_segments');
            }
        });
    }
};
