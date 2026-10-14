<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Kategori mobil Nissan. Aman dijalankan ulang: kategori yang sudah ada tidak diubah.
     */
    public function run(): void
    {
        foreach (['SUV', 'MPV', 'Hatchback', 'Pickup', 'Mobil Sport', 'EV'] as $name) {
            Category::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]);
        }
    }
}
