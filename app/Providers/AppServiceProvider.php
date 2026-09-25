<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
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
    }
}
