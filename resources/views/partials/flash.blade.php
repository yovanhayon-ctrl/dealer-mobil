@php
    $flashTypes = [
        'success' => ['class' => 'success', 'icon' => 'bi-check-circle-fill'],
        'status' => ['class' => 'info', 'icon' => 'bi-info-circle-fill'],
        'error' => ['class' => 'danger', 'icon' => 'bi-exclamation-triangle-fill'],
    ];
@endphp

@foreach ($flashTypes as $key => $type)
    @if (session($key))
        <div class="alert alert-{{ $type['class'] }} alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi {{ $type['icon'] }} me-2"></i>
            <div>{{ session($key) }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
@endforeach
