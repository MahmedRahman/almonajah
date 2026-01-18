<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HlsVersion extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'asset_id',
        'resolution',
        'width',
        'height',
        'bitrate',
        'audio_bitrate',
        'playlist_path',
        'master_playlist_path',
        'total_size_bytes',
        'segment_count',
    ];

    protected function casts(): array
    {
        return [
            'asset_id' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'total_size_bytes' => 'integer',
            'segment_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
