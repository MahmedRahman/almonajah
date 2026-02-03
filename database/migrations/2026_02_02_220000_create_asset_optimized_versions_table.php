<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * نسخ الفيديو المحسّنة (تقليل المساحة) — تُعرض في "ملفات الفيديو المتاحة".
     */
    public function up(): void
    {
        Schema::create('asset_optimized_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->string('relative_path'); // مسار الملف ضمن storage/app/public
            $table->string('quality_preset', 20); // high, balanced, small
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();

            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_optimized_versions');
    }
};
