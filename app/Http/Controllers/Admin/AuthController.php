<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        if (! $this->passwordMatches($request->string('password')->value())) {
            throw ValidationException::withMessages([
                'password' => 'Kata laluan salah.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put(EnsureAdmin::SESSION_KEY, true);

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Cincangan diutamakan. Teks biasa kekal sebagai sandaran supaya
     * pemasangan sedia ada tidak terkunci di luar, tetapi ia dibandingkan
     * dengan hash_equals supaya masa tindak balas tidak membocorkan aksara
     * demi aksara.
     */
    private function passwordMatches(string $given): bool
    {
        $hash = (string) config('momenkita.admin_password_hash');

        if ($hash !== '') {
            return Hash::check($given, $hash);
        }

        $plain = (string) config('momenkita.admin_password');

        return $plain !== '' && hash_equals($plain, $given);
    }

    public function logout(Request $request)
    {
        $request->session()->forget(EnsureAdmin::SESSION_KEY);
        $request->session()->regenerate();

        return redirect()->route('landing');
    }
}
