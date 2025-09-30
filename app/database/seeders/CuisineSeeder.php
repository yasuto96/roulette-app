<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cuisine;

class CuisineSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'ラーメン','寿司','焼肉','カレー','イタリアン',
            '中華','カフェ','うどん','そば','居酒屋'
        ];
        foreach ($names as $name) {
            Cuisine::firstOrCreate(['name' => $name]);
        }
    }
}
