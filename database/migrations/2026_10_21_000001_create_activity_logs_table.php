<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log aktivitas sensitif staf (akun, Pengaturan, keuangan, status pengajuan, hapus data, login), lihat App\Models\ActivityLog.
     * Nama & role pelaku disalin agar tetap terbaca bila akunnya dihapus atau rolenya berubah. Tidak ada kolom updated_at:
     * catatan tidak pernah diubah.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name', 100);
            $table->string('user_role', 30);
            $table->string('category', 20);
            $table->string('action', 50);
            $table->string('description', 500);
            $table->nullableMorphs('subject');
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['category', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
