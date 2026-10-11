@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan')

@section('content')
    <section class="container py-5 text-center">
        <i class="bi bi-signpost-split display-1 text-accent"></i>
        <p class="text-muted fw-semibold mt-3 mb-1">Error 404</p>
        <h1 class="h2">Halaman Tidak Ditemukan</h1>
        <p class="text-muted mb-4">Halaman yang Anda cari tidak ada atau sudah dipindahkan.</p>
        <a href="{{ route('cars.index') }}" class="btn btn-outline-primary me-2">
            <i class="bi bi-car-front"></i>Lihat Mobil
        </a>
        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="bi bi-house"></i>Kembali ke Beranda
        </a>
    </section>
@endsection
