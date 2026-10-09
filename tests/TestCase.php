<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pengaman: RefreshDatabase mengosongkan seluruh database. Test hanya boleh berjalan di
     * SQLite in-memory (phpunit.xml) atau database MySQL berakhiran "_testing" (phpunit.mysql.xml),
     * jadi database lokal "dealer_mobil" tidak pernah ikut terhapus, misalnya saat config:cache aktif.
     * Dicek sebelum trait (RefreshDatabase) dijalankan.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $connection = config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' && ! str_ends_with($database, '_testing')) {
            throw new RuntimeException(
                "Test dibatalkan: database \"{$database}\" ({$connection}) bukan database testing. "
                .'Jalankan "php artisan optimize:clear" lalu gunakan phpunit.xml atau phpunit.mysql.xml.'
            );
        }
    }
}
