<?php

namespace App\Providers;

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

        // Booking test drive: batasi per user agar tidak di-spam; pesan ramah, bukan halaman 429.
        RateLimiter::for('test-drive-booking', fn (Request $request) => Limit::perMinute(TestDriveController::MAX_BOOKINGS_PER_MINUTE)
            ->by('test-drive-booking:'.$request->user()?->id)
            ->response(fn () => redirect()->back(fallback: route('test-drives.create'))
                ->withInput()
                ->with('error', 'Terlalu banyak percobaan booking. Coba lagi dalam 1 menit.')));

        RateLimiter::for('service-booking', fn (Request $request) => Limit::perMinute(ServiceBookingController::MAX_BOOKINGS_PER_MINUTE)
            ->by('service-booking:'.$request->user()?->id)
            ->response(fn () => redirect()->back(fallback: route('service-bookings.create'))
                ->withInput()
                ->with('error', 'Terlalu banyak percobaan booking servis. Coba lagi dalam 1 menit.')));
    }
}
