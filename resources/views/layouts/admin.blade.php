<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head')
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        @include('partials.admin-sidebar')

        <div class="admin-content flex-grow-1">
            <header class="admin-topbar bg-white border-bottom sticky-top">
                <div class="container-fluid d-flex align-items-center gap-2 py-3 px-3 px-lg-4">
                    <button class="btn btn-link text-body p-1 me-1 d-lg-none" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#adminSidebar"
                            aria-controls="adminSidebar" aria-label="Buka menu">
                        <i class="bi bi-list fs-4 m-0"></i>
                    </button>

                    <h1 class="h5 mb-0 text-truncate">@yield('title', 'Admin')</h1>

                    <div class="d-flex align-items-center gap-2 gap-sm-3 ms-auto">
                        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary" title="Lihat Website">
                            <i class="bi bi-box-arrow-up-right"></i><span class="d-none d-md-inline">Lihat Website</span>
                        </a>
                        <span class="text-muted small d-none d-sm-inline text-truncate admin-user-name">
                            <i class="bi bi-person-circle"></i> {{ auth()->user()->name }}
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger" title="Keluar">
                                <i class="bi bi-box-arrow-right"></i><span class="d-none d-sm-inline">Keluar</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="container-fluid p-3 p-lg-4">
                @hasSection('breadcrumb')
                    @yield('breadcrumb')
                @endif

                @include('partials.flash')

                @yield('content')
            </main>
        </div>
    </div>

    @include('partials.scripts')
</body>
</html>
