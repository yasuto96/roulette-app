<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersExport;

class UserReportController extends Controller
{
    // /admin/users/stats?month=2025-10
    public function stats(Request $request)
    {
        // 全ユーザー数 / 全メンバー数（削除済み含む）
        $totalUsers   = User::withTrashed()->count();
        $totalMembers = Member::withTrashed()->count(); // members も SoftDeletes 対応の場合

        // 選択月（YYYY-MM）
        $month = $request->query('month');
        $monthly = null;

        if ($month) {
            // 2025-10 → 2025-10-01 00:00:00 ～ 2025-11-01 00:00:00
            try {
                $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $end   = (clone $start)->addMonth(); // end は [start, end)
                $monthly = User::withTrashed()
                    ->where('created_at', '>=', $start)
                    ->where('created_at', '<',  $end)
                    ->count();
            } catch (\Exception $e) {
                $monthly = 0;
            }
        }

        return response()->json([
            'total_users'   => $totalUsers,
            'total_members' => $totalMembers,
            'monthly'       => $monthly, // null のままもあり
        ]);
    }

    // /admin/users/export?from=2025-09&to=2025-10  または  ?scope=all
    public function export(Request $request)
    {
        $params = [
            'scope' => $request->query('scope'),
            'from'  => $request->query('from'),
            'to'    => $request->query('to'),
        ];

        return Excel::download(new UsersExport($params), 'users.xlsx');
    }
}
