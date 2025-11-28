<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        // resources/views/admin/auth/login.blade.php
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        if (Auth::guard('admin')->attempt($data, $request->filled('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.users.index'));
        }

        return back()
            ->withErrors(['email' => 'メールアドレスまたはパスワードが違います。'])
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
