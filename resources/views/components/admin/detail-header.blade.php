{{--
    Kepala halaman detail admin: tautan kembali, judul + keterangan, dan aksi/status di kanan.
    <x-admin.detail-header title="Pengajuan #1" subtitle="Diajukan …" :back-url="…" back-label="Pengajuan"> … </x-admin.detail-header>
--}}
@props(['title', 'subtitle' => null, 'backUrl' => null, 'backLabel' => 'Kembali'])

<div {{ $attributes->class(['admin-detail-header']) }}>
    <div class="min-w-0">
        @if ($backUrl)
            <a href="{{ $backUrl }}" class="admin-detail-back"><i class="bi bi-arrow-left"></i>{{ $backLabel }}</a>
        @endif
        <h2 class="h4 mb-0">{{ $title }}</h2>
        @if ($subtitle)
            <p class="small text-muted mb-0">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="d-flex flex-wrap align-items-center gap-2">{{ $slot }}</div>
    @endif
</div>
