@extends('layouts.app')

@section('title', 'Profil Saya')

@php
    $shortcuts = [
        ['account.test-drives.index', 'bi-calendar-check', 'Test Drive Saya', $user->test_drives_count ?? null],
        ['account.purchase-requests.index', 'bi-card-checklist', 'Pengajuan Saya', $user->purchase_requests_count ?? null],
        ['account.service-bookings.index', 'bi-wrench-adjustable', 'Servis Saya', $user->service_bookings_count ?? null],
        ['account.favorites.index', 'bi-heart', 'Favorit Saya', $user->favorite_cars_count ?? null],
    ];
@endphp

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [['label' => 'Profil Saya']]])

        <h1 class="h3 mb-4">Profil Saya</h1>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body p-4 text-center">
                        <span class="service-card-icon mb-3" aria-hidden="true"><i class="bi bi-person"></i></span>
                        <h2 class="h5 mb-1">{{ $user->name }}</h2>
                        <p class="small text-muted text-break mb-2">{{ $user->email }}</p>
                        <span class="badge rounded-pill {{ $user->isAdmin() ? 'badge-status-dark' : 'badge-status-muted' }}">
                            {{ $user->isAdmin() ? 'Admin' : 'Customer' }}
                        </span>
                        <p class="small text-muted mt-3 mb-0">Terdaftar sejak {{ $user->created_at->translatedFormat('d F Y') }}</p>
                    </div>
                </div>

                @if ($user->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary w-100">
                        <i class="bi bi-speedometer2"></i>Dashboard Admin
                    </a>
                @else
                    <div class="list-group">
                        @foreach ($shortcuts as [$route, $icon, $label, $count])
                            @continue(! Route::has($route))
                            <a href="{{ route($route) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bi {{ $icon }} me-2"></i>{{ $label }}</span>
                                <span class="badge rounded-pill badge-status-muted">{{ $count }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body p-3 p-lg-4">
                        <h2 class="h5 mb-3">Data Diri</h2>
                        <form method="POST" action="{{ route('account.profile.update') }}" novalidate data-disable-on-submit>
                            @csrf
                            @method('PATCH')

                            <x-form.input name="name" label="Nama Lengkap" :value="$user->name" required maxlength="100" autocomplete="name" />
                            <x-form.input name="email" type="email" label="Email" :value="$user->email" required maxlength="255" autocomplete="email"
                                          help="Dipakai untuk masuk ke akun." />
                            <x-form.input name="phone" type="tel" label="Nomor WhatsApp" :value="$user->phone" required inputmode="tel" autocomplete="tel"
                                          help="Format 08xx. Otomatis terisi saat booking test drive, servis, dan pengajuan." />

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i>Simpan Data Diri
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-3 p-lg-4">
                        <h2 class="h5 mb-3">Ganti Kata Sandi</h2>
                        <form method="POST" action="{{ route('account.profile.password') }}" novalidate data-disable-on-submit>
                            @csrf
                            @method('PUT')

                            <x-form.input name="current_password" type="password" label="Kata Sandi Saat Ini" required autocomplete="current-password" />
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <x-form.input name="password" type="password" label="Kata Sandi Baru" required autocomplete="new-password"
                                                  help="Minimal 8 karakter." />
                                </div>
                                <div class="col-sm-6">
                                    <x-form.input name="password_confirmation" type="password" label="Ulangi Kata Sandi Baru" required autocomplete="new-password" />
                                </div>
                            </div>

                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-key"></i>Ganti Kata Sandi
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
