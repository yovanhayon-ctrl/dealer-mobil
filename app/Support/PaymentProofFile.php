<?php

namespace App\Support;

use App\Models\PaymentProof;
use App\Models\PaymentRefund;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menampilkan file bukti pembayaran / pengembalian dana dari disk privat (dipakai halaman customer & admin setelah cek hak akses).
 */
final class PaymentProofFile
{
    public static function response(PaymentProof|PaymentRefund $record): StreamedResponse
    {
        $disk = Storage::disk($record::DISK);

        abort_unless($record->hasFile() && $disk->exists($record->file_path), 404);

        $extension = strtolower(pathinfo($record->file_path, PATHINFO_EXTENSION));
        $prefix = $record instanceof PaymentRefund ? 'bukti-pengembalian' : 'bukti-pembayaran';

        // Nama unduhan dari server (bukan nama asli dari pengunggah) dan tidak di-cache di perangkat bersama.
        return $disk->response($record->file_path, "{$prefix}-{$record->id}.{$extension}", [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
