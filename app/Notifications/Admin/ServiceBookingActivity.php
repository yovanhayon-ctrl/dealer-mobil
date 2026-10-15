<?php

namespace App\Notifications\Admin;

use App\Models\ServiceBooking;
use App\Notifications\ServiceBookingStatusChanged;

class ServiceBookingActivity extends AdminActivityNotification
{
    public function __construct(ServiceBooking $serviceBooking, string $event = self::CREATED)
    {
        parent::__construct($serviceBooking->loadMissing('user:id,name', 'service'), $event);
    }

    protected function subject(): string
    {
        return 'Booking Servis';
    }

    protected function description(): string
    {
        $description = ServiceBookingStatusChanged::describe($this->record);

        if ($this->record->mechanic_chosen_by_customer) {
            $this->record->loadMissing('mechanic:id,name');
        }

        return $this->record->mechanic_chosen_by_customer && $this->record->mechanic
            ? "{$description} · mekanik pilihan: {$this->record->mechanic->name}"
            : $description;
    }

    public function permission(): string
    {
        return 'service-bookings.manage';
    }

    protected function routeName(): string
    {
        return 'admin.service-bookings.show';
    }
}
