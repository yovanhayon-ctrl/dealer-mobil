@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    {{-- Beranda sementara (Phase 3). Section lengkap dibuat di phase halaman public. --}}
    <section class="bg-section py-5">
        <div class="container py-lg-4">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1 class="display-5 fw-bold mb-3">{{ config('dealer.name') }}</h1>
                    <p class="lead text-muted mb-4">
                        {{ config('dealer.tagline') ?: 'Temukan mobil impian Anda dengan proses yang mudah dan transparan.' }}
                    </p>

                    <div class="d-flex flex-wrap gap-2">
                        @guest
                            <a href="{{ route('register') }}" class="btn btn-accent btn-lg">
                                <i class="bi bi-person-plus"></i>Daftar Sekarang
                            </a>
                            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i>Masuk
                            </a>
                        @else
                            <p class="mb-0">Halo, <strong>{{ auth()->user()->name }}</strong>. Selamat datang kembali!</p>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
