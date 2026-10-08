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
     * 21 mobil dummy JAF Dealer (harga perkiraan, data fiktif, tanpa gambar).
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
     * Fokus Nissan (mobil baru + heritage), ditambah mobil klasik Jepang lain sebagai koleksi bekas.
     * Variasi sengaja dipertahankan: mobil baru stok 0 (Leaf, unit display), mobil bekas stok 0
     * (Silvia S15, sudah terjual), dan satu mobil nonaktif (X-Trail T32).
     *
     * @return list<array<string, mixed>>
     */
    private function cars(): array
    {
        return [
            // Nissan — baru
            ['brand' => 'Nissan', 'category' => 'SUV', 'name' => 'Kicks e-Power VL', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 520_000_000, 'transmission' => 'automatic', 'fuel' => 'hybrid', 'cc' => 1198, 'seats' => 5, 'color' => 'Putih', 'stock' => 4,
                'description' => 'Crossover e-Power: roda digerakkan motor listrik, mesin bensin hanya mengisi baterai. Responsif dan irit.'],
            ['brand' => 'Nissan', 'category' => 'MPV', 'name' => 'Serena e-Power Highway Star', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 650_000_000, 'transmission' => 'automatic', 'fuel' => 'hybrid', 'cc' => 1433, 'seats' => 7, 'color' => 'Hitam', 'stock' => 3,
                'description' => 'MPV keluarga dengan pintu geser elektrik, ProPILOT, dan kabin lapang untuk tujuh penumpang.'],
            ['brand' => 'Nissan', 'category' => 'SUV', 'name' => 'X-Trail e-Power e-4ORCE VL', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 830_000_000, 'transmission' => 'automatic', 'fuel' => 'hybrid', 'cc' => 1497, 'seats' => 7, 'color' => 'Abu-abu', 'stock' => 2,
                'description' => 'SUV tujuh penumpang dengan penggerak semua roda elektrik e-4ORCE.'],
            ['brand' => 'Nissan', 'category' => 'MPV', 'name' => 'Livina VL CVT', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 330_000_000, 'transmission' => 'automatic', 'fuel' => 'bensin', 'cc' => 1499, 'seats' => 7, 'color' => 'Silver', 'stock' => 5,
                'description' => 'Low MPV tujuh penumpang yang praktis untuk kebutuhan harian keluarga.'],
            ['brand' => 'Nissan', 'category' => 'SUV', 'name' => 'Magnite Premium CVT', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 285_000_000, 'transmission' => 'automatic', 'fuel' => 'bensin', 'cc' => 999, 'seats' => 5, 'color' => 'Merah', 'stock' => 6,
                'description' => 'Compact SUV bermesin turbo 1.0 dengan ground clearance tinggi.'],
            ['brand' => 'Nissan', 'category' => 'Hatchback', 'name' => 'Leaf', 'condition' => 'baru', 'year' => 2025, 'mileage' => 0,
                'price' => 750_000_000, 'transmission' => 'automatic', 'fuel' => 'listrik', 'cc' => null, 'seats' => 5, 'color' => 'Biru', 'stock' => 0,
                'description' => 'Mobil listrik penuh dengan e-Pedal. Stok sedang kosong, unit display tersedia untuk test drive.'],
            ['brand' => 'Nissan', 'category' => 'Pickup', 'name' => 'Navara VL 4x4 AT', 'condition' => 'baru', 'year' => 2024, 'mileage' => 0,
                'price' => 560_000_000, 'transmission' => 'automatic', 'fuel' => 'diesel', 'cc' => 2488, 'seats' => 5, 'color' => 'Abu-abu', 'stock' => 2,
                'description' => 'Double cabin 4x4 bermesin diesel untuk kerja berat maupun petualangan.'],
            ['brand' => 'Nissan', 'category' => 'Sport', 'name' => 'GT-R Premium Edition', 'condition' => 'baru', 'year' => 2024, 'mileage' => 0,
                'price' => 4_650_000_000, 'transmission' => 'automatic', 'fuel' => 'bensin', 'cc' => 3799, 'seats' => 4, 'color' => 'Putih', 'stock' => 1,
                'description' => 'R35 GT-R: V6 twin-turbo rakitan tangan Takumi dengan penggerak ATTESA E-TS.'],

            // Nissan — heritage (bekas)
            ['brand' => 'Nissan', 'category' => 'Sport', 'name' => 'Skyline GT-R V-Spec II (R34)', 'condition' => 'bekas', 'year' => 2000, 'mileage' => 86_000,
                'price' => 3_500_000_000, 'transmission' => 'manual', 'fuel' => 'bensin', 'cc' => 2568, 'seats' => 4, 'color' => 'Biru', 'stock' => 1,
                'description' => 'Ikon JDM bermesin RB26DETT, warna Bayside Blue, dokumen lengkap.'],
            ['brand' => 'Nissan', 'category' => 'Sport', 'name' => 'Silvia Spec-R (S15)', 'condition' => 'bekas', 'year' => 2001, 'mileage' => 105_000,
                'price' => 650_000_000, 'transmission' => 'manual', 'fuel' => 'bensin', 'cc' => 1998, 'seats' => 4, 'color' => 'Putih', 'stock' => 0,
                'description' => 'Mesin SR20DET dengan penggerak roda belakang. Unit sudah terjual.'],
            ['brand' => 'Nissan', 'category' => 'Sport', 'name' => '350Z (Z33)', 'condition' => 'bekas', 'year' => 2007, 'mileage' => 98_000,
                'price' => 480_000_000, 'transmission' => 'manual', 'fuel' => 'bensin', 'cc' => 3498, 'seats' => 2, 'color' => 'Silver', 'stock' => 1,
                'description' => 'Fairlady Z generasi Z33 dengan V6 VQ35, kondisi terawat.'],
            ['brand' => 'Nissan', 'category' => 'SUV', 'name' => 'Juke RX CVT', 'condition' => 'bekas', 'year' => 2016, 'mileage' => 92_000,
                'price' => 165_000_000, 'transmission' => 'automatic', 'fuel' => 'bensin', 'cc' => 1498, 'seats' => 5, 'color' => 'Merah', 'stock' => 1,
                'description' => 'Crossover berdesain unik, servis rutin di bengkel resmi.'],
            ['brand' => 'Nissan', 'category' => 'MPV', 'name' => 'Grand Livina XV', 'condition' => 'bekas', 'year' => 2018, 'mileage' => 85_000,
                'price' => 125_000_000, 'transmission' => 'automatic', 'fuel' => 'bensin', 'cc' => 1498, 'seats' => 7, 'color' => 'Hitam', 'stock' => 2,
                'description' => 'MPV keluarga yang nyaman, pajak hidup.'],
            ['brand' => 'Nissan', 'category' => 'SUV', 'name' => 'X-Trail 2.5 CVT (T32)', 'condition' => 'bekas', 'year' => 2015, 'mileage' => 120_000,
                'price' => 185_000_000, 'transmission' => 'automatic', 'fuel' => 'bensin', 'cc' => 2488, 'seats' => 7, 'color' => 'Hitam', 'stock' => 1,
                'description' => 'Sedang dalam perbaikan bodi, belum ditampilkan di katalog.', 'active' => false],

            // Klasik Jepang lainnya (bekas)
            ['brand' => 'Toyota', 'category' => 'Sport', 'name' => 'Supra RZ (A80)', 'condition' => 'bekas', 'year' => 1997, 'mileage' => 112_000,
                'price' => 2_800_000_000, 'transmission' => 'manual', 'fuel' => 'bensin', 'cc' => 2997, 'seats' => 4, 'color' => 'Hitam', 'stock' => 1,
                'description' => 'Mesin legendaris 2JZ-GTE twin-turbo dengan transmisi manual 6 percepatan.'],
            ['brand' => 'Toyota', 'category' => 'Hatchback', 'name' => 'Sprinter Trueno GT-Apex (AE86)', 'condition' => 'bekas', 'year' => 1986, 'mileage' => 165_000,
                'price' => 550_000_000, 'transmission' => 'manual', 'fuel' => 'bensin', 'cc' => 1587, 'seats' => 4, 'color' => 'Putih', 'stock' => 1,
                'description' => 'Hachi-roku dengan lampu pop-up dan mesin 4A-GE, penggerak roda belakang.'],
            ['brand' => 'Honda', 'category' => 'Sport', 'name' => 'NSX (NA1)', 'condition' => 'bekas', 'year' => 1991, 'mileage' => 88_000,
                'price' => 2_400_000_000, 'transmission' => 'manual', 'fuel' => 'bensin', 'cc' => 2977, 'seats' => 2, 'color' => 'Merah', 'stock' => 1,
                'description' => 'Supercar bermesin tengah dengan sasis aluminium dan V6 VTEC.'],
            ['brand' => 'Honda', 'category' => 'Hatchback', 'name' => 'Civic Type R (EK9)', 'condition' => 'bekas', 'year' => 1998, 'mileage' => 125_000,
                'price' => 450_000_000, 'transmission' => 'manual', 'fuel' => 'bensin', 'cc' => 1595, 'seats' => 5, 'color' => 'Putih', 'stock' => 1,
                'description' => 'Type R pertama dengan mesin B16B, warna Championship White.'],
            ['brand' => 'Mazda', 'category' => 'Sport', 'name' => 'RX-7 Spirit R (FD3S)', 'condition' => 'bekas', 'year' => 2002, 'mileage' => 76_000,
                'price' => 1_650_000_000, 'transmission' => 'manual', 'fuel' => 'bensin', 'cc' => 1308, 'seats' => 4, 'color' => 'Kuning', 'stock' => 1,
                'description' => 'Edisi terakhir RX-7 bermesin rotary 13B-REW twin-turbo.'],
            ['brand' => 'Mitsubishi', 'category' => 'Sedan', 'name' => 'Lancer Evolution IX GSR', 'condition' => 'bekas', 'year' => 2006, 'mileage' => 130_000,
                'price' => 750_000_000, 'transmission' => 'manual', 'fuel' => 'bensin', 'cc' => 1997, 'seats' => 5, 'color' => 'Abu-abu', 'stock' => 1,
                'description' => 'Sedan rally bermesin 4G63 turbo MIVEC dengan penggerak semua roda.'],
            ['brand' => 'Subaru', 'category' => 'Sedan', 'name' => 'Impreza WRX STI (GC8)', 'condition' => 'bekas', 'year' => 1998, 'mileage' => 140_000,
                'price' => 600_000_000, 'transmission' => 'manual', 'fuel' => 'bensin', 'cc' => 1994, 'seats' => 5, 'color' => 'Biru', 'stock' => 1,
                'description' => 'Mesin boxer EJ20 turbo dan AWD simetris, warna khas WR Blue.'],
        ];
    }
}
