<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 18: index untuk urutan/rentang created_at dan versi aset (cache browser).
 */
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, array<int, string>}>
     */
    public static function indexes(): array
    {
        return [
            'katalog terbaru' => ['cars', ['is_active', 'created_at']],
            'laporan & daftar pengajuan' => ['purchase_requests', ['created_at']],
            'daftar test drive' => ['test_drives', ['created_at']],
            'daftar booking servis' => ['service_bookings', ['created_at']],
        ];
    }

    #[DataProvider('indexes')]
    public function test_index_created_at_tersedia(string $table, array $columns): void
    {
        $this->assertTrue(Schema::hasIndex($table, $columns), "Index {$table}(".implode(',', $columns).') belum ada.');
    }

    public function test_url_aset_lokal_memakai_versi_waktu_ubah_file(): void
    {
        $this->assertSame(asset('css/app.css').'?v='.filemtime(public_path('css/app.css')), Asset::url('css/app.css'));
        // File yang tidak ada tetap menghasilkan URL biasa (tanpa error).
        $this->assertSame(asset('css/tidak-ada.css'), Asset::url('css/tidak-ada.css'));
    }

    public function test_layout_publik_dan_admin_memakai_aset_berversi(): void
    {
        $this->get(route('home'))
            ->assertSee(Asset::url('css/app.css'), false)
            ->assertSee(Asset::url('js/app.js'), false);

        $this->get(route('credit.index'))->assertSee(Asset::url('js/credit-simulation.js'), false);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertSee(Asset::url('js/admin.js'), false);
    }

    public function test_tidak_ada_aset_lokal_tanpa_versi_di_view(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $this->assertDoesNotMatchRegularExpression("/asset\\('(css|js)\\//", file_get_contents($file->getPathname()), "Aset tanpa versi di {$file->getFilename()}");
            }
        }
    }

    public function test_aturan_cache_dan_kompresi_ada_di_htaccess(): void
    {
        $htaccess = file_get_contents(public_path('.htaccess'));

        $this->assertStringContainsString('<IfModule mod_deflate.c>', $htaccess);
        $this->assertStringContainsString('max-age=31536000, immutable', $htaccess);
        // Aturan front controller Laravel tetap ada.
        $this->assertStringContainsString('RewriteRule ^ index.php [L]', $htaccess);
    }
}
