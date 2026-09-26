<?php

namespace Database\Seeders;

use App\Actions\ChangePurchaseRequestStatus;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Support\CreditCalculator;
use Database\Seeders\Concerns\SeedsDummyTransactions;
use DomainException;
use Illuminate\Database\Seeder;

class PurchaseRequestSeeder extends Seeder
{
    use SeedsDummyTransactions;

    public function __construct(
        private readonly ChangePurchaseRequestStatus $changeStatus,
        private readonly CreditCalculator $calculator,
    ) {}

    /**
     * Pengajuan dummy dengan variasi status & metode (hanya local/testing).
     * - car_price = harga setelah promo aktif; kredit dihitung CreditCalculator (config/credit.php).
     * - Status akhir dicapai lewat ChangePurchaseRequestStatus langkah demi langkah, sehingga stok
     *   mobil konsisten dengan pengajuan approved/completed (sama seperti aksi admin).
     * - Aman dijalankan ulang: satu pengajuan per pasangan customer–mobil; status & stok hanya
     *   diproses untuk pengajuan yang baru dibuat.
     */
    public function run(): void
    {
        if (! $this->allowedEnvironment()) {
            return;
        }

        $entries = $this->purchaseRequests();
        $customers = $this->dummyCustomers();
        $cars = $this->carsBySlug(array_column($entries, 'car'))->load('activePromos');

        if (! $this->hasPrerequisites($customers, $cars)) {
            return;
        }

        $created = 0;

        foreach ($entries as $entry) {
            $customer = $customers->get($entry['email']);
            $car = $cars->get($entry['car']);

            if (! $customer || ! $car) {
                continue;
            }

            $purchaseRequest = PurchaseRequest::firstOrCreate(
                ['user_id' => $customer->id, 'car_id' => $car->id],
                [
                    ...$this->priceData($car, $entry),
                    'phone' => $customer->phone ?? '081200000000',
                    'address' => $entry['address'],
                    'notes' => $entry['notes'] ?? null,
                    'status' => PurchaseRequest::STATUS_PENDING,
                ],
            );

            if (! $purchaseRequest->wasRecentlyCreated) {
                continue;
            }

            $created++;
            $this->applyStatusPath($purchaseRequest, $entry['path'], $entry['admin_note'] ?? null);
        }

        $this->command?->info("Pengajuan dummy siap ({$created} baru).");
    }

    /**
     * @return array<string, mixed>
     */
    private function priceData(Car $car, array $entry): array
    {
        $price = $car->finalPrice();

        if ($entry['method'] === PurchaseRequest::PAYMENT_CASH) {
            return ['car_price' => $price, 'payment_method' => PurchaseRequest::PAYMENT_CASH];
        }

        // DP dibulatkan ke atas per Rp 1 juta, minimal sesuai config.
        $downPayment = max(
            (int) ceil($price * $entry['dp'] / 100 / 1_000_000) * 1_000_000,
            $this->calculator->minDownPayment($price),
        );
        $credit = $this->calculator->calculate($price, $downPayment, $entry['tenor']);

        return [
            'car_price' => $price,
            'payment_method' => PurchaseRequest::PAYMENT_CREDIT,
            'down_payment' => $credit['down_payment'],
            'tenor_months' => $credit['tenor_months'],
            'interest_rate' => $credit['interest_rate'],
            'monthly_installment' => $credit['monthly_installment'],
        ];
    }

    /**
     * Jalankan transisi status satu per satu; catatan admin dipasang pada langkah terakhir.
     *
     * @param  array<int, string>  $path
     */
    private function applyStatusPath(PurchaseRequest $purchaseRequest, array $path, ?string $adminNote): void
    {
        foreach ($path as $index => $status) {
            $isLast = $index === array_key_last($path);

            try {
                $purchaseRequest = $this->changeStatus->handle($purchaseRequest, $status, $isLast ? $adminNote : null);
            } catch (DomainException $e) {
                $this->command?->warn("Pengajuan #{$purchaseRequest->id} berhenti di status {$purchaseRequest->statusLabel()}: {$e->getMessage()}");

                return;
            }
        }
    }

    /**
     * Mobil untuk status approved/completed dipilih yang stoknya cukup; Fortuner (stok 0)
     * dibiarkan pending untuk menampilkan peringatan stok habis di admin.
     *
     * @return array<int, array<string, mixed>>
     */
    private function purchaseRequests(): array
    {
        $processing = PurchaseRequest::STATUS_PROCESSING;
        $approved = PurchaseRequest::STATUS_APPROVED;

        return [
            ['email' => 'budi.santoso@example.test', 'car' => 'toyota-avanza-1-5-g-cvt-2025', 'method' => 'credit', 'dp' => 20, 'tenor' => 36,
                'path' => [], 'address' => 'Jl. Merdeka No. 10, Bandung', 'notes' => 'Mohon info promo terbaru.'],
            ['email' => 'dewi.lestari@example.test', 'car' => 'mitsubishi-xpander-cross-premium-cvt-2025', 'method' => 'cash',
                'path' => [], 'address' => 'Jl. Diponegoro No. 5, Semarang'],
            ['email' => 'andi.pratama@example.test', 'car' => 'toyota-fortuner-2-8-gr-sport-2025', 'method' => 'cash',
                'path' => [], 'address' => 'Jl. Sudirman No. 21, Jakarta Pusat', 'notes' => 'Siap inden bila stok kosong.'],
            ['email' => 'rizky.hidayat@example.test', 'car' => 'honda-hr-v-1-5-se-cvt-2025', 'method' => 'credit', 'dp' => 30, 'tenor' => 48,
                'path' => [$processing], 'address' => 'Jl. Pemuda No. 8, Surabaya'],
            ['email' => 'andi.pratama@example.test', 'car' => 'daihatsu-xenia-1-3-r-cvt-2025', 'method' => 'credit', 'dp' => 25, 'tenor' => 24,
                'path' => [$processing, $approved], 'address' => 'Jl. Sudirman No. 21, Jakarta Pusat'],
            ['email' => 'nur.aisyah@example.test', 'car' => 'hyundai-creta-1-5-prime-ivt-2025', 'method' => 'cash',
                'path' => [$processing, $approved, PurchaseRequest::STATUS_COMPLETED], 'address' => 'Jl. Gajah Mada No. 3, Medan',
                'admin_note' => 'Unit sudah diserahterimakan.'],
            ['email' => 'maya.putri@example.test', 'car' => 'honda-brio-satya-e-cvt-2025', 'method' => 'credit', 'dp' => 20, 'tenor' => 60,
                'path' => [PurchaseRequest::STATUS_REJECTED], 'address' => 'Jl. Malioboro No. 12, Yogyakarta',
                'admin_note' => 'Dokumen penghasilan belum lengkap.'],
            ['email' => 'agus.setiawan@example.test', 'car' => 'toyota-innova-zenix-2-0-q-hv-2025', 'method' => 'cash',
                'path' => [$processing, $approved, PurchaseRequest::STATUS_CANCELLED], 'address' => 'Jl. Asia Afrika No. 7, Bandung',
                'admin_note' => 'Customer membatalkan setelah disetujui; unit dikembalikan ke stok.'],
            ['email' => 'siti.rahmawati@example.test', 'car' => 'mitsubishi-pajero-sport-dakar-4x2-2021', 'method' => 'credit', 'dp' => 40, 'tenor' => 12,
                'path' => [PurchaseRequest::STATUS_CANCELLED], 'address' => 'Jl. Ahmad Yani No. 45, Makassar',
                'admin_note' => 'Customer memilih mobil lain.'],
        ];
    }
}
