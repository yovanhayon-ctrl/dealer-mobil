<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            $table->enum('vehicle_condition', ['baru', 'bekas'])->default('baru');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('mileage')->default(0);
            $table->unsignedBigInteger('price');
            $table->enum('transmission', ['manual', 'automatic']);
            $table->enum('fuel_type', ['bensin', 'diesel', 'hybrid', 'listrik']);
            $table->unsignedSmallInteger('engine_cc')->nullable();
            $table->unsignedTinyInteger('seats');
            $table->string('color', 50)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'vehicle_condition']);
            $table->index('price');
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
