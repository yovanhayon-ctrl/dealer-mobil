<?php

namespace App\Actions;

use App\Models\Car;
use App\Models\PurchaseRequest;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Ubah status pengajuan beserta efek stok mobil (RANCANGAN §5):
 * → approved: stok −1 (ditolak jika stok 0); approved → rejected/cancelled: stok +1;
 * approved → completed: stok tetap.
 *
 * Baris pengajuan & mobil dikunci (lockForUpdate) di dalam transaksi, lalu status diperiksa ulang,
 * sehingga dua admin yang menyetujui bersamaan tidak mengurangi stok dua kali.
 */
class ChangePurchaseRequestStatus
{
    /**
     * @param  string|null  $status  status baru; null = status tetap (hanya catatan yang diperbarui).
     *                               Jika status baru ternyata sudah terpasang, tidak ada yang disimpan
     *                               (hasil ->wasChanged('status') = false).
     *
     * @throws DomainException jika transisi tidak diizinkan atau stok habis (pesan siap tampil)
     */
    public function handle(PurchaseRequest $purchaseRequest, ?string $status, ?string $adminNote): PurchaseRequest
    {
        return DB::transaction(function () use ($purchaseRequest, $status, $adminNote) {
            $locked = PurchaseRequest::whereKey($purchaseRequest->id)->lockForUpdate()->firstOrFail();

            // Status tujuan ternyata sudah terpasang (admin lain lebih dulu): jangan simpan apa pun,
            // termasuk catatan, agar catatan admin sebelumnya tidak tertimpa.
            if ($status !== null && $status === $locked->status) {
                return $locked;
            }

            if ($status !== null) {
                $this->applyStatus($locked, $status);
            }

            $locked->admin_note = $adminNote;
            $locked->save();

            return $locked;
        });
    }

    private function applyStatus(PurchaseRequest $purchaseRequest, string $status): void
    {
        if (! $purchaseRequest->canTransitionTo($status)) {
            $from = $purchaseRequest->statusLabel();
            $to = PurchaseRequest::STATUS_LABELS[$status] ?? $status;

            throw new DomainException("Status pengajuan tidak bisa diubah dari {$from} ke {$to}.");
        }

        $car = Car::whereKey($purchaseRequest->car_id)->lockForUpdate()->firstOrFail();

        if ($status === PurchaseRequest::STATUS_APPROVED) {
            if ($car->stock < 1) {
                throw new DomainException(
                    "Stok mobil \"{$car->name} {$car->year}\" habis, pengajuan tidak bisa disetujui."
                );
            }

            $car->decrement('stock');
        } elseif ($purchaseRequest->status === PurchaseRequest::STATUS_APPROVED
            && in_array($status, [PurchaseRequest::STATUS_REJECTED, PurchaseRequest::STATUS_CANCELLED], true)) {
            // Unit yang sudah dipesan dikembalikan ke stok.
            $car->increment('stock');
        }

        $purchaseRequest->status = $status;
    }
}
