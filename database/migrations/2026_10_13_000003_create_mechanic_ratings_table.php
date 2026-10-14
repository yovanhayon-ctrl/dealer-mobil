<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rating mekanik dari customer setelah servis selesai (satu per booking). Hanya untuk evaluasi internal.
     */
    public function up(): void
    {
        Schema::create('mechanic_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('mechanic_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['mechanic_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mechanic_ratings');
    }
};
