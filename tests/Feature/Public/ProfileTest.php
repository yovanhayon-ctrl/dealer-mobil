<?php

namespace Tests\Feature\Public;

use App\Models\PurchaseRequest;
use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Profil akun (/akun/profil): data diri & ganti kata sandi.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'name' => 'Budi Santoso', 'email' => 'budi@example.test', 'phone' => '081211112222',
        ]);
    }

    private function updateProfile(array $data, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->customer)
            ->from(route('account.profile'))
            ->patch(route('account.profile.update'), [
                'name' => 'Budi Santoso', 'email' => 'budi@example.test', 'phone' => '081211112222', ...$data,
            ]);
    }

    private function updatePassword(array $data)
    {
        return $this->actingAs($this->customer)
            ->from(route('account.profile'))
            ->put(route('account.profile.password'), [
                'current_password' => 'password', 'password' => 'rahasia-baru-123', 'password_confirmation' => 'rahasia-baru-123', ...$data,
            ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('account.profile'))->assertRedirect(route('login'));
        $this->patch(route('account.profile.update'), ['name' => 'X'])->assertRedirect(route('login'));
        $this->put(route('account.profile.password'), [])->assertRedirect(route('login'));
    }

    public function test_halaman_profil_customer_menampilkan_data_dan_ringkasan_aktivitas(): void
    {
        TestDrive::factory()->recycle($this->customer)->create();
        PurchaseRequest::factory()->count(2)->recycle($this->customer)->create();
        ServiceBooking::factory()->recycle($this->customer)->create();

        $this->actingAs($this->customer)->get(route('account.profile'))
            ->assertOk()
            ->assertSee('<title>Profil Saya | ', false)
            ->assertSee('value="Budi Santoso"', false)
            ->assertSee('value="budi@example.test"', false)
            ->assertSee('value="081211112222"', false)
            ->assertSee('Customer')
            ->assertSeeInOrder(['account-nav', 'Pengajuan', '2', 'Test Drive', '1', 'Servis', '1'], false)
            ->assertDontSee('Dashboard Admin</a>', false);
    }

    public function test_admin_juga_punya_profil_dengan_link_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('account.profile'))
            ->assertOk()
            ->assertSee('Admin')
            ->assertSee('href="'.route('admin.dashboard').'"', false)
            ->assertDontSee('Servis Saya');
    }

    public function test_menu_profil_di_navbar(): void
    {
        $this->actingAs($this->customer)->get(route('home'))
            ->assertSee('href="'.route('account.profile').'"', false);
    }

    // ---------- Data diri ----------

    public function test_ubah_data_diri_dengan_normalisasi(): void
    {
        $this->updateProfile(['name' => '  Budi   Santoso  Putra ', 'email' => ' BUDI.Baru@Example.TEST ', 'phone' => '+62 812-3456-7890'])
            ->assertRedirect(route('account.profile'))
            ->assertSessionHas('success', 'Data diri berhasil diperbarui.');

        $this->customer->refresh();
        $this->assertSame('Budi Santoso Putra', $this->customer->name);
        $this->assertSame('budi.baru@example.test', $this->customer->email);
        $this->assertSame('081234567890', $this->customer->phone);
    }

    public function test_tanpa_perubahan_menampilkan_info(): void
    {
        $this->updateProfile([])->assertSessionHas('success', 'Tidak ada perubahan data diri.');
    }

    public function test_role_tidak_bisa_diubah_lewat_profil(): void
    {
        $this->updateProfile(['role' => 'admin'])->assertSessionHasNoErrors();

        $this->assertSame(User::ROLE_CUSTOMER, $this->customer->fresh()->role);
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function invalidProfiles(): array
    {
        return [
            'nama kosong' => [['name' => '  '], 'name'],
            'nama terlalu panjang' => [['name' => str_repeat('a', 101)], 'name'],
            'email tidak valid' => [['email' => 'bukan-email'], 'email'],
            'nomor tidak valid' => [['phone' => '021555'], 'phone'],
        ];
    }

    #[DataProvider('invalidProfiles')]
    public function test_validasi_data_diri(array $data, string $field): void
    {
        $this->updateProfile($data)
            ->assertRedirect(route('account.profile'))
            ->assertSessionHasErrors($field);

        $this->assertSame('Budi Santoso', $this->customer->fresh()->name);
    }

    public function test_email_milik_akun_lain_ditolak_tapi_email_sendiri_boleh(): void
    {
        User::factory()->create(['email' => 'siti@example.test']);

        $this->updateProfile(['email' => 'SITI@example.test'])
            ->assertSessionHasErrors(['email' => 'Email ini sudah dipakai akun lain.']);

        $this->updateProfile(['email' => 'budi@example.test', 'name' => 'Budi S.'])->assertSessionHasNoErrors();
    }

    // ---------- Kata sandi ----------

    public function test_ganti_kata_sandi(): void
    {
        $this->updatePassword([])
            ->assertRedirect(route('account.profile'))
            ->assertSessionHas('success', 'Kata sandi berhasil diganti.');

        $this->assertTrue(Hash::check('rahasia-baru-123', $this->customer->fresh()->password));
        $this->assertAuthenticatedAs($this->customer);
    }

    /**
     * @return array<string, array{array<string, string>, string}>
     */
    public static function invalidPasswords(): array
    {
        return [
            'kata sandi saat ini salah' => [['current_password' => 'salah-total'], 'current_password'],
            'kata sandi saat ini kosong' => [['current_password' => ''], 'current_password'],
            'terlalu pendek' => [['password' => 'pendek', 'password_confirmation' => 'pendek'], 'password'],
            'konfirmasi tidak cocok' => [['password_confirmation' => 'beda-sekali-123'], 'password'],
            'sama dengan yang lama' => [['password' => 'password', 'password_confirmation' => 'password'], 'password'],
        ];
    }

    #[DataProvider('invalidPasswords')]
    public function test_validasi_kata_sandi(array $data, string $field): void
    {
        $this->updatePassword($data)
            ->assertRedirect(route('account.profile'))
            ->assertSessionHasErrors($field);

        $this->assertTrue(Hash::check('password', $this->customer->fresh()->password));
    }

    public function test_pesan_kata_sandi_saat_ini_salah(): void
    {
        $this->updatePassword(['current_password' => 'salah-total'])
            ->assertSessionHasErrors(['current_password' => 'Kata sandi saat ini salah.']);
    }

    public function test_ganti_kata_sandi_dibatasi_rate_limit(): void
    {
        foreach (range(1, 6) as $i) {
            $this->updatePassword(['current_password' => 'salah-total'])->assertSessionHasErrors('current_password');
        }

        $this->updatePassword([])->assertTooManyRequests();
        $this->assertTrue(Hash::check('password', $this->customer->fresh()->password));
    }
}
