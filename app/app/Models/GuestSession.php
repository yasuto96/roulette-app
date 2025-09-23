<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestSession extends Model
{
    protected $fillable = ['token','payload','expires_at'];
    protected $casts = [
        'payload'    => 'array',
        'expires_at' => 'datetime',
    ];
}
