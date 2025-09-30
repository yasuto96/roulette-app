<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\RouletteHistory;

class RouletteHistoryController extends Controller
{
    public function index()
    {
        $histories = RouletteHistory::with(['restaurant','cuisine'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return view('histories.index', compact('histories'));
    }
}
