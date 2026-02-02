<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scholar extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image_path',
        'status',
        'order',
        'description',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
