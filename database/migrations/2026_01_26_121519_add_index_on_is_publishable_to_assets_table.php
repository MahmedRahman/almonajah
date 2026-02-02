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
            // Index for is_publishable (used in WHERE clauses)
            try {
                $table->index('is_publishable', 'idx_assets_is_publishable');
            } catch (\Exception $e) {
                // Index might already exist
            }
            
            // Composite index for common query pattern (relative_path + is_publishable)
            try {
                $table->index(['relative_path', 'is_publishable'], 'idx_assets_path_publishable');
            } catch (\Exception $e) {
                // Index might already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_assets_is_publishable');
            } catch (\Exception $e) {
                // Index might not exist
            }
            try {
                $table->dropIndex('idx_assets_path_publishable');
            } catch (\Exception $e) {
                // Index might not exist
            }
        });
    }
};
