@if ($paginator->total() > 0)
    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 px-3 py-3 border-top">
        <div class="small text-muted">
            Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
        </div>
        @if ($paginator->hasPages())
            {{-- Versi simple: teks tombol dari lang/id/pagination.php (tanpa teks bawaan berbahasa Inggris). --}}
            {{ $paginator->links('pagination::simple-bootstrap-5') }}
        @endif
    </div>
@endif
