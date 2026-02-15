<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * فلاج إظهار التعليقات: عند true تظهر قسم التعليقات على صفحة الفيديو العامة.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('assets', 'show_comments')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->boolean('show_comments')->default(true)->after('show_translation');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('assets', 'show_comments')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropColumn('show_comments');
            });
        }
    }
};
