@extends('layouts.app')

@section('title', 'Promo')
@section('meta_description', 'Promo yang sedang berjalan di '.config('dealer.name').': potongan harga mobil Nissan dan penawaran spesial lainnya.')

@php
    $tabs = ['' => 'Semua'] + \App\Http\Controllers\PromoController::TYPE_FILTERS;
@endphp

@section('content')
    <div class="container py-4">
        @include('partials.public-breadcrumb', ['items' => [['label' => 'Promo']]])

        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Promo Berjalan</h1>
                <p class="text-muted mb-0">Penawaran yang berlaku hari ini. Promo yang paling cepat berakhir tampil lebih dulu.</p>
            </div>

            <ul class="nav nav-pills small flex-nowrap text-nowrap flex-shrink-0" aria-label="Jenis promo">
                @foreach ($tabs as $value => $label)
                    @php($isActive = (string) $type === (string) $value)
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => $isActive])
                           href="{{ route('promos.index', $value === '' ? [] : ['jenis' => $value]) }}"
                           @if ($isActive) aria-current="page" @endif>{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($promos->isEmpty())
            <div class="card">
                <x-empty-state icon="bi-percent" title="Belum ada promo berjalan"
                               :message="$type ? 'Tidak ada promo jenis ini yang sedang berjalan.' : 'Nantikan penawaran menarik dari kami.'">
                    <a href="{{ route('cars.index') }}" class="btn btn-primary btn-sm">Lihat Mobil</a>
                </x-empty-state>
            </div>
        @else
            <div class="row g-4">
                @foreach ($promos as $promo)
                    <div class="col-12 col-md-6 col-lg-4">
                        <x-promo-card :promo="$promo" :show-remaining="true" />
                    </div>
                @endforeach
            </div>

            @if ($promos->hasPages())
                <div class="mt-4">
                    {{ $promos->onEachSide(1)->links('partials.pagination-links') }}
                </div>
            @endif
        @endif
    </div>
@endsection
