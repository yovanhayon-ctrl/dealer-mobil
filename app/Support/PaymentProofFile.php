<?php

namespace App\Support;

use App\Models\PaymentProof;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menampilkan file bukti pembayaran dari disk privat (dipakai halaman customer & admin setelah cek hak akses).
 */
final class PaymentProofFile
{
    public static function response(PaymentProof $paymentProof): StreamedResponse
    {
        $disk = Storage::disk(PaymentProof::DISK);

        abort_unless($disk->exists($paymentProof->file_path), 404);

        $extension = strtolower(pathinfo($paymentProof->file_path, PATHINFO_EXTENSION));

        // Nama unduhan dari server (bukan nama asli dari customer) dan tidak di-cache di perangkat bersama.
        return $disk->response($paymentProof->file_path, "bukti-pembayaran-{$paymentProof->id}.{$extension}", [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
