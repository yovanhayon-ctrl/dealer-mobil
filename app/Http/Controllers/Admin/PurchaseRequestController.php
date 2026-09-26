<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ChangePurchaseRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseRequestStatusRequest;
use App\Models\PurchaseRequest;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola pengajuan pembelian: hanya lihat & ubah status + catatan (efek stok lewat action).
 * Data dibuat customer di halaman publik.
 */
class PurchaseRequestController extends Controller
{
    /** Pilihan urutan: nilai query string => arah created_at. */
    public const SORTS = [
        'terbaru' => 'desc',
        'terlama' => 'asc',
    ];

    /** Nilai filter metode di query string => payment_method. */
    public const METHOD_FILTERS = [
        'cash' => PurchaseRequest::PAYMENT_CASH,
        'kredit' => PurchaseRequest::PAYMENT_CREDIT,
    ];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $purchaseRequests = PurchaseRequest::query()
            ->with(['user:id,name,email', 'car:id,brand_id,name,year', 'car.brand:id,name'])
            ->tap(fn (Builder $query) => $this->applyFilters($query, $filters))
            ->orderBy('created_at', self::SORTS[$filters['urut']])
            ->orderBy('id', self::SORTS[$filters['urut']])
            ->paginate(10)
            ->withQueryString();

        return view('admin.purchase-requests.index', [
            'purchaseRequests' => $purchaseRequests,
            'filters' => $filters,
            'hasFilters' => collect($filters)->except('urut')->filter(fn ($value) => $value !== null)->isNotEmpty(),
        ]);
    }

    public function show(PurchaseRequest $purchaseRequest): View
    {
        $purchaseRequest->load([
            'user:id,name,email,phone',
            'car:id,brand_id,name,slug,year,vehicle_condition,price,stock,is_active',
            'car.brand:id,name',
        ]);

        return view('admin.purchase-requests.show', ['purchaseRequest' => $purchaseRequest]);
    }

    public function updateStatus(
        PurchaseRequestStatusRequest $request,
        PurchaseRequest $purchaseRequest,
        ChangePurchaseRequestStatus $changeStatus,
    ): RedirectResponse {
        $oldLabel = $purchaseRequest->statusLabel();
        $status = $request->validated('status');

        try {
            $updated = $changeStatus->handle($purchaseRequest, $status, $request->validated('admin_note'));
        } catch (DomainException $e) {
            return redirect()->route('admin.purchase-requests.show', $purchaseRequest)
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $message = $status === null
            ? 'Catatan admin pengajuan berhasil disimpan.'
            : "Status pengajuan diubah dari {$oldLabel} menjadi {$updated->statusLabel()}.";

        return redirect()->route('admin.purchase-requests.show', $purchaseRequest)->with('success', $message);
    }

    /**
     * Ambil filter dari query string; nilai yang tidak dikenal diabaikan.
     *
     * @return array{q: ?string, status: ?string, metode: ?string, urut: string}
     */
    private function filters(Request $request): array
    {
        $pick = fn (string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? $request->query($key) : null;

        return [
            'q' => trim((string) $request->query('q')) ?: null,
            'status' => $pick('status', PurchaseRequest::STATUSES),
            'metode' => $pick('metode', array_keys(self::METHOD_FILTERS)),
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
            ->when($filters['metode'], fn ($q, $method) => $q->where('payment_method', self::METHOD_FILTERS[$method]));
    }
}
