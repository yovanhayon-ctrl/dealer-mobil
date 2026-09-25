<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_customer_mendapat_403(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin/dashboard')
            ->assertForbidden()
            ->assertSee('Akses Ditolak');

        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }

    public function test_admin_bisa_membuka_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Selamat datang, '.$admin->name);

        $this->actingAs($admin)->get('/admin')->assertRedirect('/admin/dashboard');
    }

    public function test_seeder_admin_membaca_config_dan_aman_dijalankan_ulang(): void
    {
        config([
            'dealer.admin.email' => 'admin@dealermobil.test',
            'dealer.admin.password' => 'KataSandiAdmin123',
        ]);

        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::where('email', 'admin@dealermobil.test')->count());
        $admin = User::firstWhere('email', 'admin@dealermobil.test');
        $this->assertTrue($admin->isAdmin());

        $this->post('/login', ['email' => 'admin@dealermobil.test', 'password' => 'KataSandiAdmin123'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_seeder_admin_gagal_jika_env_kosong(): void
    {
        config(['dealer.admin.email' => null, 'dealer.admin.password' => null]);

        $this->expectException(\RuntimeException::class);

        $this->seed(AdminUserSeeder::class);
    }
}
