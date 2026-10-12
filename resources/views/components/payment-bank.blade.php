{{-- Rekening resmi dealer (config dealer.bank). Tidak tampil bila nama bank / nomor rekening belum diisi di .env. --}}
@php($bank = config('dealer.bank'))

@if (filled($bank['name'] ?? null) && filled($bank['account'] ?? null))
    <p {{ $attributes->class(['small mb-2']) }}>
        <i class="bi bi-bank me-1" aria-hidden="true"></i>Transfer ke <span class="fw-semibold">{{ $bank['name'] }} {{ $bank['account'] }}</span>@if (filled($bank['holder'] ?? null)) a.n. <span class="fw-semibold">{{ $bank['holder'] }}</span>@endif.
        Jangan transfer ke rekening lain.
    </p>
@endif
