<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cuisine;
use App\Models\Restaurant;
use App\Models\RouletteHistory;
use App\Services\StoreFinder\StoreFinder;

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

    /** 会員限定：検索ルーレット（抽選） */
    public function searchSpin(Request $request)
    {
        $data = $request->validate([
            'keyword'    => 'nullable|string|max:100',
            'cuisine_id' => 'nullable|integer|exists:cuisines,id',
        ]);

        // プロバイダ用ヒントとしてカテゴリ名も渡す
        if (!empty($data['cuisine_id'])) {
            $cuisine = Cuisine::find($data['cuisine_id']);
            $data['cuisine_name'] = $cuisine ? $cuisine->name : null;
        }

        // プロバイダ（local/後でgoogleに切替）から候補取得→ランダム1件
        $candidates = $this->finder->search($data);
        $picked = $candidates->isNotEmpty() ? $candidates->random() : null;

        // ローカルDBの店なら id を結果へ
        if ($picked && isset($picked['provider']) && $picked['provider'] === 'local' && !empty($picked['id'])) {
            return redirect()->route('roulette.result', [
                'id'         => $picked['id'],
                'src'        => 'search',
                'cuisine_id' => isset($data['cuisine_id']) ? $data['cuisine_id'] : null,
            ]);
        }

        // 外部結果（Google等）は名称/住所だけで表示
        return redirect()->route('roulette.result', [
            'src'            => 'search',
            'picked_name'    => $picked['name']    ?? null,
            'picked_address' => $picked['address'] ?? null,
            'place_id'       => $picked['place_id'] ?? null,
            'provider'       => $picked['provider'] ?? null,
            'cuisine_id'     => $data['cuisine_id'] ?? null,
        ]);
    }

    /** 共通：結果表示＆（検索ルーレットのみ）履歴保存 */
    public function result(Request $request)
    {
        // ローカル店（id指定）が来たときだけ取得
        $restaurant = $request->filled('id')
            ? Restaurant::with('cuisines')->find($request->id)
            : null;

        // 表示用カテゴリ名（クエリ優先→IDから補完）
        $categoryLabel = $request->get('cuisine_name');
        if (!$categoryLabel && $request->filled('cuisine_id')) {
            $c = Cuisine::find($request->cuisine_id);
            $categoryLabel = $c ? $c->name : null;
        }

        // 検索ルーレット経由 かつ ローカル店がある時のみ履歴保存
        if ($restaurant && $request->get('src') === 'search' && Auth::check()) {
            $history = new RouletteHistory();
            $history->user_id       = Auth::id();
            $history->restaurant_id = $restaurant->id;
            if ($request->filled('cuisine_id')) {
                $history->cuisine_id = (int) $request->cuisine_id;
            }
            $history->save();
        }

        // 外部プロバイダで拾った名称/住所（任意）
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
}
