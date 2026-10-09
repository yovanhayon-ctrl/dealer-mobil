<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\TestimonialRequest;
use App\Models\PurchaseRequest;
use App\Models\Testimonial;
use App\Notifications\Admin\AdminActivityNotification;
use App\Notifications\Admin\TestimonialActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ulasan customer untuk pengajuan pembelian yang sudah selesai (satu ulasan per pengajuan).
 * Bisa diubah selama menunggu / ditolak; setelah disetujui admin terkunci.
 */
class TestimonialController extends Controller
{
    public function edit(Request $request, PurchaseRequest $purchaseRequest): View|RedirectResponse
    {
        $this->ensureOwner($request, $purchaseRequest);

        if ($redirect = $this->rejectIfNotAllowed($purchaseRequest)) {
            return $redirect;
        }

        $purchaseRequest->load('car:id,brand_id,name,year', 'car.brand:id,name');

        return view('pages.account.testimonials.edit', [
            'purchaseRequest' => $purchaseRequest,
            'testimonial' => $purchaseRequest->testimonial,
        ]);
    }

    public function update(TestimonialRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->ensureOwner($request, $purchaseRequest);

        if ($redirect = $this->rejectIfNotAllowed($purchaseRequest)) {
            return $redirect;
        }

        $testimonial = $purchaseRequest->testimonial ?? new Testimonial([
            'user_id' => $request->user()->id,
            'purchase_request_id' => $purchaseRequest->id,
        ]);
        $isNew = ! $testimonial->exists;

        // Setiap kiriman (baru / perbaikan) kembali menunggu moderasi admin.
        $testimonial->fill([
            ...$request->validated(),
            'status' => Testimonial::STATUS_PENDING,
            'admin_note' => null,
            'approved_at' => null,
        ])->save();

        AdminActivityNotification::notifyAdmins(new TestimonialActivity(
            $testimonial,
            $isNew ? AdminActivityNotification::CREATED : AdminActivityNotification::UPDATED,
        ));

        return redirect()->to(route('account.purchase-requests.index')."#pengajuan-{$purchaseRequest->id}")
            ->with('success', 'Terima kasih! Ulasan Anda dikirim dan akan tampil di beranda setelah disetujui admin.');
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

        if (! $purchaseRequest->canBeReviewed()) {
            return $back->with('error', 'Ulasan hanya bisa diberikan untuk pembelian yang sudah selesai.');
        }

        if ($purchaseRequest->testimonial && ! $purchaseRequest->testimonial->canBeEditedByCustomer()) {
            return $back->with('status', 'Ulasan Anda untuk pembelian ini sudah disetujui dan tidak bisa diubah lagi.');
        }

        return null;
    }
}
