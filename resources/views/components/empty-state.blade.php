@props([
    'icon' => 'bi-inbox',
    'title',
    'message' => null,
])

<div {{ $attributes->class(['empty-state text-center py-5 px-3']) }}>
    <i class="bi {{ $icon }} empty-state-icon"></i>
    <p class="fw-semibold font-heading mt-3 mb-1">{{ $title }}</p>
    @if ($message)
        <p class="text-muted small mb-0">{{ $message }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-3">{{ $slot }}</div>
    @endif
</div>
