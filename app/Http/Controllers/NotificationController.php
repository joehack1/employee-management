<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function desktopFeed(Request $request)
    {
        $validated = $request->validate([
            'after' => ['required', 'date'],
        ]);

        $after = Carbon::parse($validated['after'])
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');

        $notifications = Auth::user()->notifications()
            ->where('created_at', '>=', $after)
            ->orderBy('created_at')
            ->limit(50)
            ->get(['id', 'data', 'created_at']);

        return response()->json($notifications->map(fn ($notification) => [
            'id' => $notification->id,
            'title' => $notification->data['title'] ?? 'LeaveFlow notification',
            'message' => $notification->data['message'] ?? '',
            'url' => $notification->data['url'] ?? route('notifications.index'),
            'created_at' => $notification->created_at->toISOString(),
        ]));
    }

    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(20);
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function open($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();
        $target = $notification->data['url'] ?? route('notifications.index');
        $targetPath = parse_url($target, PHP_URL_PATH) ?: '/notifications';
        $targetQuery = parse_url($target, PHP_URL_QUERY);

        return redirect($targetPath . ($targetQuery ? '?' . $targetQuery : ''));
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back()->with('success', 'All notifications marked as read.');
    }
}
