<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengaturan dealer dari admin (profil, rekening, hero beranda). Nilai di sini menggantikan config dealer.* dari .env;
     * baris yang dihapus kembali ke nilai .env (lihat App\Models\Setting).
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->text('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
