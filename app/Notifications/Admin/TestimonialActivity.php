<?php

namespace App\Notifications\Admin;

use App\Models\Testimonial;
use Illuminate\Support\Str;

/**
 * Ulasan baru / diperbarui customer yang menunggu moderasi.
 */
class TestimonialActivity extends AdminActivityNotification
{
    public function __construct(Testimonial $testimonial, string $event = self::CREATED)
    {
        parent::__construct($testimonial->loadMissing('user:id,name', 'purchaseRequest.car.brand'), $event);
    }

    protected function subject(): string
    {
        return 'Ulasan';
    }

    protected function description(): string
    {
        $car = $this->record->purchaseRequest->car;

        return Testimonial::stars($this->record->rating)." · {$car->brand->name} {$car->name} {$car->year} · "
            .'"'.Str::limit($this->record->comment, 80).'"';
    }

    protected function routeName(): string
    {
        return 'admin.testimonials.index';
    }

    protected function routeParams(): array
    {
        return ['status' => Testimonial::STATUS_PENDING];
    }
}
