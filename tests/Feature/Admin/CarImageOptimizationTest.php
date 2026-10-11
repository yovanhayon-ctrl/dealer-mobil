<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\User;
use App\Support\CarImageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Optimasi foto mobil: WebP maks. 1600 px + thumbnail 480 px, dan perintah untuk foto lama.
 */
class CarImageOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
        $this->car = Car::factory()->create(['brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id, 'name' => 'Skyline', 'year' => 1999]);
    }

    /**
     * @return array{0: int, 1: int, 2: string}
     */
    private function imageInfo(string $path): array
    {
        $contents = Storage::disk('public')->get($path);
        [$width, $height] = getimagesizefromstring($contents);

        return [$width, $height, $contents];
    }

    private function upload(UploadedFile $file)
    {
        return $this->actingAs($this->admin)->post(route('admin.cars.images.store', $this->car), ['images' => [$file]]);
    }

    public function test_foto_besar_dikecilkan_ke_webp_dan_dibuat_thumbnail(): void
    {
        $this->upload(UploadedFile::fake()->image('dari-hp.jpg', 4000, 3000))->assertSessionHasNoErrors();

        $image = CarImage::sole();
        $this->assertStringStartsWith("cars/{$this->car->id}/", $image->path);
        $this->assertStringEndsWith('.webp', $image->path);
        $this->assertStringStartsWith("cars/{$this->car->id}/thumbs/", $image->thumb_path);
        $this->assertStringNotContainsString('dari-hp', $image->path);

        [$width, $height, $contents] = $this->imageInfo($image->path);
        $this->assertSame([1600, 1200], [$width, $height]);
        $this->assertSame('RIFF', substr($contents, 0, 4));
        $this->assertSame('WEBP', substr($contents, 8, 4));

        [$thumbWidth, $thumbHeight] = $this->imageInfo($image->thumb_path);
        $this->assertSame([480, 360], [$thumbWidth, $thumbHeight]);

        // Hanya dua file: foto & thumbnail (file asli tidak disimpan).
        $this->assertCount(2, Storage::disk('public')->allFiles("cars/{$this->car->id}"));
    }

    public function test_foto_potret_dibatasi_sisi_terpanjang(): void
    {
        $this->upload(UploadedFile::fake()->image('tegak.jpg', 1200, 3200))->assertSessionHasNoErrors();

        [$width, $height] = $this->imageInfo(CarImage::sole()->path);
        $this->assertSame([600, 1600], [$width, $height]);
    }

    public function test_foto_kecil_tidak_diperbesar(): void
    {
        $this->upload(UploadedFile::fake()->image('kecil.png', 800, 600))->assertSessionHasNoErrors();

        $image = CarImage::sole();
        $this->assertSame([800, 600], array_slice($this->imageInfo($image->path), 0, 2));
        $this->assertSame([480, 360], array_slice($this->imageInfo($image->thumb_path), 0, 2));
    }

    public function test_latar_transparan_tetap_transparan(): void
    {
        $canvas = imagecreatetruecolor(800, 600);
        imagesavealpha($canvas, true);
        imagealphablending($canvas, false);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        $file = tempnam(sys_get_temp_dir(), 'png');
        imagepng($canvas, $file);

        $stored = app(CarImageProcessor::class)->store($file, 'cars/uji', 'public');
        $webp = imagecreatefromstring(Storage::disk('public')->get($stored['path']));
        $alpha = (imagecolorat($webp, 10, 10) >> 24) & 0x7F;

        $this->assertGreaterThan(100, $alpha, 'Piksel latar harus tetap transparan.');
        @unlink($file);
    }

    public function test_hapus_gambar_ikut_menghapus_thumbnail(): void
    {
        $this->upload(UploadedFile::fake()->image('foto.jpg', 1000, 700));
        $image = CarImage::sole();

        $this->actingAs($this->admin)->delete(route('admin.cars.images.destroy', [$this->car, $image]))->assertSessionHas('success');

        Storage::disk('public')->assertMissing($image->path);
        Storage::disk('public')->assertMissing($image->thumb_path);
    }

    public function test_kartu_katalog_memakai_thumbnail_dan_detail_memakai_foto_besar(): void
    {
        $this->upload(UploadedFile::fake()->image('foto.jpg', 2000, 1200));
        $image = CarImage::sole();

        $this->get(route('cars.index'))->assertOk()
            ->assertSee(Storage::disk('public')->url($image->thumb_path), false);

        $this->get(route('cars.show', $this->car))->assertOk()
            ->assertSee('src="'.Storage::disk('public')->url($image->path).'"', false);
    }

    public function test_perintah_mengoptimasi_foto_lama_dan_aman_dijalankan_ulang(): void
    {
        $oldPath = "cars/{$this->car->id}/lama.jpg";
        $source = UploadedFile::fake()->image('lama.jpg', 2400, 1600);
        Storage::disk('public')->put($oldPath, file_get_contents($source->getRealPath()));
        $old = CarImage::create(['car_id' => $this->car->id, 'path' => $oldPath, 'is_primary' => true, 'sort_order' => 1]);
        $missing = CarImage::create(['car_id' => $this->car->id, 'path' => "cars/{$this->car->id}/hilang.jpg", 'sort_order' => 2]);

        $this->artisan('cars:optimize-images')
            ->expectsOutput('Selesai: 1 foto dioptimasi, 1 dilewati, 0 gagal.')
            ->assertSuccessful();

        $old->refresh();
        $this->assertStringEndsWith('.webp', $old->path);
        $this->assertNotNull($old->thumb_path);
        $this->assertSame([1600, 1067], array_slice($this->imageInfo($old->path), 0, 2));
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertNull($missing->fresh()->thumb_path);

        $this->artisan('cars:optimize-images')
            ->expectsOutput('Selesai: 0 foto dioptimasi, 1 dilewati, 0 gagal.')
            ->assertSuccessful();
    }

    public function test_foto_lama_tanpa_thumbnail_tetap_tampil(): void
    {
        $image = CarImage::create(['car_id' => $this->car->id, 'path' => 'cars/1/lama.jpg', 'is_primary' => true, 'sort_order' => 1]);

        $this->assertSame($image->url, $image->thumb_url);
    }
}
