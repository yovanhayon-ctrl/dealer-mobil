{{--
    Tombol pilih/batal bandingkan: <x-compare-button :car="$car" /> (kartu) atau variant="full" (halaman detail).
--}}
@props(['car', 'variant' => 'small'])

@php
    $selected = app(\App\Support\CarComparison::class)->has($car);
    $title = "{$car->name} {$car->year}";
@endphp

@if (Route::has('compare.store'))
    <form method="POST" action="{{ route($selected ? 'compare.destroy' : 'compare.store', $car) }}"
          {{ $attributes->class(['compare-form', 'd-grid' => $variant === 'full']) }}>
        @csrf
        @if ($selected)
            @method('DELETE')
        @endif
        <button type="submit" @class([
                    'btn',
                    'btn-sm' => $variant !== 'full',
                    'btn-outline-secondary' => ! $selected,
                    'btn-secondary' => $selected,
                ])
                aria-pressed="{{ $selected ? 'true' : 'false' }}"
                aria-label="{{ $selected ? 'Batal bandingkan' : 'Bandingkan' }}: {{ $title }}">
            <i class="bi {{ $selected ? 'bi-check2-square' : 'bi-layout-three-columns' }}"></i>{{ $selected ? 'Dibandingkan' : 'Bandingkan' }}
        </button>
    </form>
@endif
