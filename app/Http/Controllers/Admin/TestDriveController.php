<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestDriveStatusRequest;
use App\Models\TestDrive;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola test drive: hanya lihat & ubah status + catatan. Data dibuat customer di halaman publik.
 */
class TestDriveController extends Controller
{
    /** Pilihan urutan: nilai query string => [[kolom, arah], ...]. */
    public const SORTS = [
        'terbaru' => [['created_at', 'desc']],
        'jadwal' => [['preferred_date', 'asc'], ['preferred_time', 'asc']],
    ];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $testDrives = TestDrive::query()
            ->with(['user:id,name,email', 'car:id,brand_id,name,year', 'car.brand:id,name'])
            ->tap(fn (Builder $query) => $this->applyFilters($query, $filters))
            ->tap(function (Builder $query) use ($filters) {
                foreach (self::SORTS[$filters['urut']] as [$column, $direction]) {
                    $query->orderBy($column, $direction);
                }
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.test-drives.index', [
            'testDrives' => $testDrives,
            'filters' => $filters,
            'hasFilters' => collect($filters)->except('urut')->filter(fn ($value) => $value !== null)->isNotEmpty(),
        ]);
    }

    public function show(TestDrive $testDrive): View
    {
        $testDrive->load([
            'user:id,name,email,phone',
            'car:id,brand_id,name,slug,year,vehicle_condition,stock,is_active',
            'car.brand:id,name',
        ]);

        return view('admin.test-drives.show', ['testDrive' => $testDrive]);
    }

    public function updateStatus(TestDriveStatusRequest $request, TestDrive $testDrive): RedirectResponse
    {
        $oldLabel = $testDrive->statusLabel();
        $status = $request->validated('status');

        $testDrive->update([
            'status' => $status ?? $testDrive->status,
            'admin_note' => $request->validated('admin_note'),
        ]);

        $message = $status === null
            ? 'Catatan admin test drive berhasil disimpan.'
            : "Status test drive diubah dari {$oldLabel} menjadi {$testDrive->statusLabel()}.";

        return redirect()->route('admin.test-drives.show', $testDrive)->with('success', $message);
    }

    /**
     * Ambil filter dari query string; nilai yang tidak dikenal diabaikan.
     *
     * @return array{q: ?string, status: ?string, dari: ?string, sampai: ?string, urut: string}
     */
    private function filters(Request $request): array
    {
        $pick = fn (string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? $request->query($key) : null;
        $date = function (string $key) use ($request): ?string {
            $value = (string) $request->query($key);
            $parsed = DateTime::createFromFormat('!Y-m-d', $value);

            return $parsed && $parsed->format('Y-m-d') === $value ? $value : null;
        };

        return [
            'q' => trim((string) $request->query('q')) ?: null,
            'status' => $pick('status', TestDrive::STATUSES),
            'dari' => $date('dari'),
            'sampai' => $date('sampai'),
            'urut' => $pick('urut', array_keys(self::SORTS)) ?? 'terbaru',
        ];
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'], fn ($q, $search) => $q->where(fn ($q) => $q
                ->whereHas('user', fn ($user) => $user
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                ->orWhereHas('car', fn ($car) => $car
                    ->where('name', 'like', "%{$search}%")
                    ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', "%{$search}%")))))
            ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
            ->when($filters['dari'], fn ($q, $date) => $q->whereDate('preferred_date', '>=', $date))
            ->when($filters['sampai'], fn ($q, $date) => $q->whereDate('preferred_date', '<=', $date));
    }
}
