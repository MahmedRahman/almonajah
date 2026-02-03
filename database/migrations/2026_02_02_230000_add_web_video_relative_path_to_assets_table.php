<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * مسار النسخة المعروضة على الويب (إن وُجد؛ وإلا يُستخدم الملف الأصلي).
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'web_video_relative_path')) {
                $table->string('web_video_relative_path', 500)->nullable()->after('relative_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'web_video_relative_path')) {
                $table->dropColumn('web_video_relative_path');
            }
        });
    }
};
