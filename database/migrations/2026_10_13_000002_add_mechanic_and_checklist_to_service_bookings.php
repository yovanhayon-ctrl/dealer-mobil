<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penugasan mekanik & checklist pekerjaan servis:
     * - services.checklist: daftar pekerjaan bawaan layanan (satu per baris), disalin ke booking saat mekanik ditugaskan.
     * - service_bookings: mekanik yang ditugaskan, temuan/rekomendasi mekanik, waktu mulai & selesai.
     * - service_booking_tasks: checklist per booking (belum / selesai / dilewati + alasan).
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->text('checklist')->nullable()->after('description');
        });

        Schema::table('service_bookings', function (Blueprint $table) {
            $table->foreignId('mechanic_id')->nullable()->after('service_id')->constrained('users')->nullOnDelete();
            $table->text('findings')->nullable()->after('admin_note');
            $table->timestamp('started_at')->nullable()->after('findings');
            $table->timestamp('finished_at')->nullable()->after('started_at');
        });

        Schema::create('service_booking_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_booking_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->unsignedSmallInteger('position')->default(0);
            $table->enum('status', ['pending', 'done', 'skipped'])->default('pending');
            $table->string('note', 255)->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index(['service_booking_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_booking_tasks');

        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mechanic_id');
            $table->dropColumn(['findings', 'started_at', 'finished_at']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('checklist');
        });
    }
};
