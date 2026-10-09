<?php

namespace App\Providers;

use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\ServiceBookingController;
use App\Http\Controllers\TestDriveController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Batas POST /register per menit per IP (rate limiter "register"). */
    public const MAX_REGISTRATIONS_PER_MINUTE = 5;

    /** Batas POST lupa/reset kata sandi per menit per IP (rate limiter "password-reset"). */
    public const MAX_PASSWORD_RESETS_PER_MINUTE = 5;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Lazy loading (penyebab N+1) langsung melempar exception di lokal & test.
        Model::preventLazyLoading(! $this->app->isProduction());

        // URL resource berbahasa Indonesia: /admin/merek/tambah, /admin/merek/{id}/ubah.
        Route::resourceVerbs([
            'create' => 'tambah',
            'edit' => 'ubah',
        ]);

        Paginator::useBootstrapFive();

        // Registrasi: batasi per IP agar tidak dipakai membuat banyak akun otomatis.
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(self::MAX_REGISTRATIONS_PER_MINUTE)
            ->by('register:'.$request->ip())
            ->response(fn () => redirect()->route('register')
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', 'Terlalu banyak percobaan pendaftaran. Coba lagi dalam 1 menit.')));

        // Lupa/reset kata sandi: batasi per IP agar tidak dipakai membanjiri email atau menebak token.
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(self::MAX_PASSWORD_RESETS_PER_MINUTE)
            ->by('password-reset:'.$request->ip())
            ->response(fn () => redirect()->back(fallback: route('password.request'))
                ->withInput($request->only('email'))
                ->with('error', 'Terlalu banyak percobaan. Coba lagi dalam 1 menit.')));

        // Booking test drive: batasi per user agar tidak di-spam; pesan ramah, bukan halaman 429.
        RateLimiter::for('test-drive-booking', fn (Request $request) => Limit::perMinute(TestDriveController::MAX_BOOKINGS_PER_MINUTE)
            ->by('test-drive-booking:'.$request->user()?->id)
            ->response(fn () => redirect()->back(fallback: route('test-drives.create'))
                ->withInput()
                ->with('error', 'Terlalu banyak percobaan booking. Coba lagi dalam 1 menit.')));

        RateLimiter::for('purchase-request', fn (Request $request) => Limit::perMinute(PurchaseRequestController::MAX_SUBMISSIONS_PER_MINUTE)
            ->by('purchase-request:'.$request->user()?->id)
            ->response(fn () => redirect()->back(fallback: route('cars.index'))
                ->withInput()
                ->with('error', 'Terlalu banyak percobaan pengajuan. Coba lagi dalam 1 menit.')));

        RateLimiter::for('service-booking', fn (Request $request) => Limit::perMinute(ServiceBookingController::MAX_BOOKINGS_PER_MINUTE)
            ->by('service-booking:'.$request->user()?->id)
            ->response(fn () => redirect()->back(fallback: route('service-bookings.create'))
                ->withInput()
                ->with('error', 'Terlalu banyak percobaan booking servis. Coba lagi dalam 1 menit.')));
    }
}
