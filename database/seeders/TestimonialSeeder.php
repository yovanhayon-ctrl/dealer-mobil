<?php

namespace Database\Seeders;

use App\Models\PurchaseRequest;
use App\Models\Testimonial;
use Database\Seeders\Concerns\SeedsDummyTransactions;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    use SeedsDummyTransactions;

    /**
     * Ulasan contoh yang sudah disetujui untuk pembelian selesai milik customer dummy (hanya local/testing).
     * Aman dijalankan ulang: satu ulasan per pengajuan (firstOrCreate). Pengajuan yang belum ada atau
     * belum selesai dilewati.
     */
    public function run(): void
    {
        if (! $this->allowedEnvironment()) {
            return;
        }

        $customers = $this->dummyCustomers();

        if ($customers->isEmpty()) {
            $this->command?->warn(static::class.' dilewati: customer dummy @example.test belum ada (isi SEED_CUSTOMER_PASSWORD lalu jalankan CustomerSeeder).');

            return;
        }

        $created = 0;

        foreach ($this->testimonials() as $index => $entry) {
            $customer = $customers->get($entry['email']);
            $purchase = $customer ? PurchaseRequest::query()
                ->where('user_id', $customer->id)
                ->whereHas('car', fn ($query) => $query->where('slug', $entry['car']))
                ->where('status', PurchaseRequest::STATUS_COMPLETED)
                ->first() : null;

            if (! $purchase) {
                continue;
            }

            $testimonial = Testimonial::firstOrCreate(['purchase_request_id' => $purchase->id], [
                'user_id' => $customer->id,
                'rating' => $entry['rating'],
                'comment' => $entry['comment'],
                'status' => Testimonial::STATUS_APPROVED,
                'approved_at' => now()->subDays(count($this->testimonials()) - $index),
            ]);

            $created += $testimonial->wasRecentlyCreated ? 1 : 0;
        }

        $this->command?->info("Ulasan dummy siap ({$created} baru).");
    }

    /**
     * @return array<int, array{email: string, car: string, rating: int, comment: string}>
     */
    private function testimonials(): array
    {
        return [
            ['email' => 'nur.aisyah@example.test', 'car' => 'nissan-navara-vl-4x4-at-2024', 'rating' => 5,
                'comment' => 'Proses pembelian Navara cepat dan jelas. Sales menjelaskan fitur 4x4 dengan sabar, unit diantar tepat waktu dalam kondisi bersih.'],
            ['email' => 'budi.santoso@example.test', 'car' => 'nissan-livina-vl-cvt-2025', 'rating' => 5,
                'comment' => 'Pertama kali beli mobil keluarga dan dibantu dari simulasi kredit sampai serah terima. Livina-nya nyaman untuk mudik.'],
            ['email' => 'dewi.lestari@example.test', 'car' => 'nissan-magnite-premium-cvt-2025', 'rating' => 4,
                'comment' => 'Pengajuan kredit Magnite diproses dalam beberapa hari dan statusnya bisa dipantau dari website. Semoga ke depan ada pilihan warna lebih banyak.'],
        ];
    }
}
