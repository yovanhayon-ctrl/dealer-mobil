{{--
    Pemakaian: @include('partials.breadcrumb', ['items' => [
        ['label' => 'Mobil', 'url' => route('admin.cars.index')],
        ['label' => 'Tambah'],
    ]])
    "Dashboard" selalu menjadi item pertama; item terakhir tanpa link.
--}}
@php
    $items = array_merge([['label' => 'Dashboard', 'url' => route('admin.dashboard')]], $items ?? []);
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
