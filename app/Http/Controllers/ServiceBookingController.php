<?php

namespace App\Http\Controllers;

use App\Actions\BookService;
use App\Http\Requests\ServiceBookingRequest;
use App\Models\Service;
use App\Notifications\Admin\AdminActivityNotification;
use App\Notifications\Admin\ServiceBookingActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Booking servis oleh customer (/servis/booking). Riwayat & pembatalan ada di Account\ServiceBookingController.
 */
class ServiceBookingController extends Controller
{
    /** Batas POST booking per menit per user (rate limiter "service-booking"). */
    public const MAX_BOOKINGS_PER_MINUTE = 5;

    public function create(Request $request): View
    {
        if ($request->user()->isAdmin()) {
            return view('pages.service-bookings.create', ['isAdmin' => true]);
        }

        $services = Service::query()->active()->orderBy('id')->get(['id', 'name', 'slug', 'price_from']);

        // ?layanan=slug dari halaman /servis: pilih otomatis bila layanan aktif.
        $slug = $request->query('layanan');

        return view('pages.service-bookings.create', [
            'isAdmin' => false,
            'services' => $services,
            'selectedService' => is_string($slug) ? $services->firstWhere('slug', $slug) : null,
            'firstDate' => ServiceBookingRequest::firstDate(),
            'lastDate' => ServiceBookingRequest::lastDate(),
        ]);
    }

    public function store(ServiceBookingRequest $request, BookService $bookService): RedirectResponse
    {
        $booking = $bookService->handle($request->user(), $request->bookingData());
        $booking->load('service:id,name');
        AdminActivityNotification::notifyAdmins(new ServiceBookingActivity($booking));

        return redirect()->route('account.service-bookings.index')->with('success', sprintf(
            'Booking %s untuk %s pada %s pukul %s WIB berhasil dikirim. Tunggu konfirmasi dari dealer.',
            $booking->service->name,
            $booking->plate_number,
            $booking->preferred_date->translatedFormat('d M Y'),
            $booking->timeLabel(),
        ));
    }
}
