<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        
        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Clear all notifications for current user.
     */
    public function clearAll()
    {
        auth()->user()->notifications()->delete();
        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications cleared.');
    }

    /**
     * Get latest notification for popup polling
     */
    public function latest(Request $request)
    {
        $notification = $request->user()
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $notification) {
            return response()->json(['success' => true, 'notification' => null]);
        }

        return response()->json([
            'success' => true,
            'notification' => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'message' => $notification->data['message'] ?? '',
                'action_url' => $notification->data['action_url'] ?? '#',
                'created_at' => $notification->created_at?->toIso8601String(),
            ],
        ]);
    }
}
