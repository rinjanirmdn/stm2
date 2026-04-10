<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ResetPasswordController extends Controller
{
    /**
     * Token validity in minutes.
     */
    private const TOKEN_LIFETIME_MINUTES = 60;

    /**
     * Show the password reset form.
     */
    public function showResetForm(Request $request)
    {
        $token = $request->query('token', '');
        $email = $request->query('email', '');

        if ($token === '' || $email === '') {
            return redirect()->route('login')->with('error', 'Invalid password reset link.');
        }

        // Validate token exists and is not expired
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record) {
            return redirect()->route('forgot-password')->with('error', 'Invalid or expired password reset link. Please submit a new request.');
        }

        if (!Hash::check($token, $record->token)) {
            return redirect()->route('forgot-password')->with('error', 'Invalid or expired password reset link. Please submit a new request.');
        }

        // Check expiry
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(self::TOKEN_LIFETIME_MINUTES)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return redirect()->route('forgot-password')->with('error', 'Password reset link has expired. Please submit a new request.');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Process the password reset.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $email = $request->input('email');
        $token = $request->input('token');

        // Validate token
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record) {
            return back()->with('error', 'Invalid or expired password reset token.');
        }

        if (!Hash::check($token, $record->token)) {
            return back()->with('error', 'Invalid or expired password reset token.');
        }

        // Check expiry
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(self::TOKEN_LIFETIME_MINUTES)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return redirect()->route('forgot-password')->with('error', 'Password reset link has expired. Please submit a new request.');
        }

        // Find user
        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->with('error', 'User not found.');
        }

        // Update password
        $user->update([
            'password' => $request->input('password'), // auto-hashed via cast
        ]);

        // Also clear lock and must_change_password flags
        DB::table('md_users')->where('id', $user->id)->update([
            'is_locked' => false,
            'must_change_password' => false,
        ]);

        // Clear login lockout cache
        $identifiers = [
            strtolower(trim($user->email ?? '')),
            strtolower(trim($user->nik ?? '')),
            strtolower(trim($user->username ?? '')),
        ];
        foreach (array_filter(array_unique($identifiers)) as $identifier) {
            Cache::forget('login_attempts_'.$identifier);
        }

        // Delete the used token
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        Log::info('Admin password reset completed via email token', [
            'user_id' => $user->id,
            'email' => $email,
        ]);

        return redirect()->route('login')->with('success', 'Your password has been reset successfully. Please sign in with your new password.');
    }
}
