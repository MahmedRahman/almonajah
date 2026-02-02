<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'image_path',
        'show_on_site',
        'order',
    ];

    protected $casts = [
        'show_on_site' => 'boolean',
    ];

    public function contentItems()
    {
        return $this->belongsToMany(ContentItem::class, 'content_category');
    }

    /**
     * علاقة many-to-many مع جدول assets
     */
    public function assets()
    {
        return $this->belongsToMany(Asset::class, 'asset_category');
    }
}


