<?php

namespace App\Notifications;

use App\Models\Testimonial;
use Illuminate\Support\Str;

/**
 * Ulasan customer disetujui (tampil di beranda) atau ditolak admin (dengan alasan).
 */
class TestimonialModerated extends StatusChangedNotification
{
    public function __construct(Testimonial $testimonial)
    {
        parent::__construct($testimonial->loadMissing('purchaseRequest.car.brand'));
    }

    protected function subject(): string
    {
        return 'Ulasan';
    }

    protected function description(): string
    {
        $car = $this->record->purchaseRequest->car;
        $result = $this->record->isApproved()
            ? 'Terima kasih, ulasan Anda kini tampil di beranda.'
            : 'Silakan perbaiki ulasan Anda dari halaman Pengajuan Saya.';

        return "{$car->brand->name} {$car->name} {$car->year}: “".Str::limit($this->record->comment, 60).'” '.$result;
    }

    protected function routeName(): string
    {
        return 'account.purchase-requests.index';
    }

    protected function anchorPrefix(): string
    {
        return 'pengajuan';
    }

    /** Kartu pengajuan (bukan id ulasan). */
    protected function anchor(): string
    {
        return "pengajuan-{$this->record->purchase_request_id}";
    }
}
