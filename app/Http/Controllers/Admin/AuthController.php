<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->get(EnsureAdmin::SESSION_KEY)) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate(['password' => ['required', 'string']]);

        $expected = (string) config('momenkita.admin_password');

        if ($expected === '' || ! hash_equals($expected, $request->string('password')->value())) {
            throw ValidationException::withMessages([
                'password' => 'Kata laluan salah.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put(EnsureAdmin::SESSION_KEY, true);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget(EnsureAdmin::SESSION_KEY);
        $request->session()->regenerate();

        return redirect()->route('landing');
    }
}
