<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AudioFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'format',
        'bitrate',
        'sample_rate',
        'channels',
        'file_path',
        'file_size_bytes',
        'duration_seconds',
    ];

    protected function casts(): array
    {
        return [
            'asset_id' => 'integer',
            'sample_rate' => 'integer',
            'channels' => 'integer',
            'file_size_bytes' => 'integer',
            'duration_seconds' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
