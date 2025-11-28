<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RestaurantController extends Controller
{
    public function index()
    {
        // 必要なら with('cuisines') でリレーションも同時取得
        $restaurants = Restaurant::query()->latest('id')->paginate(20);
        return view('restaurants.index', compact('restaurants'));

        $restaurants = Restaurant::with('cuisines')
        ->latest('id')
        ->paginate(20);

    }
    public function placeDetail(string $placeId)
    {
        $key = config('services.google.maps_key'); // config/services.php に設定
        $url = 'https://maps.googleapis.com/maps/api/place/details/json';

        $res = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $placeId,
            // opening_hours のサブフィールドを明示
            'fields'   => 'name,opening_hours,opening_hours/weekday_text,opening_hours/open_now,formatted_address,rating,geometry,utc_offset_minutes',
            'language' => 'ja',
            'key'      => $key,
        ])->json();

        $r = $res['result'] ?? [];

        return response()->json([
            'open_now'      => $r['opening_hours']['open_now'] ?? null,
            'weekday_text'  => $r['opening_hours']['weekday_text'] ?? null, // ["月曜日: 11:00～22:00", ...]
            // ついでに返しておくとUIで使える
            'name'          => $r['name'] ?? null,
            'address'       => $r['formatted_address'] ?? null,
            'rating'        => $r['rating'] ?? null,
            'lat'           => $r['geometry']['location']['lat'] ?? null,
            'lng'           => $r['geometry']['location']['lng'] ?? null,
        ]);
    }
    public function reverseGeocode(Request $request)
    {
        $lat = (float) $request->query('lat');
        $lng = (float) $request->query('lng');
        if (!$lat || !$lng) {
            return response()->json(['area' => null], 400);
        }

        // 環境変数からキーを取得（どちらでも可）
        $apiKey = config('services.google.maps_key')
            ?? env('GOOGLE_MAPS_API_KEY')
            ?? env('GOOGLE_API_KEY');

        if (!$apiKey) {
            // キー未設定でも落ちないようにする
            return response()->json(['area' => null, 'error' => 'no_api_key'], 200);
        }

        $res = Http::timeout(6)->get(
            'https://maps.googleapis.com/maps/api/geocode/json',
            [
                'latlng'   => $lat . ',' . $lng,
                'language' => 'ja',
                'result_type' => 'street_address|political',
                'key'      => $apiKey,
            ]
        );

        if (!$res->ok()) {
            return response()->json(['area' => null], 200);
        }

        $data = $res->json();
        if (($data['status'] ?? '') !== 'OK' || empty($data['results'])) {
            return response()->json(['area' => null], 200);
        }

        $components = $data['results'][0]['address_components'] ?? [];
        $pref = $city = $ward = null;
        foreach ($components as $c) {
            $types = $c['types'] ?? [];
            if (in_array('administrative_area_level_1', $types, true)) $pref = $c['long_name'];
            if (in_array('administrative_area_level_2', $types, true)) $city = $c['long_name'];          // 例: 仙台市/○○郡
            if (
                in_array('locality', $types, true) ||
                in_array('sublocality_level_1', $types, true) ||
                in_array('administrative_area_level_3', $types, true)
            ) $ward = $c['long_name']; // 例: 青葉区/新宿区 など
        }

        // 優先度: 市区町村+区 > 市区町村 > 区 > 都道府県
        $area = ($city && $ward) ? ($city . $ward) : ($city ?: $ward ?: $pref ?: null);

        return response()->json(['area' => $area], 200);
    }
}

