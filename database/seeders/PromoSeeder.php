<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Promo;
use Illuminate\Database\Seeder;

class PromoSeeder extends Seeder
{
    /**
     * 5 promo contoh (tanpa gambar), tanggal relatif terhadap hari ini:
     * 2 promo khusus mobil berjalan, 1 promo umum berjalan, 1 terjadwal, 1 berakhir.
     * Aman dijalankan ulang: dicari berdasarkan slug, promo yang sudah ada tidak diubah
     * (termasuk tanggalnya). Slug mobil sama persis dengan yang dibuat CarSeeder.
     */
    public function run(): void
    {
        foreach ($this->promos() as $promo) {
            $carId = null;

            if ($promo['car'] !== null) {
                $carId = Car::where('slug', $promo['car'])->value('id');

                if ($carId === null) {
                    $this->command?->warn("Promo \"{$promo['title']}\" dilewati: mobil {$promo['car']} belum ada (jalankan CarSeeder).");

                    continue;
                }
            }

            Promo::firstOrCreate(['slug' => Promo::slugify($promo['title'])], [
                'car_id' => $carId,
                'title' => $promo['title'],
                'description' => $promo['description'],
                'discount_amount' => $promo['discount'],
                'start_date' => today()->addDays($promo['start']),
                'end_date' => today()->addDays($promo['end']),
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return array<int, array{title: string, car: ?string, discount: ?int, start: int, end: int, description: string}>
     */
    private function promos(): array
    {
        return [
            [
                'title' => 'Diskon Spesial Avanza', 'car' => 'toyota-avanza-1-5-g-cvt-2025', 'discount' => 15_000_000,
                'start' => -7, 'end' => 23,
                'description' => 'Potongan harga Rp 15 juta untuk pembelian Toyota Avanza 1.5 G CVT selama periode promo.',
            ],
            [
                'title' => 'Cashback HR-V', 'car' => 'honda-hr-v-1-5-se-cvt-2025', 'discount' => 10_000_000,
                'start' => -7, 'end' => 23,
                'description' => 'Cashback Rp 10 juta untuk Honda HR-V 1.5 SE CVT, berlaku cash maupun kredit.',
            ],
            [
                'title' => 'Gratis Servis 3 Tahun', 'car' => null, 'discount' => null,
                'start' => -7, 'end' => 53,
                'description' => 'Gratis biaya jasa servis berkala selama 3 tahun untuk setiap pembelian mobil baru.',
            ],
            [
                'title' => 'Promo Xpander Akhir Tahun', 'car' => 'mitsubishi-xpander-cross-premium-cvt-2025', 'discount' => 12_000_000,
                'start' => 10, 'end' => 40,
                'description' => 'Potongan harga Rp 12 juta untuk Mitsubishi Xpander Cross Premium CVT menjelang akhir tahun.',
            ],
            [
                'title' => 'Promo Kemerdekaan', 'car' => null, 'discount' => null,
                'start' => -60, 'end' => -30,
                'description' => 'Bunga ringan dan hadiah langsung untuk setiap pembelian selama bulan kemerdekaan.',
            ],
        ];
    }
}
