<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;

class RestaurantController extends Controller
{
    public function index()
    {
        // 必要なら with('cuisines') でリレーションも同時取得
        $restaurants = Restaurant::query()->latest('id')->paginate(20);
        return view('restaurants.index', compact('restaurants'));
    }
}
