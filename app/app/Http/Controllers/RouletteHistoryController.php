<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Member;
use App\Models\RouletteHistory;
use App\Models\Restaurant;

class RouletteHistoryController extends Controller
{
    public function index()
    {
        // users.id → members.id を取得（無ければ自動作成）
        $u = Auth::user();
        $member = Member::firstOrCreate(
            ['user_id' => $u->id],
            [
                'user_id'  => $u->id,
                'name'     => $u->name ?? '非公開',
                'email'    => $u->email ?? null,
                'password' => $u->password, // membersで必須なら
            ]
        );
        $memberId = $member->id;

            $histories = \App\Models\RouletteHistory::where('member_id', $memberId)
            ->orderByDesc('id')
            // ★列絞り込みをやめるか、必要な列を全部入れる
            // ->paginate(20);

            // もし select を使うなら↓のように
            ->select([
                'id','member_id','restaurant_id', // ←これが大事
                'name','criteria',
                'visited_at','my_rating','memo',  // 画面で使ってる列も
                'created_at'
            ])
            ->paginate(20);

        // お気に入り状態を初期反映したい場合
        $favoriteIds = \App\Models\Favorite::where('member_id', $member->id)
                        ->pluck('restaurant_id')->all();

        return view('histories.index', compact('histories','favoriteIds'));
    }

    public function update(Request $request, RouletteHistory $history)
    {
        // 所有チェック
        $u = Auth::user();
        $member = Member::where('user_id',$u->id)->firstOrFail();
        abort_unless($history->member_id === $member->id, 403);

        $data = $request->validate([
            'visited_at' => ['nullable','date'],
            'my_rating'  => ['nullable','integer','min:1','max:5'],
            'memo'       => ['nullable','string','max:2000'],
        ]);

        $history->fill($data)->save();

        return response()->json([
            'ok' => true,
            'row' => [
                'id'         => $history->id,
                'visited_at' => optional($history->visited_at)->format('Y-m-d H:i'),
                'my_rating'  => $history->my_rating,
                'memo'       => $history->memo,
            ],
        ]);
    }
    public function destroy(RouletteHistory $history)
    {
        $u = Auth::user();
        $member = Member::where('user_id',$u->id)->firstOrFail();
        abort_unless($history->member_id === $member->id, 403);

        $history->delete();
        return redirect()->route('histories.index')->with('success', '削除しました');
    }
    public function toggleFavorite(Request $request)
    {
        $request->validate(['restaurant_id'=>['required','integer','exists:restaurants,id']]);

        $u = Auth::user();
        $memberId = Member::where('user_id',$u->id)->value('id');

        $fav = \App\Models\Favorite::where('member_id',$memberId)
                ->where('restaurant_id',$request->restaurant_id)->first();

        if ($fav) { $fav->delete(); $isFav = false; }
        else { \App\Models\Favorite::create(['member_id'=>$memberId,'restaurant_id'=>$request->restaurant_id]); $isFav = true; }

        return response()->json(['ok'=>true,'favorited'=>$isFav]);
    }




    public function store(Request $request)
    {
        $request->validate([
            'name'     => ['nullable','string','max:50'],
            'criteria' => ['required'], // JSON
        ]);

        // 会員を取得/作成
        $u = Auth::user();
        $member = Member::firstOrCreate(
            ['user_id' => $u->id],
            [
                'user_id'  => $u->id,
                'name'     => $u->name ?? '非公開',
                'email'    => $u->email ?? null,
                'password' => $u->password,
            ]
        );

        // criteria から restaurant を upsert→ID取得
        $crit = json_decode($request->input('criteria'), true) ?? [];
        $rid  = $this->findOrCreateRestaurantFromCriteria($crit['restaurant'] ?? null, $request->input('name'));

        // 履歴は“1回だけ”作成
        $history = new RouletteHistory();
        $history->member_id     = $member->id;
        $history->name          = Str::limit($request->input('name','履歴'), 50, '');
        $history->criteria      = $request->input('criteria');
        $history->visited_at    = now();           // 任意
        if ($rid) $history->restaurant_id = $rid;  // あればセット
        $history->save();

        return redirect()->route('histories.index')->with('success', '履歴に追加しました。');
    }
    public function visit(Request $request)
    {
        $data = $request->validate([
            'restaurant_id' => ['required','integer','exists:restaurants,id'],
            'criteria'      => ['nullable','array'],
            'seed'          => ['nullable','string','max:64'],
        ]);

        // ここも members.id を取得して使う
        $u = Auth::user();
        $member = Member::firstOrCreate(
            ['user_id' => $u->id],
            [
                'user_id'  => $u->id,
                'name'     => $u->name ?? '非公開',
                'email'    => $u->email ?? null,
                'password' => $u->password,
            ]
        );

        $history = new RouletteHistory();
        $history->member_id     = $member->id;
        $history->restaurant_id = $data['restaurant_id'];
        $history->mode          = 'restaurant';
        $history->criteria      = $data['criteria'] ?? null;
        $history->seed          = $data['seed'] ?? null;
        $history->save();

        // criteria に place 情報があるのに restaurant_id が空なら補完
        if (empty($history->restaurant_id) && !empty($data['criteria']['restaurant'])) {
            $rid = $this->findOrCreateRestaurantFromCriteria($data['criteria']['restaurant'], $history->name);
            if ($rid) {
                $history->restaurant_id = $rid;
                $history->save();
            }
        }

        return response()->json(['ok' => true, 'id' => $history->id]);
    }

    private function findOrCreateRestaurantFromCriteria(?array $r, ?string $fallbackName = null): ?int
    {
        if (!$r) return null;

        $placeId = $r['place_id'] ?? null;

        // Google Place の ID があれば、source/source_id で upsert
        if ($placeId) {
            $restaurant = \App\Models\Restaurant::firstOrCreate(
                ['source' => 'google', 'source_id' => $placeId],
                [
                    'name'    => $r['name']    ?? ($fallbackName ?? '不明'),
                    'address' => $r['address'] ?? null,
                    'lat'     => $r['lat']     ?? null,
                    'lng'     => $r['lng']     ?? null,
                ]
            );
            return $restaurant->id;
        }

        // place_id が無いケースのフォールバック（名前+住所で擬似キー）
        if (!empty($r['name']) && !empty($r['address'])) {
            $pseudoId = sha1(($r['name'] ?? '').'|'.($r['address'] ?? ''));
            $restaurant = \App\Models\Restaurant::firstOrCreate(
                ['source' => 'manual', 'source_id' => $pseudoId],
                [
                    'name'    => $r['name'],
                    'address' => $r['address'],
                    'lat'     => $r['lat'] ?? null,
                    'lng'     => $r['lng'] ?? null,
                ]
            );
            return $restaurant->id;
        }

        return null;
    }
}
