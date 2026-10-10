<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Account\Concerns\FiltersByStatusGroup;
use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Notifications\Admin\AdminActivityNotification;
use App\Notifications\Admin\PurchaseRequestActivity;
use App\Support\PurchaseRequestPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pengajuan Saya: riwayat pengajuan milik customer yang login + pembatalan saat status pending.
 * Pending belum memotong stok, jadi pembatalan customer tidak perlu menyentuh stok mobil.
 */
class PurchaseRequestController extends Controller
{
    use FiltersByStatusGroup;

    public const PER_PAGE = 10;

    /** Kelompok penyaring status di halaman riwayat. */
    public const STATUS_GROUPS = [
        'berjalan' => [PurchaseRequest::STATUS_PENDING, PurchaseRequest::STATUS_PROCESSING, PurchaseRequest::STATUS_APPROVED],
        'selesai' => [PurchaseRequest::STATUS_COMPLETED],
        'dibatalkan' => [PurchaseRequest::STATUS_REJECTED, PurchaseRequest::STATUS_CANCELLED],
    ];

    public function index(Request $request): View
    {
        $group = $this->statusGroup($request);

        $purchaseRequests = $request->user()->purchaseRequests()
            ->with([
                'car:id,brand_id,name,slug,year,is_active',
                'car.brand:id,name',
                'car.primaryImage:id,car_id,path',
                'testimonial:id,purchase_request_id,rating,comment,status,admin_note',
            ])
            ->latest()
            ->orderByDesc('id')
            ->when($group, fn ($query, $group) => $query->whereIn('status', self::STATUS_GROUPS[$group]))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.account.purchase-requests.index', [
            'purchaseRequests' => $purchaseRequests,
            'statusGroup' => $group,
            'statusCounts' => $this->statusGroupCounts($request->user()->purchaseRequests()),
        ]);
    }

    /**
     * Bukti pengajuan (PDF) milik customer yang login; milik customer lain = 404.
     */
    public function pdf(Request $request, PurchaseRequest $purchaseRequest, PurchaseRequestPdf $pdf): Response
    {
        abort_unless($purchaseRequest->user_id === $request->user()->id, 404);

        return $pdf->response($purchaseRequest);
    }

    public function cancel(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        // Milik customer lain: 404 agar keberadaan datanya tidak terlihat.
        abort_unless($purchaseRequest->user_id === $request->user()->id, 404);

        $redirect = redirect()->back(fallback: route('account.purchase-requests.index'));

        // Baca ulang baris yang dikunci: admin mungkin sudah memproses, atau tombol diklik dua kali.
        $result = DB::transaction(function () use ($purchaseRequest) {
            $locked = PurchaseRequest::whereKey($purchaseRequest->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === PurchaseRequest::STATUS_CANCELLED) {
                return 'already';
            }

            if (! $locked->canBeCancelledByCustomer()) {
                return 'denied';
            }

            $locked->update(['status' => PurchaseRequest::STATUS_CANCELLED]);

            return 'cancelled';
        });

        // Hanya pembatalan yang benar-benar terjadi sekarang (bukan klik ganda / sudah batal) yang dikabarkan.
        if ($result === 'cancelled') {
            AdminActivityNotification::notifyAdmins(new PurchaseRequestActivity($purchaseRequest, AdminActivityNotification::CANCELLED));
        }

        return match ($result) {
            'already' => $redirect->with('status', 'Pengajuan ini sudah dibatalkan.'),
            'denied' => $redirect->with('error', 'Pengajuan yang sudah diproses tidak bisa dibatalkan dari sini. Silakan hubungi dealer.'),
            default => $redirect->with('success', 'Pengajuan pembelian berhasil dibatalkan.'),
        };
    }
}
