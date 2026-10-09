<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Concerns\HandlesNotifications;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notifikasi perubahan status (test drive, pengajuan, booking servis) milik customer yang login.
 */
class NotificationController extends Controller
{
    use HandlesNotifications;

    public const PER_PAGE = 15;

    public function index(Request $request): View|RedirectResponse
    {
        // Admin punya halaman notifikasi sendiri dengan layout admin.
        if ($request->user()->isAdmin()) {
            return redirect()->route('admin.notifications.index');
        }

        $notifications = $request->user()->notifications()->paginate(self::PER_PAGE);

        return view('pages.account.notifications.index', [
            'notifications' => $notifications,
            'hasUnread' => $request->user()->unreadNotifications()->exists(),
        ]);
    }

    /**
     * Tandai terbaca lalu buka riwayat terkait (langsung ke kartu datanya lewat anchor).
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        return $this->openNotification($request, $notification, 'account.notifications.index');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        return $this->markAllNotificationsAsRead($request, 'account.notifications.index');
    }
}
