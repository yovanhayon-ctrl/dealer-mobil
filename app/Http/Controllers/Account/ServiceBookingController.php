<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Account\Concerns\FiltersByStatusGroup;
use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use App\Notifications\Admin\AdminActivityNotification;
use App\Notifications\Admin\ServiceBookingActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Riwayat servis milik customer yang login + pembatalan saat status pending.
 */
class ServiceBookingController extends Controller
{
    use FiltersByStatusGroup;

    public const PER_PAGE = 10;

    /** Kelompok penyaring status di halaman riwayat. */
    public const STATUS_GROUPS = [
        'berjalan' => [ServiceBooking::STATUS_PENDING, ServiceBooking::STATUS_CONFIRMED, ServiceBooking::STATUS_IN_PROGRESS],
        'selesai' => [ServiceBooking::STATUS_COMPLETED],
        'dibatalkan' => [ServiceBooking::STATUS_CANCELLED],
    ];

    public function index(Request $request): View
    {
        $group = $this->statusGroup($request);

        $bookings = $request->user()->serviceBookings()
            ->with('service:id,name')
            ->latest()
            ->orderByDesc('id')
            ->when($group, fn ($query, $group) => $query->whereIn('status', self::STATUS_GROUPS[$group]))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.account.service-bookings.index', [
            'bookings' => $bookings,
            'statusGroup' => $group,
            'statusCounts' => $this->statusGroupCounts($request->user()->serviceBookings()),
        ]);
    }

    public function cancel(Request $request, ServiceBooking $serviceBooking): RedirectResponse
    {
        // Milik customer lain: 404 agar keberadaan datanya tidak terlihat.
        abort_unless($serviceBooking->user_id === $request->user()->id, 404);

        $redirect = redirect()->back(fallback: route('account.service-bookings.index'));

        // Baca ulang baris yang dikunci: admin mungkin sudah mengubah status, atau tombol diklik dua kali.
        $result = DB::transaction(function () use ($serviceBooking) {
            $locked = ServiceBooking::whereKey($serviceBooking->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === ServiceBooking::STATUS_CANCELLED) {
                return 'already';
            }

            if (! $locked->canBeCancelledByCustomer()) {
                return 'denied';
            }

            $locked->update(['status' => ServiceBooking::STATUS_CANCELLED]);

            return 'cancelled';
        });

        // Hanya pembatalan yang benar-benar terjadi sekarang (bukan klik ganda / sudah batal) yang dikabarkan.
        if ($result === 'cancelled') {
            AdminActivityNotification::notifyAdmins(new ServiceBookingActivity($serviceBooking, AdminActivityNotification::CANCELLED));
        }

        return match ($result) {
            'already' => $redirect->with('status', 'Booking servis ini sudah dibatalkan.'),
            'denied' => $redirect->with('error', 'Booking servis yang sudah dikonfirmasi, dikerjakan, atau selesai tidak bisa dibatalkan dari sini. Silakan hubungi dealer.'),
            default => $redirect->with('success', 'Booking servis berhasil dibatalkan.'),
        };
    }
}
