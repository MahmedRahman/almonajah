<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * نسخة نصية منقاة من التوقيتات تُستخدم عند إرسال المحتوى إلى DeepSeek.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'transcription_plain')) {
                $table->text('transcription_plain')->nullable()->after('transcription');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'transcription_plain')) {
                $table->dropColumn('transcription_plain');
            }
        });
    }
};
