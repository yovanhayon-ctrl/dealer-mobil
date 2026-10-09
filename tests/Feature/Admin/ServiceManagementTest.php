<?php

namespace Tests\Feature\Admin;

use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    private function validData(array $overrides = []): array
    {
        return [
            'name' => 'Servis Berkala',
            'description' => 'Ganti oli dan filter.',
            'price_from' => '450.000',
            'duration_minutes' => '120',
            'is_active' => '1',
            ...$overrides,
        ];
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $service = Service::factory()->create();

        $this->get('/admin/layanan')->assertRedirect(route('login'));
        $this->get('/admin/layanan/tambah')->assertRedirect(route('login'));
        $this->post('/admin/layanan', $this->validData())->assertRedirect(route('login'));
        $this->get("/admin/layanan/{$service->id}/ubah")->assertRedirect(route('login'));
        $this->put("/admin/layanan/{$service->id}", $this->validData())->assertRedirect(route('login'));
        $this->delete("/admin/layanan/{$service->id}")->assertRedirect(route('login'));

        $this->assertModelExists($service);
    }

    public function test_customer_mendapat_403(): void
    {
        $service = Service::factory()->create();

        $this->actingAs(User::factory()->create());
        $this->get('/admin/layanan')->assertForbidden();
        $this->get('/admin/layanan/tambah')->assertForbidden();
        $this->post('/admin/layanan', $this->validData())->assertForbidden();
        $this->get("/admin/layanan/{$service->id}/ubah")->assertForbidden();
        $this->put("/admin/layanan/{$service->id}", $this->validData())->assertForbidden();
        $this->delete("/admin/layanan/{$service->id}")->assertForbidden();

        $this->assertModelExists($service);
        $this->assertDatabaseMissing('services', ['name' => 'Servis Berkala']);
    }

    public function test_daftar_menampilkan_harga_jumlah_booking_dan_filter(): void
    {
        $berkala = Service::factory()->create(['name' => 'Servis Berkala', 'price_from' => 450_000, 'duration_minutes' => 120]);
        Service::factory()->create(['name' => 'Suku Cadang', 'price_from' => null, 'duration_minutes' => null]);
        Service::factory()->inactive()->create(['name' => 'Cuci Mesin']);
        ServiceBooking::factory()->count(2)->recycle($berkala)->create();

        $this->actingAs($this->admin)->get(route('admin.services.index'))
            ->assertOk()
            ->assertSeeInOrder(['Cuci Mesin', 'Nonaktif', 'Servis Berkala', 'Rp 450.000', '120 menit', '2', 'Aktif', 'Suku Cadang', 'Hubungi dealer'])
            ->assertSee('Sudah dipakai 2 booking');

        $this->get(route('admin.services.index', ['q' => 'berkala']))
            ->assertSee('Servis Berkala')->assertDontSee('Suku Cadang');

        $this->get(route('admin.services.index', ['status' => 'nonaktif']))
            ->assertSee('Cuci Mesin')->assertDontSee('Servis Berkala');

        $this->get(route('admin.services.index', ['status' => 'aneh']))
            ->assertSee('Cuci Mesin')->assertSee('Servis Berkala');
    }

    public function test_empty_state(): void
    {
        $this->actingAs($this->admin)->get(route('admin.services.index'))
            ->assertOk()->assertSee('Belum ada layanan');
    }

    public function test_admin_bisa_menambah_layanan_dengan_titik_ribuan(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.services.store'), $this->validData())
            ->assertRedirect(route('admin.services.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('services', [
            'name' => 'Servis Berkala',
            'slug' => 'servis-berkala',
            'price_from' => 450_000,
            'duration_minutes' => 120,
            'is_active' => true,
        ]);
    }

    public function test_harga_dan_durasi_boleh_kosong_dan_checkbox_kosong_berarti_nonaktif(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.services.store'), $this->validData(['price_from' => '', 'duration_minutes' => '', 'is_active' => null]))
            ->assertSessionHasNoErrors();

        $service = Service::sole();
        $this->assertNull($service->price_from);
        $this->assertNull($service->duration_minutes);
        $this->assertFalse($service->is_active);
    }

    public function test_validasi(): void
    {
        Service::factory()->create(['name' => 'Detailing']);

        $this->actingAs($this->admin)
            ->post(route('admin.services.store'), $this->validData(['name' => ' detailing ', 'price_from' => '0', 'duration_minutes' => '5']))
            ->assertSessionHasErrors(['name', 'price_from', 'duration_minutes']);

        $this->post(route('admin.services.store'), $this->validData(['name' => '', 'description' => str_repeat('a', 2001), 'duration_minutes' => '1500']))
            ->assertSessionHasErrors(['name', 'description', 'duration_minutes']);

        $this->assertSame(1, Service::count());
    }

    public function test_admin_bisa_mengubah_layanan_dan_slug_ikut_nama(): void
    {
        $service = Service::factory()->create(['name' => 'Inspeksi', 'slug' => 'inspeksi']);

        $this->actingAs($this->admin)->get(route('admin.services.edit', $service))
            ->assertOk()->assertSee('Inspeksi');

        $this->put(route('admin.services.update', $service), $this->validData(['name' => 'Inspeksi Kendaraan', 'is_active' => null]))
            ->assertRedirect(route('admin.services.index'));

        $service->refresh();
        $this->assertSame('inspeksi-kendaraan', $service->slug);
        $this->assertFalse($service->is_active);

        // Nama sendiri tidak dianggap duplikat.
        $this->put(route('admin.services.update', $service), $this->validData(['name' => 'INSPEKSI KENDARAAN']))
            ->assertSessionHasNoErrors();
    }

    public function test_hapus_layanan_tanpa_booking(): void
    {
        $service = Service::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.services.destroy', $service))
            ->assertRedirect(route('admin.services.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($service);
    }

    public function test_hapus_ditolak_jika_sudah_dipakai_booking(): void
    {
        $booking = ServiceBooking::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.services.destroy', $booking->service))
            ->assertRedirect(route('admin.services.index'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, '1 booking servis'));

        $this->assertModelExists($booking->service);
    }

    public function test_menu_sidebar_tampil(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertSee(route('admin.services.index'))
            ->assertSee(route('admin.service-bookings.index'));
    }
}
