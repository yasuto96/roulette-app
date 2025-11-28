<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Restaurant; 

class Restaurant extends Model
{

    protected $fillable = [
        'source', 'source_id',
        'name', 'address',
        'lat', 'lng',
        'phone', 'website',
        'price_level', 'rating', 'rating_total',
        'opening_hours',
    ];

    protected $casts = [
        'lat'            => 'float',
        'lng'            => 'float',
        'rating'         => 'float',
        'rating_total'   => 'integer',
        'price_level'    => 'integer',
        'opening_hours'  => 'array',
    ];

    use HasFactory;

    public function cuisines()
    {
        return $this->belongsToMany(Cuisine::class, 'restaurant_cuisines');
    }

    public function favoredBy()
    {
        return $this->belongsToMany(Member::class, 'favorites');
    }

    public function histories()
    {
        return $this->hasMany(\App\Models\RouletteHistory::class);
    }

}
