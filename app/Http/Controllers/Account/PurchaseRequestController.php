<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Notifications\Admin\AdminActivityNotification;
use App\Notifications\Admin\PurchaseRequestActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pengajuan Saya: riwayat pengajuan milik customer yang login + pembatalan saat status pending.
 * Pending belum memotong stok, jadi pembatalan customer tidak perlu menyentuh stok mobil.
 */
class PurchaseRequestController extends Controller
{
    public const PER_PAGE = 10;

    public function index(Request $request): View
    {
        $purchaseRequests = $request->user()->purchaseRequests()
            ->with([
                'car:id,brand_id,name,slug,year,is_active',
                'car.brand:id,name',
                'car.primaryImage:id,car_id,path',
                'testimonial:id,purchase_request_id,rating,comment,status,admin_note',
            ])
            ->latest()
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        return view('pages.account.purchase-requests.index', ['purchaseRequests' => $purchaseRequests]);
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
