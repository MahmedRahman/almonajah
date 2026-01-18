<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hls_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->string('resolution'); // 360p, 480p, 720p, etc.
            $table->integer('width');
            $table->integer('height');
            $table->string('bitrate'); // 800k, 1400k, 2800k
            $table->string('audio_bitrate'); // 96k, 128k
            $table->string('playlist_path'); // path to index.m3u8
            $table->string('master_playlist_path')->nullable(); // path to master.m3u8
            $table->bigInteger('total_size_bytes')->nullable(); // total size of all segments
            $table->integer('segment_count')->nullable(); // number of segments
            $table->timestamps();
            
            $table->index('asset_id');
            $table->index('resolution');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hls_versions');
    }
};
