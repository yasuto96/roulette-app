<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Favorite;

class FavoriteController extends Controller
{
    public function index()
    {
        $favorites = Favorite::with('restaurant')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return view('favorites.index', compact('favorites'));
    }
}
