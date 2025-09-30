<?php

namespace App\Http\Controllers;

class TopController extends Controller
{
    public function index()
    {
        // 画面名は  resources/views/index.blade.php に合わせました
        // もし top.blade.php を使うなら 'top' に変えてください
        return view('index');
    }
}
