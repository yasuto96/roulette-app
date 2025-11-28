<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Restaurant;

// app/Models/RouletteHistory.php
class RouletteHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id','restaurant_id','name','criteria','visited_at','my_rating','memo'
    ];

    protected $casts = [
        'criteria'   => 'array',
        'visited_at' => 'datetime',
        'my_rating'  => 'integer',
    ];

    public function member(){ return $this->belongsTo(Member::class); }
    public function restaurant(){ return $this->belongsTo(Restaurant::class); }
}

