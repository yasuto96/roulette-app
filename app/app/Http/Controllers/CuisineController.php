<?php

namespace App\Http\Controllers;

use App\Models\Cuisine;

class CuisineController extends Controller
{
    public function index()
    {
        $cuisines = Cuisine::orderBy('name')->paginate(20);
        return view('cuisines.index', compact('cuisines'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        // 「既存なら使う」にする（重複は作らない）
        $name = trim((string)$request->input('name'));

        $request->validate([
            'name' => 'required|string|max:50', // ← unique は外す
        ]);

        $cuisine = \App\Models\Cuisine::firstOrCreate(['name' => $name]);
        $status  = $cuisine->wasRecentlyCreated ? 201 : 200;

        if ($request->expectsJson() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'id'      => $cuisine->id,
                'name'    => $cuisine->name,
                'created' => $cuisine->wasRecentlyCreated, // 新規作成なら true
            ], $status);
        }

        return back()->with('success', 'カテゴリを追加しました');
    }

    public function destroyMany(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:cuisines,id',
        ]);

        // ピボットを外してから削除（FK制約があればCASCADEでもOK）
        $ids = collect($data['ids'])->unique()->values();
        \App\Models\Cuisine::whereIn('id', $ids)->get()->each(function ($c) {
            if (method_exists($c, 'restaurants')) {
                $c->restaurants()->detach();
            }
            $c->delete();
        });

        return response()->json(['deleted_ids' => $ids], 200);
    }
}
