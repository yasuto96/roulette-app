<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuisine extends Model
{
    use HasFactory;

    protected $table = 'cuisines';   // 実テーブル名
    protected $fillable = ['name'];

    public function restaurants()
    {
        // 中間テーブル名はあなたのDBに合わせる（例は restaurant_cuisines）
        return $this->belongsToMany(Restaurant::class, 'restaurant_cuisines', 'cuisine_id', 'restaurant_id');
    }
}
