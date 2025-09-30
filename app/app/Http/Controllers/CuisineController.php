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
}
