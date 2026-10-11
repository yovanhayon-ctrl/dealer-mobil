<?php

namespace Tests\Feature\Public;

use App\Models\Car;
use App\Models\CarImage;
use App\Models\Promo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * SEO & berbagi tautan: sitemap.xml, robots.txt, Open Graph, canonical, noindex, dan JSON-LD.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_berisi_halaman_publik_mobil_aktif_dan_promo_berjalan(): void
    {
        $active = Car::factory()->create();
        $inactive = Car::factory()->inactive()->create();
        $running = Promo::factory()->create();
        $ended = Promo::factory()->ended()->create();
        $scheduled = Promo::factory()->scheduled()->create();
        $disabled = Promo::factory()->inactive()->create();
        $hiddenCarPromo = Promo::factory()->forCar($inactive)->create();

        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'Sitemap harus XML yang valid.');

        $urls = [];
        foreach ($xml->url as $url) {
            $urls[] = (string) $url->loc;
        }

        foreach (['home', 'cars.index', 'promos.index', 'services.index', 'credit.index', 'about', 'contact'] as $name) {
            $this->assertContains(route($name), $urls);
        }
        $this->assertContains(route('cars.show', $active), $urls);
        $this->assertContains(route('promos.show', $running), $urls);

        foreach ([route('cars.show', $inactive), route('promos.show', $ended), route('promos.show', $scheduled),
            route('promos.show', $disabled), route('promos.show', $hiddenCarPromo)] as $hidden) {
            $this->assertNotContains($hidden, $urls);
        }

        $this->assertStringNotContainsString('/admin', $response->getContent());
    }

    public function test_jumlah_query_sitemap_tetap(): void
    {
        Car::factory()->count(2)->create();
        Promo::factory()->count(2)->create();

        DB::enableQueryLog();
        $this->get('/sitemap.xml')->assertOk();
        $before = count(DB::getQueryLog());

        Car::factory()->count(5)->create();
        Promo::factory()->count(5)->create();

        DB::flushQueryLog();
        $this->get('/sitemap.xml')->assertOk();

        $this->assertSame($before, count(DB::getQueryLog()));
    }

    public function test_robots_menutup_admin_dan_akun_serta_menyebut_sitemap(): void
    {
        $response = $this->get('/robots.txt')->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $this->assertFileDoesNotExist(public_path('robots.txt'), 'File statis akan menutupi route robots.txt.');

        $body = $response->getContent();
        $this->assertStringContainsString('User-agent: *', $body);
        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertStringContainsString('Disallow: /akun', $body);
        $this->assertStringContainsString('Disallow: /login', $body);
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $body);
    }

    public function test_beranda_dan_katalog_punya_open_graph_dengan_gambar_bawaan(): void
    {
        $this->assertFileExists(public_path('images/og-default.jpg'));

        foreach (['/', '/mobil', '/promo', '/kontak'] as $path) {
            $this->get($path)->assertOk()
                ->assertSee('<meta property="og:site_name" content="'.e(config('dealer.name')).'">', false)
                ->assertSee('<meta property="og:locale" content="id_ID">', false)
                ->assertSee('<meta property="og:type" content="website">', false)
                ->assertSee('<meta property="og:image" content="'.asset('images/og-default.jpg').'">', false)
                ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
                ->assertDontSee('name="robots"', false);
        }
    }

    public function test_canonical_katalog_tanpa_filter(): void
    {
        $this->get('/mobil?kondisi=baru&urut=termurah')->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('cars.index').'">', false)
            ->assertSee('<meta property="og:url" content="'.route('cars.index').'">', false);
    }

    public function test_detail_mobil_memakai_foto_mobil_dan_data_terstruktur(): void
    {
        $car = Car::factory()->create(['price' => 300_000_000, 'stock' => 2]);
        Promo::factory()->forCar($car, 20_000_000)->create();
        $image = CarImage::create(['car_id' => $car->id, 'path' => "cars/{$car->id}/foto.webp", 'is_primary' => true, 'sort_order' => 1]);

        $response = $this->get(route('cars.show', $car))->assertOk()
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="og:image" content="'.$image->url.'">', false)
            ->assertSee('<link rel="canonical" href="'.route('cars.show', $car).'">', false);

        // Tag canonical/og tidak boleh dobel.
        $this->assertSame(1, substr_count($response->getContent(), 'rel="canonical"'));
        $this->assertSame(1, substr_count($response->getContent(), 'property="og:image"'));

        preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $response->getContent(), $match);
        $this->assertNotEmpty($match, 'JSON-LD tidak ditemukan.');
        $data = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Car', $data['@type']);
        $this->assertSame($car->brand->name, $data['brand']['name']);
        $this->assertSame(280_000_000, $data['offers']['price']);
        $this->assertSame('IDR', $data['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/InStock', $data['offers']['availability']);
        $this->assertSame('https://schema.org/NewCondition', $data['itemCondition']);
        $this->assertSame([$image->url], $data['image']);
    }

    public function test_json_ld_tidak_bisa_keluar_dari_tag_script(): void
    {
        $car = Car::factory()->outOfStock()->create(['description' => 'Bagus </script><script>alert(1)</script>']);

        $html = $this->get(route('cars.show', $car))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)', $html);
        preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $html, $match);
        $data = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('https://schema.org/OutOfStock', $data['offers']['availability']);
        $this->assertArrayNotHasKey('image', $data);
    }

    public function test_detail_promo_memakai_foto_mobil_bila_tanpa_banner(): void
    {
        $car = Car::factory()->create();
        $image = CarImage::create(['car_id' => $car->id, 'path' => "cars/{$car->id}/foto.webp", 'is_primary' => true, 'sort_order' => 1]);
        $carPromo = Promo::factory()->forCar($car)->create();
        $generalPromo = Promo::factory()->create();

        $this->get(route('promos.show', $carPromo))->assertOk()
            ->assertSee('<meta property="og:image" content="'.$image->url.'">', false)
            ->assertSee('<meta property="og:title" content="'.e($carPromo->title.' | '.config('dealer.name')).'">', false);

        $this->get(route('promos.show', $generalPromo))->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset('images/og-default.jpg').'">', false);
    }

    public function test_halaman_admin_akun_dan_login_noindex(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('og:title', false);

        $this->get('/bandingkan')->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->actingAs(User::factory()->create())->get(route('account.profile'))->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
