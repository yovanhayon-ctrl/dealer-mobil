<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Waktu pengingat H-1 dikirim (reminders:send). Kosong = belum diingatkan; dikosongkan lagi saat dijadwalkan ulang.
     */
    public function up(): void
    {
        foreach (['test_drives', 'service_bookings'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->timestamp('reminded_at')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['test_drives', 'service_bookings'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('reminded_at');
            });
        }
    }
};
