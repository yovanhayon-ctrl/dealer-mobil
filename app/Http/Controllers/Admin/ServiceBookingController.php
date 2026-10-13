<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ChangeServiceBookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceBookingStatusRequest;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceBookingTask;
use App\Models\User;
use DateTime;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Kelola booking servis: lihat, ubah status + catatan, tugaskan mekanik & atur checklist. Data dibuat customer di halaman publik.
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
            ->with(['user:id,name,email', 'service:id,name', 'mechanic:id,name'])
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
        $serviceBooking->load([
            'user:id,name,email,phone',
            'service:id,name,price_from,duration_minutes,is_active,checklist',
            'mechanic:id,name,is_active',
            'tasks',
        ]);

        return view('admin.service-bookings.show', [
            'booking' => $serviceBooking,
            'mechanics' => User::where('role', User::ROLE_MECHANIC)->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
        ]);
    }

    public function updateStatus(
        ServiceBookingStatusRequest $request,
        ServiceBooking $serviceBooking,
        ChangeServiceBookingStatus $changeStatus,
    ): RedirectResponse {
        $oldLabel = $serviceBooking->statusLabel();
        $status = $request->validated('status');
        $redirect = redirect()->route('admin.service-bookings.show', $serviceBooking);

        try {
            $updated = $changeStatus->handle($serviceBooking, $status, ['admin_note' => $request->validated('admin_note')]);
        } catch (DomainException $e) {
            return $redirect->withInput()->with('error', $e->getMessage());
        }

        if ($status === null) {
            return $redirect->with('success', 'Catatan admin booking servis berhasil disimpan.');
        }

        if (! $updated->wasChanged('status')) {
            return $redirect->with('status', "Booking servis ini sudah berstatus {$updated->statusLabel()}. Tidak ada perubahan. Catatan Anda tidak disimpan.");
        }

        return $redirect->with('success', "Status booking servis diubah dari {$oldLabel} menjadi {$updated->statusLabel()}.");
    }

    /**
     * Tugaskan (atau ganti) mekanik. Checklist layanan disalin saat pertama kali ditugaskan.
     */
    public function assignMechanic(Request $request, ServiceBooking $serviceBooking): RedirectResponse
    {
        $validated = $request->validateWithBag('mechanic', [
            'mechanic_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_MECHANIC)->where('is_active', true)],
        ], ['mechanic_id.required' => 'Pilih mekanik.', 'mechanic_id.exists' => 'Mekanik tidak ditemukan atau sudah nonaktif.']);

        $redirect = $this->backToWork($serviceBooking);

        if (! $serviceBooking->canBeAssigned()) {
            return $redirect->with('error', 'Mekanik hanya bisa ditugaskan pada booking yang masih berjalan.');
        }

        $mechanic = User::findOrFail($validated['mechanic_id']);
        $serviceBooking->update(['mechanic_id' => $mechanic->id]);
        $serviceBooking->copyChecklistFromService();

        return $redirect->with('success', "Booking ditugaskan ke {$mechanic->name}. Checklist pekerjaan siap dikerjakan.");
    }

    /**
     * Tambah pekerjaan di luar daftar bawaan (mis. dari keluhan customer).
     */
    public function addTask(Request $request, ServiceBooking $serviceBooking): RedirectResponse
    {
        $validated = $request->validateWithBag('task', [
            'name' => ['required', 'string', 'min:3', 'max:'.ServiceBookingTask::MAX_NAME_LENGTH],
        ], [], ['name' => 'nama pekerjaan']);

        $redirect = $this->backToWork($serviceBooking);

        if (! $serviceBooking->canBeAssigned()) {
            return $redirect->with('error', 'Checklist tidak bisa diubah setelah booking selesai atau dibatalkan.');
        }

        $serviceBooking->tasks()->create([
            'name' => Str::squish($validated['name']),
            'position' => (int) $serviceBooking->tasks()->max('position') + 1,
        ]);

        return $redirect->with('success', 'Pekerjaan ditambahkan ke checklist.');
    }

    /**
     * Hapus pekerjaan yang belum dikerjakan.
     */
    public function removeTask(ServiceBooking $serviceBooking, ServiceBookingTask $task): RedirectResponse
    {
        $redirect = $this->backToWork($serviceBooking);

        if (! $serviceBooking->canBeAssigned() || ! $task->isPending()) {
            return $redirect->with('error', 'Hanya pekerjaan yang belum dikerjakan yang bisa dihapus.');
        }

        $task->delete();

        return $redirect->with('success', "Pekerjaan \"{$task->name}\" dihapus dari checklist.");
    }

    private function backToWork(ServiceBooking $serviceBooking): RedirectResponse
    {
        return redirect()->to(route('admin.service-bookings.show', $serviceBooking).'#pekerjaan');
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
