<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CarSeeder extends Seeder
{
    /**
     * 15 mobil dummy (harga perkiraan OTR, tanpa gambar).
     * Aman dijalankan ulang: dicari berdasarkan slug, mobil yang sudah ada tidak diubah.
     */
    public function run(): void
    {
        foreach ($this->cars() as $car) {
            $brand = $this->brand($car['brand']);
            $slug = Car::slugify("{$brand->name} {$car['name']} {$car['year']}");

            Car::firstOrCreate(['slug' => $slug], [
                'brand_id' => $brand->id,
                'category_id' => $this->category($car['category'])->id,
                'name' => $car['name'],
                'vehicle_condition' => $car['condition'],
                'year' => $car['year'],
                'mileage' => $car['condition'] === Car::CONDITION_USED ? $car['mileage'] : 0,
                'price' => $car['price'],
                'transmission' => $car['transmission'],
                'fuel_type' => $car['fuel'],
                'engine_cc' => $car['cc'],
                'seats' => $car['seats'],
                'color' => $car['color'],
                'stock' => $car['stock'],
                'description' => $car['description'],
                'is_active' => $car['active'] ?? true,
            ]);
        }
    }

    private function brand(string $name): Brand
    {
        return Brand::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]);
    }

    private function category(string $name): Category
    {
        return Category::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cars(): array
    {
        return [
            // Toyota
            ['brand' => 'Toyota', 'category' => 'MPV', 'name' => 'Avanza 1.5 G CVT', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 285_000_000, 'transmission' => 'automatic', 'fuel' => 'bensin', 'cc' => 1496, 'seats' => 7, 'color' => 'Putih', 'stock' => 5,
                'description' => 'MPV keluarga terlaris dengan kabin lega tujuh penumpang dan transmisi CVT yang halus.'],
            ['brand' => 'Toyota', 'category' => 'SUV', 'name' => 'Fortuner 2.8 GR Sport', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 731_000_000, 'transmission' => 'automatic', 'fuel' => 'diesel', 'cc' => 2755, 'seats' => 7, 'color' => 'Hitam', 'stock' => 0,
                'description' => 'SUV diesel bertenaga dengan penggerak 4x4 dan tampilan sporty GR Sport. Stok sedang kosong, unit display tersedia untuk test drive.'],
            ['brand' => 'Toyota', 'category' => 'MPV', 'name' => 'Innova Zenix 2.0 Q HV', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 612_000_000, 'transmission' => 'automatic', 'fuel' => 'hybrid', 'cc' => 1987, 'seats' => 7, 'color' => 'Silver', 'stock' => 3,
                'description' => 'MPV hybrid premium yang irit bahan bakar dengan captain seat baris kedua.'],
            ['brand' => 'Toyota', 'category' => 'LCGC', 'name' => 'Calya 1.2 G', 'condition' => 'bekas', 'year' => 2022, 'mileage' => 38_000,
                'price' => 135_000_000, 'transmission' => 'manual', 'fuel' => 'bensin', 'cc' => 1197, 'seats' => 7, 'color' => 'Abu-abu', 'stock' => 1,
                'description' => 'Tangan pertama, servis rutin di bengkel resmi, pajak hidup.'],

            // Honda
            ['brand' => 'Honda', 'category' => 'SUV', 'name' => 'HR-V 1.5 SE CVT', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 402_000_000, 'transmission' => 'automatic', 'fuel' => 'bensin', 'cc' => 1498, 'seats' => 5, 'color' => 'Merah', 'stock' => 4,
                'description' => 'Compact SUV stylish dengan fitur keselamatan Honda Sensing.'],
            ['brand' => 'Honda', 'category' => 'LCGC', 'name' => 'Brio Satya E CVT', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 199_000_000, 'transmission' => 'automatic', 'fuel' => 'bensin', 'cc' => 1199, 'seats' => 5, 'color' => 'Kuning', 'stock' => 6,
                'description' => 'City car lincah dan irit, cocok untuk mobil pertama.'],
            ['brand' => 'Honda', 'category' => 'Sedan', 'name' => 'Civic 1.5 RS Turbo', 'condition' => 'bekas', 'year' => 2022, 'mileage' => 24_000,
                'price' => 520_000_000, 'transmission' => 'automatic', 'fuel' => 'bensin', 'cc' => 1498, 'seats' => 5, 'color' => 'Putih', 'stock' => 0,
                'description' => 'Sedan sporty bermesin turbo, kondisi istimewa. Unit sudah terjual.'],

            // Daihatsu
            ['brand' => 'Daihatsu', 'category' => 'MPV', 'name' => 'Xenia 1.3 R CVT', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 254_000_000, 'transmission' => 'automatic', 'fuel' => 'bensin', 'cc' => 1329, 'seats' => 7, 'color' => 'Silver', 'stock' => 5,
                'description' => 'MPV tujuh penumpang dengan platform DNGA yang nyaman dan irit.'],
            ['brand' => 'Daihatsu', 'category' => 'LCGC', 'name' => 'Sigra 1.2 X', 'condition' => 'bekas', 'year' => 2021, 'mileage' => 61_000,
                'price' => 118_000_000, 'transmission' => 'manual', 'fuel' => 'bensin', 'cc' => 1197, 'seats' => 7, 'color' => 'Putih', 'stock' => 1,
                'active' => false,
                'description' => 'LCGC tujuh penumpang, sedang dalam pengecekan sebelum dijual kembali.'],

            // Mitsubishi
            ['brand' => 'Mitsubishi', 'category' => 'MPV', 'name' => 'Xpander Cross Premium CVT', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 363_000_000, 'transmission' => 'automatic', 'fuel' => 'bensin', 'cc' => 1499, 'seats' => 7, 'color' => 'Hijau', 'stock' => 3,
                'description' => 'Crossover MPV dengan ground clearance tinggi, tangguh untuk berbagai medan.'],
            ['brand' => 'Mitsubishi', 'category' => 'SUV', 'name' => 'Pajero Sport Dakar 4x2', 'condition' => 'bekas', 'year' => 2021, 'mileage' => 72_000,
                'price' => 455_000_000, 'transmission' => 'automatic', 'fuel' => 'diesel', 'cc' => 2442, 'seats' => 7, 'color' => 'Hitam', 'stock' => 1,
                'description' => 'SUV diesel tangguh, riwayat servis lengkap, ban baru.'],
            ['brand' => 'Mitsubishi', 'category' => 'Pickup', 'name' => 'Triton Exceed Double Cab 4x4', 'condition' => 'baru', 'year' => 2024, 'mileage' => 0,
                'price' => 575_000_000, 'transmission' => 'automatic', 'fuel' => 'diesel', 'cc' => 2442, 'seats' => 5, 'color' => 'Abu-abu', 'stock' => 2,
                'description' => 'Double cabin 4x4 untuk kebutuhan kerja maupun petualangan.'],

            // Suzuki
            ['brand' => 'Suzuki', 'category' => 'Hatchback', 'name' => 'Swift 1.2 GL', 'condition' => 'bekas', 'year' => 2019, 'mileage' => 54_000,
                'price' => 145_000_000, 'transmission' => 'manual', 'fuel' => 'bensin', 'cc' => 1197, 'seats' => 5, 'color' => 'Biru', 'stock' => 1,
                'description' => 'Hatchback ringan dan responsif, kondisi terawat.'],

            // Hyundai
            ['brand' => 'Hyundai', 'category' => 'SUV', 'name' => 'Creta 1.5 Prime IVT', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 412_000_000, 'transmission' => 'automatic', 'fuel' => 'bensin', 'cc' => 1497, 'seats' => 5, 'color' => 'Putih', 'stock' => 3,
                'description' => 'Compact SUV dengan panoramic sunroof dan fitur keselamatan Hyundai SmartSense.'],
            ['brand' => 'Hyundai', 'category' => 'SUV', 'name' => 'Ioniq 5 Signature Long Range', 'condition' => 'baru', 'year' => 2024, 'mileage' => 0,
                'price' => 859_000_000, 'transmission' => 'automatic', 'fuel' => 'listrik', 'cc' => null, 'seats' => 5, 'color' => 'Silver', 'stock' => 2,
                'description' => 'Mobil listrik dengan jarak tempuh jauh dan pengisian daya cepat.'],
        ];
    }
}
