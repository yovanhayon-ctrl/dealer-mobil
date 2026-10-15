<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengembalian dana untuk pengajuan yang dibatalkan/ditolak setelah menerima pembayaran,
     * dan penutupan tanpa pengembalian (mis. tanda jadi hangus) beserta alasannya.
     */
    public function up(): void
    {
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('refunded_on');
            $table->enum('method', ['transfer', 'cash']);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->timestamp('refund_closed_at')->nullable()->after('approved_at');
            $table->text('refund_closed_note')->nullable()->after('refund_closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['refund_closed_at', 'refund_closed_note']);
        });

        Schema::dropIfExists('payment_refunds');
    }
};
