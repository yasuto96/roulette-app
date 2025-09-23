<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuisines extends Model
{
    public function restaurants()
    {
        return $this->belongsToMany(Restaurant::class, 'restaurant_cuisines');
    }
}
