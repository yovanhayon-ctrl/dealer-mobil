<?php

namespace Tests\Feature\Admin;

use App\Models\Car;
use App\Models\CarImage;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CarImageManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
        $this->car = Car::factory()->create(['name' => 'Avanza', 'year' => 2025]);
    }

    private function photo(string $name = 'foto.jpg', int $width = 800, int $height = 600): UploadedFile
    {
        return UploadedFile::fake()->image($name, $width, $height);
    }

    /**
     * Buat gambar tersimpan (baris + file) tanpa lewat form upload.
     */
    private function addImage(Car $car, int $sortOrder, bool $primary = false): CarImage
    {
        return CarImage::create([
            'car_id' => $car->id,
            'path' => $this->photo()->store(CarImage::directory($car->id), 'public'),
            'is_primary' => $primary,
            'sort_order' => $sortOrder,
        ]);
    }

    private function upload(array $files, ?Car $car = null)
    {
        $car ??= $this->car;

        return $this->actingAs($this->admin)
            ->from(route('admin.cars.images.index', $car))
            ->post(route('admin.cars.images.store', $car), ['images' => $files]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $image = $this->addImage($this->car, 1, true);
        $base = "/admin/mobil/{$this->car->id}/gambar";

        $this->get($base)->assertRedirect(route('login'));
        $this->post($base, ['images' => [$this->photo()]])->assertRedirect(route('login'));
        $this->patch("{$base}/{$image->id}/utama")->assertRedirect(route('login'));
        $this->patch("{$base}/{$image->id}/naik")->assertRedirect(route('login'));
        $this->delete("{$base}/{$image->id}")->assertRedirect(route('login'));

        $this->assertSame(1, CarImage::count());
        $this->assertModelExists($image);
    }

    public function test_customer_mendapat_403(): void
    {
        $image = $this->addImage($this->car, 1, true);
        $base = "/admin/mobil/{$this->car->id}/gambar";

        $this->actingAs(User::factory()->create());
        $this->get($base)->assertForbidden();
        $this->post($base, ['images' => [$this->photo()]])->assertForbidden();
        $this->patch("{$base}/{$image->id}/utama")->assertForbidden();
        $this->patch("{$base}/{$image->id}/turun")->assertForbidden();
        $this->delete("{$base}/{$image->id}")->assertForbidden();

        $this->assertSame(1, CarImage::count());
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_halaman_galeri_tampil_dengan_alt_nama_mobil(): void
    {
        $this->addImage($this->car, 1, true);

        $this->actingAs($this->admin)->get(route('admin.cars.images.index', $this->car))
            ->assertOk()
            ->assertSee('Galeri Mobil')
            ->assertSee('alt="Avanza 2025"', false)
            ->assertSee('Utama')
            ->assertSee('1 dari 10 gambar');
    }

    public function test_upload_beberapa_gambar_berhasil_dengan_nama_acak(): void
    {
        $this->upload([$this->photo('depan.jpg'), $this->photo('samping.png'), $this->photo('dalam.webp')])
            ->assertRedirect(route('admin.cars.images.index', $this->car))
            ->assertSessionHas('success', '3 gambar berhasil diunggah.');

        $images = $this->car->images()->get();

        $this->assertCount(3, $images);
        $this->assertSame([1, 2, 3], $images->pluck('sort_order')->all());

        foreach ($images as $image) {
            Storage::disk('public')->assertExists($image->path);
            $this->assertStringStartsWith("cars/{$this->car->id}/", $image->path);
            $this->assertDoesNotMatchRegularExpression('/depan|samping|dalam/', $image->path);
        }

        $this->assertSame(
            Storage::url($images->first()->path),
            $images->first()->url,
        );
    }

    public function test_gambar_pertama_otomatis_menjadi_utama(): void
    {
        $this->upload([$this->photo(), $this->photo()]);
        $first = $this->car->images()->first();

        $this->upload([$this->photo()]);

        $this->assertSame(3, $this->car->images()->count());
        $this->assertSame(1, $this->car->images()->where('is_primary', true)->count());
        $this->assertTrue($first->fresh()->is_primary);
    }

    public function test_upload_tanpa_file_ditolak(): void
    {
        $this->upload([])->assertSessionHasErrors('images');

        $this->assertSame(0, CarImage::count());
    }

    public function test_batas_sepuluh_gambar_per_mobil(): void
    {
        foreach (range(1, 8) as $order) {
            $this->addImage($this->car, $order, $order === 1);
        }

        $this->upload([$this->photo(), $this->photo(), $this->photo()])
            ->assertRedirect(route('admin.cars.images.index', $this->car))
            ->assertSessionHasErrors(['images' => 'Maksimal 10 gambar per mobil. Sisa slot: 2 gambar.']);
        $this->assertSame(8, $this->car->images()->count());
        $this->assertCount(8, Storage::disk('public')->files("cars/{$this->car->id}"));

        $this->upload([$this->photo(), $this->photo()])->assertSessionHasNoErrors();
        $this->assertSame(10, $this->car->images()->count());

        $this->upload([$this->photo()])
            ->assertSessionHasErrors(['images' => 'Galeri sudah penuh (10 gambar). Hapus gambar lain terlebih dahulu.']);
        $this->assertSame(10, $this->car->images()->count());
        $this->assertCount(10, Storage::disk('public')->files("cars/{$this->car->id}"));
    }

    /**
     * File dibuat di dalam test (lewat closure) karena data provider berjalan sebelum aplikasi disiapkan.
     *
     * @return array<string, array{Closure(self): UploadedFile, string}>
     */
    public static function invalidFileProvider(): array
    {
        $wrongType = 'Gambar ke-1 harus berupa JPG, JPEG, PNG, atau WEBP.';
        $tooSmall = 'Gambar ke-1 minimal berukuran 600×400 piksel.';

        return [
            'svg' => [fn () => UploadedFile::fake()->createWithContent(
                'logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600"></svg>',
            ), $wrongType],
            'pdf' => [fn () => UploadedFile::fake()->create('brosur.pdf', 100, 'application/pdf'), $wrongType],
            'gif' => [fn () => UploadedFile::fake()->image('animasi.gif', 800, 600), $wrongType],
            'lebih dari 2 MB' => [fn (self $test) => $test->photo('besar.png')->size(2049), 'Ukuran gambar ke-1 maksimal 2 MB.'],
            'lebar 599 px' => [fn (self $test) => $test->photo('sempit.jpg', 599, 400), $tooSmall],
            'tinggi 399 px' => [fn (self $test) => $test->photo('pendek.jpg', 600, 399), $tooSmall],
        ];
    }

    #[DataProvider('invalidFileProvider')]
    public function test_file_tidak_valid_ditolak(Closure $makeFile, string $message): void
    {
        $this->upload([$makeFile($this)])
            ->assertRedirect(route('admin.cars.images.index', $this->car))
            ->assertSessionHasErrors(['images.0' => $message]);

        $this->assertSame(0, CarImage::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_satu_file_tidak_valid_menggagalkan_seluruh_upload(): void
    {
        $this->upload([$this->photo(), $this->photo('sempit.jpg', 500, 300)])
            ->assertSessionHasErrors(['images.1' => 'Gambar ke-2 minimal berukuran 600×400 piksel.'])
            ->assertSessionDoesntHaveErrors('images.0');

        $this->assertSame(0, CarImage::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_ukuran_pas_batas_diterima(): void
    {
        $this->upload([$this->photo('pas.jpg', 600, 400)->size(2048)])->assertSessionHasNoErrors();

        $this->assertSame(1, CarImage::count());
    }

    public function test_jadikan_utama_hanya_satu_per_mobil(): void
    {
        $first = $this->addImage($this->car, 1, true);
        $second = $this->addImage($this->car, 2);
        $otherCarImage = $this->addImage(Car::factory()->create(), 1, true);

        $this->actingAs($this->admin)->patch(route('admin.cars.images.primary', [$this->car, $second]))
            ->assertRedirect(route('admin.cars.images.index', $this->car))
            ->assertSessionHas('success', 'Gambar utama berhasil diubah.');

        $this->assertFalse($first->fresh()->is_primary);
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertSame(1, $this->car->images()->where('is_primary', true)->count());
        $this->assertTrue($otherCarImage->fresh()->is_primary);
    }

    public function test_naik_dan_turun_mengubah_urutan(): void
    {
        $a = $this->addImage($this->car, 1, true);
        $b = $this->addImage($this->car, 2);
        $c = $this->addImage($this->car, 3);

        $this->actingAs($this->admin)->patch(route('admin.cars.images.move', [$this->car, $c, 'naik']))
            ->assertRedirect(route('admin.cars.images.index', $this->car))
            ->assertSessionHas('success', 'Urutan gambar berhasil diubah.');
        $this->assertSame([$a->id, $c->id, $b->id], $this->car->images()->pluck('id')->all());

        $this->actingAs($this->admin)->patch(route('admin.cars.images.move', [$this->car, $a, 'turun']));
        $this->assertSame([$c->id, $a->id, $b->id], $this->car->images()->pluck('id')->all());
        $this->assertSame([1, 2, 3], $this->car->images()->pluck('sort_order')->all());
    }

    public function test_naik_di_posisi_pertama_dan_turun_di_posisi_terakhir_tidak_berubah(): void
    {
        $a = $this->addImage($this->car, 1, true);
        $b = $this->addImage($this->car, 2);

        $this->actingAs($this->admin)->patch(route('admin.cars.images.move', [$this->car, $a, 'naik']))
            ->assertSessionHas('warning', 'Gambar sudah berada di urutan pertama.');
        $this->actingAs($this->admin)->patch(route('admin.cars.images.move', [$this->car, $b, 'turun']))
            ->assertSessionHas('warning', 'Gambar sudah berada di urutan terakhir.');

        $this->assertSame([$a->id, $b->id], $this->car->images()->pluck('id')->all());
    }

    public function test_arah_urutan_tidak_dikenal_404(): void
    {
        $image = $this->addImage($this->car, 1, true);

        $this->actingAs($this->admin)
            ->patch("/admin/mobil/{$this->car->id}/gambar/{$image->id}/kiri")
            ->assertNotFound();
    }

    public function test_hapus_gambar_menghapus_baris_dan_file(): void
    {
        $primary = $this->addImage($this->car, 1, true);
        $other = $this->addImage($this->car, 2);

        $this->actingAs($this->admin)->delete(route('admin.cars.images.destroy', [$this->car, $other]))
            ->assertRedirect(route('admin.cars.images.index', $this->car))
            ->assertSessionHas('success', 'Gambar berhasil dihapus.');

        $this->assertModelMissing($other);
        Storage::disk('public')->assertMissing($other->path);
        $this->assertTrue($primary->fresh()->is_primary);
    }

    public function test_hapus_gambar_utama_memindahkan_utama_ke_gambar_berikutnya(): void
    {
        $primary = $this->addImage($this->car, 1, true);
        $next = $this->addImage($this->car, 2);
        $last = $this->addImage($this->car, 3);

        $this->actingAs($this->admin)->delete(route('admin.cars.images.destroy', [$this->car, $primary]))
            ->assertSessionHas('success', 'Gambar berhasil dihapus. Gambar berikutnya dijadikan gambar utama.');

        $this->assertModelMissing($primary);
        Storage::disk('public')->assertMissing($primary->path);
        $this->assertTrue($next->fresh()->is_primary);
        $this->assertFalse($last->fresh()->is_primary);
    }

    public function test_hapus_satu_satunya_gambar(): void
    {
        $only = $this->addImage($this->car, 1, true);

        $this->actingAs($this->admin)->delete(route('admin.cars.images.destroy', [$this->car, $only]))
            ->assertSessionHas('success', 'Gambar berhasil dihapus.');

        $this->assertSame(0, CarImage::count());
        Storage::disk('public')->assertMissing($only->path);
    }

    public function test_gambar_milik_mobil_lain_404(): void
    {
        $otherCar = Car::factory()->create();
        $foreign = $this->addImage($otherCar, 1, true);
        $own = $this->addImage($this->car, 1, true);

        $this->actingAs($this->admin);
        $this->patch(route('admin.cars.images.primary', [$this->car, $foreign]))->assertNotFound();
        $this->patch(route('admin.cars.images.move', [$this->car, $foreign, 'naik']))->assertNotFound();
        $this->delete(route('admin.cars.images.destroy', [$this->car, $foreign]))->assertNotFound();

        $this->assertModelExists($foreign);
        Storage::disk('public')->assertExists($foreign->path);
        $this->assertTrue($foreign->fresh()->is_primary);
        $this->assertTrue($own->fresh()->is_primary);
    }

    public function test_hapus_mobil_ikut_menghapus_folder_gambar(): void
    {
        $this->addImage($this->car, 1, true);
        $this->addImage($this->car, 2);
        $otherCar = Car::factory()->create();
        $otherImage = $this->addImage($otherCar, 1, true);

        $this->actingAs($this->admin)->delete(route('admin.cars.destroy', $this->car))
            ->assertRedirect(route('admin.cars.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($this->car);
        $this->assertSame(0, CarImage::where('car_id', $this->car->id)->count());
        Storage::disk('public')->assertMissing("cars/{$this->car->id}");
        Storage::disk('public')->assertExists($otherImage->path);
    }

    public function test_daftar_mobil_menampilkan_gambar_utama_atau_placeholder(): void
    {
        $this->addImage($this->car, 1);
        $primary = $this->addImage($this->car, 2, true);
        Car::factory()->create(['name' => 'Tanpa Foto']);

        $this->actingAs($this->admin)->get(route('admin.cars.index'))
            ->assertOk()
            ->assertSee('src="'.$primary->url.'"', false)
            ->assertSee('alt="Avanza 2025"', false)
            ->assertSee('car-thumb-empty', false);
    }

    public function test_jumlah_query_daftar_tidak_bertambah_dengan_gambar(): void
    {
        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->admin)->get(route('admin.cars.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $this->addImage($this->car, 1, true);
        $queriesWithOneCar = $count();

        foreach (Car::factory()->count(9)->create() as $car) {
            $this->addImage($car, 1, true);
        }

        $this->assertSame($queriesWithOneCar, $count());
    }
}
