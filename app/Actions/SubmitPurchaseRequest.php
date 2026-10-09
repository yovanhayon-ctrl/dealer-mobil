<?php

namespace App\Actions;

use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Support\CreditCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pengajuan pembelian oleh customer (RANCANGAN §5).
 *
 * Aturan: mobil aktif & stok > 0, customer belum punya pengajuan aktif (menunggu/diproses/disetujui)
 * untuk mobil yang sama, dan DP kredit 20–90% dari harga. car_price = harga setelah promo aktif dan
 * nilai kredit (bunga, cicilan) selalu dihitung ulang di server; angka dari browser tidak dipakai.
 * Stok tidak berubah di sini: stok baru dipotong saat admin menyetujui (ChangePurchaseRequestStatus).
 *
 * Aturan dicek di PurchaseRequestRequest (pesan ramah) lalu dicek ulang di sini dengan baris mobil
 * dikunci (lockForUpdate), sehingga dua pengajuan bersamaan tidak lolos keduanya.
 */
class SubmitPurchaseRequest
{
    public function __construct(private readonly CreditCalculator $credit) {}

    /**
     * @param  array{payment_method: string, down_payment: ?int, tenor_months: ?int, phone: string, address: string, notes: ?string}  $data
     *
     * @throws ValidationException jika aturan pengajuan dilanggar
     */
    public function handle(User $user, Car $car, array $data): PurchaseRequest
    {
        return DB::transaction(function () use ($user, $car, $data) {
            $locked = Car::whereKey($car->id)->with('activePromos')->lockForUpdate()->firstOrFail();

            $errors = $this->conflicts($user, $locked, $data);

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            return PurchaseRequest::create([
                'user_id' => $user->id,
                'car_id' => $locked->id,
                ...$this->priceData($locked, $data),
                'phone' => $data['phone'],
                'address' => $data['address'],
                'notes' => ($data['notes'] ?? null) ?: null,
                'status' => PurchaseRequest::STATUS_PENDING,
            ]);
        });
    }

    /**
     * Pelanggaran aturan pengajuan: [field => pesan]; kosong jika boleh. Butuh eager load `activePromos`.
     *
     * @return array<string, string>
     */
    public function conflicts(User $user, Car $car, array $data): array
    {
        if (! $car->is_active) {
            return ['car' => 'Mobil ini tidak tersedia untuk diajukan.'];
        }

        if (! $car->inStock()) {
            return ['car' => 'Stok mobil ini habis, pengajuan belum bisa dilakukan.'];
        }

        if ($existing = $this->activeRequest($user, $car)) {
            return ['car' => "Anda sudah punya pengajuan untuk mobil ini ({$existing->statusLabel()}, "
                .$existing->created_at->translatedFormat('d M Y').'). Lihat Pengajuan Saya.'];
        }

        if (($data['payment_method'] ?? null) === PurchaseRequest::PAYMENT_CREDIT) {
            $price = $car->finalPrice();
            $min = $this->credit->minDownPayment($price);
            $max = $this->credit->maxDownPayment($price);
            $downPayment = (int) ($data['down_payment'] ?? 0);

            if ($downPayment < $min || $downPayment > $max) {
                return ['down_payment' => sprintf(
                    'Uang muka harus antara %s (%d%%) dan %s (%d%%) dari harga.',
                    self::rupiah($min), config('credit.dp_min'), self::rupiah($max), config('credit.dp_max'),
                )];
            }
        }

        return [];
    }

    public function activeRequest(User $user, Car $car): ?PurchaseRequest
    {
        return PurchaseRequest::query()
            ->where('user_id', $user->id)
            ->where('car_id', $car->id)
            ->whereIn('status', PurchaseRequest::ACTIVE_STATUSES)
            ->latest()
            ->first(['id', 'status', 'created_at']);
    }

    /**
     * Harga & nilai kredit snapshot. Cash: kolom kredit null.
     *
     * @return array<string, mixed>
     */
    private function priceData(Car $car, array $data): array
    {
        $price = $car->finalPrice();

        if ($data['payment_method'] === PurchaseRequest::PAYMENT_CASH) {
            return [
                'car_price' => $price,
                'payment_method' => PurchaseRequest::PAYMENT_CASH,
                'down_payment' => null,
                'tenor_months' => null,
                'interest_rate' => null,
                'monthly_installment' => null,
            ];
        }

        $credit = $this->credit->calculate($price, (int) $data['down_payment'], (int) $data['tenor_months']);

        return [
            'car_price' => $price,
            'payment_method' => PurchaseRequest::PAYMENT_CREDIT,
            'down_payment' => $credit['down_payment'],
            'tenor_months' => $credit['tenor_months'],
            'interest_rate' => $credit['interest_rate'],
            'monthly_installment' => $credit['monthly_installment'],
        ];
    }

    private static function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
