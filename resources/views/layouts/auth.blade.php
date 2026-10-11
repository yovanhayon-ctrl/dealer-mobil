<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['noindex' => true])
</head>
<body class="auth-wrapper">
    <button type="button" class="btn btn-outline-secondary btn-sm theme-toggle auth-theme-toggle" data-theme-toggle aria-pressed="false"
            aria-label="Aktifkan mode gelap" title="Aktifkan mode gelap">
        <i class="bi bi-moon-stars theme-icon-moon" aria-hidden="true"></i><i class="bi bi-sun theme-icon-sun" aria-hidden="true"></i>
    </button>
    <main class="d-flex align-items-center justify-content-center py-5 px-3">
        <div class="auth-card">
            <a href="{{ route('home') }}" class="d-block text-center text-decoration-none mb-4">
                <span class="h3 fw-bold d-inline-flex align-items-center gap-2"><x-brand-logo :size="52" /> {{ config('dealer.name') }}</span>
            </a>

            @include('partials.flash')

            <div class="card">
                <div class="card-body p-4 p-md-5">
                    @yield('content')
                </div>
            </div>

            <p class="text-center text-muted small mt-4 mb-0">
                <a href="{{ route('home') }}" class="text-muted"><i class="bi bi-arrow-left"></i> Kembali ke beranda</a>
            </p>
        </div>
    </main>

    @include('partials.scripts')
</body>
</html>
