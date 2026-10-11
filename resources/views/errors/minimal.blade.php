{{--
    Halaman error mandiri (500/503): tanpa navbar/layout aplikasi karena error ini bisa terjadi
    saat database atau sesi bermasalah. Hanya CSS dari CDN + app.css, tanpa query.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | {{ config('dealer.name', 'JAF Dealer') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ \App\Support\Asset::url('css/app.css') }}" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/favicon-64.png') }}">
</head>
<body>
    <main class="container py-5 text-center">
        <p class="fw-bold fs-5 mb-4 d-inline-flex align-items-center gap-2"><img src="{{ asset('images/logo-128.webp') }}" alt="" width="40" height="40" class="brand-logo"> {{ config('dealer.name', 'JAF Dealer') }}</p>
        <i class="bi @yield('icon') display-1 text-accent"></i>
        <p class="text-muted fw-semibold mt-3 mb-1">Error @yield('code')</p>
        <h1 class="h2">@yield('heading')</h1>
        <p class="text-muted mb-4">@yield('message')</p>
        <a href="{{ url('/') }}" class="btn btn-primary"><i class="bi bi-house"></i>Kembali ke Beranda</a>
    </main>
</body>
</html>
