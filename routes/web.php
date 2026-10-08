<?php

use App\Http\Controllers\Account\ServiceBookingController as AccountServiceBookingController;
use App\Http\Controllers\Account\TestDriveController as AccountTestDriveController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\CarImageController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PromoController as AdminPromoController;
use App\Http\Controllers\Admin\PurchaseRequestController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ServiceBookingController as AdminServiceBookingController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\TestDriveController as AdminTestDriveController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CreditSimulationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ServiceBookingController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TestDriveController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/mobil', [CarController::class, 'index'])->name('cars.index');
Route::get('/mobil/{car:slug}', [CarController::class, 'show'])->name('cars.show');
Route::get('/promo', [PromoController::class, 'index'])->name('promos.index');
Route::get('/promo/{promo:slug}', [PromoController::class, 'show'])->name('promos.show');
Route::get('/simulasi-kredit', [CreditSimulationController::class, 'index'])->name('credit.index');
Route::get('/servis', [ServiceController::class, 'index'])->name('services.index');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Customer (wajib login)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/test-drive', [TestDriveController::class, 'create'])->name('test-drives.create');
    Route::post('/test-drive', [TestDriveController::class, 'store'])
        ->middleware('throttle:test-drive-booking')
        ->name('test-drives.store');

    Route::get('/servis/booking', [ServiceBookingController::class, 'create'])->name('service-bookings.create');
    Route::post('/servis/booking', [ServiceBookingController::class, 'store'])
        ->middleware('throttle:service-booking')
        ->name('service-bookings.store');

    Route::prefix('akun')->name('account.')->group(function () {
        Route::get('test-drive', [AccountTestDriveController::class, 'index'])->name('test-drives.index');
        Route::patch('test-drive/{testDrive}/batal', [AccountTestDriveController::class, 'cancel'])->name('test-drives.cancel');

        Route::get('servis', [AccountServiceBookingController::class, 'index'])->name('service-bookings.index');
        Route::patch('servis/{serviceBooking}/batal', [AccountServiceBookingController::class, 'cancel'])->name('service-bookings.cancel');
    });
});

/*
|--------------------------------------------------------------------------
| Admin (URL berbahasa Indonesia, controller berbahasa Inggris)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard')->name('home');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::patch('mobil/{car}/status', [AdminCarController::class, 'toggleActive'])->name('cars.toggle-active');

    // Galeri gambar mobil; scopeBindings: {image} harus milik {car}, selain itu 404.
    Route::prefix('mobil/{car}/gambar')->name('cars.images.')->controller(CarImageController::class)
        ->scopeBindings()
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::patch('{image}/utama', 'makePrimary')->name('primary');
            Route::patch('{image}/{direction}', 'move')->whereIn('direction', ['naik', 'turun'])->name('move');
            Route::delete('{image}', 'destroy')->name('destroy');
        });

    Route::resource('mobil', AdminCarController::class)
        ->except('show')
        ->parameters(['mobil' => 'car'])
        ->names('cars');

    Route::resource('merek', BrandController::class)
        ->except('show')
        ->parameters(['merek' => 'brand'])
        ->names('brands');

    Route::resource('kategori', CategoryController::class)
        ->except('show')
        ->parameters(['kategori' => 'category'])
        ->names('categories');

    Route::resource('promo', AdminPromoController::class)
        ->except('show')
        ->parameters(['promo' => 'promo'])
        ->names('promos');

    // Test drive & pengajuan dibuat customer (halaman publik); admin hanya melihat & mengubah status.
    Route::controller(AdminTestDriveController::class)->prefix('test-drive')->name('test-drives.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{testDrive}', 'show')->name('show');
        Route::patch('{testDrive}/status', 'updateStatus')->name('update-status');
    });

    Route::controller(PurchaseRequestController::class)->prefix('pengajuan')->name('purchase-requests.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{purchaseRequest}', 'show')->name('show');
        Route::patch('{purchaseRequest}/status', 'updateStatus')->name('update-status');
    });

    // JAF Service: master layanan (CRUD) + booking servis dari customer (lihat & ubah status).
    Route::resource('layanan', AdminServiceController::class)
        ->except('show')
        ->parameters(['layanan' => 'service'])
        ->names('services');

    Route::controller(AdminServiceBookingController::class)->prefix('servis')->name('service-bookings.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{serviceBooking}', 'show')->name('show');
        Route::patch('{serviceBooking}/status', 'updateStatus')->name('update-status');
    });

    // Laporan (hanya baca) + export CSV dengan periode yang sama.
    Route::get('laporan', [ReportController::class, 'index'])->name('reports.index');
    Route::get('laporan/export', [ReportController::class, 'export'])->name('reports.export');

    // Hanya baca: hapus user akan ikut menghapus riwayat test drive & pengajuan (cascade).
    Route::resource('pengguna', UserController::class)
        ->only(['index', 'show'])
        ->parameters(['pengguna' => 'user'])
        ->names('users');
});
