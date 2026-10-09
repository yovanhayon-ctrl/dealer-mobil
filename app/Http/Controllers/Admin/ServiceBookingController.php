<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceBookingStatusRequest;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Notifications\ServiceBookingStatusChanged;
use DateTime;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Kelola booking servis: hanya lihat & ubah status + catatan. Data dibuat customer di halaman publik.
 */
class ServiceBookingController extends Controller
{
    /** Pilihan urutan: nilai query string => [[kolom, arah], ...]. */
    public const SORTS = [
        'terbaru' => [['created_at', 'desc']],
        'jadwal' => [['preferred_date', 'asc'], ['preferred_time', 'asc']],
    ];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $bookings = ServiceBooking::query()
            ->with(['user:id,name,email', 'service:id,name'])
            ->tap(fn (Builder $query) => $this->applyFilters($query, $filters))
            ->tap(function (Builder $query) use ($filters) {
                foreach (self::SORTS[$filters['urut']] as [$column, $direction]) {
                    $query->orderBy($column, $direction);
                }
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.service-bookings.index', [
            'bookings' => $bookings,
            'filters' => $filters,
            'serviceOptions' => Service::orderBy('name')->pluck('name', 'id')->all(),
            'hasFilters' => collect($filters)->except('urut')->filter(fn ($value) => $value !== null)->isNotEmpty(),
        ]);
    }

    public function show(ServiceBooking $serviceBooking): View
    {
        $serviceBooking->load(['user:id,name,email,phone', 'service:id,name,price_from,duration_minutes,is_active']);

        return view('admin.service-bookings.show', ['booking' => $serviceBooking]);
    }

    public function updateStatus(ServiceBookingStatusRequest $request, ServiceBooking $serviceBooking): RedirectResponse
    {
        $oldLabel = $serviceBooking->statusLabel();
        $status = $request->validated('status');
        $redirect = redirect()->route('admin.service-bookings.show', $serviceBooking);

        try {
            // Baca ulang baris yang dikunci: admin lain mungkin sudah mengubah status sejak halaman dibuka.
            $updated = DB::transaction(function () use ($serviceBooking, $status, $request) {
                $locked = ServiceBooking::whereKey($serviceBooking->id)->lockForUpdate()->firstOrFail();

                // Status tujuan sudah terpasang (admin lain lebih dulu): jangan simpan catatan juga.
                if ($status !== null && $status === $locked->status) {
                    return $locked;
                }

                if ($status !== null && ! $locked->canTransitionTo($status)) {
                    throw new DomainException("Status booking servis tidak bisa diubah dari {$locked->statusLabel()} ke ".ServiceBooking::STATUS_LABELS[$status].'.');
                }

                $locked->update([
                    'status' => $status ?? $locked->status,
                    'admin_note' => $request->validated('admin_note'),
                ]);

                return $locked;
            });
        } catch (DomainException $e) {
            return $redirect->withInput()->with('error', $e->getMessage());
        }

        if ($status === null) {
            return $redirect->with('success', 'Catatan admin booking servis berhasil disimpan.');
        }

        if (! $updated->wasChanged('status')) {
            return $redirect->with('status', "Booking servis ini sudah berstatus {$updated->statusLabel()}. Tidak ada perubahan. Catatan Anda tidak disimpan.");
        }

        // Hanya perubahan status yang benar-benar tersimpan yang dikabarkan ke customer.
        $updated->user->notify(new ServiceBookingStatusChanged($updated));

        return $redirect->with('success', "Status booking servis diubah dari {$oldLabel} menjadi {$updated->statusLabel()}.");
    }

    /**
     * Ambil filter dari query string; nilai yang tidak dikenal diabaikan.
     *
     * @return array{q: ?string, status: ?string, layanan: ?int, dari: ?string, sampai: ?string, urut: string}
     */
    private function filters(Request $request): array
    {
        $pick = fn (string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? $request->query($key) : null;
        $date = function (string $key) use ($request): ?string {
            $value = (string) $request->query($key);
            $parsed = DateTime::createFromFormat('!Y-m-d', $value);

            return $parsed && $parsed->format('Y-m-d') === $value ? $value : null;
        };
        $serviceId = filter_var($request->query('layanan'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return [
            'q' => trim((string) $request->query('q')) ?: null,
            'status' => $pick('status', ServiceBooking::STATUSES),
            'layanan' => $serviceId === false ? null : $serviceId,
            'dari' => $date('dari'),
            'sampai' => $date('sampai'),
            'urut' => $pick('urut', array_keys(self::SORTS)) ?? 'terbaru',
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'], fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('plate_number', 'like', "%{$search}%")
                ->orWhere('vehicle_model', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($user) => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))))
            ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
            ->when($filters['layanan'], fn ($q, $serviceId) => $q->where('service_id', $serviceId))
            ->when($filters['dari'], fn ($q, $date) => $q->whereDate('preferred_date', '>=', $date))
            ->when($filters['sampai'], fn ($q, $date) => $q->whereDate('preferred_date', '<=', $date));
    }
}
