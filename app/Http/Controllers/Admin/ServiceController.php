<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Master layanan JAF Service. Layanan yang sudah punya booking tidak bisa dihapus; nonaktifkan saja.
 */
class ServiceController extends Controller
{
    /** Nilai filter status di query string => is_active. */
    public const STATUS_FILTERS = ['aktif' => true, 'nonaktif' => false];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = array_key_exists((string) $request->query('status'), self::STATUS_FILTERS) ? $request->query('status') : null;

        $services = Service::query()
            ->withCount('bookings')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($status !== null, fn ($query) => $query->where('is_active', self::STATUS_FILTERS[$status]))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.services.index', [
            'services' => $services,
            'search' => $search,
            'status' => $status,
            'hasFilters' => $search !== '' || $status !== null,
        ]);
    }

    public function create(): View
    {
        return view('admin.services.create', ['service' => new Service(['is_active' => true])]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $data = $request->serviceData();

        $service = Service::create([
            ...$data,
            'slug' => Service::uniqueSlug($data['name']),
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', "Layanan \"{$service->name}\" berhasil ditambahkan.");
    }

    public function edit(Service $service): View
    {
        return view('admin.services.edit', compact('service'));
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $data = $request->serviceData();

        $service->update([
            ...$data,
            'slug' => Service::uniqueSlug($data['name'], $service->id),
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', "Layanan \"{$service->name}\" berhasil diperbarui.");
    }

    public function destroy(Service $service): RedirectResponse
    {
        $bookingsCount = $service->bookings()->count();

        if ($bookingsCount > 0) {
            return $this->cannotDelete($service, $bookingsCount);
        }

        try {
            $service->delete();
        } catch (QueryException) {
            // Booking masuk bersamaan (FK restrict): tampilkan pesan ramah, bukan error SQL.
            return $this->cannotDelete($service);
        }

        return redirect()->route('admin.services.index')
            ->with('success', "Layanan \"{$service->name}\" berhasil dihapus.");
    }

    private function cannotDelete(Service $service, ?int $bookingsCount = null): RedirectResponse
    {
        $usage = $bookingsCount ? "{$bookingsCount} booking servis" : 'booking servis';

        return redirect()->route('admin.services.index')
            ->with('error', "Layanan \"{$service->name}\" tidak bisa dihapus karena sudah dipakai {$usage}. Nonaktifkan layanan agar tidak bisa dipilih customer.");
    }
}
