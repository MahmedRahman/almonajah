<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * فلاج إظهار الترجمة: عند true تظهر على صفحة الفيديو العامة شريط لغة الترجمة والإعدادات ونمط الترجمة.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('assets', 'show_translation')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->boolean('show_translation')->default(true)->after('translation_segments');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('assets', 'show_translation')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropColumn('show_translation');
            });
        }
    }
};
