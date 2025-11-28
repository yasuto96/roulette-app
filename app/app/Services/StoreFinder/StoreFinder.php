<?php

namespace App\Services\StoreFinder;

use Illuminate\Support\Collection;

interface StoreFinder
{
    /**
     * @param array $filters 例: [
     *   'lat' => 35.68, 'lng' => 139.76, 'radius' => 5000,
     *   'q' => '渋谷 ラーメン', 'genres' => ['寿司','中華'], // 任意
     *   'price' => null, 'open_now' => 0, 'min_rating' => null,
     * ]
     */
    public function search(array $filters): Collection;
}
