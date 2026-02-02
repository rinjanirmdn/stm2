<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()
                ->withErrors(['email' => 'Email atau password salah'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();
        // Check active status removed as column does not exist
        /*
        if ($user && array_key_exists('is_active', $user->getAttributes()) && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'Akun Anda tidak aktif. Silakan hubungi administrator.'])
                ->onlyInput('email');
        }
        */

        if ($user) {
            try {
                // Role sync logic removed/disabled as role columns do not exist
            } catch (\Throwable $e) {
            }
        }

        if ($user->hasRole('vendor')) {
            return redirect()->intended(route('vendor.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
