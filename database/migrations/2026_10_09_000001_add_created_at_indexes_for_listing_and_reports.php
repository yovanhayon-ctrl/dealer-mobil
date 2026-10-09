<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 18: index untuk urutan "terbaru" dan rentang tanggal laporan.
 * Sebelumnya EXPLAIN menunjukkan full scan + filesort pada:
 * - katalog/beranda: cars WHERE is_active = 1 ORDER BY created_at DESC
 * - laporan & daftar admin pengajuan: purchase_requests rentang/urutan created_at
 * - daftar admin test drive & booking servis (urutan default terbaru).
 * Hanya menambah index (tidak mengubah data).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->index(['is_active', 'created_at']);
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('test_drives', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('service_bookings', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'created_at']);
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('test_drives', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
