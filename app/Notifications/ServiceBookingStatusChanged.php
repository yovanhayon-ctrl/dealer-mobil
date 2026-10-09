<?php

namespace App\Notifications;

use App\Models\ServiceBooking;

class ServiceBookingStatusChanged extends StatusChangedNotification
{
    public function __construct(ServiceBooking $serviceBooking)
    {
        parent::__construct($serviceBooking->loadMissing('service'));
    }

    protected function subject(): string
    {
        return 'Booking Servis';
    }

    protected function description(): string
    {
        return "{$this->record->service->name} · {$this->record->vehicle_model} ({$this->record->plate_number}) · "
            .$this->record->preferred_date->translatedFormat('d M Y')." pukul {$this->record->timeLabel()} WIB";
    }

    protected function routeName(): string
    {
        return 'account.service-bookings.index';
    }

    protected function anchorPrefix(): string
    {
        return 'servis';
    }
}
