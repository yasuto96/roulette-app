<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    // 有効ユーザー一覧
    public function index(Request $request)
    {
        $users = User::select(['id','name','email','created_at','last_login_at'])
            ->orderBy('created_at','desc')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    // 論理削除済み一覧
    public function deleted(Request $request)
    {
        $users = User::onlyTrashed()
            ->select(['id','name','email','created_at','last_login_at','deleted_at'])
            ->orderBy('deleted_at','desc')
            ->paginate(20);

        return view('admin.users.deleted', compact('users'));
    }

    // 詳細（モーダル用：JSON）
    public function show(User $user)
    {
        return response()->json([
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'created_at'    => optional($user->created_at)->toDateTimeString(),
            'last_login_at' => optional($user->last_login_at)->toDateTimeString(),
        ]);
    }

    // 論理削除（users と members をまとめて）
    public function destroy(User $user)
    {
        DB::transaction(function () use ($user) {
            // users→members が 1:1 の前提
            if (method_exists($user, 'member') && $user->member) {
                $user->member->delete(); // SoftDeletes
            }
            $user->delete(); // SoftDeletes
        });

        return back()->with('success', 'ユーザーを削除しました');
    }

    // 復元（まとめて）
    public function restore($id)
    {
        DB::transaction(function () use ($id) {
            if ($member = Member::onlyTrashed()->where('user_id', $id)->first()) {
                $member->restore();
            }
            if ($user = User::onlyTrashed()->find($id)) {
                $user->restore();
            }
        });

        return back()->with('success', 'ユーザーを復元しました。');
    }

    // 完全削除（必要なら）
    public function forceDelete($id)
    {
        DB::transaction(function () use ($id) {
            Member::onlyTrashed()->where('user_id', $id)->forceDelete();
            if ($user = User::onlyTrashed()->find($id)) {
                $user->forceDelete();
            }
        });

        return back()->with('success', 'ユーザーを完全に削除しました。');
    }
}
