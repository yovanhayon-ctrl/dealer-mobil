<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Merek dummy (tanpa logo): Nissan sebagai fokus JAF Dealer + merek mobil klasik Jepang.
     * Aman dijalankan ulang: merek yang sudah ada tidak diubah.
     */
    public function run(): void
    {
        foreach (['Nissan', 'Toyota', 'Honda', 'Mazda', 'Mitsubishi', 'Subaru'] as $name) {
            Brand::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]);
        }
    }
}
