<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="auth-wrapper">
    <main class="d-flex align-items-center justify-content-center py-5 px-3">
        <div class="auth-card">
            <a href="{{ route('home') }}" class="d-block text-center text-decoration-none mb-4">
                <span class="h3 fw-bold"><i class="bi bi-car-front-fill text-accent"></i> {{ config('dealer.name') }}</span>
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
