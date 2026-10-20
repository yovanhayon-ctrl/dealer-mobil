<?php

namespace App\Actions;

use App\Models\PurchaseRequest;
use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Notifications\MechanicJobAssigned;
use App\Notifications\Notifier;
use App\Notifications\PaymentDueReminder;
use App\Notifications\ScheduleReminder;
use Illuminate\Support\Collection;

/**
 * Pengingat H-1: test drive & booking servis berstatus Dikonfirmasi yang dijadwalkan besok dan belum diingatkan.
 * Customer menerima ScheduleReminder; mekanik yang ditugaskan menerima "Pekerjaan Servis Besok".
 * Juga pengingat batas pembayaran pertama (PaymentDueReminder) untuk pengajuan yang batasnya besok.
 * Aman dijalankan berulang (cron, tombol dashboard, artisan): kolom penanda diisi secara atomik sebelum mengirim.
 */
class SendScheduleReminders
{
    /**
     * @return array{test_drives: Collection<int, TestDrive>, service_bookings: Collection<int, ServiceBooking>, purchase_requests: Collection<int, PurchaseRequest>}
     */
    public function due(): array
    {
        $tomorrow = today()->addDay()->toDateString();
        $query = fn (string $model) => $model::query()
            ->where('status', $model::STATUS_CONFIRMED)
            ->whereDate('preferred_date', $tomorrow)
            ->whereNull('reminded_at')
            ->orderBy('preferred_time');

        return [
            'test_drives' => $query(TestDrive::class)->with(['user', 'car.brand'])->get(),
            'service_bookings' => $query(ServiceBooking::class)->with(['user', 'service', 'mechanic'])->get(),
            'purchase_requests' => PurchaseRequest::query()->paymentDueTomorrow()->whereNull('payment_reminded_at')
                ->with(['user', 'car.brand'])->orderBy('approved_at')->get(),
        ];
    }

    /**
     * @return array{test_drives: int, service_bookings: int, mechanics: int, payments: int}
     */
    public function handle(): array
    {
        $sent = ['test_drives' => 0, 'service_bookings' => 0, 'mechanics' => 0, 'payments' => 0];
        $due = $this->due();

        foreach ([...$due['test_drives'], ...$due['service_bookings']] as $booking) {
            // Ditandai lebih dulu: proses lain (cron & tombol bersamaan) tidak ikut mengirim.
            if ($booking::query()->whereKey($booking->id)->whereNull('reminded_at')->update(['reminded_at' => now()]) === 0) {
                continue;
            }

            Notifier::send($booking->user, new ScheduleReminder($booking));

            if ($booking instanceof TestDrive) {
                $sent['test_drives']++;

                continue;
            }

            $sent['service_bookings']++;

            if ($booking->mechanic) {
                Notifier::send($booking->mechanic, new MechanicJobAssigned($booking, 'Pekerjaan Servis Besok'));
                $sent['mechanics']++;
            }
        }

        foreach ($due['purchase_requests'] as $purchaseRequest) {
            if (PurchaseRequest::query()->whereKey($purchaseRequest->id)->whereNull('payment_reminded_at')->update(['payment_reminded_at' => now()]) === 0) {
                continue;
            }

            Notifier::send($purchaseRequest->user, new PaymentDueReminder($purchaseRequest));
            $sent['payments']++;
        }

        return $sent;
    }
}
