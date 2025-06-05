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

            // ActivityLogger::log(
            //     'login',
            //     'Admin ' . Auth::user()->name . ' berhasil login.'
            // );

            return redirect()->intended('/home');
        }

        return back()->with('error', 'Login gagal! Silahkan periksa kembali "username" dan "password" anda!');
    }
}
