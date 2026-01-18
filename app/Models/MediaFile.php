<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'original_name',
        'path',
        'mime_type',
        'size',
        'type',
        'uploaded_by',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getSizeInKbAttribute(): float
    {
        return round($this->size / 1024, 2);
    }

    public function getSizeInMbAttribute(): float
    {
        return round($this->size / (1024 * 1024), 2);
    }
}


