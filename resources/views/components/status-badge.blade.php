@props(['status'])

@php
    // Warna sesuai RANCANGAN §2.
    $map = [
        'pending' => ['Menunggu', 'yellow'],
        'confirmed' => ['Dikonfirmasi', 'blue'],
        'processing' => ['Diproses', 'blue'],
        'approved' => ['Disetujui', 'green'],
        'completed' => ['Selesai', 'green'],
        'rejected' => ['Ditolak', 'red'],
        'cancelled' => ['Dibatalkan', 'red'],
        'out_of_stock' => ['Stok Habis', 'dark'],
    ];

    [$label, $color] = $map[$status] ?? [ucfirst((string) $status), 'muted'];
@endphp

<span {{ $attributes->class(['badge rounded-pill', 'badge-status-'.$color]) }}>{{ $label }}</span>
