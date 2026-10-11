<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Test tidak boleh menyentuh file asli di storage/app/public (foto mobil, banner promo) di komputer lokal.
 */
class TestIsolationTest extends TestCase
{
    public function test_disk_public_selalu_palsu_selama_test(): void
    {
        $root = str_replace('\\', '/', Storage::disk('public')->path(''));

        $this->assertStringContainsString('storage/framework/testing/disks/public', $root);
        $this->assertStringNotContainsString('storage/app/public', $root);
    }
}
