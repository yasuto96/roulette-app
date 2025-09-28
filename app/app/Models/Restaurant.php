<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Restaurant extends Model
{


    use HasFactory;

    public function cuisines()
    {
        return $this->belongsToMany(Cuisine::class, 'restaurant_cuisines');
    }

    public function favoredBy()
    {
        return $this->belongsToMany(Member::class, 'favorites');
    }

}
