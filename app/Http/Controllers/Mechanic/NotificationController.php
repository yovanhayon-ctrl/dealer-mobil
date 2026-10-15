<?php

namespace App\Http\Controllers\Mechanic;

use App\Http\Controllers\Concerns\HandlesNotifications;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notifikasi mekanik: pekerjaan servis baru yang ditugaskan (MechanicJobAssigned).
 */
class NotificationController extends Controller
{
    use HandlesNotifications;

    public const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(self::PER_PAGE);

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            // Status saat ditugaskan; status terkini ada di halaman pekerjaan.
            'badges' => $notifications->getCollection()
                ->mapWithKeys(fn ($notification) => [$notification->id => isset($notification->data['status'])
                    ? [['status' => $notification->data['status'], 'label' => null]]
                    : []])
                ->all(),
            'hasUnread' => $request->user()->unreadNotifications()->exists(),
            'routePrefix' => 'mechanic.notifications',
            'intro' => 'Pekerjaan servis baru yang ditugaskan kepada Anda.',
            'emptyMessage' => 'Notifikasi muncul saat ada booking servis yang ditugaskan kepada Anda.',
        ]);
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        return $this->openNotification($request, $notification, 'mechanic.notifications.index');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        return $this->markAllNotificationsAsRead($request, 'mechanic.notifications.index');
    }
}
