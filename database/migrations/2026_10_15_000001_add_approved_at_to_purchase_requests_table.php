<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Waktu pengajuan disetujui: dasar batas pembayaran pertama (DEALER_PAYMENT_DUE_DAYS).
     * Pengajuan lama yang sudah disetujui/selesai diisi dari updated_at (perkiraan).
     */
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('status');
        });

        DB::table('purchase_requests')
            ->whereIn('status', ['approved', 'completed'])
            ->update(['approved_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
