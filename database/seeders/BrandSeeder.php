<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Merek awal (tanpa logo): JAF Dealer khusus Nissan. Merek lain bisa ditambah admin bila diperlukan.
     * Aman dijalankan ulang: merek yang sudah ada tidak diubah.
     */
    public function run(): void
    {
        foreach (['Nissan'] as $name) {
            Brand::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]);
        }
    }
}
