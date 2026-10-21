@extends('layouts.admin')

@section('title', 'Riwayat Aktivitas')

@section('breadcrumb')
    @include('partials.breadcrumb', ['items' => [
        ['label' => 'Staf', 'url' => route('admin.staff.index')],
        ['label' => 'Riwayat Aktivitas'],
    ]])
@endsection

@php
    use App\Models\ActivityLog;

    $activeFilters = collect($filters)->filter()->count();
@endphp

@section('content')
    <x-admin.list-header :summary="number_format($logs->total(), 0, ',', '.').' aktivitas'.($hasFilters ? ' ditemukan' : '').'. Catatan tidak bisa diubah atau dihapus.'"></x-admin.list-header>

    <x-admin.filters :action="route('admin.staff.activity')" :reset-url="route('admin.staff.activity')"
                     :active="$activeFilters" :show-reset="$hasFilters">
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_kelompok" class="form-label small mb-1">Kelompok</label>
            <select id="filter_kelompok" name="kelompok" class="form-select form-select-sm">
                <option value="">Semua</option>
                @foreach (ActivityLog::CATEGORIES as $value => [$label])
                    <option value="{{ $value }}" @selected($filters['kelompok'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg">
            <label for="filter_staf" class="form-label small mb-1">Staf</label>
            <select id="filter_staf" name="staf" class="form-select form-select-sm">
                <option value="">Semua</option>
                @foreach ($staffOptions as $id => $name)
                    <option value="{{ $id }}" @selected($filters['staf'] === $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_dari" class="form-label small mb-1">Dari tanggal</label>
            <input type="date" id="filter_dari" name="dari" value="{{ $filters['dari'] }}" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-4 col-lg-auto">
            <label for="filter_sampai" class="form-label small mb-1">Sampai tanggal</label>
            <input type="date" id="filter_sampai" name="sampai" value="{{ $filters['sampai'] }}" class="form-control form-control-sm">
        </div>
    </x-admin.filters>

    <div class="card">
        @if ($logs->isEmpty())
            @if ($hasFilters)
                <x-empty-state icon="bi-search" title="Aktivitas tidak ditemukan" message="Tidak ada aktivitas yang cocok dengan filter." />
            @else
                <x-empty-state icon="bi-clock-history" title="Belum ada aktivitas"
                               message="Login staf, perubahan akun & Pengaturan, pembayaran, status pengajuan, dan penghapusan data akan tercatat di sini." />
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 admin-table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th class="d-none d-md-table-cell">Staf</th>
                            <th class="d-none d-md-table-cell">Kelompok</th>
                            <th>Aktivitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="text-nowrap small">
                                    {{ $log->created_at->translatedFormat('d M Y') }}
                                    <div class="text-muted">{{ $log->created_at->format('H:i') }} WIB</div>
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    <div class="fw-semibold">{{ $log->user_name }}</div>
                                    <div class="text-muted">{{ $log->roleLabel() }}</div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <span class="badge rounded-pill badge-status-{{ $log->categoryColor() }}">{{ $log->categoryLabel() }}</span>
                                </td>
                                <td class="small">
                                    {{-- HP: staf & kelompok pindah ke sini (kolomnya disembunyikan). --}}
                                    <div class="d-md-none mb-1">
                                        <span class="badge rounded-pill badge-status-{{ $log->categoryColor() }}">{{ $log->categoryLabel() }}</span>
                                        <span class="fw-semibold ms-1">{{ $log->user_name }}</span>
                                        <span class="text-muted">({{ $log->roleLabel() }})</span>
                                    </div>
                                    @if ($url = $log->subjectUrl())
                                        <a href="{{ $url }}">{{ $log->description }}</a>
                                    @else
                                        {{ $log->description }}
                                    @endif
                                    @if ($log->ip_address)
                                        <div class="text-muted">IP {{ $log->ip_address }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($logs->hasPages())
        <div class="mt-4">
            {{ $logs->onEachSide(1)->links('partials.pagination-links') }}
        </div>
    @endif
@endsection
