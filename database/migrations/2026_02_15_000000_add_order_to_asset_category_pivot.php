<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * عمود ترتيب الفيديو داخل التصنيف (على صفحة تفاصيل التصنيف).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('asset_category', 'order')) {
            Schema::table('asset_category', function (Blueprint $table) {
                $table->unsignedInteger('order')->default(0)->after('category_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('asset_category', 'order')) {
            Schema::table('asset_category', function (Blueprint $table) {
                $table->dropColumn('order');
            });
        }
    }
};
