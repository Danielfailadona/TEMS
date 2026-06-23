<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = SystemNotification::forUser(auth()->user())
            ->latest()
            ->paginate(10);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(SystemNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->update(['is_read' => true, 'read_at' => now()]);

        return back();
    }

    public function markAllAsRead(): RedirectResponse
    {
        SystemNotification::forUser(auth()->user())
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(SystemNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->delete();

        return back()->with('success', 'Notification removed.');
    }

    public function unreadCount(): JsonResponse
    {
        $count = SystemNotification::forUser(auth()->user())->unread()->count();

        return response()->json(['count' => $count]);
    }

    public function latest(): JsonResponse
    {
        $notifications = SystemNotification::forUser(auth()->user())
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'message' => \Illuminate\Support\Str::limit($n->message, 80),
                'is_read' => $n->is_read,
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        return response()->json($notifications);
    }
}
