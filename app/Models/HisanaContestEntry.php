<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HisanaContestEntry extends Model
{
    protected $fillable = [
        'email',
        'answer',
        'name',
        'ip_address',
        'user_agent',
    ];
}
