<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // resources/views/admin/dashboard.blade.php
        return view('admin.dashboard');
    }
}
