<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audio_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->string('format'); // mp3, wav, etc.
            $table->string('bitrate'); // 192k, 320k, etc.
            $table->integer('sample_rate')->nullable(); // 44100, 48000, etc.
            $table->integer('channels')->nullable(); // 1 (mono), 2 (stereo)
            $table->string('file_path'); // path to audio file
            $table->bigInteger('file_size_bytes')->nullable(); // file size in bytes
            $table->integer('duration_seconds')->nullable(); // duration in seconds
            $table->timestamps();
            
            $table->index('asset_id');
            $table->index('format');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_files');
    }
};
