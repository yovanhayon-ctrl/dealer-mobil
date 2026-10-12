<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesNotifications;
use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\ServiceBooking;
use App\Models\TestDrive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Notifikasi admin: test drive, pengajuan, dan booking servis baru / dibatalkan customer.
 */
class NotificationController extends Controller
{
    use HandlesNotifications;

    public const PER_PAGE = 20;

    /**
     * Route detail di data notifikasi => model, untuk menampilkan status terkini (bukan status saat notifikasi dibuat).
     *
     * @var array<string, class-string<Model>>
     */
    private const RECORDS = [
        'admin.purchase-requests.show' => PurchaseRequest::class,
        'admin.test-drives.show' => TestDrive::class,
        'admin.service-bookings.show' => ServiceBooking::class,
    ];

    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(self::PER_PAGE);

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'records' => $this->currentRecords($notifications),
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

    /**
     * Data terkait notifikasi di halaman ini, satu query per jenis. Kunci: "{route}:{id}".
     * Data yang sudah terhapus tidak ada di hasil; tampilannya memakai status yang tersimpan di notifikasi.
     *
     * @return array<string, Model>
     */
    private function currentRecords(LengthAwarePaginator $notifications): array
    {
        $records = [];

        $notifications->getCollection()
            ->map(fn ($notification) => $notification->data)
            ->filter(fn (array $data) => isset(self::RECORDS[$data['route'] ?? '']) && is_numeric($data['params'][0] ?? null))
            ->groupBy('route')
            ->each(function ($items, string $route) use (&$records) {
                $query = self::RECORDS[$route]::query()->whereKey($items->pluck('params.0')->unique()->all());

                if ($route === 'admin.purchase-requests.show') {
                    $query->with('paymentProofs:id,purchase_request_id,amount,status,created_at');
                }

                foreach ($query->get() as $record) {
                    $records["{$route}:{$record->getKey()}"] = $record;
                }
            });

        return $records;
    }
}
