<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('relative_path')->unique();
            $table->string('extension', 10);
            $table->bigInteger('size_bytes');
            $table->timestamp('modified_at');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('orientation')->nullable(); // portrait, landscape, square
            $table->string('aspect_ratio')->nullable(); // 9:16, 16:9, 1:1, etc.
            $table->timestamps();
            
            $table->index('file_name');
            $table->index('extension');
            $table->index('orientation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};


