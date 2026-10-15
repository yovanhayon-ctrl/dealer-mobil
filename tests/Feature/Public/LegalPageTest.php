<?php

namespace Tests\Feature\Public;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Kebijakan Privasi (/kebijakan-privasi) & Syarat Ketentuan (/syarat-ketentuan).
 */
class LegalPageTest extends TestCase
{
    use RefreshDatabase;

    private function dealer(array $overrides = []): void
    {
        config(['dealer' => [...config('dealer'), ...[
            'name' => 'JAF Dealer',
            'address' => 'Jl. Otomotif Raya No. 88, Jakarta Selatan',
            'whatsapp' => '6281200008888',
            'email' => 'info@jafdealer.test',
        ], ...$overrides]]);
    }

    /**
     * @return array<string, array{string, string, list<string>}>
     */
    public static function pages(): array
    {
        return [
            'kebijakan privasi' => ['privacy', 'Kebijakan Privasi', ['Data yang kami kumpulkan', 'Hak Anda', 'Undang-Undang Nomor 27 Tahun 2022', 'penyimpanan privat']],
            'syarat ketentuan' => ['terms', 'Syarat &amp; Ketentuan', ['Pengajuan pembelian', 'Pembayaran', 'tidak pernah', 'hukum Republik Indonesia']],
        ];
    }

    /**
     * @param  list<string>  $texts
     */
    #[DataProvider('pages')]
    public function test_halaman_bisa_dibuka_tamu_dengan_kontak_dari_konfigurasi(string $route, string $title, array $texts): void
    {
        $this->dealer();

        $response = $this->get(route($route))
            ->assertOk()
            ->assertSee("<title>{$title} | JAF Dealer</title>", false)
            ->assertSee('Berlaku sejak 15 Oktober 2026')
            ->assertSee('Daftar isi')
            ->assertSee('mailto:info@jafdealer.test', false)
            ->assertSee('https://wa.me/6281200008888', false)
            ->assertSee('Jl. Otomotif Raya No. 88, Jakarta Selatan')
            ->assertSee('<meta name="description"', false);

        foreach ($texts as $text) {
            $response->assertSee($text, false);
        }
    }

    #[DataProvider('pages')]
    public function test_kontak_kosong_tidak_ditampilkan(string $route): void
    {
        $this->dealer(['email' => '', 'whatsapp' => '', 'address' => '']);

        $this->get(route($route))
            ->assertOk()
            ->assertDontSee('mailto:', false)
            ->assertDontSee('https://wa.me/', false)
            ->assertSee(route('contact'), false);
    }

    public function test_daftar_isi_menunjuk_ke_setiap_bagian(): void
    {
        $html = $this->get(route('privacy'))->getContent();

        preg_match_all('/<a href="#([a-z-]+)">/', $html, $links);
        $this->assertCount(8, $links[1]);
        foreach ($links[1] as $id) {
            $this->assertStringContainsString("<h2 id=\"{$id}\">", $html);
        }
    }

    public function test_tautan_di_footer_dan_form_daftar(): void
    {
        $this->get(route('home'))
            ->assertSee('href="'.route('privacy').'"', false)
            ->assertSee('href="'.route('terms').'"', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSeeInOrder(['Dengan mendaftar, Anda menyetujui', 'Syarat &amp; Ketentuan', 'Kebijakan Privasi', 'Daftar'], false)
            ->assertSee('<a href="'.route('terms').'" target="_blank" rel="noopener">', false);
    }

    public function test_masuk_sitemap(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee('<loc>'.route('privacy').'</loc>', false)
            ->assertSee('<loc>'.route('terms').'</loc>', false);
    }
}
