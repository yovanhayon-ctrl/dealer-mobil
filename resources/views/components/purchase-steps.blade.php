{{-- Langkah setelah pengajuan pembelian dikirim (form /mobil/{slug}/ajukan). Langkah 3 mengikuti pilihan cash/kredit
     lewat purchase-request.js; tanpa JavaScript kedua versi tampil. --}}
@php
    $dealer = config('dealer');
    $showroom = collect([$dealer['address'], $dealer['hours'] ? 'buka '.$dealer['hours'] : null])->filter()->implode(', ');
@endphp

<div class="purchase-steps-box rounded-3 p-3 mb-3">
    <p class="small fw-semibold mb-2">Setelah Anda mengirim pengajuan</p>
    <ol class="purchase-steps list-unstyled small mb-3">
        <li><span class="fw-semibold">Dihubungi tim kami.</span> Kami menghubungi lewat WhatsApp dalam 1 hari kerja untuk konfirmasi.</li>
        <li>
            <span class="fw-semibold">Cek unit.</span> Lihat dan coba mobilnya di showroom kami{{ $showroom !== '' ? ': '.$showroom : '' }}.
        </li>
        <li>
            <span class="fw-semibold">Pembayaran.</span>
            <span data-step-method="cash">Cash: pelunasan di showroom atau transfer ke rekening atas nama dealer.</span>
            <span data-step-method="credit">Kredit: survei leasing, lalu bayar DP setelah kredit disetujui.</span>
        </li>
        <li><span class="fw-semibold">Serah terima.</span> Mobil diserahkan bersama dokumennya.</li>
    </ol>
    <p class="small text-muted mb-0">
        <i class="bi bi-shield-check me-1" aria-hidden="true"></i>{{ \App\Models\PurchaseRequest::SAFE_PAYMENT_NOTE }}
    </p>
</div>
