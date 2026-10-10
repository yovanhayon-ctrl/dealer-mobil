@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <x-account.layout title="Profil Saya" active="account.profile*">
        <div class="card mb-4">
            <div class="card-body p-3 p-lg-4 d-flex flex-wrap align-items-center gap-3">
                <span class="service-card-icon" aria-hidden="true"><i class="bi bi-person"></i></span>
                <div class="min-w-0 flex-grow-1">
                    <h2 class="h5 mb-0">{{ $user->name }}</h2>
                    <p class="small text-muted text-break mb-0">{{ $user->email }} · terdaftar sejak {{ $user->created_at->translatedFormat('d F Y') }}</p>
                </div>
                <span class="badge rounded-pill {{ $user->isAdmin() ? 'badge-status-dark' : 'badge-status-muted' }}">
                    {{ $user->isAdmin() ? 'Admin' : 'Customer' }}
                </span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card h-100">
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

            </div>
            <div class="col-xl-6">
                <div class="card h-100">
                    <div class="card-body p-3 p-lg-4">
                        <h2 class="h5 mb-3">Ganti Kata Sandi</h2>
                        <form method="POST" action="{{ route('account.profile.password') }}" novalidate data-disable-on-submit>
                            @csrf
                            @method('PUT')

                            <x-form.input name="current_password" type="password" label="Kata Sandi Saat Ini" required autocomplete="current-password" />
                            <div class="row g-3">
                                <div class="col-12">
                                    <x-form.input name="password" type="password" label="Kata Sandi Baru" required autocomplete="new-password"
                                                  help="Minimal 8 karakter." />
                                </div>
                                <div class="col-12">
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
    </x-account.layout>
@endsection
