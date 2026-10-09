<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
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
