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
        if (! $user->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.force-change-password');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        
        if (! $user->must_change_password) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user->forceFill([
            'password' => Hash::make($request->new_password),
            'must_change_password' => false,
        ])->save();

        return redirect()->route('dashboard')->with('success', 'Your password has been successfully changed.');
    }
}
