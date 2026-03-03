<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Check if user is vendor (by role or vendor_code) and return vendor-specific view
        if ($user->hasRole('vendor') || !empty($user->vendor_code)) {
            return view('vendor.profile.index', [
                'user' => $user,
            ]);
        }

        return view('profile.index', [
            'user' => $user,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'email' => 'nullable|email|max:255',
            'full_name' => 'nullable|string|max:255',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:6|confirmed',
        ]);

        // Update email
        $user->email = $request->input('email') ?: null;

        // Update full name if provided
        if ($request->filled('full_name')) {
            $user->full_name = $request->input('full_name');
        }

        // Update password if provided (ADMIN ONLY)
        if ($request->filled('new_password')) {
            // Only allow admin users to update password
            if (!$user->hasRole('admin')) {
                return back()->with('error', 'Only administrators can change passwords. Please contact your admin for password assistance.');
            }

            if (!$request->filled('current_password') || !Hash::check($request->current_password, $user->password)) {
                return back()->with('error', 'Current password is incorrect.');
            }
            $user->password = $request->new_password; // Will be auto-hashed via cast
        }

        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }
}
