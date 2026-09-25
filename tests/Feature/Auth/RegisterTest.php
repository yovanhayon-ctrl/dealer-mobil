<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '+62 812-3456-7890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }

    public function test_halaman_register_bisa_dibuka(): void
    {
        $this->get('/register')->assertOk()->assertSee('Daftar Akun');
    }

    public function test_register_membuat_customer_dan_langsung_login(): void
    {
        $response = $this->post('/register', $this->validData());

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('success');

        $user = User::firstWhere('email', 'budi@example.com');
        $this->assertNotNull($user);
        $this->assertSame(User::ROLE_CUSTOMER, $user->role);
        $this->assertSame('081234567890', $user->phone);
        $this->assertAuthenticatedAs($user);
    }

    public function test_role_admin_dari_input_diabaikan(): void
    {
        $this->post('/register', $this->validData(['role' => 'admin']));

        $this->assertSame(User::ROLE_CUSTOMER, User::firstWhere('email', 'budi@example.com')->role);
    }

    public function test_validasi_gagal_dengan_pesan_bahasa_indonesia(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => '',
            'email' => 'bukan-email',
            'phone' => '12345',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'name' => 'Nama wajib diisi.',
            'email' => 'Email harus berupa alamat email yang valid.',
            'phone' => 'Format nomor HP tidak valid. Gunakan nomor Indonesia, contoh 081234567890.',
        ]);
        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_email_tidak_boleh_duplikat(): void
    {
        User::factory()->create(['email' => 'budi@example.com']);

        $this->post('/register', $this->validData())
            ->assertSessionHasErrors(['email' => 'Email sudah terdaftar.']);
    }
}
