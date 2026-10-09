<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Buka & tandai-baca notifikasi milik user yang login (dipakai halaman Notifikasi customer dan admin).
 * Tujuan diambil dari data notifikasi: nama route + parameter (opsional) + anchor (opsional).
 */
trait HandlesNotifications
{
    protected function openNotification(Request $request, string $id, string $fallbackRoute): RedirectResponse
    {
        // Hanya notifikasi milik sendiri; milik user lain = 404.
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $data = $notification->data;
        $route = $data['route'] ?? null;
        $params = is_array($data['params'] ?? null) ? $data['params'] : [];

        if (! is_string($route) || ! Route::has($route)) {
            return redirect()->route($fallbackRoute);
        }

        $anchor = $data['anchor'] ?? null;

        return redirect()->to(route($route, $params).(is_string($anchor) ? "#{$anchor}" : ''));
    }

    protected function markAllNotificationsAsRead(Request $request, string $indexRoute): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route($indexRoute)->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
