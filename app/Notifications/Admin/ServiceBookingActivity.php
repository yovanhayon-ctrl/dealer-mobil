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
        return ServiceBookingStatusChanged::describe($this->record);
    }

    protected function routeName(): string
    {
        return 'admin.service-bookings.show';
    }
}
