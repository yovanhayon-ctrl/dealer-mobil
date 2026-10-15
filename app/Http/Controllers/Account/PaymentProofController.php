<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\PaymentProofRequest;
use App\Models\PaymentProof;
use App\Models\PaymentRefund;
use App\Models\PurchaseRequest;
use App\Notifications\Admin\AdminActivityNotification;
use App\Notifications\Admin\PaymentProofActivity;
use App\Support\PaymentProofFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Customer mengirim bukti pembayaran (tanda jadi / DP / pelunasan) untuk pengajuan yang sudah disetujui.
 * Admin memverifikasi di detail pengajuan.
 */
class PaymentProofController extends Controller
{
    public function create(Request $request, PurchaseRequest $purchaseRequest): View|RedirectResponse
    {
        $this->ensureOwner($request, $purchaseRequest);

        if ($redirect = $this->rejectIfNotAllowed($purchaseRequest)) {
            return $redirect;
        }

        $purchaseRequest->load('car:id,brand_id,name,year', 'car.brand:id,name', 'paymentProofs');

        return view('pages.account.payment-proofs.create', ['purchaseRequest' => $purchaseRequest]);
    }

    public function store(PaymentProofRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->ensureOwner($request, $purchaseRequest);

        if ($redirect = $this->rejectIfNotAllowed($purchaseRequest)) {
            return $redirect;
        }

        $file = $request->file('file');
        $path = $file->store(PaymentProof::directory($purchaseRequest->id), PaymentProof::DISK);

        $proof = $purchaseRequest->paymentProofs()->create([
            ...$request->safe()->only(['type', 'amount', 'paid_on', 'note']),
            'user_id' => $request->user()->id,
            'file_path' => $path,
            'file_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'status' => PaymentProof::STATUS_PENDING,
        ]);

        AdminActivityNotification::notifyAdmins(new PaymentProofActivity($proof, $purchaseRequest));

        return redirect()->to(route('account.purchase-requests.index')."#pengajuan-{$purchaseRequest->id}")
            ->with('success', 'Bukti pembayaran terkirim dan menunggu verifikasi admin.');
    }

    /**
     * File bukti (disk privat) hanya untuk pemilik pengajuan.
     */
    public function file(Request $request, PurchaseRequest $purchaseRequest, PaymentProof $paymentProof): StreamedResponse
    {
        $this->ensureOwner($request, $purchaseRequest);

        return PaymentProofFile::response($paymentProof);
    }

    /**
     * File bukti pengembalian dana (disk privat) hanya untuk pemilik pengajuan.
     */
    public function refundFile(Request $request, PurchaseRequest $purchaseRequest, PaymentRefund $paymentRefund): StreamedResponse
    {
        $this->ensureOwner($request, $purchaseRequest);

        return PaymentProofFile::response($paymentRefund);
    }

    /**
     * Pengajuan milik customer lain: 404 agar keberadaan datanya tidak terlihat.
     */
    private function ensureOwner(Request $request, PurchaseRequest $purchaseRequest): void
    {
        abort_unless($purchaseRequest->user_id === $request->user()->id, 404);
    }

    private function rejectIfNotAllowed(PurchaseRequest $purchaseRequest): ?RedirectResponse
    {
        $back = redirect()->route('account.purchase-requests.index');

        if ($purchaseRequest->status !== PurchaseRequest::STATUS_APPROVED) {
            return $back->with('error', 'Bukti pembayaran hanya bisa dikirim untuk pengajuan yang sudah disetujui.');
        }

        if ($purchaseRequest->isPaidOff()) {
            return $back->with('status', 'Pembayaran pengajuan ini sudah '.mb_strtolower($purchaseRequest->paymentStatusLabel()).'. Tidak perlu mengirim bukti lagi.');
        }

        if ($purchaseRequest->paymentProofs()->count() >= PaymentProof::MAX_PER_PURCHASE) {
            return $back->with('error', 'Batas '.PaymentProof::MAX_PER_PURCHASE.' bukti pembayaran untuk pengajuan ini sudah tercapai. Silakan hubungi dealer lewat WhatsApp.');
        }

        return null;
    }
}
