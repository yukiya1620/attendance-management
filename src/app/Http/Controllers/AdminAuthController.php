<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスの形式で入力してください',
            'password.required' => 'パスワードを入力してください',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'ログイン情報が登録されていません'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        if (Auth::user()->role !== 'admin') {
            Auth::logout();

            return back()
                ->withErrors(['email' => '管理者ではありません'])
                ->onlyInput('email');
        }

        return redirect()->route('attendance.list');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}