<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Restaurant;
use App\Models\Cuisine;

class RestaurantCuisineSeeder extends Seeder
{
    public function run(): void
    {
        $cuisineIds = Cuisine::pluck('id');

        // 各レストランに 1〜3個のカテゴリをランダム付与
        Restaurant::chunk(200, function ($restaurants) use ($cuisineIds) {
            foreach ($restaurants as $r) {
                $attach = $cuisineIds->shuffle()->take(rand(1,3));
                $r->cuisines()->syncWithoutDetaching($attach);
            }
        });
    }
}
