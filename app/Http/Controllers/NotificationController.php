<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        $url = $record->data['url'] ?? route('notifications.index');
        $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;
        if (! is_string($url) || str_starts_with($url, '//') || ($host !== null && ! hash_equals($request->getHost(), $host))) {
            $url = route('notifications.index');
        }

        return redirect()->to($url);
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'ყველა შეტყობინება წაკითხულად მოინიშნა.');
    }
}
