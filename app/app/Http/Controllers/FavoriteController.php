<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Favorite;
use App\Models\Member;
use App\Models\Restaurant;
use App\Models\RouletteHistory;


class FavoriteController extends Controller
{
    public function index()
    {
        $member = \App\Models\Member::where('user_id', \Auth::id())->firstOrFail();

        $favorites = \App\Models\Favorite::with('restaurant')
        ->where('member_id', $member->id)
        ->latest()
        ->paginate(20);

        $count = \App\Models\Favorite::where('member_id', $member->id)->count();

        $restaurantIds = $favorites->pluck('restaurant_id')->all();

        $histories = RouletteHistory::select('restaurant_id', 'my_rating', 'memo', 'visited_at', 'created_at')
            ->where('member_id', $member->id)
            ->whereIn('restaurant_id', $restaurantIds)
            ->orderBy('restaurant_id')
            ->orderByDesc('visited_at')   // 訪問日があれば優先
            ->orderByDesc('created_at')   // 同日なら作成日時で新しい方
            ->get();

        $latestPerRestaurant = $histories->unique('restaurant_id')->keyBy('restaurant_id');

        return view('favorites.index', [
            'favorites'            => $favorites,
            'count'                => $count,
            'latestPerRestaurant'  => $latestPerRestaurant, // ← 追加
        ]);
    }

    public function toggle(Request $request)
    {
        $data = $request->validate([
            'restaurant_id' => ['required','integer','exists:restaurants,id'],
        ]);

        $member = Member::where('user_id', Auth::id())->firstOrFail();

        $fav = Favorite::where('member_id', $member->id)
            ->where('restaurant_id', $data['restaurant_id'])
            ->first();

        $favorited = false;

        if ($fav) {
            // OFF: 削除
            $fav->delete();
            // 任意: 店舗のお気に入り数カウンタを下げたい場合
            // Restaurant::where('id', $data['restaurant_id'])->decrement('favorites_count');
        } else {
            // ON: 追加
            Favorite::create([
                'member_id'     => $member->id,
                'restaurant_id' => $data['restaurant_id'],
            ]);
            $favorited = true;
            // 任意: カウンタを上げたい場合
            // Restaurant::where('id', $data['restaurant_id'])->increment('favorites_count');
        }

        return response()->json(['favorited' => $favorited]);
    }
}
