<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\ActivityLogger;

class LoginController extends Controller
{

    public function index()
    {
        return view('login.index', [
            'title' => 'Login'
        ]);
    }

    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|min:8|max:255',
        ]);

        if ($validator->fails()) {
            return back()->with('error', 'Login gagal! Silahkan periksa kembali "username" dan "password" anda!');
        }

        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            if (Auth::user()->status === 'inactive') {
                Auth::logout();
                return back()->with('error', 'Akun anda tidak aktif. Segera hubungi atasan!');
            }

            activity('auth')
                ->causedBy(Auth::user())
                ->withProperties([
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('User berhasil login');

            return redirect()->intended(route('home'));
        }

        return back()->with('error', 'Login gagal! Silahkan periksa kembali "username" dan "password" anda!');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            activity('auth')
                ->causedBy(Auth::user())
                ->withProperties([
                    'ip'         => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log('User berhasil logout');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil logout.');
    }
}
