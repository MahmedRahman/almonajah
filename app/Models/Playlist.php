<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'image_path',
    ];

    /**
     * علاقة many-to-many مع جدول assets (مع عمود الترتيب في الـ pivot)
     */
    public function assets()
    {
        return $this->belongsToMany(Asset::class, 'asset_playlist')
            ->withPivot('order')
            ->orderByPivot('order')
            ->orderBy('assets.id');
    }
}
