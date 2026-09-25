<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="admin-body">
    <div class="d-lg-flex">
        @include('partials.admin-sidebar')

        <div class="flex-grow-1">
            <header class="bg-white border-bottom">
                <div class="container-fluid d-flex align-items-center justify-content-between py-3 px-4">
                    <h1 class="h5 mb-0">@yield('title', 'Admin')</h1>

                    <div class="d-flex align-items-center gap-3">
                        <span class="text-muted small d-none d-sm-inline">
                            <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-box-arrow-right"></i>Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="container-fluid p-4">
                @include('partials.flash')

                @yield('content')
            </main>
        </div>
    </div>

    @include('partials.scripts')
</body>
</html>
