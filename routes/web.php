<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CarController;
use App\Http\Controllers\Admin\CarImageController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PromoController;
use App\Http\Controllers\Admin\PurchaseRequestController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\TestDriveController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::view('/', 'pages.home')->name('home');

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
| Admin (URL berbahasa Indonesia, controller berbahasa Inggris)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard')->name('home');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::patch('mobil/{car}/status', [CarController::class, 'toggleActive'])->name('cars.toggle-active');

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

    Route::resource('mobil', CarController::class)
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

    Route::resource('promo', PromoController::class)
        ->except('show')
        ->parameters(['promo' => 'promo'])
        ->names('promos');

    // Test drive & pengajuan dibuat customer (halaman publik); admin hanya melihat & mengubah status.
    Route::controller(TestDriveController::class)->prefix('test-drive')->name('test-drives.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{testDrive}', 'show')->name('show');
        Route::patch('{testDrive}/status', 'updateStatus')->name('update-status');
    });

    Route::controller(PurchaseRequestController::class)->prefix('pengajuan')->name('purchase-requests.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{purchaseRequest}', 'show')->name('show');
        Route::patch('{purchaseRequest}/status', 'updateStatus')->name('update-status');
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
