<?php

namespace App\Http\Controllers;

use App\Models\MemberCuisineState;
use App\Models\Cuisine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberCuisineStateController extends Controller
{
    /** 画像どおりの“初回ON”集合（名前一致で解決） */
    private const BASELINE_NAMES = [
        'イタリアン','カフェ','そば','中華','寿司','洋食',
        '焼肉','うどん','カレー','ラーメン','和食','居酒屋','海鮮',
    ];

    /** 現ユーザーの状態一覧を返す（必要なら初期化／自動追加） */
    public function index(Request $req)
    {
        $memberId = $req->user()->id;

        // 既存状態
        $existing = MemberCuisineState::where('member_id', $memberId)->get()
            ->keyBy('cuisine_id');

        if ($existing->isEmpty()) {
            // ---- 初回：固定集合ON／それ以外OFF で一括初期化 ----
            $all = Cuisine::query()->get(['id','name']);
            $baselineIds = $all
                ->whereIn('name', self::BASELINE_NAMES)
                ->pluck('id')->all();

            DB::transaction(function () use ($memberId, $all, $baselineIds) {
                $now = now();
                $rows = [];
                foreach ($all as $c) {
                    $rows[] = [
                        'member_id'  => $memberId,
                        'cuisine_id' => $c->id,
                        'is_checked' => in_array($c->id, $baselineIds, true),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                // 1回で insert
                foreach (array_chunk($rows, 500) as $chunk) {
                    MemberCuisineState::insert($chunk);
                }
            });

            $existing = MemberCuisineState::where('member_id',$memberId)->get()->keyBy('cuisine_id');
        } else {
            // ---- 2回目以降：新規カテゴリを ON で自動追加 ----
            $knownIds = $existing->keys()->all();
            $newOnes  = Cuisine::query()
                ->whereNotIn('id', $knownIds)
                ->get(['id']);
            if ($newOnes->isNotEmpty()) {
                $now = now();
                $rows=[];
                foreach ($newOnes as $c) {
                    $rows[] = [
                        'member_id'=>$memberId,
                        'cuisine_id'=>$c->id,
                        'is_checked'=>true, // 追加はONで保持
                        'created_at'=>$now,
                        'updated_at'=>$now,
                    ];
                }
                MemberCuisineState::insert($rows);
                // 返却用に足しておく
                foreach ($rows as $r) {
                    $existing[$r['cuisine_id']] = (object)$r;
                }
            }
        }

        return response()->json([
            'states' => $existing->values()->map(fn($s)=>[
                'cuisine_id'=>(int)$s->cuisine_id,
                'is_checked'=>(bool)$s->is_checked,
            ]),
        ]);
    }

    /** 1件を即時保存（非同期） */
    public function upsert(Request $req)
    {
        $data = $req->validate([
            'cuisine_id' => ['required','integer','exists:cuisines,id'],
            'is_checked' => ['required','boolean'],
        ]);
        MemberCuisineState::updateOrCreate(
            ['member_id'=>$req->user()->id, 'cuisine_id'=>$data['cuisine_id']],
            ['is_checked'=>$data['is_checked']]
        );
        return response()->json(['ok'=>true]);
    }
}