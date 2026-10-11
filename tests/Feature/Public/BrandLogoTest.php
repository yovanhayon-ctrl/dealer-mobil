<?php

namespace Tests\Feature\Public;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Logo JAF (public/images/logo-128.webp) menggantikan ikon mobil di navbar, footer, login, dan admin.
 */
class BrandLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_logo_dan_favicon_dipakai_di_publik_login_dan_admin(): void
    {
        $this->assertFileExists(public_path('images/logo-128.webp'));
        $this->assertFileExists(public_path('images/favicon-64.png'));

        $logo = 'src="'.asset('images/logo-128.webp').'"';
        $favicon = '<link rel="icon" type="image/png" href="'.asset('images/favicon-64.png').'">';

        $home = $this->get('/')->assertOk()->assertSee($favicon, false)->getContent();
        $this->assertSame(2, substr_count($home, $logo), 'Logo di navbar dan footer.');

        $this->get('/login')->assertOk()->assertSee($logo, false);

        $admin = $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk()->getContent();
        $this->assertSame(2, substr_count($admin, $logo), 'Logo di sidebar (layar besar) dan judul menu HP.');

        foreach ([$home, $admin] as $html) {
            $this->assertStringNotContainsString('bi-car-front-fill', $html);
        }
    }
}
