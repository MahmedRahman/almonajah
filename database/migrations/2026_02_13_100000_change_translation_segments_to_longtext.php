<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * توسيع عمود translation_segments لاستيعاب ترجمات طويلة (LONGTEXT بدل JSON إن لزم).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('assets', 'translation_segments')) {
            return;
        }
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE assets MODIFY translation_segments LONGTEXT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('assets', 'translation_segments')) {
            return;
        }
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE assets MODIFY translation_segments JSON NULL');
        }
    }
};
