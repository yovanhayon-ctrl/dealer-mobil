<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 19: halaman error berbahasa Indonesia (tanpa detail teknis).
 */
class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_404_berbahasa_indonesia(): void
    {
        $this->get('/halaman-tidak-ada')
            ->assertNotFound()
            ->assertSee('<title>Halaman Tidak Ditemukan — ', false)
            ->assertSee('404 — Halaman Tidak Ditemukan')
            ->assertSee('href="'.route('cars.index').'"', false);

        $this->get('/mobil/slug-tidak-ada')->assertNotFound()->assertSee('Halaman Tidak Ditemukan');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function views(): array
    {
        return [
            '419 sesi berakhir' => ['errors.419', '419 — Sesi Berakhir'],
            '429 terlalu banyak' => ['errors.429', '429 — Terlalu Banyak Permintaan'],
            '500 kesalahan server' => ['errors.500', '500 — Terjadi Kesalahan'],
            '503 pemeliharaan' => ['errors.503', '503 — Sedang Pemeliharaan'],
        ];
    }

    #[DataProvider('views')]
    public function test_halaman_error_lain_berbahasa_indonesia(string $view, string $heading): void
    {
        $html = view($view)->render();

        $this->assertStringContainsString($heading, $html);
        $this->assertStringContainsString('Kembali ke Beranda', $html);
    }

    public function test_halaman_500_dan_503_tidak_bergantung_database(): void
    {
        // Layout mandiri tanpa navbar/auth: tidak ada query, jadi tetap tampil walau database bermasalah.
        $this->actingAs(User::factory()->create());
        DB::enableQueryLog();
        DB::flushQueryLog();

        foreach (['errors.500', 'errors.503'] as $view) {
            $this->assertStringContainsString('Kembali ke Beranda', view($view)->render());
        }

        $this->assertSame([], DB::getQueryLog());
    }
}
