<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForcePasswordChangeController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        if (! $user->must_change_password && ! $user->isPasswordExpired()) {
            return redirect($this->getRedirectRoute($user));
        }

        return view('auth.force-change-password');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (! $user->must_change_password && ! $user->isPasswordExpired()) {
            return redirect($this->getRedirectRoute($user));
        }

        $request->validate([
            'new_password' => [
                'required',
                'string',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
                'confirmed',
            ],
        ], [
            'new_password' => 'Password must be at least 8 characters and contain uppercase, lowercase, number, and symbol.',
            'new_password.confirmed' => 'Password confirmation does not match.',
        ]);

        $user->forceFill([
            'password' => Hash::make($request->new_password),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        return redirect($this->getRedirectRoute($user))->with('success', 'Your password has been successfully changed.');
    }

    protected function getRedirectRoute($user)
    {
        if ($user && method_exists($user, 'isVendor') && $user->isVendor()) {
            return route('vendor.dashboard');
        }

        if ($user && $user->can('dashboard.view')) {
            return route('dashboard');
        }

        if ($user && $user->can('slots.index')) {
            return route('slots.index');
        }

        return route('profile');
    }
}
