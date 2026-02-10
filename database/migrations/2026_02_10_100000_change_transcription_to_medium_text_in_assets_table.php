<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * TEXT (64KB) قد يقطع المحتوى النصي الطويل؛ MEDIUMTEXT يدعم حتى ~16MB.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            return;
        }
        if (Schema::hasColumn('assets', 'transcription')) {
            DB::statement('ALTER TABLE assets MODIFY transcription MEDIUMTEXT NULL');
        }
        if (Schema::hasColumn('assets', 'transcription_plain')) {
            DB::statement('ALTER TABLE assets MODIFY transcription_plain MEDIUMTEXT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            return;
        }
        if (Schema::hasColumn('assets', 'transcription')) {
            DB::statement('ALTER TABLE assets MODIFY transcription TEXT NULL');
        }
        if (Schema::hasColumn('assets', 'transcription_plain')) {
            DB::statement('ALTER TABLE assets MODIFY transcription_plain TEXT NULL');
        }
    }
};
