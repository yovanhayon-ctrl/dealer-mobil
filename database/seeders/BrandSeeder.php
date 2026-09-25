<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Merek dummy (tanpa logo). Aman dijalankan ulang: merek yang sudah ada tidak diubah.
     */
    public function run(): void
    {
        foreach (['Toyota', 'Honda', 'Daihatsu', 'Mitsubishi', 'Suzuki', 'Hyundai', 'Wuling'] as $name) {
            Brand::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]);
        }
    }
}
