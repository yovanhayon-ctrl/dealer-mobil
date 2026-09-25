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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('car_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('car_price');
            $table->enum('payment_method', ['cash', 'credit']);
            $table->unsignedBigInteger('down_payment')->nullable();
            $table->unsignedTinyInteger('tenor_months')->nullable();
            $table->decimal('interest_rate', 5, 2)->nullable();
            $table->unsignedBigInteger('monthly_installment')->nullable();
            $table->string('phone', 20);
            $table->text('address');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'processing', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
