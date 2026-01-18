<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Index for relative_path (used in WHERE clauses)
            $table->index('relative_path', 'idx_assets_relative_path');
            
            // Index for speaker_name (used in filtering)
            $table->index('speaker_name', 'idx_assets_speaker_name');
            
            // Index for orientation (used in Shorts filtering)
            $table->index('orientation', 'idx_assets_orientation');
            
            // Index for duration_seconds (used in Shorts filtering)
            $table->index('duration_seconds', 'idx_assets_duration');
            
            // Composite index for common query pattern (relative_path + orientation + duration)
            $table->index(['relative_path', 'orientation', 'duration_seconds'], 'idx_assets_path_orientation_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex('idx_assets_relative_path');
            $table->dropIndex('idx_assets_speaker_name');
            $table->dropIndex('idx_assets_orientation');
            $table->dropIndex('idx_assets_duration');
            $table->dropIndex('idx_assets_path_orientation_duration');
        });
    }
};
