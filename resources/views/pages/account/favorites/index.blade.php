@extends('layouts.app')

@section('title', 'Favorit Saya')

@section('content')
    <x-account.layout title="Favorit Saya" active="account.favorites.*"
                      :summary="$cars->total().' dari '.\App\Http\Controllers\Account\FavoriteController::MAX_FAVORITES.' mobil'">
        <x-slot:actions>
            <a href="{{ route('cars.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-car-front"></i>Lihat Mobil</a>
        </x-slot:actions>

        @if ($cars->isEmpty())
            <div class="card">
                <x-empty-state icon="bi-heart" title="Belum ada mobil favorit"
                               message="Tekan ikon hati pada mobil yang Anda minati agar mudah dibuka lagi.">
                    <a href="{{ route('cars.index') }}" class="btn btn-primary btn-sm">Lihat Mobil</a>
                </x-empty-state>
            </div>
        @else
            <div class="row g-4">
                @foreach ($cars as $car)
                    <div class="col-12 col-sm-6 col-xl-4">
                        @if ($car->is_active)
                            <x-car-card :car="$car" />
                        @else
                            {{-- Dinonaktifkan admin: halaman detail 404, jadi hanya tampil info + tombol hapus. --}}
                            <article class="card h-100">
                                <div class="card-body d-flex flex-column p-3">
                                    <p class="small text-muted mb-1">{{ $car->brand->name }} · {{ $car->category->name }}</p>
                                    <h2 class="h6 mb-2">{{ $car->name }} {{ $car->year }}</h2>
                                    <p class="mb-3"><x-status-badge status="inactive" class="me-1" /><span class="small text-muted">Tidak tersedia</span></p>
                                    <div class="mt-auto">
                                        <x-favorite-button :car="$car" variant="full" />
                                    </div>
                                </div>
                            </article>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($cars->hasPages())
                <div class="mt-4">
                    {{ $cars->onEachSide(1)->links('partials.pagination-links') }}
                </div>
            @endif
        @endif
    </x-account.layout>
@endsection
