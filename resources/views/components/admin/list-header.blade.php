{{--
    Kepala halaman daftar admin: ringkasan jumlah data di kiri, tombol aksi utama di kanan.
    <x-admin.list-header summary="20 mobil"> <a class="btn …">Tambah</a> </x-admin.list-header>
--}}
@props(['summary' => null])

<div {{ $attributes->class(['admin-list-header']) }}>
    <p class="admin-list-summary mb-0">{{ $summary }}</p>
    @if ($slot->isNotEmpty())
        <div class="d-flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</div>
