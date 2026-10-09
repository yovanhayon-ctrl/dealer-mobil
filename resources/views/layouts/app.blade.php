<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body>
    @include('partials.navbar')

    <main>
        {{-- Wadah pesan hanya dirender bila ada pesan, agar hero beranda menempel ke navbar. --}}
        @if (collect(['success', 'status', 'warning', 'error'])->contains(fn ($key) => session()->has($key)))
            <div class="container pt-3">
                @include('partials.flash')
            </div>
        @endif

        @yield('content')
    </main>

    @include('partials.compare-bar')

    @include('partials.footer')

    @include('partials.scripts')
    <script src="{{ \App\Support\Asset::url('js/app.js') }}"></script>
</body>
</html>
