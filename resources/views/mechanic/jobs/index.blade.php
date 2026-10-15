@extends('layouts.admin')

@section('title', 'Pekerjaan Saya')

@php
    $empty = $inProgress->isEmpty() && $upcoming->isEmpty();
@endphp

@section('content')
    <p class="text-muted mb-3">Halo, {{ auth()->user()->name }}. Servis yang ditugaskan admin ke Anda tampil di sini.</p>

    @if ($empty)
        <div class="card mb-4">
            <x-empty-state icon="bi-wrench-adjustable" title="Belum ada servis yang ditugaskan"
                           message="Admin menugaskan servis saat mengonfirmasi booking. Daftar pekerjaan dan checklist-nya akan muncul di halaman ini." />
        </div>
    @endif

    @foreach ([['Sedang dikerjakan', $inProgress], ['Jadwal berikutnya', $upcoming]] as [$heading, $jobs])
        @continue($jobs->isEmpty())

        <h2 class="h6 mb-2">{{ $heading }} <span class="text-muted fw-normal">({{ $jobs->count() }})</span></h2>
        <div class="card mb-4">
            <div class="list-group list-group-flush">
                @foreach ($jobs as $job)
                    @php([$handled, $total] = $job->taskProgress())
                    <a href="{{ route('mechanic.jobs.show', $job) }}" class="list-group-item list-group-item-action p-3">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="fw-semibold">{{ $job->plate_number }}</span>
                            <span>{{ $job->vehicle_model }}</span>
                            <span class="ms-auto d-inline-flex gap-1">
                                @if ($job->isOverdue())
                                    <x-status-badge status="rejected" label="Lewat jadwal" />
                                @endif
                                <x-status-badge :status="$job->status" />
                            </span>
                        </div>
                        <div class="small text-muted">
                            {{ $job->service->name }}, {{ $job->preferred_date->translatedFormat('l, d M Y') }} pukul {{ $job->timeLabel() }}
                        </div>
                        @if ($total > 0)
                            <div class="small mt-1">{{ $handled }} dari {{ $total }} pekerjaan ditangani</div>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach

    @if ($completed->isNotEmpty())
        <h2 class="h6 mb-2">Selesai terakhir</h2>
        <div class="card">
            <div class="list-group list-group-flush">
                @foreach ($completed as $job)
                    <a href="{{ route('mechanic.jobs.show', $job) }}" class="list-group-item list-group-item-action p-3 small">
                        <span class="fw-semibold">{{ $job->plate_number }}</span> {{ $job->vehicle_model }}
                        <span class="text-muted">, {{ $job->service->name }}, selesai {{ $job->finished_at?->translatedFormat('d M Y H:i') }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
@endsection
