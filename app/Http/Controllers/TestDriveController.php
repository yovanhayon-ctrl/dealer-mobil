<?php

namespace App\Http\Controllers;

use App\Actions\BookTestDrive;
use App\Http\Requests\TestDriveRequest;
use App\Models\Car;
use App\Notifications\Admin\AdminActivityNotification;
use App\Notifications\Admin\TestDriveActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Booking test drive oleh customer (/test-drive). Riwayat & pembatalan ada di Account\TestDriveController.
 */
class TestDriveController extends Controller
{
    /** Batas POST booking per menit per user (rate limiter "test-drive-booking"). */
    public const MAX_BOOKINGS_PER_MINUTE = 5;

    public function create(Request $request): View
    {
        if ($request->user()->isAdmin()) {
            return view('pages.test-drives.create', ['isAdmin' => true]);
        }

        // Label "Merek Nama Tahun"; diurutkan di PHP (satu query + eager load).
        $cars = Car::query()
            ->testDrivable()
            ->with(['brand:id,name', 'primaryImage:id,car_id,path'])
            ->get()
            ->sortBy(fn (Car $car) => mb_strtolower("{$car->brand->name} {$car->name} {$car->year}"))
            ->values();

        // ?mobil=slug dari halaman detail: pilih otomatis bila mobil boleh di-test drive.
        $slug = $request->query('mobil');
        $selectedCar = is_string($slug) ? $cars->firstWhere('slug', $slug) : null;
        $unavailableCar = is_string($slug) && $selectedCar === null
            ? Car::query()->with('brand:id,name')->where('slug', $slug)->first(['id', 'brand_id', 'name', 'year'])
            : null;

        return view('pages.test-drives.create', [
            'isAdmin' => false,
            'cars' => $cars,
            'selectedCar' => $selectedCar,
            'unavailableCar' => $unavailableCar,
            'firstDate' => TestDriveRequest::firstDate(),
            'lastDate' => TestDriveRequest::lastDate(),
        ]);
    }

    public function store(TestDriveRequest $request, BookTestDrive $bookTestDrive): RedirectResponse
    {
        $testDrive = $bookTestDrive->handle($request->user(), $request->validated());
        $testDrive->load('car:id,brand_id,name,year', 'car.brand:id,name');
        AdminActivityNotification::notifyAdmins(new TestDriveActivity($testDrive));

        return redirect()->route('account.test-drives.index')->with('success', sprintf(
            'Booking test drive %s %s %s pada %s pukul %s WIB berhasil dikirim. Tunggu konfirmasi dari dealer.',
            $testDrive->car->brand->name,
            $testDrive->car->name,
            $testDrive->car->year,
            $testDrive->preferred_date->translatedFormat('d M Y'),
            $testDrive->timeLabel(),
        ));
    }
}
