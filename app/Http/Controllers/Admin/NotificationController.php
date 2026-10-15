<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesNotifications;
use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Models\Testimonial;
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
     * Jenis data notifikasi => model, untuk menampilkan status terkini (bukan status saat notifikasi dibuat).
     *
     * @var array<string, class-string<Model>>
     */
    private const RECORDS = [
        'PurchaseRequest' => PurchaseRequest::class,
        'TestDrive' => TestDrive::class,
        'ServiceBooking' => ServiceBooking::class,
        'Testimonial' => Testimonial::class,
    ];

    /**
     * Notifikasi lama belum menyimpan "record": jenis datanya dikenali dari route detail.
     *
     * @var array<string, string>
     */
    private const LEGACY_ROUTES = [
        'admin.purchase-requests.show' => 'PurchaseRequest',
        'admin.test-drives.show' => 'TestDrive',
        'admin.service-bookings.show' => 'ServiceBooking',
    ];

    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->paginate(self::PER_PAGE);

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'badges' => $this->statusBadges($notifications),
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
     * Badge status per notifikasi (kunci: id notifikasi) dari data terkininya; satu query per jenis data.
     * Data sudah terhapus: status yang tersimpan. Data tidak dikenali (notifikasi ulasan lama tanpa id): tanpa badge,
     * agar tidak menampilkan status yang sudah usang.
     *
     * @return array<string, list<array{status: string, label: ?string}>>
     */
    private function statusBadges(LengthAwarePaginator $notifications): array
    {
        $targets = $notifications->getCollection()
            ->mapWithKeys(fn ($notification) => [$notification->id => $this->target($notification->data)])
            ->filter();

        $records = $targets->groupBy('type')->map(function ($items, string $type) {
            $query = self::RECORDS[$type]::query()->whereKey($items->pluck('id')->unique()->all());

            if ($type === 'PurchaseRequest') {
                $query->with(['paymentProofs:id,purchase_request_id,amount,status,created_at', 'paymentRefunds:id,purchase_request_id,amount']);
            }

            return $query->get()->keyBy(fn (Model $record) => $record->getKey());
        });

        $badges = [];

        foreach ($notifications as $notification) {
            $target = $targets[$notification->id] ?? null;

            if ($target === null) {
                continue;
            }

            $record = $records[$target['type']][$target['id']] ?? null;

            if ($record === null) {
                $badges[$notification->id] = isset($notification->data['status'])
                    ? [['status' => $notification->data['status'], 'label' => null]]
                    : [];

                continue;
            }

            $badges[$notification->id] = [['status' => $record->status, 'label' => null]];

            if ($record instanceof PurchaseRequest && $record->hasPaymentSection()) {
                $badges[$notification->id][] = ['status' => $record->paymentStatusBadge(), 'label' => $record->paymentStatusLabel()];
            }
        }

        return $badges;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{type: string, id: int}|null
     */
    private function target(array $data): ?array
    {
        $type = $data['record']['type'] ?? self::LEGACY_ROUTES[$data['route'] ?? ''] ?? null;
        $id = $data['record']['id'] ?? $data['params'][0] ?? null;

        return isset(self::RECORDS[$type]) && is_numeric($id) ? ['type' => $type, 'id' => (int) $id] : null;
    }
}
