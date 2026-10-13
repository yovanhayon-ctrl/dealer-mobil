@if ($user->isStaff())
    <span class="badge rounded-pill bg-primary">{{ $user->roleLabel() }}</span>
@else
    <span class="badge rounded-pill text-bg-light border">Customer</span>
@endif
@unless ($user->isActive() || ($hideInactive ?? false))
    <span class="badge rounded-pill text-bg-light border">Nonaktif</span>
@endunless
