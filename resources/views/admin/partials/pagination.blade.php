{{--
    Info jumlah data + nomor halaman untuk tabel admin. Teks ditulis langsung (tanpa kunci terjemahan global).
    Tidak tampil bila semua data muat di satu halaman.
--}}
@if ($paginator->hasPages())
    <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-2 px-3 py-3 border-top">
        <div class="small text-muted">
            Menampilkan <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
            sampai <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
            dari <span class="fw-semibold">{{ $paginator->total() }}</span> data
        </div>

        {{ $paginator->onEachSide(1)->links('partials.pagination-links') }}
    </div>
@endif
