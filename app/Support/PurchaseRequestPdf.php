<?php

namespace App\Support;

use App\Models\PurchaseRequest;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

/**
 * Bukti pengajuan pembelian dalam bentuk PDF (A4), dipakai halaman customer & admin.
 * HTML dirender dari view `pdf.purchase-request` lalu diubah dompdf; tanpa sumber eksternal
 * (remote dimatikan), sehingga isi dokumen hanya dari data aplikasi.
 */
class PurchaseRequestPdf
{
    public function html(PurchaseRequest $purchaseRequest): string
    {
        $purchaseRequest->loadMissing(['user:id,name,email', 'car.brand:id,name']);

        return view('pdf.purchase-request', [
            'purchase' => $purchaseRequest,
            'car' => $purchaseRequest->car,
            'customer' => $purchaseRequest->user,
            // Hanya data kop surat (config dealer juga berisi pengaturan lain).
            'dealer' => Arr::only(config('dealer'), ['name', 'tagline', 'address', 'phone', 'email', 'hours']),
        ])->render();
    }

    public function render(PurchaseRequest $purchaseRequest): string
    {
        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->html($purchaseRequest), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    public function filename(PurchaseRequest $purchaseRequest): string
    {
        return 'bukti-pengajuan-'.$purchaseRequest->documentNumber().'.pdf';
    }

    /**
     * Ditampilkan di tab browser (inline); tetap bisa disimpan dengan nama file di atas.
     */
    public function response(PurchaseRequest $purchaseRequest): Response
    {
        return response($this->render($purchaseRequest), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->filename($purchaseRequest).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
