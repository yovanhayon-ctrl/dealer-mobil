<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thumbnail kecil (lebar 480 px) untuk kartu & daftar. Kosong = pakai foto utama.
     */
    public function up(): void
    {
        Schema::table('car_images', function (Blueprint $table) {
            $table->string('thumb_path')->nullable()->after('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_images', function (Blueprint $table) {
            $table->dropColumn('thumb_path');
        });
    }
};
