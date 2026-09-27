{{--
    Breadcrumb halaman publik: @include('partials.public-breadcrumb', ['items' => [
        ['label' => 'Mobil', 'url' => route('cars.index')],
        ['label' => 'Toyota Avanza 2025'],
    ]])
    "Beranda" selalu menjadi item pertama; item terakhir tanpa link.
--}}
@php
    $items = array_merge([['label' => 'Beranda', 'url' => route('home')]], $items ?? []);
@endphp

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        @foreach ($items as $item)
            @if ($loop->last || empty($item['url']))
                <li class="breadcrumb-item active" @if ($loop->last) aria-current="page" @endif>{{ $item['label'] }}</li>
            @else
                <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
            @endif
        @endforeach
    </ol>
</nav>
