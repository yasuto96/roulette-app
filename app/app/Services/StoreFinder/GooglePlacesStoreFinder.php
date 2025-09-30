<?php
namespace App\Services\StoreFinder;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class GooglePlacesStoreFinder implements StoreFinder
{
    public function search(array $filters): Collection
    {
        $key = config('services.google.places_key');
        if (!$key) return collect(); // キー未設定なら空返し

        // 例：Text Search。後で Nearby/Search に差し替えOK
        $query = $filters['keyword'] ?? 'restaurant';
        if (!empty($filters['cuisine_name'])) {
            $query = $filters['cuisine_name'].' '.$query;
        }

        $resp = Http::get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
            'query' => $query,
            'language' => 'ja',
            'key' => $key,
            // 'location' => '35.68,139.76', 'radius' => 2000, // 位置情報を使うなら
        ]);

        if (!$resp->ok()) return collect();

        return collect($resp->json('results', []))->map(fn($p)=>[
            'name'     => $p['name'] ?? '',
            'address'  => $p['formatted_address'] ?? null,
            'id'       => null,                 // ← ローカルIDなし
            'place_id' => $p['place_id'] ?? null,
            'provider' => 'google',
        ]);
    }
}
