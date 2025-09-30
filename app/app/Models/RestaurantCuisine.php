<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantCuisine extends Model
{
    use HasFactory;

    public function cuisines()
    {
        return $this->belongsToMany(Cuisine::class, 'restaurant_cuisines', 'restaurant_id', 'cuisine_id');
    }

    // 明示的にテーブル名を指定
    protected $table = 'restaurant_cuisines';

    // タイムスタンプが不要なら false にする
    public $timestamps = false;
}
