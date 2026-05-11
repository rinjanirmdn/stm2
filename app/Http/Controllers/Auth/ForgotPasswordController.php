<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /**
     * Token validity in minutes.
     */
    private const TOKEN_LIFETIME_MINUTES = 60;

    public function showForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetEmail(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        $login = $request->input('login');
        $reason = $request->input('reason');

        // Find user by login field
        $user = null;
        $fields = ['email', 'username', 'nik'];

        foreach ($fields as $field) {
            $user = User::where($field, $login)->first();
            if ($user) {
                break;
            }
        }

        if (! $user) {
            return back()->with('error', 'User not found with the provided Email/Username/NIK.');
        }

        // ── Admin: send direct reset link to their own email ──
        if ($user->can('auth.direct_reset')) {
            return $this->sendAdminResetLink($user, $reason);
        }

        // ── Non-admin: send request to administrator (original flow) ──
        $resetFlagKey = 'password_reset_requested_user_'.(int) $user->id;

        // Get admin email
        $adminEmail = config('mail.admin_email', 'admin@example.com');

        $appName = 'e-Docking Control System';
        $companyName = 'PT Oneject Indonesia';
        $logoUrl = url('/img/e-Docking Control System.png');
        $adminEditUrl = route('users.edit', ['userId' => $user->id, 'from_reset_email' => 1]);

        try {
            $html = view('emails.password-reset-request-admin', [
                'appName' => $appName,
                'companyName' => $companyName,
                'logoUrl' => $logoUrl,
                'user' => $user,
                'reason' => $reason,
                'adminEditUrl' => $adminEditUrl,
            ])->render();

            // Send email to admin using the shared HTML template
            Mail::html($html, function ($message) use ($adminEmail, $user, $appName) {
                $message->to($adminEmail)
                    ->subject('['.$appName.'] Password Reset Request - '.($user->full_name ?? $user->username ?? 'Vendor'));
            });

            $resetFlagKey = 'password_reset_requested_user_'.(int) $user->id;
            Cache::put($resetFlagKey, now()->toDateTimeString(), now()->addHours(6));

            // Store request to prevent spam
            $requestKey = 'password_reset_request_'.strtolower($login);
            Cache::put($requestKey, now(), now()->addHours(1));

            // Mark that this user has an active reset request
            Cache::put($resetFlagKey, now()->toDateTimeString(), now()->addHours(6));

            return back()->with('success', 'Password reset request sent to administrator. You will be contacted shortly.');
        } catch (\Exception $e) {
            Log::error('Password reset request (forgot-password form) failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->with('error', 'Failed to send reset request. Please try again later. Error: '.$e->getMessage());
        }
    }

    /**
     * Send a direct password reset link to the admin's own email.
     */
    private function sendAdminResetLink(User $user, string $reason)
    {
        $email = $user->email;

        if (empty($email)) {
            return back()->with('error', 'Admin account does not have an email address configured. Please contact system support.');
        }

        // Rate limit: one request per 5 minutes per admin
        $rateLimitKey = 'admin_reset_link_'.(int) $user->id;
        if (Cache::has($rateLimitKey)) {
            return back()->with('error', 'A reset link was already sent recently. Please check your email or wait a few minutes before trying again.');
        }

        try {
            // Generate a secure token
            $plainToken = Str::random(64);
            $hashedToken = Hash::make($plainToken);

            // Store (upsert) the token
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => $hashedToken,
                    'created_at' => now(),
                ]
            );

            $appName = 'e-Docking Control System';
            $companyName = 'PT Oneject Indonesia';

            $resetUrl = url('/reset-password?'.http_build_query([
                'token' => $plainToken,
                'email' => $email,
            ]));

            $html = view('emails.password-reset-link-admin', [
                'appName' => $appName,
                'companyName' => $companyName,
                'userName' => $user->full_name ?? $user->username ?? 'Administrator',
                'resetUrl' => $resetUrl,
                'expiryMinutes' => self::TOKEN_LIFETIME_MINUTES,
            ])->render();

            Mail::html($html, function ($message) use ($email, $user, $appName) {
                $message->to($email, $user->full_name ?? 'Administrator')
                    ->subject('['.$appName.'] Reset Your Password');
            });

            // Rate limit: 5 minutes
            Cache::put($rateLimitKey, true, now()->addMinutes(5));

            Log::info('Admin password reset link sent', [
                'user_id' => $user->id,
                'email' => $email,
            ]);

            return back()->with('success', 'A password reset link has been sent to your registered email address. The link will expire in '.self::TOKEN_LIFETIME_MINUTES.' minutes.');
        } catch (\Exception $e) {
            Log::error('Admin password reset link failed: '.$e->getMessage(), [
                'user_id' => $user->id,
                'exception' => $e,
            ]);

            return back()->with('error', 'Failed to send reset link. Please try again later.');
        }
    }

    /**
     * Handle password reset request from authenticated profile page.
     */
    public function requestFromProfile(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->can('profile.change_password')) {
            return back()->with('error', 'Administrator dapat mengganti password langsung dari form profile.');
        }

        $adminEmail = config('mail.admin_email', 'admin@example.com');

        $reason = 'Password change requested from profile page.';

        // Use explicit application name for branding in emails
        $appName = 'e-Docking Control System';
        $companyName = 'PT Oneject Indonesia';
        $logoUrl = url('/img/e-Docking Control System.png');
        $adminEditUrl = route('users.edit', ['userId' => $user->id, 'from_reset_email' => 1]);

        try {
            $html = view('emails.password-reset-request-admin', [
                'appName' => $appName,
                'companyName' => $companyName,
                'logoUrl' => $logoUrl,
                'user' => $user,
                'reason' => $reason,
                'adminEditUrl' => $adminEditUrl,
            ])->render();

            Mail::html($html, function ($message) use ($adminEmail, $user, $appName) {
                $message->to($adminEmail)
                    ->subject('['.$appName.'] Password Reset Request - '.($user->full_name ?? $user->username ?? 'Vendor'));
            });

            return back()->with('success', 'Password reset request sent to administrator. You will be contacted shortly.');
        } catch (\Exception $e) {
            Log::error('Password reset request (from profile) failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->with('error', 'Failed to send reset request. Please try again later. Error: '.$e->getMessage());
        }
    }
}
