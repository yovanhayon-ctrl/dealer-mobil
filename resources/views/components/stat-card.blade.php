@props([
    'label',
    'value',
    'icon',
    'color' => 'navy', // navy | red | yellow | green | blue | dark
    'href' => null,
])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class(['card stat-card h-100 text-decoration-none', 'card-hover' => $href]) }}>
    <div class="card-body d-flex align-items-center gap-3 p-3">
        <span class="stat-card-icon stat-card-icon-{{ $color }}">
            <i class="bi {{ $icon }}"></i>
        </span>
        <div class="min-w-0">
            <div class="stat-card-value font-heading">{{ is_numeric($value) ? number_format($value, 0, ',', '.') : $value }}</div>
            <div class="small text-muted text-truncate">{{ $label }}</div>
        </div>
    </div>
</{{ $tag }}>
