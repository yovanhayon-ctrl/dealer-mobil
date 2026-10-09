{{-- Bar melayang selama ada mobil yang dipilih untuk dibandingkan (tidak tampil di halaman bandingkan sendiri). --}}
@php($compareCount = Route::has('compare.index') && ! request()->routeIs('compare.index') ? app(\App\Support\CarComparison::class)->count() : 0)

@if ($compareCount > 0)
    <div class="compare-bar" role="region" aria-label="Perbandingan mobil">
        <div class="container d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
            <span class="small">
                <i class="bi bi-layout-three-columns me-1"></i>
                <strong>{{ $compareCount }}</strong> dari {{ \App\Support\CarComparison::MAX_CARS }} mobil dipilih
            </span>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('compare.clear') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-light btn-sm">Kosongkan</button>
                </form>
                <a href="{{ route('compare.index') }}" class="btn btn-accent btn-sm">
                    <i class="bi bi-arrow-right-circle"></i>Bandingkan
                </a>
            </div>
        </div>
    </div>
@endif
