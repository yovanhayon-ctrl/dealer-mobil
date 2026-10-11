<?php

namespace Tests\Feature\Public;

use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Halaman akun & login tanpa pola template: inisial nama, label "Baru", satu warna tombol utama.
 */
class AccountCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_profil_menampilkan_inisial_nama(): void
    {
        $user = User::factory()->create(['name' => 'Budi Santoso Wijaya']);

        $this->actingAs($user)->get(route('account.profile'))->assertOk()
            ->assertSee('<span class="account-avatar" aria-hidden="true">BS</span>', false)
            ->assertDontSee('bi-person"></i></span>', false);
    }

    public function test_notifikasi_belum_dibaca_berlabel_baru(): void
    {
        $user = User::factory()->create();
        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\TestDriveStatusChanged',
            'data' => ['title' => 'Test Drive Dikonfirmasi', 'message' => 'Jadwal Anda dikonfirmasi.'],
        ]);

        $this->actingAs($user)->get(route('account.notifications.index'))->assertOk()
            ->assertSee('<span class="notification-new">Baru<span class="visually-hidden"> (belum dibaca)</span></span>', false);
    }

    public function test_servis_saya_tanpa_ikon_berulang(): void
    {
        $user = User::factory()->create();
        ServiceBooking::factory()->count(2)->for($user)->for(Service::factory())->create();

        $html = $this->actingAs($user)->get(route('account.service-bookings.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('service-card-icon', $html);
    }

    public function test_login_dan_daftar_memakai_tombol_merah_dan_label_whatsapp(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('<button type="submit" class="btn btn-accent w-100">', false);

        $this->get(route('register'))->assertOk()
            ->assertSee('<label for="phone" class="form-label">Nomor WhatsApp</label>', false)
            ->assertDontSee('Nomor HP');
    }
}
