<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pembayaran langsung di showroom (tunai / EDC / transfer di tempat) dicatat admin:
     * cara bayar, admin pencatat, dan file kwitansi opsional.
     */
    public function up(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->enum('method', ['transfer', 'cash', 'card', 'onsite_transfer'])->default('transfer')->after('type');
            $table->foreignId('recorded_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('file_path')->nullable()->change();
            $table->string('file_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn('method');
        });
    }
};
