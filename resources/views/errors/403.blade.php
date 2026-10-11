@extends('layouts.app')

@section('title', 'Akses Ditolak')

@section('content')
    <section class="container py-5 text-center">
        <i class="bi bi-shield-lock display-1 text-accent"></i>
        <p class="text-muted fw-semibold mt-3 mb-1">Error 403</p>
        <h1 class="h2">Akses Ditolak</h1>
        <p class="text-muted mb-4">Anda tidak memiliki akses ke halaman ini.</p>
        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="bi bi-house"></i>Kembali ke Beranda
        </a>
    </section>
@endsection
