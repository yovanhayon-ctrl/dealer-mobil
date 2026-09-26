@if ($user->isAdmin())
    <span class="badge rounded-pill bg-primary">Admin</span>
@else
    <span class="badge rounded-pill text-bg-light border">Customer</span>
@endif
