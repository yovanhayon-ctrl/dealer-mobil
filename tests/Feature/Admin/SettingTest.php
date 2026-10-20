<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\Admin\BankAccountChanged;
use App\Notifications\PaymentDueReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admin > Pengaturan: profil dealer, teks "Siapa Kami", rekening pembayaran, dan hero beranda menggantikan nilai .env.
 */
class SettingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        // Nilai bawaan ".env" yang sama untuk semua test (config & cadangannya, lihat Setting::apply()).
        $defaults = [
            'name' => 'JAF Dealer', 'tagline' => 'Nissan Heritage', 'address' => 'Jl. Ahmad Yani 1, Kupang', 'phone' => '0380-821000',
            'whatsapp' => '6281200000000', 'email' => 'info@jaf.test', 'hours' => 'Senin-Sabtu 08.00-17.00', 'maps_embed_url' => '',
            'bank' => ['name' => 'BCA', 'account' => '1111111111', 'holder' => 'PT JAF Dealer'],
        ];
        config(['dealer' => [...config('dealer'), ...$defaults], 'dealer_defaults' => [...config('dealer_defaults', []), ...$defaults]]);

        $this->owner = User::factory()->owner()->create(['name' => 'Fadlan']);
    }

    /**
     * @return array<string, mixed>
     */
    private function form(array $overrides = []): array
    {
        return [
            'name' => 'JAF Dealer', 'tagline' => 'Nissan Heritage', 'address' => 'Jl. Ahmad Yani 1, Kupang', 'phone' => '0380-821000',
            'whatsapp' => '6281200000000', 'email' => 'info@jaf.test', 'hours' => 'Senin-Sabtu 08.00-17.00', 'maps_embed_url' => '',
            'about_text' => '', 'bank_name' => 'BCA', 'bank_account' => '1111111111', 'bank_holder' => 'PT JAF Dealer',
            'hero_kicker' => '', 'hero_title' => 'Nissan pilihan, dari klasik sampai terbaru.',
            'hero_lead' => 'Lihat stok, hitung cicilan, dan jadwalkan test drive langsung dari sini.', 'hero_background' => 'video',
            ...$overrides,
        ];
    }

    private function save(array $overrides = [], ?User $user = null)
    {
        return $this->actingAs($user ?? $this->owner)->put(route('admin.settings.update'), $this->form($overrides));
    }

    public function test_halaman_menampilkan_nilai_saat_ini(): void
    {
        $this->actingAs($this->owner)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('value="JAF Dealer"', false)
            ->assertSee('value="1111111111"', false)
            ->assertDontSee('Kosongkan untuk kembali ke bawaan')
            ->assertSee('href="'.route('admin.settings.edit').'"', false);

        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.settings.edit'))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.settings.edit'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.settings.update'), $this->form())->assertForbidden();
    }

    public function test_nilai_sama_dengan_bawaan_tidak_disimpan(): void
    {
        $this->save()->assertRedirect(route('admin.settings.edit'))->assertSessionHas('success', 'Pengaturan disimpan.');

        $this->assertSame(0, Setting::count());
    }

    public function test_pengaturan_menggantikan_env_di_halaman_publik_dan_notifikasi(): void
    {
        $this->save([
            'name' => 'JAF Nissan Kupang', 'phone' => '0380 999 111', 'whatsapp' => '+62 813-1111-2222', 'address' => 'Jl. El Tari 9',
            'about_text' => "Paragraf pertama.\r\nBaris kedua.\r\n\r\n\r\n\r\nParagraf <b>kedua</b>.",
            'bank_name' => 'Mandiri', 'bank_account' => '123-456-7890', 'bank_holder' => 'PT JAF Nissan',
            'hero_kicker' => 'Promo Oktober', 'hero_title' => 'Serena e-Power sudah tiba.', 'hero_lead' => '',
        ])->assertSessionHasNoErrors();

        $this->assertSame('6281311112222', Setting::find('whatsapp')->value);
        $this->assertSame('1234567890', Setting::find('bank.account')->value);
        $this->assertSame($this->owner->id, Setting::find('name')->updated_by);
        $this->assertNull(Setting::find('tagline'));

        // Seperti request baru: config kembali ke .env, lalu pengaturan diterapkan dari cache/database.
        Cache::forget(Setting::CACHE_KEY);
        config(['dealer.name' => 'JAF Dealer', 'dealer.bank.name' => 'BCA']);
        Setting::apply();

        $this->get(route('home'))
            ->assertSee('Promo Oktober')
            ->assertSee('Serena e-Power sudah tiba.')
            // Teks di bawah judul dikosongkan: kembali ke teks bawaan.
            ->assertSee('Lihat stok, hitung cicilan, dan jadwalkan test drive langsung dari sini.')
            ->assertSee('JAF Nissan Kupang');
        $this->get(route('contact'))->assertSee('0380 999 111')->assertSee('Jl. El Tari 9')->assertSee('wa.me/6281311112222', false);
        $this->get(route('about'))
            ->assertSee('Paragraf pertama.<br />'."\n".'Baris kedua.', false)
            ->assertSee('Paragraf &lt;b&gt;kedua&lt;/b&gt;.', false)
            ->assertDontSee('dealer mobil berbasis web');
        $this->assertSame(
            'Transfer ke Mandiri 1234567890 a.n. PT JAF Nissan atau bayar langsung di showroom, lalu kirim bukti transfer di menu Pengajuan Saya.',
            PaymentDueReminder::howToPay()
        );
    }

    public function test_mengosongkan_kembali_ke_nilai_env(): void
    {
        $this->save(['tagline' => 'Tagline Admin', 'hero_kicker' => 'Kicker Admin']);
        $this->assertSame('Tagline Admin', config('dealer.tagline'));
        $this->actingAs($this->owner)->get(route('admin.settings.edit'))->assertSee('Kosongkan untuk kembali ke bawaan: Nissan Heritage');

        $this->save(['tagline' => '', 'hero_kicker' => '']);

        $this->assertSame(0, Setting::count());
        $this->assertSame('Nissan Heritage', config('dealer.tagline'));
        $this->assertNull(config('dealer.hero.kicker'));
        $this->get(route('home'))->assertSee('<p class="hero-kicker">Nissan Heritage</p>', false);
    }

    public function test_validasi(): void
    {
        $this->save([
            'name' => '', 'whatsapp' => '021555', 'email' => 'bukan-email', 'phone' => 'telepon',
            'maps_embed_url' => 'https://example.com/peta', 'bank_account' => '12AB', 'hero_title' => '', 'hero_background' => 'slide',
        ])->assertSessionHasErrors(['name', 'whatsapp', 'email', 'phone', 'maps_embed_url', 'bank_account', 'hero_title', 'hero_background']);

        $this->save(['bank_name' => 'BRI', 'bank_account' => '', 'bank_holder' => ''])
            ->assertSessionHasErrors(['bank_name' => 'Lengkapi nama bank, nomor rekening, dan atas nama (atau kosongkan ketiganya).']);

        $this->save(['hero_background' => 'image'])
            ->assertSessionHasErrors(['hero_image' => 'Unggah gambar banner untuk latar "Gambar banner unggahan".']);

        $this->save(['hero_background' => 'image', 'hero_image' => UploadedFile::fake()->image('kecil.jpg', 800, 400)])
            ->assertSessionHasErrors('hero_image');

        $this->assertSame(0, Setting::count());

        $this->save(['maps_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18'])->assertSessionHasNoErrors();
        $this->assertSame('https://www.google.com/maps/embed?pb=!1m18', config('dealer.maps_embed_url'));
    }

    public function test_gambar_banner_diunggah_diganti_dan_dihapus(): void
    {
        $this->save(['hero_background' => 'image', 'hero_image' => UploadedFile::fake()->image('banner.jpg', 2400, 1200)])
            ->assertSessionHasNoErrors();

        $first = Setting::find('hero.image')->value;
        $this->assertStringStartsWith('settings/', $first);
        $this->assertStringEndsWith('.webp', $first);
        Storage::disk('public')->assertExists($first);
        $this->assertSame(1920, getimagesize(Storage::disk('public')->path($first))[0]);

        $this->get(route('home'))
            ->assertSee('has-banner', false)
            ->assertSee(Storage::disk('public')->url($first), false)
            ->assertDontSee('hero-feature', false);
        $this->actingAs($this->owner)->get(route('admin.settings.edit'))->assertSee('Hapus gambar banner saat ini');

        // Ganti gambar: file lama dihapus.
        $this->save(['hero_background' => 'image', 'hero_image' => UploadedFile::fake()->image('baru.png', 1600, 900)]);
        $second = Setting::find('hero.image')->value;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);

        // Hapus gambar selagi latar masih "gambar": ditolak.
        $this->save(['hero_background' => 'image', 'remove_hero_image' => '1'])->assertSessionHasErrors('hero_image');
        Storage::disk('public')->assertExists($second);

        $this->save(['hero_background' => 'car', 'remove_hero_image' => '1'])->assertSessionHasNoErrors();
        $this->assertNull(Setting::find('hero.image'));
        Storage::disk('public')->assertMissing($second);
        $this->assertSame('car', config('dealer.hero.background'));
        $this->get(route('home'))->assertDontSee('has-banner', false)->assertDontSee('hero-video', false);
    }

    public function test_perubahan_rekening_diberitahukan_ke_owner_lain(): void
    {
        $otherOwner = User::factory()->owner()->create(['name' => 'Yoseph']);
        User::factory()->owner()->create(['is_active' => false]);
        $super = User::factory()->superAdmin()->create(['name' => 'Rifky']);

        // Bukan perubahan rekening: tidak ada notifikasi.
        $this->save(['tagline' => 'Baru'], $super);
        $this->assertSame(0, $otherOwner->notifications()->count());

        $this->travelTo(now()->setDateTime(2026, 10, 20, 10, 30));
        $this->save(['bank_name' => 'BNI', 'bank_account' => '9876543210', 'bank_holder' => 'PT JAF Dealer'], $super);

        foreach ([$this->owner, $otherOwner] as $owner) {
            $notification = $owner->notifications()->sole();
            $this->assertSame(BankAccountChanged::class, $notification->type);
            $this->assertSame('Rekening Pembayaran Diubah · Rifky', $notification->data['title']);
            $this->assertSame('Rekening sekarang: BNI 9876543210 a.n. PT JAF Dealer. Sebelumnya: BCA 1111111111 a.n. PT JAF Dealer.', $notification->data['message']);
        }
        $this->assertSame(0, $super->notifications()->count());

        $this->actingAs($this->owner)->get(route('admin.notifications.open', $this->owner->notifications()->sole()->id))
            ->assertRedirect(route('admin.settings.edit').'#rekening');
        $this->get(route('admin.settings.edit'))->assertSee('Terakhir diubah oleh Rifky pada 20 Okt 2026 10:30.');

        // Owner sendiri mengubah: hanya owner lain yang diberi tahu; kosongkan semua = kembali ke rekening .env.
        $this->travel(1)->minutes();
        $this->save(['bank_name' => '', 'bank_account' => '', 'bank_holder' => '']);
        $this->assertSame(1, $this->owner->notifications()->count());
        $this->assertSame(
            'Rekening sekarang: BCA 1111111111 a.n. PT JAF Dealer. Sebelumnya: BNI 9876543210 a.n. PT JAF Dealer.',
            $otherOwner->notifications()->latest()->first()->data['message']
        );
        $this->assertSame('1111111111', config('dealer.bank.account'));
    }
}
