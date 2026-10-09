{{--
    Tombol favorit: <x-favorite-button :car="$car" /> (ikon di kartu) atau variant="full" (halaman detail).
    Tamu: tautan ke login (kembali ke halaman ini setelah masuk). Admin: tidak ditampilkan.
--}}
@props(['car', 'variant' => 'icon'])

@php
    $user = auth()->user();
    $isFavorite = $user && isset($user->favoriteCarIds()[$car->id]);
    $label = $isFavorite ? 'Hapus dari Favorit' : 'Simpan ke Favorit';
    $title = "{$car->name} {$car->year}";
    $icon = $isFavorite ? 'bi-heart-fill' : 'bi-heart';
    $classes = $variant === 'full'
        ? ['btn', 'btn-outline-danger' => ! $isFavorite, 'btn-danger' => $isFavorite]
        : ['btn btn-light btn-sm rounded-circle car-card-favorite', 'is-favorite' => $isFavorite];
@endphp

@if (Route::has('favorites.store') && ! $user?->isAdmin())
    @guest
        <a href="{{ route('login') }}" @class($classes) aria-label="{{ $label }}: {{ $title }}" title="Masuk untuk menyimpan favorit">
            <i class="bi bi-heart"></i>@if ($variant === 'full'){{ $label }}@endif
        </a>
    @else
        <form method="POST" action="{{ route($isFavorite ? 'favorites.destroy' : 'favorites.store', $car) }}"
              @class(['d-grid' => $variant === 'full'])>
            @csrf
            @if ($isFavorite)
                @method('DELETE')
            @endif
            <button type="submit" @class($classes) aria-pressed="{{ $isFavorite ? 'true' : 'false' }}"
                    aria-label="{{ $label }}: {{ $title }}" title="{{ $label }}">
                <i class="bi {{ $icon }}"></i>@if ($variant === 'full'){{ $label }}@endif
            </button>
        </form>
    @endguest
@endif
