<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesNotifications;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notifikasi admin: test drive, pengajuan, dan booking servis baru / dibatalkan customer.
 */
class NotificationController extends Controller
{
    use HandlesNotifications;

    public const PER_PAGE = 20;

    public function index(Request $request): View
    {
        return view('admin.notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(self::PER_PAGE),
            'hasUnread' => $request->user()->unreadNotifications()->exists(),
        ]);
    }

    /**
     * Tandai terbaca lalu buka halaman detail admin terkait.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        return $this->openNotification($request, $notification, 'admin.notifications.index');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        return $this->markAllNotificationsAsRead($request, 'admin.notifications.index');
    }
}
