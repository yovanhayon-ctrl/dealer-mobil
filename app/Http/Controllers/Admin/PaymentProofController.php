<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentProof;
use App\Models\PurchaseRequest;
use App\Support\PaymentProofFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Verifikasi bukti pembayaran customer di detail pengajuan: terima atau tolak dengan alasan.
 * Status pengajuan tetap diubah manual oleh admin.
 */
class PaymentProofController extends Controller
{
    public function file(PurchaseRequest $purchaseRequest, PaymentProof $paymentProof): StreamedResponse
    {
        return PaymentProofFile::response($paymentProof);
    }

    public function approve(PurchaseRequest $purchaseRequest, PaymentProof $paymentProof): RedirectResponse
    {
        $redirect = $this->backToProofs($purchaseRequest);

        if (! $paymentProof->isPending()) {
            return $redirect->with('status', "Bukti pembayaran ini sudah {$this->decidedLabel($paymentProof)}.");
        }

        // Total yang diterima tidak boleh melebihi tagihan (mis. bukti pelunasan terkirim dua kali).
        if ($paymentProof->amount > $purchaseRequest->remainingPayment()) {
            return $redirect->with('error', 'Bukti tidak bisa diterima: total pembayaran akan melebihi Rp '
                .number_format($purchaseRequest->amountPayableToDealer(), 0, ',', '.')
                .' (sisa Rp '.number_format($purchaseRequest->remainingPayment(), 0, ',', '.').'). Periksa lagi atau tolak bukti ini.');
        }

        $paymentProof->update(['status' => PaymentProof::STATUS_APPROVED, 'admin_note' => null, 'verified_at' => now()]);
        $purchaseRequest->load('paymentProofs');

        return $redirect->with('success', "Bukti {$paymentProof->typeLabel()} Rp ".number_format($paymentProof->amount, 0, ',', '.')
            ." diterima. Status pembayaran: {$purchaseRequest->paymentStatusLabel()}.");
    }

    public function reject(Request $request, PurchaseRequest $purchaseRequest, PaymentProof $paymentProof): RedirectResponse
    {
        $validated = $request->validateWithBag(
            "rejectProof{$paymentProof->id}",
            ['admin_note' => ['required', 'string', 'min:5', 'max:500']],
            [],
            ['admin_note' => 'alasan penolakan'],
        );

        $redirect = $this->backToProofs($purchaseRequest);

        if (! $paymentProof->isPending()) {
            return $redirect->with('status', "Bukti pembayaran ini sudah {$this->decidedLabel($paymentProof)}.");
        }

        $paymentProof->update([
            'status' => PaymentProof::STATUS_REJECTED,
            'admin_note' => $validated['admin_note'],
            'verified_at' => now(),
        ]);

        return $redirect->with('success', 'Bukti pembayaran ditolak. Customer bisa melihat alasannya dan mengirim ulang.');
    }

    private function backToProofs(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        return redirect()->to(route('admin.purchase-requests.show', $purchaseRequest).'#bukti-pembayaran');
    }

    private function decidedLabel(PaymentProof $paymentProof): string
    {
        return mb_strtolower($paymentProof->statusLabel());
    }
}
