@extends('layouts.app')

@section('title', 'Terlalu Banyak Permintaan')

@section('content')
    <section class="container py-5 text-center">
        <i class="bi bi-speedometer display-1 text-accent"></i>
        <p class="text-muted fw-semibold mt-3 mb-1">Error 429</p>
        <h1 class="h2">Terlalu Banyak Permintaan</h1>
        <p class="text-muted mb-4">Terlalu banyak percobaan dalam waktu singkat. Tunggu sekitar 1 menit lalu coba lagi.</p>
        <a href="{{ route('home') }}" class="btn btn-primary">
            <i class="bi bi-house"></i>Kembali ke Beranda
        </a>
    </section>
@endsection
