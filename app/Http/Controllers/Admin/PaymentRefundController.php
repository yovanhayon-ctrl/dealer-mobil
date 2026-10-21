<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentRefundRequest;
use App\Models\ActivityLog;
use App\Models\PaymentRefund;
use App\Models\PurchaseRequest;
use App\Notifications\Notifier;
use App\Notifications\PaymentRefundRecorded;
use App\Support\PaymentProofFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pengembalian dana untuk pengajuan Dibatalkan/Ditolak yang sempat menerima pembayaran:
 * catat pengembalian (boleh bertahap) atau tutup tanpa (sisa) pengembalian dengan alasan. Customer diberi tahu.
 */
class PaymentRefundController extends Controller
{
    public function store(PaymentRefundRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $redirect = $this->backToPayments($purchaseRequest);

        if (! $purchaseRequest->needsRefundAction()) {
            return $redirect->with('status', 'Tidak ada dana yang perlu dikembalikan untuk pengajuan ini.');
        }

        $file = $request->file('file');

        $refund = $purchaseRequest->paymentRefunds()->create([
            ...$request->safe()->only(['amount', 'refunded_on', 'method', 'note']),
            'recorded_by' => $request->user()->id,
            'file_path' => $file?->store(PaymentRefund::directory($purchaseRequest->id), PaymentRefund::DISK),
            'file_name' => $file ? mb_substr($file->getClientOriginalName(), 0, 255) : null,
        ]);
        $purchaseRequest->load('paymentRefunds');

        Notifier::send($purchaseRequest->loadMissing('user')->user, PaymentRefundRecorded::refunded($refund, $purchaseRequest));
        ActivityLog::record(ActivityLog::CATEGORY_FINANCE, 'refund.recorded',
            'Mencatat pengembalian dana '.ActivityLog::rupiah($refund->amount).", pengajuan {$purchaseRequest->documentNumber()}",
            $purchaseRequest, ['payment_refund_id' => $refund->id, 'amount' => $refund->amount]);

        return $redirect->with('success', 'Pengembalian dana Rp '.number_format($refund->amount, 0, ',', '.')
            ." dicatat. Status dana: {$purchaseRequest->paymentStatusLabel()}.");
    }

    public function close(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $validated = $request->validateWithBag('refundClose', [
            'refund_closed_note' => ['required', 'string', 'min:5', 'max:500'],
        ], [], ['refund_closed_note' => 'alasan']);

        $redirect = $this->backToPayments($purchaseRequest);

        if (! $purchaseRequest->needsRefundAction()) {
            return $redirect->with('status', 'Tidak ada dana yang perlu dikembalikan untuk pengajuan ini.');
        }

        $purchaseRequest->refund_closed_at = now();
        $purchaseRequest->refund_closed_note = trim($validated['refund_closed_note']);
        $purchaseRequest->save();

        Notifier::send($purchaseRequest->loadMissing('user')->user, PaymentRefundRecorded::closed($purchaseRequest));
        ActivityLog::record(ActivityLog::CATEGORY_FINANCE, 'refund.closed',
            "Menutup pengembalian dana pengajuan {$purchaseRequest->documentNumber()}: {$purchaseRequest->refund_closed_note}", $purchaseRequest);

        return $redirect->with('success', "Pengembalian dana ditutup. Status dana: {$purchaseRequest->paymentStatusLabel()}.");
    }

    public function file(PurchaseRequest $purchaseRequest, PaymentRefund $paymentRefund): StreamedResponse
    {
        return PaymentProofFile::response($paymentRefund);
    }

    private function backToPayments(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        return redirect()->to(route('admin.purchase-requests.show', $purchaseRequest).'#bukti-pembayaran');
    }
}
