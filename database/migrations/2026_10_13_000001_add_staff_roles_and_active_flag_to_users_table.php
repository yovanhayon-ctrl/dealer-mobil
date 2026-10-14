<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Role staf: owner (pemilik, kelola staf), admin, mechanic (mekanik bengkel); customer tetap.
     * is_active: staf yang dinonaktifkan owner tidak bisa login, riwayatnya tetap tersimpan.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['owner', 'admin', 'mechanic', 'customer'])->default('customer')->change();
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->enum('role', ['admin', 'customer'])->default('customer')->change();
        });
    }
};
