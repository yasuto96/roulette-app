<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cuisine;
use App\Models\Restaurant;
use App\Models\RouletteHistory;
use App\Services\StoreFinder\StoreFinder;
use App\Models\Member;
use App\Models\Favorite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RouletteController extends Controller
{
    /** @var StoreFinder */
    private $finder; // ← PHP8未満でも動くよう型を付けない

    public function __construct(StoreFinder $finder)
    {
        $this->finder = $finder;
    }

    /** 公開：カテゴリルーレット（フォーム表示） */
    public function categoryForm(Request $request)
    {
        // reset=1 のときは保持選択をクリア
        if ($request->boolean('reset')) {
            $request->session()->forget('roulette.selected_cuisine_ids');
            $request->session()->forget('roulette.visible_cuisine_ids');
        }

        // デフォルトで見せる6カテゴリ（順番も固定）
        $defaults = ['カフェ', 'イタリアン', '寿司', '焼肉', '中華', 'ラーメン'];

        if ($request->boolean('reset')) {
            // 念のため、存在しなければ作成（種別マスタとして常に出せるように）
            foreach ($defaults as $name) {
                Cuisine::firstOrCreate(['name' => $name]);
            }

            // 表示は6件だけ＆並びも指定順で
            $cuisines = Cuisine::whereIn('name', $defaults)
                ->orderByRaw('FIELD(name, ?, ?, ?, ?, ?, ?)', $defaults)
                ->get();
        } else {
            // ★ 前回表示していた集合があればそれを優先表示
            $visibleIds = $request->session()->get('roulette.visible_cuisine_ids', []);
            if (!empty($visibleIds)) {
                $ids = array_map('intval', $visibleIds);
                $in  = implode(',', $ids);
                $cuisines = \App\Models\Cuisine::whereIn('id', $ids)
                    ->orderByRaw("FIELD(id, {$in})")
                    ->get();
            }else {
                // resetなし（結果→戻る等）は全件を名前順で
                $cuisines = Cuisine::orderBy('name')->get();
            }
        }
        // 直前の選択を復元（なければ空配列）
        $selectedIds = $request->session()->get('roulette.selected_cuisine_ids', []);

        return view('roulette.category', compact('cuisines', 'selectedIds'));
    }

    /** 公開：カテゴリルーレット（抽選） */
    public function categorySpin(Request $request)
    {
        $data = $request->validate([
            'cuisine_ids'   => 'nullable|array',
            'cuisine_ids.*' => 'integer|exists:cuisines,id',
            'new_cuisine'   => 'nullable|string|max:50',
            // ★ 表示されていたIDを受け取る
            'visible_ids'   => 'nullable|array',
            'visible_ids.*' => 'integer|exists:cuisines,id',
        ]);

        $selected = collect($data['cuisine_ids'] ?? []);

        // 追加入力があれば作成して対象に加える
        if ($request->filled('new_cuisine')) {
            $name = trim((string) $request->new_cuisine);
            if ($name !== '') {
                $created = Cuisine::firstOrCreate(['name' => $name]);
                $selected->push($created->id);
                // 追加された分は visibleIds にも含めたい（念のため）
                $data['visible_ids'][] = $created->id;
            }
        }

        // ★ 「その時画面に出ていたID」も保存（戻る時に同じ集合を表示）
        $visible = collect($data['visible_ids'] ?? [])->map(fn($v)=>(int)$v)->unique()->values();
        $request->session()->put('roulette.visible_cuisine_ids', $visible->all());

        // 何も選ばれていなければ全カテゴリから
        if ($selected->isEmpty()) {
            $selected = Cuisine::pluck('id');
        }

        if ($selected->isEmpty()) {
            // DBにカテゴリが1件も無いときのフェイルセーフ
            return redirect()->route('roulette.result', ['src' => 'category']);
        }

        // ★ 直前選択をセッションに保持（結果→戻るでそのまま使う）
        $request->session()->put('roulette.selected_cuisine_ids', $selected->values()->all());

        // カテゴリだけを抽選
        $pickedId = $selected->random();
        $cuisine  = Cuisine::find($pickedId);

        return redirect()->route('roulette.result', [
            'src'          => 'category',
            'cuisine_id'   => $cuisine ? $cuisine->id   : null,
            'cuisine_name' => $cuisine ? $cuisine->name : null, // ← 日本語名そのまま
        ]);
    }

    /** 会員限定：検索ルーレット（フォーム表示） */
    public function searchForm(Request $request)
    {
        $cuisines = Cuisine::orderBy('name')->get();
        return view('roulette.search', compact('cuisines'));
    }

    
    public function geocode(Request $req)
    {
        $q = trim((string)$req->query('q', ''));
        if ($q === '') {
            return response()->json(['error' => 'empty query'], 422);
        }

        $resp = Http::withHeaders([
                // Nominatim ポリシーに従い UA を明示
                'User-Agent' => 'GohanRoulette/1.0 (+your-contact@example.com)',
                'Accept'     => 'application/json',
            ])
            ->timeout(10)
            ->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'jsonv2',
                'limit'  => 1,
                'q'      => $q,
                'accept-language' => 'ja',
            ]);

        if (!$resp->ok()) {
            return response()->json(['error' => 'geocode failed'], 502);
        }

        $arr = $resp->json();
        if (!is_array($arr) || !count($arr)) {
            return response()->json(['hit' => null]);
        }

        $hit = $arr[0];
        return response()->json([
            'hit' => [
                'lat'   => (float)($hit['lat'] ?? 0),
                'lng'   => (float)($hit['lon'] ?? 0),
                'label' => (string)($hit['display_name'] ?? ''),
            ]
        ]);
    }

    /**
     * 条件→候補を取得してJSONで返す（会員限定）
     */
    public function searchSpin(Request $request)
    {
        $data = $request->validate([
            'q'              => ['nullable','string','max:100'],
            // お気に入りモードのときは半径を必須にしない
            'radius'         => ['required_unless:only_favorites,1','integer','min:100','max:20000'],
            'genres'         => ['nullable','array'],
            'genres.*'       => ['string','max:50'],
            'price'          => ['nullable','integer','min:1','max:4'],
            'open_now'       => ['nullable','boolean'],
            'min_rating'     => ['nullable','numeric','min:0','max:5'],
            'lat'            => ['nullable','numeric'],
            'lng'            => ['nullable','numeric'],
            'only_favorites' => ['nullable','boolean'],
        ]);

        // ─────────────────────────────────────────
        // ① お気に入りのみ：DB から返す（外部検索なし）
        // ─────────────────────────────────────────
        if (!empty($data['only_favorites'])) {
            if (!Auth::check()) {
                return response()->json(['items' => [], 'src' => 'favorites', 'debug' => 'guest']);
            }

            // ★ 同じ user_id を持つ Member が複数あっても全部対象にする
            $memberIds = Member::where('user_id', Auth::id())->pluck('id');

            // パラメータ取り出し
            $q         = trim((string)($data['q'] ?? ''));
            $genres    = (array)($data['genres'] ?? []);
            $price     = isset($data['price']) ? (int)$data['price'] : null;
            $minRating = isset($data['min_rating']) ? (float)$data['min_rating'] : null;
            $lat       = isset($data['lat']) ? (float)$data['lat'] : 0.0;
            $lng       = isset($data['lng']) ? (float)$data['lng'] : 0.0;
            $radius    = isset($data['radius']) ? (int)$data['radius'] : 5000; // 未使用だが保持

            // ▼ ここを JOIN に変更（pluck→whereIn の取りこぼしを回避）
            $query = Restaurant::query()
                ->with('cuisines')
                ->join('favorites', 'favorites.restaurant_id', '=', 'restaurants.id')
                ->whereIn('favorites.member_id', $memberIds)
                ->select('restaurants.*'); // ← with() と併用する時は * を戻す

            if ($q !== '') {
                $query->where(function($qq) use ($q) {
                    $qq->where('restaurants.name', 'like', "%{$q}%")
                    ->orWhere('restaurants.address', 'like', "%{$q}%");
                });
            }
            if (!empty($genres)) {
                $query->whereHas('cuisines', fn($qq) => $qq->whereIn('name', $genres));
            }
            if (!is_null($price)) {
                $query->where('restaurants.price_level', $price);
            }
            if (!is_null($minRating)) {
                $query->where('restaurants.rating', '>=', $minRating);
            }

            // 距離：並び替えのみ（絞り込みはしない）
            if ($lat && $lng) {
                $haversine = sprintf(
                    '(6371000 * acos(cos(radians(%F)) * cos(radians(lat)) * cos(radians(lng) - radians(%F)) + sin(radians(%F)) * sin(radians(lat))))',
                    $lat, $lng, $lat
                );
                $query->addSelect(DB::raw("$haversine AS distance"))
                    ->orderByRaw('CASE WHEN distance IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('distance');
            } else {
                $query->latest('restaurants.id');
            }

            // 必要カラムを明示しつつ with(cuisines) が動くように restaurants.* を返す
            $rows = $query->limit(50)->get();

            // 正規化
            $items = [];
            foreach ($rows as $r) {
                $items[] = [
                    'id'       => (int) $r->id,
                    'name'     => (string) ($r->name ?? ''),     // ← “空なら削除” はフロントでやる
                    'address'  => (string) ($r->address ?? ''),
                    'lat'      => $r->lat,
                    'lng'      => $r->lng,
                    'place_id' => ($r->source === 'google') ? ($r->source_id ?: null) : null,
                    'rating'   => $r->rating,
                    'price'    => $r->price_level,
                    'cuisines' => $r->cuisines
                        ? $r->cuisines->pluck('name')->map(fn($n) => ['name' => $n])->all()
                        : [],
                ];
            }

            // デバッグ情報も少し返す（必要ならコンソールで確認）
            return response()->json([
                'items' => $items,
                'src'   => 'favorites',
                'debug' => [
                    'member_ids' => $memberIds,
                    'count_rows' => count($rows),
                ],
            ]);
        }


        // ─────────────────────────────────────────
        // ② 通常モード：StoreFinder を利用（既存ロジック）
        // ─────────────────────────────────────────
        /** @var StoreFinder $finder */
        $finder = app(StoreFinder::class);

        if (empty($data['q'])) {
            // ジャンル指定が無い完全空検索なら、とりあえず「レストラン」を入れる
            $data['q'] = 'レストラン';
        }

        // ★ Google系実装の保険：type/keyword を埋める（StoreFinder が使っていれば効く）
        $data['type']    = $data['type']    ?? 'restaurant';
        $data['keyword'] = $data['keyword'] ?? $data['q'];

        // ★ 送信内容をログに出す（何が飛んでるかを確認）
        \Log::debug('roulette.searchSpin request', [
            'lat'     => $data['lat'] ?? null,
            'lng'     => $data['lng'] ?? null,
            'radius'  => $data['radius'] ?? null,
            'q'       => $data['q'] ?? null,
            'type'    => $data['type'] ?? null,
            'keyword' => $data['keyword'] ?? null,
            'genres'  => $data['genres'] ?? [],
            'price'   => $data['price'] ?? null,
            'min'     => $data['min_rating'] ?? null,
        ]);

        
        try {
            // 何が返っても“配列の items”に落とし込む
            $raw = $finder->search($data);
            $items = [];

            if (is_array($raw)) {
                // すでに配列
                $items = $raw;
            } elseif ($raw instanceof \Illuminate\Support\Collection) {
                $items = $raw->values()->all();
            } elseif ($raw instanceof \Illuminate\Contracts\Pagination\Paginator
                || $raw instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                // Paginator 系
                $items = $raw->items();
            } elseif (is_object($raw)) {
                // Google系: results / data / items / jsonSerialize / toArray などに対応
                if (isset($raw->results) && is_array($raw->results)) {
                    $items = $raw->results;
                } elseif (isset($raw->data) && is_array($raw->data)) {
                    $items = $raw->data;
                } elseif (isset($raw->items) && is_array($raw->items)) {
                    $items = $raw->items;
                } elseif ($raw instanceof \JsonSerializable) {
                    $items = (array) $raw->jsonSerialize();
                    // results キーで来ることが多い
                    if (isset($items['results']) && is_array($items['results'])) {
                        $items = $items['results'];
                    }
                } elseif (method_exists($raw, 'toArray')) {
                    $tmp = (array) $raw->toArray();
                    if (isset($tmp['data']) && is_array($tmp['data'])) {
                        $items = $tmp['data'];
                    } elseif (isset($tmp['results']) && is_array($tmp['results'])) {
                        $items = $tmp['results'];
                    } else {
                        $items = $tmp;
                    }
                }
            }

            // 念のため最終ガード
            if (!is_array($items)) {
                $items = [];
            }
            
            // ★ 結果のログ（配列か/件数/先頭1件）
            \Log::debug('roulette.searchSpin finder.result', [
                'is_array' => is_array($items),
                'count'    => is_array($items) ? count($items) : null,
                'sample0'  => is_array($items) ? ($items[0] ?? null) : null,
            ]);

            // 価格帯
            if (isset($data['price']) && $data['price'] !== null) {
                $want = (int)$data['price'];
                $items = array_values(array_filter($items, function($it) use ($want){
                    foreach (['price_level','price','priceLevel'] as $k) {
                        if (isset($it[$k])) return (int)$it[$k] === $want;
                    }
                    return false;
                }));
            }

            // 評価
            if (!empty($data['min_rating'])) {
                $min = (float)$data['min_rating'];
                $items = array_values(array_filter($items, fn($x) => !empty($x['rating']) && (float)$x['rating'] >= $min));
            }

            // 半径（保険）
            if (!empty($data['lat']) && !empty($data['lng']) && !empty($data['radius'])) {
                $lat = (float)$data['lat']; $lng = (float)$data['lng']; $radius = (int)$data['radius'];
                $items = array_values(array_filter($items, function($it) use ($lat,$lng,$radius){
                    if (empty($it['lat']) || empty($it['lng'])) return false;
                    $d = $this->haversine($lat, $lng, (float)$it['lat'], (float)$it['lng']);
                    return $d <= $radius;
                }));
            }
                        // ★ ここから追加：StoreFinderが0件ならローカルDBへフォールバック
            if (empty($items)) {
                $rows = Restaurant::query()
                    ->when(!empty($data['q']), function ($q) use ($data) {
                        $kw = trim((string)$data['q']);
                        $q->where(function ($qq) use ($kw) {
                            $qq->where('name', 'like', "%{$kw}%")
                               ->orWhere('address', 'like', "%{$kw}%");
                        });
                    })
                    ->when(!empty($data['genres']), fn($q) =>
                        $q->whereHas('cuisines', fn($qq) => $qq->whereIn('name', (array)$data['genres']))
                    )
                    ->when(isset($data['price']) && $data['price'] !== null, fn($q) =>
                        $q->where('price_level', (int)$data['price'])
                    )
                    ->when(isset($data['min_rating']) && $data['min_rating'] !== null, fn($q) =>
                        $q->where('rating', '>=', (float)$data['min_rating'])
                    )
                    ->limit(50)
                    ->get(['name','address','lat','lng','price_level','rating']);

                $out = $rows->map(fn ($r) => [
                    'name'     => $r->name,
                    'address'  => $r->address,
                    'place_id' => null,
                    'rating'   => $r->rating,
                    'lat'      => $r->lat,
                    'lng'      => $r->lng,
                    'price'    => $r->price_level,
                ])->all();

                return response()->json(['items' => $out, 'src' => 'local']);
            }


            // 整形（店名ユニーク）
            $uniq = []; $out = [];
            foreach ($items as $it) {
                $k = mb_strtolower($it['name'] ?? '');
                if ($k !== '' && empty($uniq[$k])) {
                    $uniq[$k] = 1;
                    $out[] = [
                        'name'     => $it['name'] ?? '',
                        'address'  => $it['address'] ?? ($it['vicinity'] ?? ''),
                        'place_id' => $it['place_id'] ?? null,
                        'rating'   => $it['rating'] ?? null,
                        'lat'      => $it['lat'] ?? null,
                        'lng'      => $it['lng'] ?? null,
                        'price'    => $it['price_level'] ?? ($it['price'] ?? null),
                    ];
                    if (count($out) >= 50) break;
                }
            }
            return response()->json(['items' => $out, 'src' => 'google']);
        } catch (\Throwable $e) {
            // フォールバック（ローカルDB）
            $rows = Restaurant::query()
                ->when(!empty($data['genres']), fn($q) => $q->whereHas('cuisines', fn($qq)=>$qq->whereIn('name', $data['genres'])))
                ->limit(50)->get(['name','address','lat','lng','price_level','rating']);

            $out = $rows->map(fn($r)=>[
                'name'    => $r->name,
                'address' => $r->address,
                'place_id'=> null,
                'rating'  => $r->rating,
                'lat'     => $r->lat,
                'lng'     => $r->lng,
                'price'   => $r->price_level,
            ])->all();

            return response()->json(['items'=>$out, 'src'=>'local']);
        }
    }


    /** Haversine（メートル） */
    private function haversine($lat1, $lng1, $lat2, $lng2)
    {
        $R = 6371000; // 地球半径[m]
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2) * sin($dLat/2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $R * $c;
    }


    /** 共通：結果表示＆（検索ルーレットのみ）履歴保存 */
    public function result(Request $request)
    {
        // 1) まずローカルIDで取得（来ていれば最優先）
        $restaurant = $request->filled('id')
            ? Restaurant::with('cuisines')->find($request->id)
            : null;

        // 2) ローカルIDが無い場合でも、place_id / name / address 等から upsert して $restaurant を確定
        if (!$restaurant) {
            $placeId = $request->get('place_id');         // 画面から貰えるなら
            $name    = $request->get('picked_name');
            $address = $request->get('picked_address');
            $lat     = $request->get('lat');
            $lng     = $request->get('lng');

            $restaurantId = $this->upsertRestaurant($placeId, $name, $address, $lat, $lng);
            if ($restaurantId) {
                $restaurant = Restaurant::with('cuisines')->find($restaurantId);
            }
        }

        // 表示用カテゴリ名（クエリ優先→IDから補完）
        $categoryLabel = $request->get('cuisine_name');
        if (!$categoryLabel && $request->filled('cuisine_id')) {
            $c = Cuisine::find($request->cuisine_id);
            $categoryLabel = $c ? $c->name : null;
        }

        // 3) 履歴保存（ログイン時のみ）
        if (Auth::check()) {
            // users.id → members.id
            $member = Member::where('user_id', Auth::id())->firstOrFail();

            $history = new RouletteHistory();
            $history->member_id     = $member->id;
            $history->restaurant_id = optional($restaurant)->id;           // お気に入りに必須！
            $history->name          = $restaurant->name ?? ($request->get('picked_name') ?: '履歴');
            $history->visited_at    = now();
            // 画面の条件などを criteria に入れたい場合はここで
            // $history->criteria   = [...]; // 必要なら

            $history->save();
        }

        // 外部プロバイダで拾った名称/住所（任意：表示用）
        $pickedName    = $request->get('picked_name');
        $pickedAddress = $request->get('picked_address');

        return view('roulette.result', [
            'restaurant'      => $restaurant,
            'categoryLabel'   => $categoryLabel,
            'pickedCuisineId' => $request->get('cuisine_id'),
            'pickedName'      => $pickedName,
            'pickedAddress'   => $pickedAddress,
        ]);
    }

    /**
     * restaurants を source/source_id で upsert して ID を返す
     * - Google の place_id があれば source=google, source_id=place_id
     * - 無ければ name+address の擬似キーで source=manual
     */
    private function upsertRestaurant(?string $placeId, ?string $name, ?string $address, $lat = null, $lng = null): ?int
    {
        // 何も材料がなければ抜ける
        if (!$placeId && !$name && !$address) {
            return null;
        }

        if ($placeId) {
            $r = Restaurant::firstOrCreate(
                ['source' => 'google', 'source_id' => $placeId],
                [
                    'name'    => $name ?: '不明',
                    'address' => $address,
                    'lat'     => $lat,
                    'lng'     => $lng,
                ]
            );
            return $r->id;
        }

        if ($name && $address) {
            $pseudoId = sha1(mb_strtolower($name).'|'.mb_strtolower($address));
            $r = Restaurant::firstOrCreate(
                ['source' => 'manual', 'source_id' => $pseudoId],
                [
                    'name'    => $name,
                    'address' => $address,
                    'lat'     => $lat,
                    'lng'     => $lng,
                ]
            );
            return $r->id;
        }

        return null;
    }

    
}
