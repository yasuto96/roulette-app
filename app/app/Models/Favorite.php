<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    public $timestamps = false;
    protected $fillable = ['member_id','restaurant_id'];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function member()
    {
        return $this->belongsTo(\App\Models\Member::class);
    }

    public function histories()
    {
        return $this->hasMany(RouletteHistory::class, 'restaurant_id', 'restaurant_id')
            ->whereColumn('member_id', 'favorites.member_id');
    }
}