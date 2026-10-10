{{--
    Penyaring status riwayat (link biasa ?status=…, tanpa JavaScript).
    $counts: ['semua' => n, 'berjalan' => n, …]; $active: kunci aktif atau null.
--}}
@props(['route', 'counts', 'active' => null])

@php
    $labels = ['semua' => 'Semua', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'];
@endphp

<nav class="account-status-filter" aria-label="Saring menurut status">
    @foreach ($labels as $key => $label)
        @php($isActive = ($active ?? 'semua') === $key)
        <a href="{{ route($route, $key === 'semua' ? [] : ['status' => $key]) }}" @class(['account-status-chip', 'active' => $isActive])
           @if ($isActive) aria-current="page" @endif>
            {{ $label }} <span>{{ $counts[$key] ?? 0 }}</span>
        </a>
    @endforeach
</nav>
