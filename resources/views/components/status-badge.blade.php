@props(['status', 'label' => null])

@php
    // Warna sesuai RANCANGAN §2.
    $map = [
        'pending' => ['Menunggu', 'yellow'],
        'confirmed' => ['Dikonfirmasi', 'blue'],
        'processing' => ['Diproses', 'blue'],
        'in_progress' => ['Dikerjakan', 'blue'],
        'approved' => ['Disetujui', 'green'],
        'completed' => ['Selesai', 'green'],
        'rejected' => ['Ditolak', 'red'],
        'cancelled' => ['Dibatalkan', 'red'],
        'no_show' => ['Tidak datang', 'muted'],
        'out_of_stock' => ['Stok Habis', 'dark'],
        'active' => ['Aktif', 'green'],
        'inactive' => ['Nonaktif', 'muted'],
        // Status promo (dihitung dari tanggal, lihat Promo::status).
        'running' => ['Berjalan', 'green'],
        'scheduled' => ['Terjadwal', 'blue'],
        'ended' => ['Berakhir', 'dark'],
    ];

    [$defaultLabel, $color] = $map[$status] ?? [ucfirst((string) $status), 'muted'];
    // Label khusus (mis. bukti pembayaran: "Menunggu verifikasi" / "Diterima") dengan warna status yang sama.
    $label ??= $defaultLabel;
@endphp

<span {{ $attributes->class(['badge rounded-pill', 'badge-status-'.$color]) }}>{{ $label }}</span>
