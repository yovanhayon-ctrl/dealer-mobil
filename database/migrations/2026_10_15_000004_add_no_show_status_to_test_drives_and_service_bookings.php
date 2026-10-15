<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status "no_show" (Tidak datang): customer tidak hadir pada jadwal yang sudah dikonfirmasi.
     * Dipisah dari "cancelled" agar bisa dibedakan di daftar & laporan.
     */
    public function up(): void
    {
        Schema::table('test_drives', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('pending')->change();
        });

        Schema::table('service_bookings', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations. Data berstatus no_show harus diubah dulu (mis. ke cancelled).
     */
    public function down(): void
    {
        Schema::table('test_drives', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending')->change();
        });

        Schema::table('service_bookings', function (Blueprint $table) {
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending')->change();
        });
    }
};
