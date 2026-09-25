<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Kategori dummy. Aman dijalankan ulang: kategori yang sudah ada tidak diubah.
     */
    public function run(): void
    {
        foreach (['SUV', 'MPV', 'Sedan', 'Hatchback', 'Pickup', 'LCGC'] as $name) {
            Category::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]);
        }
    }
}
