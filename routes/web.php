<?php

use App\Http\Controllers\Account\FavoriteController;
use App\Http\Controllers\Account\NotificationController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\PurchaseRequestController as AccountPurchaseRequestController;
use App\Http\Controllers\Account\ServiceBookingController as AccountServiceBookingController;
use App\Http\Controllers\Account\TestDriveController as AccountTestDriveController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\CarImageController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PromoController as AdminPromoController;
use App\Http\Controllers\Admin\PurchaseRequestController as AdminPurchaseRequestController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ServiceBookingController as AdminServiceBookingController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\TestDriveController as AdminTestDriveController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\CreditSimulationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\PurchaseRequestController;
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

// Bandingkan mobil (pilihan di session, tamu boleh).
Route::get('/bandingkan', [CompareController::class, 'index'])->name('compare.index');
Route::middleware('throttle:60,1')->controller(CompareController::class)->group(function () {
    Route::delete('/bandingkan', 'clear')->name('compare.clear');
    Route::post('/bandingkan/{car:slug}', 'store')->name('compare.store');
    Route::delete('/bandingkan/{car:slug}', 'destroy')->name('compare.destroy');
});
Route::get('/tentang-kami', [PageController::class, 'about'])->name('about');
Route::get('/kontak', [PageController::class, 'contact'])->name('contact');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:register')
        ->name('register.store');

    // Lupa / reset kata sandi (nama route password.reset dipakai notifikasi ResetPassword bawaan Laravel).
    Route::get('/lupa-kata-sandi', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/lupa-kata-sandi', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.email');
    Route::get('/reset-kata-sandi/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-kata-sandi', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.update');
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

    Route::get('/mobil/{car:slug}/ajukan', [PurchaseRequestController::class, 'create'])->name('purchase-requests.create');
    Route::post('/mobil/{car:slug}/ajukan', [PurchaseRequestController::class, 'store'])
        ->middleware('throttle:purchase-request')
        ->name('purchase-requests.store');

    Route::middleware('throttle:30,1')->controller(FavoriteController::class)->group(function () {
        Route::post('/mobil/{car:slug}/favorit', 'store')->name('favorites.store');
        Route::delete('/mobil/{car:slug}/favorit', 'destroy')->name('favorites.destroy');
    });

    Route::get('/servis/booking', [ServiceBookingController::class, 'create'])->name('service-bookings.create');
    Route::post('/servis/booking', [ServiceBookingController::class, 'store'])
        ->middleware('throttle:service-booking')
        ->name('service-bookings.store');

    Route::prefix('akun')->name('account.')->group(function () {
        Route::get('profil', [ProfileController::class, 'edit'])->name('profile');
        Route::patch('profil', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profil/password', [ProfileController::class, 'updatePassword'])
            ->middleware('throttle:6,1')
            ->name('profile.password');

        Route::get('test-drive', [AccountTestDriveController::class, 'index'])->name('test-drives.index');
        Route::patch('test-drive/{testDrive}/batal', [AccountTestDriveController::class, 'cancel'])->name('test-drives.cancel');

        Route::get('pengajuan', [AccountPurchaseRequestController::class, 'index'])->name('purchase-requests.index');
        Route::patch('pengajuan/{purchaseRequest}/batal', [AccountPurchaseRequestController::class, 'cancel'])->name('purchase-requests.cancel');

        Route::get('servis', [AccountServiceBookingController::class, 'index'])->name('service-bookings.index');
        Route::patch('servis/{serviceBooking}/batal', [AccountServiceBookingController::class, 'cancel'])->name('service-bookings.cancel');

        Route::get('favorit', [FavoriteController::class, 'index'])->name('favorites.index');

        Route::get('notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('notifikasi/baca-semua', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::get('notifikasi/{notification}', [NotificationController::class, 'open'])->whereUuid('notification')->name('notifications.open');
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

    Route::controller(AdminPurchaseRequestController::class)->prefix('pengajuan')->name('purchase-requests.')->group(function () {
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
