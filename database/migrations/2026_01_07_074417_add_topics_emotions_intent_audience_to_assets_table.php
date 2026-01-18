<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'topics')) {
                $table->text('topics')->nullable()->after('transcription');
            }
            if (!Schema::hasColumn('assets', 'emotions')) {
                $table->text('emotions')->nullable()->after('topics');
            }
            if (!Schema::hasColumn('assets', 'intent')) {
                $table->text('intent')->nullable()->after('emotions');
            }
            if (!Schema::hasColumn('assets', 'audience')) {
                $table->text('audience')->nullable()->after('intent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'topics')) {
                $table->dropColumn('topics');
            }
            if (Schema::hasColumn('assets', 'emotions')) {
                $table->dropColumn('emotions');
            }
            if (Schema::hasColumn('assets', 'intent')) {
                $table->dropColumn('intent');
            }
            if (Schema::hasColumn('assets', 'audience')) {
                $table->dropColumn('audience');
            }
        });
    }
};
