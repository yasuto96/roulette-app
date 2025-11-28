<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        // resources/views/admin/auth/register.blade.php
        return view('admin.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required','string','max:255'],
            'email'        => ['required','string','email','max:255','unique:admins,email'],
            'password'     => ['required','string','min:8','confirmed'], // password_confirmation 必須
            'admin_secret' => ['required','string'],
        ]);

        // .env の ADMIN_REGISTER_SECRET と比較
        $secret = config('app.admin_register_secret', env('ADMIN_REGISTER_SECRET'));
        if ($data['admin_secret'] !== $secret) {
            return back()
                ->withErrors(['admin_secret' => '管理者用パスワードが正しくありません。'])
                ->withInput();
        }

        $admin = Admin::create([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'role_level' => 1,
        ]);

        auth('admin')->login($admin);

        return redirect()->route('admin.dashboard');
    }
}
