<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Role staf baru: super_admin, sales, customer_service (lihat User::PERMISSIONS). Role "admin" tetap, berlabel Admin Dealer.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['owner', 'super_admin', 'admin', 'sales', 'customer_service', 'mechanic', 'customer'])
                ->default('customer')->change();
        });
    }

    /**
     * Reverse the migrations. Akun dengan role baru harus diubah dulu ke role lama.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['owner', 'admin', 'mechanic', 'customer'])->default('customer')->change();
        });
    }
};
