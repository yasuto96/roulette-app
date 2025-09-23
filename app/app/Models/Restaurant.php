<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    public function cuisines()
    {
        return $this->belongsToMany(Cuisine::class, 'restaurant_cuisines');
    }

    public function favoredBy()
    {
        return $this->belongsToMany(Member::class, 'favorites');
    }

}
