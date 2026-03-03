<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
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
            $user = \App\Models\User::where($field, $login)->first();
            if ($user) {
                break;
            }
        }

        if (!$user) {
            return back()->with('error', 'User not found with the provided Email/Username/NIK.');
        }

        // Get admin email
        $adminEmail = config('mail.admin_email', 'admin@example.com');

        $appName = 'e-Docking Control System';
        $companyName = 'PT Oneject Indonesia';
        $logoUrl = url('/img/e-Docking Control System.png');
        $adminEditUrl = route('users.edit', $user->id);

        try {
            $html = view('emails.password-reset-request-admin', [
                'appName'      => $appName,
                'companyName'  => $companyName,
                'logoUrl'      => $logoUrl,
                'user'         => $user,
                'reason'       => $reason,
                'adminEditUrl' => $adminEditUrl,
            ])->render();

            // Send email to admin using the shared HTML template
            Mail::html($html, function ($message) use ($adminEmail, $user, $appName) {
                $message->to($adminEmail)
                        ->subject('[' . $appName . '] Password Reset Request - ' . ($user->full_name ?? $user->username ?? 'Vendor'));
            });

            // Store request to prevent spam
            $requestKey = 'password_reset_request_' . strtolower($login);
            Cache::put($requestKey, now(), now()->addHours(1));

            return back()->with('success', 'Password reset request sent to administrator. You will be contacted shortly.');

        } catch (\Exception $e) {
            Log::error('Password reset request (forgot-password form) failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->with('error', 'Failed to send reset request. Please try again later. Error: ' . $e->getMessage());
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

        $adminEmail = config('mail.admin_email', 'admin@example.com');

        $reason = 'Password change requested from profile page.';

        // Use explicit application name for branding in emails
        $appName = 'e-Docking Control System';
        $companyName = 'PT Oneject Indonesia';
        $logoUrl = url('/img/e-Docking Control System.png');
        $adminEditUrl = route('users.edit', $user->id);

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
                        ->subject('[' . $appName . '] Password Reset Request - ' . ($user->full_name ?? $user->username ?? 'Vendor'));
            });

            return back()->with('success', 'Password reset request sent to administrator. You will be contacted shortly.');

        } catch (\Exception $e) {
            Log::error('Password reset request (from profile) failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->with('error', 'Failed to send reset request. Please try again later. Error: ' . $e->getMessage());
        }
    }
}
