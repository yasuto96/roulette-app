<?php

namespace App\Services\StoreFinder;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class GooglePlacesStoreFinder implements StoreFinder
{
    // ★ PHP7.4対応：プロパティを普通に宣言
    private string $apiKey;

    // ★ PHP7.4対応：通常のコンストラクタ
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function search(array $filters): Collection
    {
        $lat    = $filters['lat'] ?? null;
        $lng    = $filters['lng'] ?? null;
        $radius = max(1, (int)($filters['radius'] ?? 1000));
        if (!$lat || !$lng || !$radius) {
            return collect();
        }

        $q       = trim((string)($filters['q'] ?? ''));
        $genres  = (array)($filters['genres'] ?? []);
        $keyword = trim(implode(' ', array_filter([$q, implode(' ', $genres)])));

        $params = [
            'key'      => $this->apiKey,
            'location' => $lat . ',' . $lng,
            'radius'   => $radius,
            'language' => 'ja',            // ← 日本語化
        ];
        if ($keyword !== '')           $params['keyword']  = $keyword;
        if (!empty($filters['open_now'])) $params['opennow'] = 'true';
        if (isset($filters['price']))  $params['maxprice'] = (int)$filters['price'];

        $res = Http::timeout(8)->get(
            'https://maps.googleapis.com/maps/api/place/nearbysearch/json',
            $params
        );
        if (!$res->ok()) return collect();

        $json = $res->json();
        if (($json['status'] ?? '') !== 'OK') return collect();

        $minRating = $filters['min_rating'] ?? null;
        return collect($json['results'] ?? [])->map(function ($r) use ($minRating) {
            $rating = $r['rating'] ?? null;
            if ($minRating !== null && ($rating === null || $rating < $minRating)) {
                return null;
            }
            return [
                'place_id'    => $r['place_id'] ?? null,
                'name'        => $r['name'] ?? '',
                'address'     => $r['vicinity'] ?? ($r['formatted_address'] ?? ''),
                'lat'         => data_get($r, 'geometry.location.lat'),
                'lng'         => data_get($r, 'geometry.location.lng'),
                'rating'      => $rating,
                'price_level' => $r['price_level'] ?? null,
            ];
        })->filter()->values();
    }
}
