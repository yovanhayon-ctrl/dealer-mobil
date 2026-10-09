<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Notifikasi perubahan status (test drive, pengajuan, booking servis) milik user yang login.
 */
class NotificationController extends Controller
{
    public const PER_PAGE = 15;

    public function index(Request $request): View
    {
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
        // Hanya notifikasi milik sendiri; milik user lain = 404.
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        $route = $notification->data['route'] ?? null;

        if (! is_string($route) || ! Route::has($route)) {
            return redirect()->route('account.notifications.index');
        }

        $anchor = $notification->data['anchor'] ?? null;

        return redirect()->to(route($route).(is_string($anchor) ? "#{$anchor}" : ''));
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route('account.notifications.index')
            ->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
