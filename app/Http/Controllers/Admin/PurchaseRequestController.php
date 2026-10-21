<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ChangePurchaseRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurchaseRequestStatusRequest;
use App\Models\ActivityLog;
use App\Models\PurchaseRequest;
use App\Notifications\Notifier;
use App\Notifications\PurchaseRequestStatusChanged;
use App\Support\PurchaseRequestPdf;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    /** Nilai filter pembayaran di query string. */
    public const PAYMENT_FILTER_OVERDUE = 'lewat-tenggat';

    public const PAYMENT_FILTER_REFUND = 'dana-belum-dikembalikan';

    /** Nilai filter tindak lanjut: pengajuan Menunggu yang belum ditanggapi (lihat PurchaseRequest::RESPONSE_DUE_DAYS). */
    public const FOLLOW_UP_FILTER = 'belum-ditanggapi';

    /** Nilai filter metode di query string => payment_method. */
    public const METHOD_FILTERS = [
        'cash' => PurchaseRequest::PAYMENT_CASH,
        'kredit' => PurchaseRequest::PAYMENT_CREDIT,
    ];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $purchaseRequests = PurchaseRequest::query()
            ->with([
                'user:id,name,email', 'car:id,brand_id,name,year', 'car.brand:id,name',
                'paymentProofs:id,purchase_request_id,amount,status,created_at',
                'paymentRefunds:id,purchase_request_id,amount',
            ])
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
            'paymentProofs.recorder:id,name',
            'paymentRefunds.recorder:id,name',
        ]);

        return view('admin.purchase-requests.show', ['purchaseRequest' => $purchaseRequest]);
    }

    /**
     * Bukti pengajuan (PDF), sama dengan yang diunduh customer.
     */
    public function pdf(PurchaseRequest $purchaseRequest, PurchaseRequestPdf $pdf): Response
    {
        return $pdf->response($purchaseRequest);
    }

    public function updateStatus(
        PurchaseRequestStatusRequest $request,
        PurchaseRequest $purchaseRequest,
        ChangePurchaseRequestStatus $changeStatus,
    ): RedirectResponse {
        $oldLabel = $purchaseRequest->statusLabel();
        $oldStatus = $purchaseRequest->status;
        $status = $request->validated('status');

        try {
            $updated = $changeStatus->handle($purchaseRequest, $status, $request->validated('admin_note'));
        } catch (DomainException $e) {
            return redirect()->route('admin.purchase-requests.show', $purchaseRequest)
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $redirect = redirect()->route('admin.purchase-requests.show', $purchaseRequest);

        if ($status === null) {
            return $redirect->with('success', 'Catatan admin pengajuan berhasil disimpan.');
        }

        // Setelah baris dikunci ternyata status sudah sama (mis. admin lain lebih dulu menyetujui);
        // action tidak menyimpan apa pun, termasuk catatan.
        if (! $updated->wasChanged('status')) {
            return $redirect->with('status', "Pengajuan ini sudah berstatus {$updated->statusLabel()}. Tidak ada perubahan. Catatan Anda tidak disimpan.");
        }

        // Hanya perubahan status yang benar-benar tersimpan yang dikabarkan ke customer.
        Notifier::send($updated->user, new PurchaseRequestStatusChanged($updated));
        ActivityLog::record(ActivityLog::CATEGORY_PURCHASE, 'purchase.status',
            "Mengubah status pengajuan {$updated->documentNumber()}: {$oldLabel} → {$updated->statusLabel()}",
            $updated, ['old' => $oldStatus, 'new' => $updated->status]);

        return $redirect->with('success', "Status pengajuan diubah dari {$oldLabel} menjadi {$updated->statusLabel()}.");
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
            'pembayaran' => $pick('pembayaran', [self::PAYMENT_FILTER_OVERDUE, self::PAYMENT_FILTER_REFUND]),
            'tindak' => $pick('tindak', [self::FOLLOW_UP_FILTER]),
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
            ->when($filters['metode'], fn ($q, $method) => $q->where('payment_method', self::METHOD_FILTERS[$method]))
            ->when($filters['pembayaran'] === self::PAYMENT_FILTER_OVERDUE, fn ($q) => $q->paymentOverdue())
            ->when($filters['pembayaran'] === self::PAYMENT_FILTER_REFUND, fn ($q) => $q->refundPending())
            ->when($filters['tindak'] === self::FOLLOW_UP_FILTER, fn ($q) => $q->awaitingResponse());
    }
}
