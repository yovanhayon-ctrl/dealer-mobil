<?php

namespace Tests\Feature;

use App\Actions\CreateBackup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PDO;
use Tests\TestCase;
use ZipArchive;

/**
 * Cadangan otomatis: php artisan backup:run (database + foto + bukti pembayaran), hapus cadangan lama, status di dashboard.
 */
class BackupTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/backups');
        File::deleteDirectory($this->directory);
        config(['dealer.backup.path' => $this->directory, 'dealer.backup.keep_days' => 30]);
        $this->travelTo(Carbon::parse('2026-10-21 02:00:00', 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    private function makeBackupFile(string $name, Carbon $time): string
    {
        File::ensureDirectoryExists($this->directory);
        $path = "{$this->directory}/{$name}";
        file_put_contents($path, 'zip');
        touch($path, $time->getTimestamp());

        return $path;
    }

    public function test_cadangan_berisi_database_foto_dan_bukti_pembayaran(): void
    {
        User::factory()->create(['name' => "Yoseph O'Hayon", 'email' => 'yoseph@jaf.test']);
        Storage::disk('public')->put('cars/1/foto.webp', 'foto');
        Storage::disk('local')->put('payment-proofs/7/bukti.jpg', 'bukti');

        $this->artisan('backup:run')
            ->expectsOutputToContain('Cadangan dibuat: ')
            ->assertSuccessful();

        $backup = CreateBackup::latest();
        $this->assertSame('cadangan-2026-10-21-020000.zip', $backup['name']);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open("{$this->directory}/{$backup['name']}"));
        $this->assertSame('foto', $zip->getFromName('storage/app/public/cars/1/foto.webp'));
        $this->assertSame('bukti', $zip->getFromName('storage/app/private/payment-proofs/7/bukti.jpg'));
        $sql = $zip->getFromName('database.sql');
        $zip->close();

        if (DB::getDriverName() !== 'sqlite') {
            // MySQL (phpunit.mysql.xml): sintaks siap impor lewat phpMyAdmin; uji impor penuh dilakukan manual (DEPLOY-CPANEL bagian 12).
            $this->assertStringContainsString('CREATE TABLE `users`', $sql);
            $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=0;', $sql);
            $this->assertStringContainsString("'Yoseph O\'Hayon'", $sql);

            return;
        }

        $this->assertStringContainsString('CREATE TABLE "users"', $sql);
        $this->assertStringContainsString("'Yoseph O''Hayon'", $sql);

        // Bisa dipulihkan: impor ke database kosong menghasilkan data yang sama.
        $restored = new PDO('sqlite::memory:');
        $restored->exec($sql);
        $this->assertSame(
            DB::table('users')->count(),
            (int) $restored->query('SELECT COUNT(*) FROM users')->fetchColumn()
        );
        $this->assertSame("Yoseph O'Hayon", $restored->query("SELECT name FROM users WHERE email = 'yoseph@jaf.test'")->fetchColumn());
        $this->assertSame(
            DB::table('migrations')->count(),
            (int) $restored->query('SELECT COUNT(*) FROM migrations')->fetchColumn()
        );
    }

    public function test_cadangan_lama_dihapus_yang_terbaru_selalu_disimpan(): void
    {
        $old = $this->makeBackupFile('cadangan-2026-09-01-020000.zip', now()->subDays(50));
        $recent = $this->makeBackupFile('cadangan-2026-10-10-020000.zip', now()->subDays(11));
        $other = $this->makeBackupFile('catatan.zip', now()->subDays(90));

        $this->artisan('backup:run')->expectsOutputToContain('1 cadangan lama dihapus.')->assertSuccessful();

        $this->assertFileDoesNotExist($old);
        $this->assertFileExists($recent);
        $this->assertFileExists($other);

        // Hanya ada satu cadangan dan sudah tua: tetap disimpan.
        File::cleanDirectory($this->directory);
        $only = $this->makeBackupFile('cadangan-2026-01-01-020000.zip', now()->subDays(200));
        $this->assertSame(0, app(CreateBackup::class)->prune(30));
        $this->assertFileExists($only);
    }

    public function test_dashboard_menampilkan_status_cadangan_untuk_owner_dan_super_admin(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get(route('admin.dashboard'))
            ->assertSee('Belum ada cadangan otomatis.')
            ->assertSee('dashboard-backup-warning', false);

        $this->makeBackupFile('cadangan-2026-10-20-020000.zip', now()->subDay());
        $this->get(route('admin.dashboard'))
            ->assertSee('Cadangan terakhir 20 Okt 2026 02:00')
            ->assertDontSee('dashboard-backup-warning', false);

        $this->travel(3)->days();
        $this->get(route('admin.dashboard'))
            ->assertSee('Cadangan terakhir 20 Okt 2026 02:00')
            ->assertSee('dashboard-backup-warning', false);

        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.dashboard'))->assertSee('Cadangan terakhir');
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertDontSee('Cadangan terakhir');
    }

    public function test_dijadwalkan_setiap_hari(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('backup:run')->assertSuccessful();
    }
}
