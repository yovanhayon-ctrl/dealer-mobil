@extends('layouts.app')

@section('title', 'Sesi Berakhir')

@section('content')
    <section class="container py-5 text-center">
        <i class="bi bi-hourglass-bottom display-1 text-accent"></i>
        <p class="text-muted fw-semibold mt-3 mb-1">Error 419</p>
        <h1 class="h2">Sesi Berakhir</h1>
        <p class="text-muted mb-4">Sesi formulir sudah kedaluwarsa. Muat ulang halaman sebelumnya lalu kirim kembali.</p>
        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="bi bi-house"></i>Kembali ke Beranda
        </a>
    </section>
@endsection
