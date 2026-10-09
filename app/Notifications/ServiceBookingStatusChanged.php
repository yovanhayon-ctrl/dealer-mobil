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
        return self::describe($this->record);
    }

    /**
     * Ringkasan data (dipakai juga notifikasi admin).
     */
    public static function describe(ServiceBooking $booking): string
    {
        return "{$booking->service->name} · {$booking->vehicle_model} ({$booking->plate_number}) · "
            .$booking->preferred_date->translatedFormat('d M Y')." pukul {$booking->timeLabel()} WIB";
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
