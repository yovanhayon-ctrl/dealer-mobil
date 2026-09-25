@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    {{-- Placeholder Phase 3: statistik dan tabel terbaru dibuat di phase dashboard admin. --}}
    <div class="card">
        <div class="card-body p-4">
            <h2 class="h5 mb-2">Selamat datang, {{ auth()->user()->name }}</h2>
            <p class="text-muted mb-0">Anda masuk sebagai <strong>admin</strong>. Menu pengelolaan akan tersedia di phase berikutnya.</p>
        </div>
    </div>
@endsection
