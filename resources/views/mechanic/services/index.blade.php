@extends('layouts.admin')

@section('title', 'Layanan Servis')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [['label' => 'Layanan Servis']]])
@endsection

@section('content')
    <p class="text-muted small mb-3">Daftar layanan bengkel dan checklist bawaannya. Perubahan layanan dilakukan admin.</p>

    @if ($services->isEmpty())
        <div class="card">
            <x-empty-state icon="bi-tools" title="Belum ada layanan aktif" message="Layanan servis yang aktif akan muncul di sini." />
        </div>
    @else
        <div class="row g-4">
            @foreach ($services as $service)
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 mb-2">
                                <h2 class="h6 mb-0">{{ $service->name }}</h2>
                                @if ($service->duration_minutes)
                                    <span class="small text-muted">± {{ $service->duration_minutes }} menit</span>
                                @endif
                            </div>
                            @if ($service->description)
                                <p class="small text-muted mb-3">{{ $service->description }}</p>
                            @endif
                            @if ($items = $service->checklistItems())
                                <ol class="small mb-0 ps-3">
                                    @foreach ($items as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ol>
                            @else
                                <p class="small text-muted mb-0">Belum ada checklist untuk layanan ini.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
