<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RestaurantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'        => $this->faker->company,
            'address'     => $this->faker->address,
            'phone'       => $this->faker->phoneNumber,
            'website'     => $this->faker->url,
            'lat'         => $this->faker->latitude,
            'lng'         => $this->faker->longitude,
            'price_level' => $this->faker->numberBetween(1, 5),
            'rating'      => $this->faker->randomFloat(1, 1, 5),

            // ★ここが重要（NOT NULL を満たす）
            'source'      => 'seed',
            'source_id'   => Str::uuid()->toString(),
        ];
    }
}
