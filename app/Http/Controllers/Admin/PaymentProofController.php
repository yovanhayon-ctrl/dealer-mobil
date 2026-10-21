<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShowroomPaymentRequest;
use App\Models\ActivityLog;
use App\Models\PaymentProof;
use App\Models\PurchaseRequest;
use App\Notifications\Notifier;
use App\Notifications\PaymentProofReviewed;
use App\Support\PaymentProofFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Verifikasi bukti pembayaran customer di detail pengajuan: terima atau tolak dengan alasan.
 * Customer diberi tahu setiap pembayaran diterima / bukti ditolak (PaymentProofReviewed).
 * Status pengajuan tetap diubah manual oleh admin.
 */
class PaymentProofController extends Controller
{
    public function file(PurchaseRequest $purchaseRequest, PaymentProof $paymentProof): StreamedResponse
    {
        return PaymentProofFile::response($paymentProof);
    }

    /**
     * Pembayaran langsung di showroom (tunai / EDC / transfer di tempat): dicatat admin dan langsung diterima.
     */
    public function store(ShowroomPaymentRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $redirect = $this->backToProofs($purchaseRequest);

        if ($purchaseRequest->status !== PurchaseRequest::STATUS_APPROVED) {
            return $redirect->with('error', 'Pembayaran hanya bisa dicatat untuk pengajuan yang berstatus Disetujui.');
        }

        if ($purchaseRequest->isPaidOff()) {
            return $redirect->with('status', 'Pembayaran pengajuan ini sudah '.mb_strtolower($purchaseRequest->paymentStatusLabel()).'.');
        }

        $file = $request->file('file');

        $proof = $purchaseRequest->paymentProofs()->create([
            ...$request->safe()->only(['type', 'method', 'amount', 'paid_on', 'note']),
            'user_id' => $purchaseRequest->user_id,
            'recorded_by' => $request->user()->id,
            'file_path' => $file?->store(PaymentProof::directory($purchaseRequest->id), PaymentProof::DISK),
            'file_name' => $file ? mb_substr($file->getClientOriginalName(), 0, 255) : null,
            'status' => PaymentProof::STATUS_APPROVED,
            'verified_at' => now(),
        ]);
        $purchaseRequest->load('paymentProofs');
        $this->notifyCustomer($proof, $purchaseRequest);
        ActivityLog::record(ActivityLog::CATEGORY_FINANCE, 'payment.showroom',
            "Mencatat pembayaran {$proof->typeLabel()} di showroom ".ActivityLog::rupiah($proof->amount).", pengajuan {$purchaseRequest->documentNumber()}",
            $purchaseRequest, ['payment_proof_id' => $proof->id, 'amount' => $proof->amount]);

        return $redirect->with('success', 'Pembayaran di showroom Rp '.number_format((int) $request->validated('amount'), 0, ',', '.')
            ." dicatat. Status pembayaran: {$purchaseRequest->paymentStatusLabel()}.");
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
        $this->notifyCustomer($paymentProof, $purchaseRequest);
        ActivityLog::record(ActivityLog::CATEGORY_FINANCE, 'payment.approved',
            "Menerima bukti {$paymentProof->typeLabel()} ".ActivityLog::rupiah($paymentProof->amount).", pengajuan {$purchaseRequest->documentNumber()}",
            $purchaseRequest, ['payment_proof_id' => $paymentProof->id, 'amount' => $paymentProof->amount]);

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
        $this->notifyCustomer($paymentProof, $purchaseRequest);
        ActivityLog::record(ActivityLog::CATEGORY_FINANCE, 'payment.rejected',
            "Menolak bukti {$paymentProof->typeLabel()} ".ActivityLog::rupiah($paymentProof->amount).", pengajuan {$purchaseRequest->documentNumber()}: {$validated['admin_note']}",
            $purchaseRequest, ['payment_proof_id' => $paymentProof->id, 'amount' => $paymentProof->amount]);

        return $redirect->with('success', 'Bukti pembayaran ditolak. Customer bisa melihat alasannya dan mengirim ulang.');
    }

    private function notifyCustomer(PaymentProof $paymentProof, PurchaseRequest $purchaseRequest): void
    {
        Notifier::send($purchaseRequest->loadMissing('user')->user, new PaymentProofReviewed($paymentProof, $purchaseRequest));
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
