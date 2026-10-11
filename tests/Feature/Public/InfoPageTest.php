<?php

namespace Tests\Feature\Public;

use App\Http\Controllers\PageController;
use App\Models\Brand;
use App\Models\Car;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Halaman informasi: Tentang Kami (/tentang-kami) & Kontak (/kontak).
 */
class InfoPageTest extends TestCase
{
    use RefreshDatabase;

    private function dealer(array $overrides = []): void
    {
        config(['dealer' => [...config('dealer'), ...[
            'name' => 'JAF Dealer',
            'tagline' => 'Dream the Legacy. Drive the Future.',
            'address' => 'Jl. Otomotif Raya No. 88, Jakarta Selatan',
            'phone' => '021-5550-8888',
            'whatsapp' => '6281200008888',
            'email' => 'info@jafdealer.test',
            'hours' => 'Senin–Sabtu 08.00–17.00 WIB',
            'maps_embed_url' => 'https://www.google.com/maps?q=Jakarta+Selatan&output=embed',
        ], ...$overrides]]);
    }

    public function test_tentang_kami_menampilkan_identitas_statistik_dan_layanan(): void
    {
        $this->dealer();
        $nissan = Brand::factory()->create(['name' => 'Nissan']);
        Car::factory()->count(2)->recycle($nissan)->create();
        Car::factory()->inactive()->create();
        Service::factory()->count(3)->create();
        Service::factory()->inactive()->create();

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('<title>Tentang Kami | JAF Dealer</title>', false)
            ->assertSee('Tentang JAF Dealer')
            ->assertSee('Dream the Legacy. Drive the Future.')
            ->assertViewHas('stats', ['cars' => 2, 'brands' => 1, 'services' => 3])
            ->assertSeeInOrder(['Nissan Baru', 'Heritage &amp; Klasik Jepang', 'JAF Service'], false)
            ->assertSeeInOrder(['Temukan', 'Kenali', 'Pertimbangkan', 'Coba', 'Ajukan', 'Tindak lanjut'])
            ->assertSee('href="'.route('cars.index', ['kondisi' => 'baru']).'"', false)
            ->assertSee('href="'.route('services.index').'"', false)
            ->assertSee('href="'.route('contact').'"', false)
            ->assertSee('tidak mewakili dealer resmi Nissan');
    }

    public function test_kontak_menampilkan_semua_info_dengan_link(): void
    {
        $this->dealer();

        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('<title>Kontak | JAF Dealer</title>', false)
            ->assertSeeInOrder(['Alamat Showroom', 'Jl. Otomotif Raya No. 88', 'Telepon', '021-5550-8888', 'WhatsApp', 'Email', 'info@jafdealer.test', 'Jam Operasional', 'Senin–Sabtu'])
            ->assertSee('href="tel:02155508888"', false)
            ->assertSee('href="mailto:info@jafdealer.test"', false)
            ->assertSee('https://wa.me/6281200008888?text='.rawurlencode('Halo JAF Dealer, saya ingin bertanya.'), false)
            ->assertSee('<iframe src="https://www.google.com/maps?q=Jakarta+Selatan&amp;output=embed"', false)
            ->assertSee('href="'.route('test-drives.create').'"', false)
            ->assertSee('href="'.route('credit.index').'"', false);
    }

    public function test_kontak_tanpa_data_menyembunyikan_baris_kosong(): void
    {
        $this->dealer(['phone' => '', 'whatsapp' => '', 'email' => '', 'maps_embed_url' => '']);

        $this->get(route('contact'))
            ->assertOk()
            ->assertDontSee('Telepon')
            ->assertDontSee('Chat via WhatsApp')
            ->assertDontSee('mailto:', false)
            ->assertDontSee('<iframe', false)
            ->assertSee('Peta belum tersedia');
    }

    /**
     * @return array<string, array{string, ?string}>
     */
    public static function mapsUrls(): array
    {
        return [
            'sematkan peta' => ['https://www.google.com/maps/embed?pb=!1m18!1m12', 'https://www.google.com/maps/embed?pb=!1m18!1m12'],
            'format lama output=embed' => ['https://maps.google.com/maps?q=Jakarta&output=embed', 'https://maps.google.com/maps?q=Jakarta&output=embed'],
            'link maps biasa (bukan embed)' => ['https://www.google.com/maps?q=Jakarta', null],
            'http (bukan https)' => ['http://www.google.com/maps/embed?pb=1', null],
            'domain lain' => ['https://evil.test/maps/embed?pb=1', null],
            'javascript' => ['javascript:alert(1)', null],
            'kosong' => ['', null],
        ];
    }

    #[DataProvider('mapsUrls')]
    public function test_hanya_url_embed_google_maps_yang_dipasang(string $url, ?string $expected): void
    {
        $this->assertSame($expected, PageController::mapsEmbedUrl($url));
    }

    public function test_menu_navbar_dan_footer(): void
    {
        $this->get(route('home'))
            ->assertSee('href="'.route('about').'"', false)
            ->assertSee('href="'.route('contact').'"', false);

        $this->get(route('contact'))->assertSee('class="nav-link active" href="'.route('contact').'"', false);
    }
}
